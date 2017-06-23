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
require_model('factura_proveedor.php');

/**
 * Class editar_amortizacion
 */
class editar_amortizacion extends fs_controller
{
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
    public $periodos = 0;
    /**
     * @var int
     */
    public $precio_venta = 0;
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

        if (filter_input(INPUT_GET,'id') !== null || filter_input(INPUT_POST,'id') !== null ) {
            $amortizacion = new amortizacion();
            $this->amortizacion = $amortizacion->get_by_amortizacion(filter_input(INPUT_GET,'id', FILTER_VALIDATE_INT));
            $factura = new factura_proveedor();
            $this->factura = $factura->get($this->amortizacion->id_factura);
            
            $this->fecha_factura = date('d-m-Y', strtotime($this->factura->fecha));

            //$this->listado_lineas = $lineas_amortizaciones->get_by_amortizacion(filter_input(INPUT_GET,'id') || filter_input(INPUT_POST,'id') );
            $this->listado_lineas = $lineas_amortizaciones->get_by_amortizacion($this->amortizacion->id_amortizacion);

            $this->calcular_precio();
            
            $this->precio_venta = $this->amortizacion->valor - $this->amortizado;

        }
    }

    /**
     * TODO PHPDoc
     */
    private function calcular_precio()
    {
        $ano_actual = (int)(Date('Y'));
        $ano_inicio = (int)(Date('Y', strtotime($this->amortizacion->fecha_inicio)));
        $ano_fin = (int)(Date('Y', strtotime($this->amortizacion->fecha_fin)));

        foreach ($this->listado_lineas as $key => $value) {
            if ($value->ano == $ano_actual) {
                //comprueba que linea coincide con el año actual, calculando por medio de los dias trancurridos este año lo que hemos amortizado.
                if ($value->ano == $ano_inicio) {
                    if (strtotime(date('d-m-Y')) > strtotime($this->amortizacion->fecha_inicio)) {
                        $dias = $this->intervalo_fechas(new DateTime($this->amortizacion->fecha_inicio),
                            new DateTime($this->today()));
                        $dias_ano = $this->intervalo_fechas(new DateTime($this->amortizacion->fecha_inicio),
                            new DateTime("31-12-$ano_inicio"));
                        $this->amortizado += ($value->cantidad / $dias_ano) * $dias;
                        $this->falta_amortizar = (($value->cantidad / $dias_ano) * $dias);
                    }
                } elseif ($value->ano == $ano_fin) {
                    if (strtotime(date('d-m-Y')) < strtotime($this->amortizacion->fecha_fin)) {
                        $dias = $this->intervalo_fechas(new DateTime($this->today()),
                            new DateTime($this->amortizacion->fecha_fin));
                        $dias_ano = $this->intervalo_fechas(new DateTime("01-01-$ano_fin"),
                            new DateTime($this->amortizacion->fecha_fin));
                        $this->amortizado += ($value->cantidad / $dias_ano) * $dias;
                        $this->falta_amortizar = (($value->cantidad / $dias_ano) * $dias);
                    } else {
                        $this->amortizado += $value->cantidad;
                    }
                } else {
                    $dias = $this->intervalo_fechas(new DateTime("01-01-$ano_actual"), new DateTime($this->today()));
                    $this->amortizado += ($value->cantidad / $this->dias_ano($value->ano)) * $dias;
                    $this->falta_amortizar = ($value->cantidad / $this->dias_ano($value->ano)) * $dias;
                }
                $this->periodos++;
            } elseif ($value->contabilizada == 1) {
                $this->amortizado += $value->cantidad;
                $this->periodos++;
            }
        }
    }

    /**
     * @param $ano
     * @return int
     */
    private function dias_ano($ano) //Comprueba si el año tiene 365 o 366 dias
    {
        if (($ano % 4 == 0) && (($ano % 100 != 0) || ($ano % 400 == 0))) {
            return 366;
        } else {
            return 365;
        }
    }

    /**
     * @param $fecha_inicio
     * @param $fecha_fin
     * @return int
     */
    private function intervalo_fechas($fecha_inicio, $fecha_fin)
    {
        $dias = $fecha_inicio->diff($fecha_fin);
        $dias = (int)($dias->format('%a'));
        $dias++;
        return $dias;
    }
}
