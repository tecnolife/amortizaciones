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

class amortizacion extends fs_model 
{
   
   public $descripcion;
   public $estado;
   public $fecha_fin;
   public $fecha_inicio;
   public $id_amortizacion;
   public $id_factura;
   public $periodos;
   public $residual;
   public $tipo;
   public $valor;

   public function __construct($t = FALSE) 
   {
      parent::__construct('amortizaciones');
      if($t)
      {/*
         $this->descripcion = NULL;
         $this->estado = NULL;
         $this->fecha_fin = Date('d-m-Y');
         $this->fecha_inicio = Date('d-m-Y');
         $this->id_amortizacion = NULL;
         $this->id_factura = NULL;
         $this->periodos = NULL;
         $this->residual = NULL;
         $this->tipo = NULL;
         $this->valor = NULL;
         */
         $this->descripcion = $t['descripcion'];
         $this->estado = $t['estado'];
         $this->fecha_fin = Date('d-m-Y', strtotime($t['fechafin']));
         $this->fecha_inicio = Date('d-m-Y', strtotime($t['fechainicio']));
         $this->id_amortizacion = $this->intval($t['idamortizacion']);
         $this->id_factura = $this->intval($t['idfactura']);
         $this->periodos = $t['periodos'];
         $this->residual = $t['residual'];
         $this->tipo = $t['tipo'];
         $this->valor = $t['valor'];
         
         //str2bool() necesario para los valores booleanos
      }
      else
      {
         $this->descripcion = NULL;
         $this->estado = NULL;
         $this->fecha_fin = Date('d-m-Y');
         $this->fecha_inicio = Date('d-m-Y');
         $this->id_amortizacion = NULL;
         $this->id_factura = NULL;
         $this->periodos = 0;
         $this->residual = 0;
         $this->tipo = NULL;
         $this->valor = 0;
         
      }
   }
   
   protected function install() 
   {
      return '';
   }
   
   public function exists()
   {
      if(is_null($this->id_amortizacion))
      {
         return FALSE;
      }
      else
         return $this->db->select ("SELECT * FROM amortizaciones WHERE idamortizacion = ".$this->var2str($this->id_amortizacion).";");
   }
   
   public function save() 
   {
      if($this->exists())
      {
         $sql = "UPDATE amortizaciones SET 
                 descripcion = ". $this->var2str($this->descripcion).", 
                 estado = ". $this->var2str($this->estado).",
                 fechafin = ". $this->var2str($this->fecha_fin).",
                 fechainicio = ". $this->var2str($this->fecha_inicio).",
                 idamortizacion = ". $this->var2str($this->id_amortizacion).",
                 idfactura = ". $this->var2str($this->id_factura).",
                 periodos = ". $this->var2str($this->periodos).",
                 residual = ". $this->var2str($this->residual).",
                 tipo = ". $this->var2str($this->tipo).",
                 valor = ".$this->var2str($this->valor)." 
                 WHERE idamortizacion = ".$this->var2str($this->id_amortizacion).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO amortizaciones (descripcion,fechafin,fechainicio,idfactura,periodos,residual,tipo,valor) VALUES ("
                . $this->var2str($this->descripcion).","
                . $this->var2str($this->fecha_fin).","
                . $this->var2str($this->fecha_inicio).","
                . $this->var2str($this->id_factura).","
                . $this->var2str($this->periodos).","
                . $this->var2str($this->residual).","
                . $this->var2str($this->tipo).","
                . $this->var2str($this->valor).");";
        
         if($this->db->exec($sql))
         {
            $this->id_amortizacion = $this->db->lastval();
            //lastval() devulelve el ultimo id asignado
            return TRUE;
         }
         else 
            return FALSE;
      }
   }
   
   public function delete() 
   {
      return $this->db->exec("DELETE FROM amortizaciones WHERE idamortizacion = ".$this->var2str($this->id_amortizacion).";");
   }
   
   public function cancel($id)
   {
      return $this->db->exec("UPDATE amortizaciones SET estado = 'anulada' WHERE idamortizacion = ".$this->var2str($id).";"); 
   }
   
   public function sale($id)
   {
      return $this->db->exec("UPDATE amortizaciones SET estado = 'vendida' WHERE idamortizacion = ".$this->var2str($id).";"); 
   }
   
   public function complete($id)
   {
      return $this->db->exec("UPDATE amortizaciones SET estado = 'completada' WHERE idamortizacion = ".$this->var2str($id).";"); 
   }
   
   public function all($offset = 0, $limit = FS_ITEM_LIMIT)
   {
      $lista = array();
      
      $sql = $this->db->select_limit("SELECT * FROM amortizaciones ORDER BY fechainicio DESC", $limit, $offset);
      if($sql)
      {
         foreach ($sql as $d)
         {
            $lista[] = new amortizacion ($d);
         }  
      }
      return $lista;
   }
   
   public function get_by_amortizacion($id_amor)
   {
     $sql = $this->db->select("SELECT * FROM amortizaciones WHERE idamortizacion = ".$this->var2str($id_amor).";");
      if($sql)
      {
         return new \amortizacion($sql[0]);
      }
      else
         return FALSE;
   }
   
}
