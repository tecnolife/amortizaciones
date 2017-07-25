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
require_model('asiento.php');
require_model('linea_amortizacion.php');
require_model('factura_proveedor.php');
require_model('ejercicio.php');


/**
 * Class editar_amortizacion
 */
class editar_amortizacion extends fs_controller
{
    /**
     * @var
     */
    public $ano_fiscal;
    /**
     * @var
     */
    public $ejercicio_actual;
    /**
     * @var
     */
    public $factura;
    /**
     * @var
     */
    public $fecha_factura;
    /**
     * @var
     */
    public $amortizacion;
    /**
     * @var
     */
    public $listado_lineas;
    /**
     * @var
     */
    public $amortizado;
    /**
     * @var int
     */
    public $periodo_inicial;
    /**
     * @var int
     */
    public $periodos = 0;
    /**
     * @var
     */
    public $falta_amortizar;

    /**
     * editar_amortizacion constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'editar amortizacion', 'compras', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core()
 {
        $this->factura = false;
        $lineas_amortizaciones = new linea_amortizacion();

        if (filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT) !== null) {
            $this->eliminar_asiento(filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT));
        }
        
        if (filter_input(INPUT_GET, 'renew', FILTER_VALIDATE_INT) !== null) {
            $this->resucitar(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));
        }
        
        if (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) !== null) {
            $amortizacion = new amortizacion();
            $this->amortizacion = $amortizacion->get_by_amortizacion(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));
            $factura = new factura_proveedor();
            $this->factura = $factura->get($this->amortizacion->id_factura);

            $this->fecha_factura = date('d-m-Y', strtotime($this->factura->fecha));
            $this->listado_lineas = $lineas_amortizaciones->get_by_amortizacion($this->amortizacion->id_amortizacion);

            $ejercicio = new ejercicio;
            $primer_ejercicio = $ejercicio->get_by_fecha($this->amortizacion->fecha_inicio);
            $this->ejercicio_actual = $ejercicio->get_by_fecha($this->today());
            $this->ano_fiscal = (int) (Date('Y', strtotime($primer_ejercicio->fechainicio)));

            //Hallar el periodo inicial
            $periodo_fecha = $this->periodo_por_fecha($this->amortizacion->fecha_inicio, $primer_ejercicio->fechafin, $primer_ejercicio->fechainicio, $this->amortizacion->contabilizacion);
            $this->periodo_inicial = $periodo_fecha['periodo'];
        }
        
    }

     /**
     * @param $fecha
     * @param $ejercicio_fecha_fin
     * @param $ejercicio_fecha_inicio
     * @param $contabilizacion
     * @return int
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
    
    /**
     * @param $id_linea
     * @return $array
     */
    private function eliminar_asiento($id_linea)
    {
        $lineas_amortizaciones = new linea_amortizacion();
        $asiento = new asiento();
        $linea = $lineas_amortizaciones->get_by_id_linea($id_linea);
        $asiento_amortizacion = $asiento->get($linea->id_asiento);
        
        if ($asiento_amortizacion->delete()) {
            $lineas_amortizaciones->discount($id_linea);
            $this->new_message('Asiento eliminado');
        } else {
            $this->new_message('No se ha podido eliminar el asiento');
        }
    }
    
    /**
     * @param $id_linea
     * @return $array
     */
    private function resucitar($id_amortizacion)
    {
        $lineas_amortizaciones = new linea_amortizacion();
        $asiento = new asiento();
        $amortizacion_model = new amortizacion();
        $amortizacion = $amortizacion_model->get_by_amortizacion($id_amortizacion);
        $asiento_fin_vida = $asiento->get($amortizacion->id_asiento_fin_vida);
        
        if ($asiento_fin_vida->delete()) {
            $amortizacion_model->resurrect($id_amortizacion);
            
            $ejercicio_model = new ejercicio();
            $ejercicio = $ejercicio_model->get_by_fecha($amortizacion->fecha_fin_vida_util);
            $periodo_fecha = $this->periodo_por_fecha($amortizacion->fecha_fin_vida_util, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);
            $ano = $mes = (int) (Date('Y', strtotime($ejercicio->fechainicio)));
            
            if (strtotime($amortizacion->fecha_fin_vida_util) < strtotime($amortizacion->fecha_fin)) {
                $linea = $lineas_amortizaciones->get_by_id_amor_ano_periodo($id_amortizacion, $ano, $periodo_fecha['periodo']);
                $asiento_amortizacion = $asiento->get($linea->id_asiento);

                if ($asiento_amortizacion->delete()) {
                    $lineas_amortizaciones->discount($linea->id_linea);
                    $this->new_message('Amortización reanudada, se ha eliminado el asiento de fin de la vida útil del amortizado y el el asiento que contabilizo el tiempo trancurrido en ese periodo');
                } else {
                    $this->new_message('No se ha podido reanudar la amortización');
                }
            } else {
                $this->new_message('Amortización reanudada, se ha eliminado el asiento de fin de la vida útil del amortizado');
            }
        } else {
            $this->new_message('No se ha podido reanudar la amortización');
        }
    }
    
}
