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

require_model('factura_proveedor.php');
require_model('ejercicio.php');

/**
 * Class nueva_amortizacion
 */
class nueva_amortizacion extends fs_controller
{
    /**
     * @var
     */
    public $amortizacion;
    /**
     * @var
     */
    public $ano_fiscal;
    /**
     * @var
     */
    public $cod_divisa;
    /**
     * @var
     */
    public $cod_serie;
    /**
     * @var
     */
    public $id_factura;
    /**
     * @var
     */
    public $inicio_ejercicio;
    /**
     * @var
     */
    public $lineas;
    /**
     * @var
     */
    public $fecha;
    /**
     * @var
     */
    public $fecha_fin;
    /**
     * @var
     */
    public $datos;
    /**
     * @var
     */
    public $documento;
    /**
     * @var
     */
    public $periodo_inicial;

    /**
     * nueva_amortizacion constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'nueva amortización', 'contabilidad', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core() {
        $this->share_extension();
        $this->amortizacion = false;

        if (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) !== null || filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) !== null) {
            $this->id_factura = (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));         
            $factura = new factura_proveedor();
            $this->amortizacion = $factura->get($this->id_factura);            
            $this->fecha = date('d-m-Y', strtotime($this->amortizacion->fecha));
            $this->cod_divisa = $this->amortizacion->coddivisa;
            $this->cod_serie = $this->amortizacion->codserie;
            $this->documento = $this->amortizacion->codigo;
        } 

        if (filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT) !== null) {
            $this->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
            if (filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT) != 0) {
                
                $this->datos = array(
                    'descripcion' => filter_input(INPUT_POST,'descripcion'),
                    'tipo' => filter_input(INPUT_POST,'tipo'),
                    'contabilizacion' => filter_input(INPUT_POST,'contabilizacion'),
                    'cod_divisa' => filter_input(INPUT_POST,'cod_divisa'),
                    'cod_serie' => filter_input(INPUT_POST,'cod_serie'),
                    'documento' => filter_input(INPUT_POST,'documento'),
                    'valor' => filter_input(INPUT_POST,'valor',FILTER_VALIDATE_FLOAT),
                    'residual' => filter_input(INPUT_POST,'residual'),
                    'periodos' => filter_input(INPUT_POST,'periodos', FILTER_VALIDATE_INT),
                    'fecha_inicio' => filter_input(INPUT_POST,'fecha_inicio'),
                );                
                
                $this->fecha_fin = date('d-m-Y', strtotime($this->datos['fecha_inicio'] . '+' . $this->datos['periodos'] . ' year - 1 day'));

                if ($this->datos['tipo'] == 'constante') {
                    $this->constante();
                }
            } else {
                $this->new_error_msg('No se han podido generar las líneas de amortización, porque el periodo de años estaba a 0');
            }
        }
    }

    /**
     * TODO PHPDoc
     */
    private function share_extension() //Botón de amortizar en las facturas de compra
    {
        $fsext = new fs_extension();
        $fsext->name = 'nueva_amortizacion';
        $fsext->from = __CLASS__;
        $fsext->to = 'compras_factura';
        $fsext->type = 'button';
        $fsext->text = 'Amortizar';
        $fsext->save();
    }

    /**
     * TODO PHPDoc
     */
    private function constante()
    {
        $ejercicio = new ejercicio;
        $ejercicio_factura = $ejercicio->get_by_fecha($this->datos['fecha_inicio']);
        $this->inicio_ejercicio = date('d-m-Y', strtotime($ejercicio_factura->fechainicio));
                
        $contador = 0;
        $this->lineas = array();
        $amortizable = $this->datos['valor'] - $this->datos['residual'];
        $this->ano_fiscal = (int) (Date('Y', strtotime($ejercicio_factura->fechainicio)));
        $dias = $this->diferencia_dias($ejercicio_factura->fechainicio, $this->datos['fecha_inicio']);
        $fecha = $ejercicio_factura->fechainicio;
        $total = 0;

         $mes = (int) (Date('m', strtotime($ejercicio_factura->fechafin)));
        if ($mes != 12) {
            $mes_final = 12 - (int) (Date('m', strtotime($ejercicio_factura->fechafin)));
            $mes_inicio = (int) (Date('m', strtotime($this->datos['fecha_inicio'])));
            $mes_fiscal = $mes_inicio + $mes_final - 12;
            if ($mes_fiscal < 1) {
                $mes_fiscal = $mes_fiscal + 12;
            }
        } else {
            $mes_fiscal = (int) (Date('m', strtotime($this->datos['fecha_inicio'])));
        }
        
        if ($this->datos['contabilizacion'] == 'anual') {
            $this->periodo_inicial = 1;
        } elseif ($this->datos['contabilizacion'] == 'trimestral') {
            $this->periodo_inicial = ceil($mes_fiscal / 3);
        } elseif ($this->datos['contabilizacion'] == 'mensual') {
            $this->periodo_inicial = $mes_fiscal;
        }
        
        //ANUAL
        if ($this->datos['contabilizacion'] == 'anual') {
            
            $periodo = 1;
            
            if ($dias != 0) {
                $fecha = date('d-m-Y', strtotime($fecha . '+1 year'));
                $dias_ano_fiscal = $this->diferencia_dias($ejercicio_factura->fechainicio, $ejercicio_factura->fechafin) + 1;
                $valor = $amortizable / $this->datos['periodos'] / $dias_ano_fiscal * ($dias_ano_fiscal - $dias);
                $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round($valor, 2),
                    'periodo' => 1
                );
                $total = $total + round($valor,2);
                $contador++;
            }

            while ($contador < $this->datos['periodos']) {
                $fecha = date('d-m-Y', strtotime($fecha . '+1 year'));
                
                if ($contador == $this->datos['periodos'] - 1 && $dias == 0) {
                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round($amortizable - $total, 2),
                        'periodo' => 1
                    );
                } else {
                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round($amortizable / $this->datos['periodos'], 2),
                        'periodo' => 1
                    );
                    $total = $total + round($amortizable / $this->datos['periodos'], 2);
                }
                $contador++;
            }

            if ($dias != 0) {
                $fecha = date('d-m-Y', strtotime($fecha . '+1 year'));
                $valor = round($amortizable / $this->datos['periodos'] - $valor, 2);
                $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round($amortizable - $total,2),
                    'periodo' => 1
                );
            }
        }
        
        //TRIMESTRAL
        if ($this->datos['contabilizacion'] == 'trimestral') {
                        
            if ($dias != 0) {  
                
                $periodo = $this->periodo_inicial * 3;
                $fecha = date('d-m-Y', strtotime($fecha . '+' .$periodo. ' month'));
                
                $dias_ano_fiscal = $this->diferencia_dias($ejercicio_factura->fechainicio, $ejercicio_factura->fechafin) + 1;
                $valor = ($amortizable / $this->datos['periodos'] / $dias_ano_fiscal * ($dias_ano_fiscal - $dias)) - ((($amortizable / $this->datos['periodos']) / 4) * (4 - $this->periodo_inicial));
                $this->lineas[$this->ano_fiscal + $contador . '_' . $this->periodo_inicial] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round($valor, 2),
                    'periodo' => $this->periodo_inicial
                );
                $total = $total + round($valor, 2);
                $periodo = $this->periodo_inicial + 1;
                
                while ($periodo <= 4) {
                    
                    $fecha = date('d-m-Y', strtotime($fecha . '+ 3 month'));
                    
                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round(($amortizable / $this->datos['periodos']) / 4, 2),
                        'periodo' => $periodo
                    );
                    $total = $total + round(($amortizable / $this->datos['periodos']) / 4, 2);
                    $periodo++;
                }
                $contador++;
            }

            while ($contador < $this->datos['periodos']) {              
                $periodo = 1;
                while ($periodo <= 4) {
                    
                    $fecha = date('d-m-Y', strtotime($fecha . '+ 3 month'));
                    
                    if ($contador == $this->datos['periodos'] - 1 && $periodo == 4 && $dias == 0) {
                        $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                            'ano' => $this->ano_fiscal + $contador,
                            'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                            'valor' => round($amortizable - $total, 2),
                            'periodo' => $periodo
                        );
                    } else {
                        $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                            'ano' => $this->ano_fiscal + $contador,
                            'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                            'valor' => round(($amortizable / $this->datos['periodos']) / 4, 2),
                            'periodo' => $periodo
                        );
                        $total = $total + round(($amortizable / $this->datos['periodos']) / 4, 2);
                    }
                    $periodo++;
                }
                $contador++;
            }

            if ($dias != 0) {
                $periodo = 1;
                while ($periodo < $this->periodo_inicial) {

                    $fecha = date('d-m-Y', strtotime($fecha . '+ 3 month'));

                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round(($amortizable / $this->datos['periodos']) / 4, 2),
                        'periodo' => $periodo
                    );
                    $total = $total + round(($amortizable / $this->datos['periodos']) / 4, 2);
                    $periodo++;
                }
                
                $fecha = date('d-m-Y', strtotime($fecha . '+ 3 month'));
                $valor = $amortizable / $this->datos['periodos'] / 4 - $valor;
                $this->lineas[$this->ano_fiscal + $contador . '_' . $this->periodo_inicial] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => $this->fecha_fin ,
                    'valor' => round($amortizable - $total, 2),
                    'periodo' => $this->periodo_inicial
                );
            }
        }
        
        //MENSUAL
        if ($this->datos['contabilizacion'] == 'mensual') {
      
            if ($dias != 0) {  
                
                $fecha = date('d-m-Y', strtotime($fecha . '+' .$mes_fiscal. ' month'));
                
                $dias_ano_fiscal = $this->diferencia_dias($ejercicio_factura->fechainicio, $ejercicio_factura->fechafin) + 1;
                $valor = ($amortizable / $this->datos['periodos'] / $dias_ano_fiscal * ($dias_ano_fiscal - $dias)) - ((($amortizable / $this->datos['periodos']) / 12) * (12 - $mes_fiscal));
                $this->lineas[$this->ano_fiscal + $contador . '_' . $mes_fiscal] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round($valor, 2),
                    'periodo' => $mes_fiscal
                );
                $total = $total + round($valor, 2);
                $periodo = $mes_fiscal + 1;
                
                while ($periodo <= 12) {
                    
                    $fecha = date('d-m-Y', strtotime($fecha . '+ 1 month'));
                    
                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round(($amortizable / $this->datos['periodos']) / 12, 2),
                        'periodo' => $periodo
                    );
                    $total = $total + round(($amortizable / $this->datos['periodos']) / 12, 2);
                    $periodo++;
                }
                $contador++;
            }

            while ($contador < $this->datos['periodos']) {              
                $periodo = 1;
                while ($periodo <= 12) {
                    
                    $fecha = date('d-m-Y', strtotime($fecha . '+ 1 month'));
                    
                    if ($contador == $this->datos['periodos'] - 1 && $periodo == 12 && $dias == 0) {
                        $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                            'ano' => $this->ano_fiscal + $contador,
                            'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                            'valor' => round($amortizable - $total,2),
                            'periodo' => $periodo
                        );
                    } else {
                        $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                            'ano' => $this->ano_fiscal + $contador,
                            'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                            'valor' => round(($amortizable / $this->datos['periodos']) / 12, 2),
                            'periodo' => $periodo
                        );
                        $total = $total + round(($amortizable / $this->datos['periodos']) / 12, 2);
                    }

                    $periodo++;
                }
                $contador++;
            }

            if ($dias != 0) {
                $periodo = 1;
                while ($periodo < $mes_fiscal) {

                    $fecha = date('d-m-Y', strtotime($fecha . '+ 1 month'));

                    $this->lineas[$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round(($amortizable / $this->datos['periodos']) / 12, 2),
                        'periodo' => $periodo
                    );
                    $total = $total + round(($amortizable / $this->datos['periodos']) / 12, 2);
                    $periodo++;
                }
                
                $fecha = date('d-m-Y', strtotime($fecha . '+ 1 month'));
                $valor = $amortizable / $this->datos['periodos'] / 12 - $valor;
                $this->lineas[$this->ano_fiscal + $contador . '_' . $mes_fiscal] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => $this->fecha_fin ,
                    'valor' => round($amortizable - $total,2),
                    'periodo' => $mes_fiscal
                );
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
}
