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

class nueva_amortizacion extends fs_controller 
{
   public $amortizacion;
   public $lineas;
   public $fecha;
   public $fecha_fin;

   public function __construct() 
   {
      parent::__construct(__CLASS__, 'nueva amortización', 'contabilidad', FALSE, FALSE);
   }

   protected function private_core() 
   {
      $this->share_extension();
      $this->amortizacion = FALSE; 

      if (isset($_REQUEST['id'])) {
         $factura = new factura_proveedor();
         $this->amortizacion = $factura->get($_REQUEST['id']);
         
         $this->fecha = date('d-m-Y', strtotime($this->amortizacion->fecha));
      }

      if (isset($_POST['periodos'])) 
      {
         if ($_POST['periodos'] != 0) 
         {
            $this->fecha_fin = date('d-m-Y',strtotime($_POST['fecha_inicio'] . '+'.$_POST['periodos'].' year - 1 day'));
            
            if ($_POST['tipo'] == 'constante') 
            {
               $this->constante();
            }
         } else
            $this->new_error_msg('No se han podido generar las líneas de amortización, porque el periodo de años estaba a 0');         
      }
   }

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

   private function constante() 
   {
      $contador = 0;
      $this->lineas = array();
      $amortizable = $_POST['valor'] - $_POST['residual'];
      $ano_inicio = intval(Date('Y', strtotime($_POST['fecha_inicio'])));
      $fecha_inicio = new DateTime($_POST['fecha_inicio']);
      $dia1 = new DateTime("01-01-$ano_inicio");
      $dias = $dia1->diff($fecha_inicio);
      $dias = $dias->format('%a');
      
      if ($dias != 0) 
      {
         $valor = $amortizable / $_POST['periodos'] / $this->dias_ano($ano_inicio) * ($this->dias_ano($ano_inicio) - $dias);
         $this->lineas[$ano_inicio + $contador] = round($valor,2);
         $contador++;
      }
      
      while ($contador < $_POST['periodos']) 
      {
         $this->lineas[$ano_inicio + $contador] = round($amortizable / $_POST['periodos'],2);
         $contador++;
      }
      
      if ($dias != 0) 
      {
         $valor = $amortizable / $_POST['periodos'] - $this->lineas[$ano_inicio];
         $this->lineas[$ano_inicio + $contador] = round($valor, 2);
      }
   }
   
   private function dias_ano($ano) //Comprueba si el año tiene 365 o 366 dias
   {
      if (($ano%4==0) && (($ano%100!=0)||($ano%400==0))) 
      {
         return 366;
      }
      else
      {
         return 365;
      }
   }
}
