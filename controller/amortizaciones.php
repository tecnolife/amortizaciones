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

class amortizaciones extends fs_controller 
{
   
   public $amortizacion;//modelo
   public $listado;
   public $linea_amortizacion;//modelo
   public $listado_lineas;
   public $listado_pendientes;
   public $offset;
   public $limite;


   public function __construct() 
   {
      parent::__construct(__CLASS__, 'amortizaciones', 'compras');
   }

   protected function private_core() 
   {
      $this->amortizacion = new amortizacion();
      $this->linea_amortizacion = new linea_amortizacion();
      $this->offset = 0;
      $this->limite = FS_ITEM_LIMIT;
      
      if (isset($_REQUEST['offset']))
      {
         $this->offset = intval($_REQUEST[offset]);
      }
      
      if( isset($_POST['fecha_inicio']))
      { 
         $this->anadir_amortizacion();
      }
      elseif ( isset($_REQUEST['delete'])) 
      {
         $this->eliminar_amortizacion();
      }
      elseif ( isset($_REQUEST['cancel'])) 
      {
         $this->amortizacion->cancel($_REQUEST['cancel']);
      }
      elseif ( isset($_REQUEST['sale'])) 
      {
         $this->amortizacion->sale($_REQUEST['sale']);
         $this->linea_amortizacion->sale($_REQUEST['sale']);
      }
      elseif ( isset($_REQUEST['count'])) 
      {
         $this->contabilizar();
      }
      
      $this->listado = $this->amortizacion->all($this->offset, $this->limite);
      $this->listado_lineas = $this->linea_amortizacion->this_year();
      
      $this->listar_pendientes();
      
   }
   
   private function anadir_amortizacion()
   {
      $this->amortizacion->cod_subcuenta_cierre = $_POST['cod_subcuenta_cierre'];
      $this->amortizacion->cod_subcuenta_debe = $_POST['cod_subcuenta_debe'];
      $this->amortizacion->cod_subcuenta_haber = $_POST['cod_subcuenta_haber'];
      $this->amortizacion->descripcion = $_POST['descripcion'];
      $this->amortizacion->fecha_fin = $_POST['fecha_fin'];
      $this->amortizacion->fecha_inicio = $_POST['fecha_inicio'];
      $this->amortizacion->id_factura = $_POST['id_factura'];
      $this->amortizacion->periodos = $_POST['periodos'];
      $this->amortizacion->residual = $_POST['residual'];
      $this->amortizacion->tipo = $_POST['tipo'];
      $this->amortizacion->valor = $_POST['valor'];
      
      if( isset($_REQUEST['editar']))
      { 
         $this->amortizacion->id_amortizacion = $_POST['id_amortizacion'];
         $this->amortizacion->estado = $_POST['estado'];
      }
      
      if ( $this->amortizacion->save())
      {
         $this->new_message("La amortización se ha creado con exito"); //Este mensaje aparece aunque la creación de las líneas fallen
         $this->anadir_lineas();
      }
      else 
         $this->new_error_msg ("Error al crear la amortización");
               
   }

   private function anadir_lineas()
   {
      $this->listado_lineas = array();
      $contador = 0;
      $ano = intval(Date('Y', strtotime($_POST['fecha_inicio'])));
      
      while ($contador <= $_POST['periodos'])
      {
         if( isset($_REQUEST['editar']))
         {
            $this->listado_lineas[$_POST['ano_'.$ano.'']] = array(
               'cantidad' => round($_POST['cantidad_'.$ano.''],2),
               'contabilizada' => $_POST['contabilizada_'.$ano.''],
               'id_amortizacion' => $_POST['id_amortizacion_'.$ano.''],
               'id_linea' => $_POST['id_linea_'.$ano.'']);
         }
         else
         {
            $this->listado_lineas[intval($_POST['ano_'.$ano.''])] = array('cantidad' => round($_POST['cantidad_'.$ano.''],2));
         }
         
         $contador++;
         $ano ++;
      }
      
      $this->linea_amortizacion->id_amortizacion = $this->amortizacion->id_amortizacion;
      
      foreach ($this->listado_lineas as $key => $value) 
      {
         
         $this->linea_amortizacion->ano = $key;
         $this->linea_amortizacion->cantidad = $value['cantidad'];
         
         if( isset($_REQUEST['editar']))
         {
            $this->linea_amortizacion->contabilizada = $value['contabilizada'];
            $this->linea_amortizacion->id_amortizacion = $value['id_amortizacion'];
            $this->linea_amortizacion->id_linea = $value['id_linea'];
         }
         
         //ERROR VISUAL, porque se muestra una línea de mensaje por cada línea que se crea
         if ($this->linea_amortizacion->save())
         {
            $this->new_message("La línea de amortizacion se ha creado con exito");
         } 
         else
         {
            $this->amortizacion->delete();
            $this->linea_amortizacion->delete();
            $this->new_error_msg("Error al crear la línea de amortización");
         }
      }
   }
   
   private function eliminar_amortizacion()
   {
      $this->amortizacion->id_amortizacion = $_REQUEST['delete'];
         $this->linea_amortizacion->id_amortizacion = $_REQUEST['delete'];
         
         if ( $this->amortizacion->delete())
         {
            $this->new_message("La amortización se ha eliminado con exito");
            
            if ( $this->linea_amortizacion->delete())
            {
               $this->new_message("Las líneas de la amortización se han eliminado con exito");
            }
            else 
               $this->new_error_msg ("Error al eliminar las líneas de la amortización");
         }
         else 
            $this->new_error_msg ("Error al eliminar la amortización");
   }
   
   private function listar_pendientes()
   {
      $this->listado_pendientes = array();
      foreach ($this->listado_lineas as $key => $value)
      {
         $amortizacion = $this->amortizacion->get_by_amortizacion($value->id_amortizacion);
         
         $this->listado_pendientes[] = array(
               'ano' => $value->ano,
               'cantidad' => $value->cantidad,
               'contabilizada' => $value->contabilizada,
               'id_amortizacion' => $value->id_amortizacion,
               'id_linea' => $value->id_linea,
               'descripcion' => $amortizacion->descripcion);
      }
   }
   
   private function contabilizar() 
   {
      /*Estamos cogiendo el año que corresponde a la fecha de hoy, lo que significa, que si intentamos hacer la contabilización en enero,
        por ejemplo, nos hara la de ese año y no la del anterior
        Lo ideal sería coger el ejercicio fiscal actual, pero en realidad no se como hacerlo, ¿si hay dos ejercicios fiscales abiertos?*/
      $ano = intval(Date('Y', strtotime($_REQUEST['year'])));
      $linea = $this->linea_amortizacion->get_to_count($_REQUEST['count'], $ano);
      
      if ($linea->contabilizada != 1) 
      {
         $this->linea_amortizacion->count($linea->id_linea);
         $amortizacion = $this->amortizacion->get_by_amortizacion($linea->id_amortizacion);
         $ano_final = intval(Date('Y', strtotime($amortizacion->fecha_fin)));
         
         $this->new_message('Línea contabilizada');
         
         if ($ano == $ano_final)
         {
            $this->amortizacion->complete($linea->id_amortizacion);
            $this->new_message('Amortización completada');
         }
         
      } else
         $this->new_message('La línea ya está contabilizada');
   }

}
