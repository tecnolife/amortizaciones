<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016  David Ruiz Eguizábal       davidruegui@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('amortizacion.php');
require_model('linea_amortizacion.php');
require_model('partida.php');
require_model('asiento.php');
require_model('ejercicio.php');
require_model('factura_proveedor.php');
require_model('subcuenta.php');

/**
 * Class amortizaciones
 */
class amortizaciones extends fs_controller
{
    /**
     * @var
     */
    public $ano_antes;
    /**
     * @var
     */
    public $amortizacion;//modelo
    /**
     * @var
     */
    public $linea_amortizacion;//modelo
    /**
     * @var
     */
    public $listado;
    /**
     * @var
     */
    public $listado_lineas;
    /**
     * @var
     */
    public $listado_pendientes;
    /**
     * @var
     */
    public $offset;
    /**
     * @var
     */
    public $limite;


    /**
     * amortizaciones constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'amortizaciones', 'compras');
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core()
    {
        $this->amortizacion = new amortizacion();
        $this->linea_amortizacion = new linea_amortizacion();
        $this->offset = 0;
        $this->limite = FS_ITEM_LIMIT;

        if (filter_input(INPUT_GET, 'offset') !== null) {
            $this->offset = (int)filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT);
        }
        if (filter_input(INPUT_POST, 'fecha_inicio') != null) {
            $this->anadir_amortizacion();
        } elseif (filter_input(INPUT_GET, 'delete') !== null || filter_input(INPUT_POST, 'delete') !== null) {
            $this->eliminar_amortizacion();
        } elseif (filter_input(INPUT_GET, 'cancel') !== null || filter_input(INPUT_POST, 'cancel') !== null) {
            $this->amortizacion->cancel(filter_input(INPUT_GET, 'cancel'));
        } elseif (filter_input(INPUT_GET, 'restart') !== null || filter_input(INPUT_POST, 'cancel') !== null) {
            $this->amortizacion->restart(filter_input(INPUT_GET, 'restart'));
        } elseif (filter_input(INPUT_GET, 'endlife') !== null) {
            $this->finalizar_vida_util(filter_input(INPUT_POST, 'id_amortizacion'), filter_input(INPUT_POST, 'fecha'));
        } elseif (filter_input(INPUT_GET, 'sale') !== null || filter_input(INPUT_POST, 'sale') !== null) {
            $this->amortizacion->sale(filter_input(INPUT_GET, 'sale'));
            $this->linea_amortizacion->sale(filter_input(INPUT_GET, 'sale') || filter_input(INPUT_POST, 'sale'));
        } elseif (filter_input(INPUT_GET, 'count') !== null || filter_input(INPUT_POST, 'count') !== null) {
            $this->contabilizar(filter_input(INPUT_GET, 'count'));
        } elseif (filter_input(INPUT_GET, 'count_by_date') !== null) {
            $lineas = $this->linea_amortizacion->get_by_date_and_amort(filter_input(INPUT_POST, 'id_amortizacion'), filter_input(INPUT_POST, 'fecha_inicial'), filter_input(INPUT_POST, 'fecha_final'));
            
            foreach ($lineas as $key => $value) {
                $this->contabilizar($value->id_linea);
            }
        }
        
        $this->listado = $this->amortizacion->all($this->offset, $this->limite);
        $this->listado_lineas = $this->linea_amortizacion->this_year();

        $this->listar_pendientes();
    }

    /**
     * TODO PHPDoc
     */
    private function anadir_amortizacion()
    {
        $this->amortizacion->cod_subcuenta_beneficios = filter_input(INPUT_POST, 'cod_subcuenta_beneficios', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_cierre = filter_input(INPUT_POST, 'cod_subcuenta_cierre', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_debe = filter_input(INPUT_POST, 'cod_subcuenta_debe', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_haber = filter_input(INPUT_POST, 'cod_subcuenta_haber', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_perdidas = filter_input(INPUT_POST, 'cod_subcuenta_perdidas', FILTER_VALIDATE_INT);
        $this->amortizacion->contabilizacion = filter_input(INPUT_POST, 'contabilizacion');
        $this->amortizacion->descripcion = filter_input(INPUT_POST, 'descripcion');
        $this->amortizacion->fecha_fin = filter_input(INPUT_POST, 'fecha_fin');
        $this->amortizacion->fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio');
        $this->amortizacion->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
        $this->amortizacion->periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT);
        $this->amortizacion->residual = filter_input(INPUT_POST, 'residual', FILTER_VALIDATE_FLOAT);
        $this->amortizacion->tipo = filter_input(INPUT_POST, 'tipo');
        $this->amortizacion->valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $this->amortizacion->coddivisa = filter_input(INPUT_POST, 'cod_divisa');
        $this->amortizacion->codserie = filter_input(INPUT_POST, 'cod_serie');
        $this->amortizacion->documento = filter_input(INPUT_POST, 'documento');
                
        $inicio_ejercicio = filter_input(INPUT_POST, 'inicio_ejercicio');
        $inicio_amortizacion = filter_input(INPUT_POST, 'fecha_inicio');
        
        if ($inicio_ejercicio == $inicio_amortizacion) {
            if ($this->amortizacion->contabilizacion == 'anual') {
                $this->amortizacion->periodo_final = 1;
            } elseif ($this->amortizacion->contabilizacion == 'trimestral') {
                $this->amortizacion->periodo_final = 4;
            } elseif ($this->amortizacion->contabilizacion == 'mensual') {
                $this->amortizacion->periodo_final = 12;
            }
        } else {
            $this->amortizacion->periodo_final = filter_input(INPUT_POST, 'periodo_inicial');
        }
                
        if (filter_input(INPUT_GET, 'editar') != null || filter_input(INPUT_POST, 'editar') != null) {
            $this->amortizacion->id_amortizacion = filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT);
        }

        if ($this->amortizacion->save()) {
            $this->anadir_lineas();
            if (filter_input(INPUT_GET, 'editar') != null) {
                $this->new_message("La amortización se ha modificado con exito");
            } else {
                $this->new_message("La amortización se ha creado con exito"); //Este mensaje aparece aunque la creación de las líneas fallen
            }
        } else {
            $this->new_error_msg("Error al crear la amortización");
        }

    }

    /**
     * TODO PHPDoc
     */
    private function anadir_lineas()
    {
        $this->listado_lineas = array();
        $contador = 0;
        $periodo_inicial = filter_input(INPUT_POST, 'periodo_inicial', FILTER_VALIDATE_INT);
        $periodo = filter_input(INPUT_POST, 'periodo_inicial', FILTER_VALIDATE_INT);
        $ano = (int)(Date('Y', strtotime(filter_input(INPUT_POST, 'fecha_inicio'))));
        $ano_fiscal = (filter_input(INPUT_POST, 'ano_fiscal'));
        $ano_fiscal_final = filter_input(INPUT_POST, 'periodos') + $ano_fiscal;
        $ano = (filter_input(INPUT_POST, 'ano_fiscal'));
                
        $inicio_ejercicio = (Date('m-d', strtotime(filter_input(INPUT_POST, 'inicio_ejercicio'))));
        $inicio_amortizacion = (Date('m-d', strtotime(filter_input(INPUT_POST, 'fecha_inicio'))));
        
        if ($inicio_ejercicio == $inicio_amortizacion) {
            $contador = 1;
        }

        if ($this->amortizacion->contabilizacion == 'anual') {
            $periodos_ano = 1;
        } elseif ($this->amortizacion->contabilizacion == 'trimestral') {
            $periodos_ano = 4;
        } elseif ($this->amortizacion->contabilizacion == 'mensual') {
            $periodos_ano = 12;
        }

        while ($contador <= filter_input(INPUT_POST, 'periodos')) {
            
            if ($ano_fiscal_final == $ano) {
                $periodo = 1;
                $periodos_ano = $periodo_inicial;
            } elseif ($ano_fiscal != $ano) {
                $periodo = 1;
            } 

            while ($periodo <= $periodos_ano) {
                if (filter_input(INPUT_GET, 'editar') != null) {
                    $this->listado_lineas[filter_input(INPUT_POST,'ano_' . $ano . '_' . $periodo . '') . '_' . $periodo] = array(
                        'ano' => filter_input(INPUT_POST, 'ano_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT),
                        'cantidad' => round(filter_input(INPUT_POST, 'cantidad_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_FLOAT), 2),
                        'contabilizada' => filter_input(INPUT_POST,'contabilizada_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT),
                        'fecha' => filter_input(INPUT_POST,'fecha_' . $ano . '_' . $periodo . ''),
                        'id_amortizacion' => filter_input(INPUT_POST,'id_amortizacion_' . $ano . '_' . $periodo . '',FILTER_VALIDATE_INT),
                        'id_linea' => filter_input(INPUT_POST,'id_linea_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT),
                        'periodo' => filter_input(INPUT_POST,'periodo_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT)
                    );
                } else {  //Nueva amortizacion
                    $this->listado_lineas[(filter_input(INPUT_POST,'ano_' . $ano . '_' . $periodo . '')) . '_' . $periodo] = array(
                        'ano' => filter_input(INPUT_POST,'ano_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT),
                        'cantidad' => round(filter_input(INPUT_POST,'cantidad_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_FLOAT), 2),
                        'fecha' => filter_input(INPUT_POST,'fecha_' . $ano . '_' . $periodo . ''),
                        'periodo' => filter_input(INPUT_POST,'periodo_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT)
                    );
                }
                $periodo++;
            }
            $contador++;
            $ano++;
        }

        $this->linea_amortizacion->id_amortizacion = $this->amortizacion->id_amortizacion;

        foreach ($this->listado_lineas as $key => $value) {
                        
            $this->linea_amortizacion->ano = $value['ano'];
            $this->linea_amortizacion->cantidad = $value['cantidad'];
            $this->linea_amortizacion->fecha = $value['fecha'];
            $this->linea_amortizacion->periodo = $value['periodo'];
            
            if (filter_input(INPUT_GET, 'editar') != null) {
                $this->linea_amortizacion->contabilizada = $value['contabilizada'];
                $this->linea_amortizacion->id_amortizacion = $value['id_amortizacion'];
                $this->linea_amortizacion->id_linea = $value['id_linea'];
            }

            //ERROR VISUAL, porque se muestra una línea de mensaje por cada línea que se crea
            if ($this->linea_amortizacion->save()) {
                $this->linea_amortizacion->id_linea = null;
            } else {
                if (filter_input(INPUT_GET, 'nueva') != null) {
                    $this->amortizacion->delete();
                    $this->linea_amortizacion->delete();
                }
                $this->new_error_msg("Error al crear la línea de amortización");
            }
        }
    }

    /**
     * TODO PHPDoc
     */
    private function eliminar_amortizacion()
    {
        $this->amortizacion->id_amortizacion = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
        $this->linea_amortizacion->id_amortizacion = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

        if ($this->amortizacion->delete()) {
            $this->new_message("La amortización se ha eliminado con exito");

            if ($this->linea_amortizacion->delete()) {
            } else {
                $this->new_error_msg("Error al eliminar la línea de la amortización");
            }
        } else {
            $this->new_error_msg("Error al eliminar la amortización");
        }
    }

    /**
     * TODO PHPDoc
     */
    private function listar_pendientes()
    {
        $ejercicio = new ejercicio;
        $ejercicio_actual = $ejercicio->get_by_fecha($this->today());
        $this->ano_antes = date('d-m-Y', strtotime($ejercicio_actual->fechainicio . '- 1 year'));
        
        $listado = $this->linea_amortizacion->slope($this->today(), $this->ano_antes);      
        
        if (filter_input(INPUT_GET, 'count_slope') !== null) {

            $this->listado_pendientes = array();
            foreach ($listado as $key => $value) {
                $amortizacion = $this->amortizacion->get_by_amortizacion($value->id_amortizacion);
                if ($amortizacion->fin_vida_util == 0 && $amortizacion->amortizando == 1) {
                    $this->listado_pendientes[] = array(
                        'ano' => $value->ano,
                        'cantidad' => $value->cantidad,
                        'fecha' => $value->fecha,
                        'id_amortizacion' => $value->id_amortizacion,
                        'id_linea' => $value->id_linea,
                        'descripcion' => $amortizacion->descripcion,
                        'periodo' => $value->periodo
                    );
                }
            }

            foreach ($this->listado_pendientes as $key => $value) {
                $this->contabilizar($value['id_linea']);
            }
            $listado = array();
            $listado = $this->linea_amortizacion->slope($this->today(), $this->ano_antes);
        }
        
        $this->listado_pendientes = array();
        foreach ($listado as $key => $value) {
            $amortizacion = $this->amortizacion->get_by_amortizacion($value->id_amortizacion);
            if ($amortizacion->fin_vida_util == 0 && $amortizacion->amortizando == 1) {
                $this->listado_pendientes[] = array(
                    'ano' => $value->ano,
                    'cantidad' => $value->cantidad,
                    'fecha' => $value->fecha,
                    'id_amortizacion' => $value->id_amortizacion,
                    'id_linea' => $value->id_linea,
                    'descripcion' => $amortizacion->descripcion,
                    'periodo' => $value->periodo
                );
            }
        }   
    }

    /**
     * TODO PHPDoc
     */
    private function contabilizar($id_linea)
    {
        $linea = $this->linea_amortizacion->get_by_id_linea($id_linea);
        $amortizacion = $this->amortizacion->get_by_amortizacion($linea->id_amortizacion);
        
        if ($amortizacion->fin_vida_util == 1){
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        }
        elseif ($amortizacion->cod_subcuenta_debe == 0 || $amortizacion->cod_subcuenta_haber == 0) {
            $this->new_error_msg('El campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables');           
        } else {
            $ejercicio_model = new ejercicio();
            $ejercicio = $ejercicio_model->get_by_fecha($linea->fecha);

            //completar amortizacion
            $ejercicio_final = $ejercicio_model->get_by_fecha($amortizacion->fecha_fin);
            $ano_final = (int) (Date('Y', strtotime($ejercicio_final->fechainicio)));
            if ($ano_final == $linea->ano && $linea->periodo == $amortizacion->periodo_final) {
                $this->amortizacion->complete($amortizacion->id_amortizacion);
            }
            //Fin de completar amortizacion

            if ($linea->contabilizada != 1) {

                $asiento = new asiento();
                //Genera la fila en la tabla co_asientos
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $linea->fecha;
                $asiento->importe = $linea->cantidad;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Amortización';

                if ($asiento->save()) { //Grava los datos en co_asientos
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                //DEBE
                $partidadebe = new partida();
                $subcuenta = new subcuenta();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_debe, $ejercicio->codejercicio
                );
                
                if ($subcuenta_debe->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }      

                $partidadebe->debe = $linea->cantidad;
                $partidadebe->coddivisa = $amortizacion->coddivisa;
                $partidadebe->codserie = $amortizacion->codserie;
                $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_debe;
                $partidadebe->concepto = $amortizacion->descripcion;
                $partidadebe->idasiento = $idasiento;
                $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                if ($partidadebe->save()) {
                    
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                    $asiento->delete();
                    $this->linea_amortizacion->discount($linea->id_linea);
                }

                //HABER
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );
                
                if ($subcuenta_haber->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }      

                $partidahaber->haber = $linea->cantidad;
                $partidahaber->coddivisa = $amortizacion->coddivisa;
                $partidahaber->codserie = $amortizacion->codserie;
                $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                $partidahaber->concepto = $amortizacion->descripcion;
                $partidahaber->idasiento = $idasiento;
                $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                if ($partidahaber->save()) {
                    $this->new_message('Línea contabilizada, asiento creado correctamente');
                    $this->linea_amortizacion->count($linea->id_linea, $idasiento);
                    $asiento->idasiento = null;
                    $partidadebe->idpartida = null;
                    $partidahaber->idpartida = null;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                    $asiento->delete();
                }
            } else {
                $this->new_error_msg('La línea ya está contabilizada');
            }
        }
    }

    /**
     * TODO PHPDoc
     */
    private function contabilizar_fin_vida($id_linea,$cantidad,$fecha)
    {
        $linea = $this->linea_amortizacion->get_by_id_linea($id_linea);
        $amortizacion = $this->amortizacion->get_by_amortizacion($linea->id_amortizacion);

        if ($amortizacion->fin_vida_util == 1){
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        }
        elseif ($amortizacion->cod_subcuenta_debe == 0 || $amortizacion->cod_subcuenta_haber == 0) {
            $this->new_error_msg('El campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables');
        } else {

            $ejercicio_model = new ejercicio();
            $ejercicio = $ejercicio_model->get_by_fecha($fecha);

            //completar amortizacion
            $ejercicio_final = $ejercicio_model->get_by_fecha($amortizacion->fecha_fin);
            $ano_final = (int) (Date('Y', strtotime($ejercicio_final->fechainicio)));
            if ($ano_final == $linea->ano && $linea->periodo == $amortizacion->periodo_final) {
                $this->amortizacion->complete($amortizacion->id_amortizacion);
            }
            //Fin de completar amortizacion

            if ($linea->contabilizada != 1) {

                $asiento = new asiento();
                //Genera la fila en la tabla co_asientos
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $cantidad;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Amortización';

                if ($asiento->save()) { //Grava los datos en co_asientos
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                //DEBE
                $partidadebe = new partida();
                $subcuenta = new subcuenta();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_debe, $ejercicio->codejercicio
                );

                if ($subcuenta_debe->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidadebe->debe = $cantidad;
                $partidadebe->coddivisa = $amortizacion->coddivisa;
                $partidadebe->codserie = $amortizacion->codserie;
                $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_debe;
                $partidadebe->concepto = $amortizacion->descripcion;
                $partidadebe->idasiento = $idasiento;
                $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                if ($partidadebe->save()) {
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                    $asiento->delete();
                }

                //HABER
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );

                if ($subcuenta_haber->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidahaber->haber = $cantidad;
                $partidahaber->coddivisa = $amortizacion->coddivisa;
                $partidahaber->codserie = $amortizacion->codserie;
                $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                $partidahaber->concepto = $amortizacion->descripcion;
                $partidahaber->idasiento = $idasiento;
                $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                if ($partidahaber->save()) {
                    $this->new_message('Línea contabilizada, asiento creado correctamente');
                    $this->linea_amortizacion->count($linea->id_linea, $idasiento);
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                }
            } else {
                $this->new_error_msg('La línea ya está contabilizada');
            }
        }
    }
    
    /**
     * TODO PHPDoc
     */
    private function finalizar_vida_util($id, $fecha)
    {
            
        //Crea el asiento
        $amortizacion = $this->amortizacion->get_by_amortizacion($id);
        $ejercicio_model = new ejercicio();
        $ejercicio = $ejercicio_model->get_by_fecha($fecha);

        if ($amortizacion->fin_vida_util == 1) {
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        } elseif ($amortizacion->cod_subcuenta_cierre == 0 || $amortizacion->cod_subcuenta_haber == 0 || $amortizacion->cod_subcuenta_perdidas == 0) {
            $this->new_error_msg('El campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables');
        } else {

            $sin_amortizar = 0;
            $amortizado = 0;
            $lineas = $this->linea_amortizacion->get_by_amortizacion($id);

            foreach ($lineas as $key => $value) {
                if ($value->contabilizada == 0 && strtotime($value->fecha) > strtotime($fecha)) {
                    $sin_amortizar = $sin_amortizar + $value->cantidad;
                } else {
                    $amortizado = $amortizado + $value->cantidad;
                }
            }

            if ($sin_amortizar != 0) {

                //saca el perido al que pertenece la fecha
                $periodo_fecha_inicio = $this->periodo_por_fecha($fecha, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);

            $ano_fiscal = (int) (date('Y', strtotime($ejercicio->fechainicio)));
            $linea = $this->linea_amortizacion->get_by_id_amor_ano_periodo($amortizacion->id_amortizacion, $ano_fiscal, $periodo_fecha_inicio['periodo']);
            
            $primer_ejercicio = $ejercicio_model->get_by_fecha($amortizacion->fecha_inicio);
            $primer_ano_fiscal = (int) (date('Y', strtotime($primer_ejercicio->fechainicio)));
            $periodo_fecha_inicio = $this->periodo_por_fecha($fecha, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);
            
            if ($periodo_fecha_inicio['periodo'] == $linea->periodo && $primer_ano_fiscal == $linea->ano) {
                $fecha_inicio = $amortizacion->fecha_inicio;
            } else {
                $fecha_inicio = $periodo_fecha_inicio['fecha_inicio_periodo'];
            }
            
            $dias_periodo = $this->diferencia_dias($fecha_inicio, $linea->fecha) + 1;
            $dias_amortizado = $this->diferencia_dias($fecha_inicio, $fecha) + 1;

            $valor = round($linea->cantidad / $dias_periodo * $dias_amortizado, 2);
                $this->contabilizar_fin_vida($linea->id_linea, $valor, $fecha);

                $amortizado = round($amortizado + $valor, 2);

                //Genera la fila en la tabla co_asientos
                $asiento = new asiento();
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $amortizacion->valor;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Fin de vida útil';

                if ($asiento->save()) {
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                $subcuenta = new subcuenta();

                //DEBE AMORTIZADO
                $partidadebe = new partida();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );

                if ($subcuenta_debe->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidadebe->debe = $amortizado;
                $partidadebe->coddivisa = $amortizacion->coddivisa;
                $partidadebe->codserie = $amortizacion->codserie;
                $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                $partidadebe->concepto = $amortizacion->descripcion;
                $partidadebe->idasiento = $idasiento;
                $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                if ($partidadebe->save()) {
                    
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                }

                $partidadebe->idpartida = null;
                //DEBE PERDIDAS
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_perdidas, $ejercicio->codejercicio
                );

                if ($subcuenta_debe->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidadebe->debe = $amortizacion->valor - $amortizado;
                $partidadebe->coddivisa = $amortizacion->coddivisa;
                $partidadebe->codserie = $amortizacion->codserie;
                $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_perdidas;
                $partidadebe->concepto = $amortizacion->descripcion;
                $partidadebe->idasiento = $idasiento;
                $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                if ($partidadebe->save()) {
                    
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                    $this->linea_amortizacion->discount($linea->id_linea);
                }


                //HABER
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio
                );

                if ($subcuenta_haber->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidahaber->haber = $amortizacion->valor;
                $partidahaber->coddivisa = $amortizacion->coddivisa;
                $partidahaber->codserie = $amortizacion->codserie;
                $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_cierre;
                $partidahaber->concepto = $amortizacion->descripcion;
                $partidahaber->idasiento = $idasiento;
                $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                if ($partidahaber->save()) {
                    $this->amortizacion->end_life($id);
                    $this->amortizacion->date_end_life($id, $fecha);
                    $this->amortizacion->end_life_count($id, $idasiento);
                    $this->new_message('Finalizada la vida útil del amortizado');
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                }
            } else {
                //Genera la fila en la tabla co_asientos
                $asiento = new asiento();
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $amortizacion->valor;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Fin de vida útil';

                if ($asiento->save()) {
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                $subcuenta = new subcuenta();

                //DEBE
                $partidadebe = new partida();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );

                if ($subcuenta_debe->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidadebe->debe = $amortizacion->valor;
                $partidadebe->coddivisa = $amortizacion->coddivisa;
                $partidadebe->codserie = $amortizacion->codserie;
                $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                $partidadebe->concepto = $amortizacion->descripcion;
                $partidadebe->idasiento = $idasiento;
                $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                if ($partidadebe->save()) {
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                }


                //HABER
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio
                );

                if ($subcuenta_haber->idsubcuenta == null) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                }

                $partidahaber->haber = $amortizacion->valor;
                $partidahaber->coddivisa = $amortizacion->coddivisa;
                $partidahaber->codserie = $amortizacion->codserie;
                $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_cierre;
                $partidahaber->concepto = $amortizacion->descripcion;
                $partidahaber->idasiento = $idasiento;
                $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                if ($partidahaber->save()) {
                    $this->amortizacion->end_life($id);
                    $this->amortizacion->date_end_life($id, $fecha);
                    $this->amortizacion->end_life_count($id, $asiento->idasiento);
                    $this->new_message('Finalizada la vida útil del amortizado');
                } else {
                    $this->new_error_msg('Error al crear la línea de partida');
                    $asiento->delete();
                    $this->linea_amortizacion->discount($linea->id_linea);
                }
            }
        }
    }

    /**
     * @param $dia1
     * @param $dia2  
     * @return int
     */
    private function diferencia_dias($dia1,$dia2) //Comprueba si el año tiene 365 o 366 dias
    {
        $dia1 = new DateTime($dia1);
        $dia2 = new DateTime($dia2);
        $dias = $dia1->diff($dia2);
        $dias = $dias->format('%a');
        return $dias;
    }
    
    /**
     * @param $fecha
     * @param $ejercicio_fecha_fin
     * @param $ejercicio_fecha_inicio
     * @param $contabilizacion
     * @return $array
     */
    private function periodo_por_fecha($fecha, $ejercicio_fecha_fin, $ejercicio_fecha_inicio, $contabilizacion) {

        $mes = (int) (Date('m', strtotime($ejercicio_fecha_fin)));
        if ($mes != 12) {
            $mes_final = 12 - (int) (Date('m', strtotime($ejercicio_fecha_fin)));
            $mes_inicio = (int) (Date('m', strtotime($fecha)));
            $mes_fiscal = $mes_inicio + $mes_final - 12;
            if ($mes_fiscal < 1) {
                $mes_fiscal = $mes_fiscal + 12;
            }
        } else {
            $mes_fiscal = (int) (Date('m', strtotime($fecha)));
        }

        if ($contabilizacion == 'anual') {
            $periodo = 1;
            $fecha_inicio_periodo = $ejercicio_fecha_inicio;
        } elseif ($contabilizacion == 'trimestral') {
            $periodo = ceil($mes_fiscal / 3);
            $meses = 3 * ($periodo - 1);
            $fecha_inicio_periodo = date('d-m-Y', strtotime($ejercicio_fecha_inicio . '+ ' . $meses . ' month'));
        } elseif ($contabilizacion == 'mensual') {
            $periodo = $mes_fiscal;
            $meses = $periodo - 1;
            $fecha_inicio_periodo = date('d-m-Y', strtotime($ejercicio_fecha_inicio . '+ ' . $meses . ' month'));
        }
        return array('periodo' => $periodo, 'fecha_inicio_periodo' => $fecha_inicio_periodo);
    }

}
