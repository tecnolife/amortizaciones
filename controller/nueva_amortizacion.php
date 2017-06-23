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
    public $id_factura;
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
            $this->id_factura = (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) || filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT));         
            $factura = new factura_proveedor();
            $this->amortizacion = $factura->get($this->id_factura);
            $this->fecha = date('d-m-Y', strtotime($this->amortizacion->fecha));           
        } 

        if (filter_input(INPUT_POST, 'periodos') !== null) {
            $this->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
            if (filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT) != 0) {
                
                $this->datos = array(
                    'descripcion' => filter_input(INPUT_POST,'descripcion'),
                    'tipo' => filter_input(INPUT_POST,'tipo'),
                    'valor' => filter_input(INPUT_POST,'valor'),
                    'residual' => filter_input(INPUT_POST,'residual'),
                    'periodos' => filter_input(INPUT_POST,'periodos'),
                    'fecha_inicio' => filter_input(INPUT_POST,'fecha_inicio'),
                );
                                          
                $this->fecha_fin = date('d-m-Y', strtotime(filter_input(INPUT_POST, 'fecha_inicio') . '+' . filter_input(INPUT_POST, 'periodos') . ' year - 1 day'));

                if (filter_input(INPUT_POST, 'tipo') == 'constante') {
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
        $contador = 0;
        $this->lineas = array();
        $amortizable = $this->datos['valor'] - $this->datos['residual'];
        $ano_inicio = (int)(Date('Y', strtotime($this->datos['fecha_inicio'])));
        $fecha_inicio = new DateTime($this->datos['fecha_inicio']);
        $dia1 = new DateTime("01-01-$ano_inicio");
        $dias = $dia1->diff($fecha_inicio);
        $dias = $dias->format('%a');
             
        
        if ($dias != 0) {
            $valor = $amortizable / $this->datos['periodos'] / $this->dias_ano($ano_inicio) * ($this->dias_ano($ano_inicio) - $dias);
            $this->lineas[$ano_inicio + $contador] = round($valor, 2);
            $contador++;
        }

        while ($contador < $this->datos['periodos']) {
            $this->lineas[$ano_inicio + $contador] = round($amortizable / $this->datos['periodos'], 2);
            $contador++;
        }

        if ($dias != 0) {
            $valor = $amortizable / $this->datos['periodos'] - $this->lineas[$ano_inicio];
            $this->lineas[$ano_inicio + $contador] = round($valor, 2);
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
        }

        return 365;
    }
}
