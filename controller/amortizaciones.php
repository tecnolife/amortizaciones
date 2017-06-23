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
    public $amortizacion;//modelo
    /**
     * @var
     */
    public $linea_amortizacion;//modelo
    /**
     * @var
     */
    public $partidadebe;//modelo
    /**
     * @var
     */
    public $partidahaber;//modelo
    /**
     * @var
     */
    public $asiento;//modelo
    /**
     * @var
     */
    public $ejercicio;//modelo
    /**
     * @var
     */
    public $factura;//modelo
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
     * @var
     */
    public $subcuenta;


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
        $this->partidadebe = new partida();
        $this->partidahaber = new partida();
        $this->asiento = new asiento();
        $this->ejercicio = new ejercicio();
        $this->factura = new factura_proveedor();
        $this->subcuenta = new subcuenta();
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
        } elseif (filter_input(INPUT_GET, 'sale') !== null || filter_input(INPUT_POST, 'sale') !== null) {
            $this->amortizacion->sale(filter_input(INPUT_GET, 'sale'));
            $this->linea_amortizacion->sale(filter_input(INPUT_GET, 'sale') || filter_input(INPUT_POST, 'sale'));
        } elseif (filter_input(INPUT_GET, 'count') !== null || filter_input(INPUT_POST, 'count') !== null) {
            $this->contabilizar();
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
        $this->amortizacion->cod_subcuenta_cierre = filter_input(INPUT_POST, 'cod_subcuenta_cierre', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_debe = filter_input(INPUT_POST, 'cod_subcuenta_debe', FILTER_VALIDATE_INT);
        $this->amortizacion->cod_subcuenta_haber = filter_input(INPUT_POST, 'cod_subcuenta_haber', FILTER_VALIDATE_INT);
        $this->amortizacion->descripcion = filter_input(INPUT_POST, 'descripcion');
        $this->amortizacion->fecha_fin = filter_input(INPUT_POST, 'fecha_fin');
        $this->amortizacion->fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio');
        $this->amortizacion->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
        $this->amortizacion->periodos = filter_input(INPUT_POST, 'periodos');
        $this->amortizacion->residual = filter_input(INPUT_POST, 'residual');
        $this->amortizacion->tipo = filter_input(INPUT_POST, 'tipo');
        $this->amortizacion->valor = filter_input(INPUT_POST, 'valor');

        if (filter_input(INPUT_GET, 'editar') != null || filter_input(INPUT_POST, 'editar') != null) {
            $this->amortizacion->id_amortizacion = filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT);
            $this->amortizacion->estado = filter_input(INPUT_POST, 'estado');
        }

        if ($this->amortizacion->save()) {
            $this->new_message("La amortización se ha creado con exito"); //Este mensaje aparece aunque la creación de las líneas fallen
            $this->anadir_lineas();
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
        $ano = (int)(Date('Y', strtotime(filter_input(INPUT_POST, 'fecha_inicio'))));
        
        while ($contador <= filter_input(INPUT_POST, 'periodos')) {
            if (filter_input(INPUT_GET, 'editar') != null || filter_input(INPUT_POST, 'editar') != null) {
                $this->listado_lineas[$_POST['ano_' . $ano . '']] = array(
                    'cantidad' => round($_POST['cantidad_' . $ano . ''], 2),
                    'contabilizada' => $_POST['contabilizada_' . $ano . ''],
                    'id_amortizacion' => $_POST['id_amortizacion_' . $ano . ''],
                    'id_linea' => $_POST['id_linea_' . $ano . '']
                );
            } else {
                $this->listado_lineas[(int)($_POST['ano_' . $ano . ''])] = array(
                    'cantidad' => round($_POST['cantidad_' . $ano . ''], 2)
                );
            }

            $contador++;
            $ano++;
        }

        $this->linea_amortizacion->id_amortizacion = $this->amortizacion->id_amortizacion;

        foreach ($this->listado_lineas as $key => $value) {

            $this->linea_amortizacion->ano = $key;
            $this->linea_amortizacion->cantidad = $value['cantidad'];

            if (filter_input(INPUT_GET, 'editar') != null || filter_input(INPUT_POST, 'editar') != null) {
                $this->linea_amortizacion->contabilizada = $value['contabilizada'];
                $this->linea_amortizacion->id_amortizacion = $value['id_amortizacion'];
                $this->linea_amortizacion->id_linea = $value['id_linea'];
            }

            //ERROR VISUAL, porque se muestra una línea de mensaje por cada línea que se crea
            if ($this->linea_amortizacion->save()) {
                $this->new_message("La línea de amortizacion se ha creado con exito");
            } else {
                $this->amortizacion->delete();
                $this->linea_amortizacion->delete();
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
                $this->new_message("Las líneas de la amortización se han eliminado con exito");
            } else {
                $this->new_error_msg("Error al eliminar las líneas de la amortización");
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
        $this->listado_pendientes = array();
        foreach ($this->listado_lineas as $key => $value) {
            $amortizacion = $this->amortizacion->get_by_amortizacion($value->id_amortizacion);

            $this->listado_pendientes[] = array(
                'ano' => $value->ano,
                'cantidad' => $value->cantidad,
                'contabilizada' => $value->contabilizada,
                'id_amortizacion' => $value->id_amortizacion,
                'id_linea' => $value->id_linea,
                'descripcion' => $amortizacion->descripcion
            );
        }
    }

    /**
     * TODO PHPDoc
     */
    private function contabilizar()
    {
        /*Estamos cogiendo el año que corresponde a la fecha de hoy, lo que significa, que si intentamos hacer la contabilización en enero,
          por ejemplo, nos hara la de ese año y no la del anterior
          Lo ideal sería coger el ejercicio fiscal actual, pero en realidad no se como hacerlo, ¿si hay dos ejercicios fiscales abiertos?*/
        $ano = (int)(Date('Y',
            strtotime(filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) || filter_input(INPUT_POST, 'year',
                    FILTER_VALIDATE_INT))));
        $linea = $this->linea_amortizacion->get_to_count(filter_input(INPUT_GET, 'count', FILTER_VALIDATE_INT) || filter_input(INPUT_POST,
                'count', FILTER_VALIDATE_INT), $ano);
        $amortizacion = $this->amortizacion->get_by_amortizacion($linea->id_amortizacion);

        $factura = new factura_proveedor();
        $this->factura = $factura->get(filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT));
        $ejercicio = $this->ejercicio->get_by_fecha('31-12-' . $ano . '');
        $numero = $this->asiento->new_numero();

        if ($linea->contabilizada != 1) {
            //Genera la fila en la tabla co_asientos
            $this->asiento->codejercicio = $ejercicio->codejercicio;
            $this->asiento->concepto = filter_input(INPUT_POST, 'descripcion');
            $this->asiento->documento = $this->factura->codigo;
            $this->asiento->editable = false;
            $this->asiento->fecha = '31-12-' . $ano . '';
            $this->asiento->importe = $linea->cantidad;
            $this->asiento->numero = $this->asiento->new_numero();
            $this->asiento->tipodocumento = 'Amortización';

            //Grava los datos en co_asientos
            if ($this->asiento->save()) {
                $this->new_message('Línea contabilizada');
                $this->linea_amortizacion->count($linea->id_linea);
                $ano_final = (int)(Date('Y', strtotime($amortizacion->fecha_fin)));
                $idasiento = $this->asiento->idasiento;

            } else {
                $this->new_error_msg('Error al contabilizar la amortización');
            }
            //Fin del añadido
            if ($ano == $ano_final) {
                $this->amortizacion->complete($linea->id_amortizacion);
                $this->new_message('Amortización completada');
            }

            //DEBE
            $subcuenta_debe = $this->subcuenta->get_by_codigo(
                $amortizacion->cod_subcuenta_debe,
                $ejercicio->codejercicio
            );

            $this->partidadebe->debe = $linea->cantidad;
            $this->partidadebe->coddivisa = $this->factura->coddivisa;
            $this->partidadebe->codserie = $this->factura->codserie;
            $this->partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_debe;
            $this->partidadebe->concepto = $amortizacion->descripcion;
            $this->partidadebe->idasiento = $idasiento;
            $this->partidadebe->idsubcuenta = $subcuenta_debe->codcuenta;

            if ($this->partidadebe->save()) {
                $this->new_message('Línea de partida creada correctamente');
            } else {
                $this->new_error_msg('Error al crear la línea de partida');
            }

            //HABER
            $subcuenta_haber = $this->subcuenta->get_by_codigo(
                $amortizacion->cod_subcuenta_haber,
                $ejercicio->codejercicio
            );

            $this->partidahaber->haber = $linea->cantidad;
            $this->partidahaber->coddivisa = $this->factura->coddivisa;
            $this->partidahaber->codserie = $this->factura->codserie;
            $this->partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_haber;
            $this->partidahaber->concepto = $amortizacion->descripcion;
            $this->partidahaber->idasiento = $idasiento;
            $this->partidahaber->idsubcuenta = $subcuenta_haber->codcuenta;

            if ($this->partidahaber->save()) {
                $this->new_message('Línea de partida creada correctamente');
            } else {
                $this->new_error_msg('Error al crear la línea de partida');
            }
        } else {
            $this->new_message('La línea ya está contabilizada');
        }
    }
}
