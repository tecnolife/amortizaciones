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
    /**
     * @var int
     */
    public $amortizando;
    /**
     * @var null
     */
    public $coddivisa;
    /**
     * @var null
     */
    public $codserie;
    /**
     * @var null
     */
    public $cod_subcuenta_beneficios;
    /**
     * @var null
     */
    public $cod_subcuenta_cierre;
    /**
     * @var null
     */
    public $cod_subcuenta_debe;
    /**
     * @var null
     */
    public $cod_subcuenta_haber;
    /**
     * @var null
     */
    public $cod_subcuenta_perdidas;
    /**
     * @var int
     */
    public $completada;
    /**
     * @var null
     */
    public $contabilizacion;
    /**
     * @var null
     */
    public $descripcion;
    /**
     * @var null
     */
    public $documento;
    /**
     * @var false|string
     */
    public $fecha_fin;
    /**
     * @var false|string
     */
    public $fecha_fin_vida_util;
    /**
     * @var false|string
     */
    public $fecha_inicio;
    /**
     * @var int
     */
    public $fin_vida_util;
    /**
     * @var null
     */
    public $id_amortizacion;
    /**
     * @var null
     */
    public $id_asiento_fin_vida;
    /**
     * @var null
     */
    public $id_factura;
    /**
     * @var int
     */
    public $periodo_final;
    /**
     * @var int
     */
    public $periodos;
    /**
     * @var int
     */
    public $residual;
    /**
     * @var null
     */
    public $tipo;
    /**
     * @var int
     */
    public $valor;
    /**
     * @var int
     */
    public $vendida;

    /**
     * amortizacion constructor.
     * @param bool $t
     */
    public function __construct($t = false)
    {
        parent::__construct('amortizaciones');
        if ($t) {/*
         $this->descripcion = NULL;
         $this->fecha_fin = Date('d-m-Y');
         $this->fecha_inicio = Date('d-m-Y');
         $this->id_amortizacion = NULL;
         $this->id_factura = NULL;
         $this->periodos = NULL;
         $this->residual = NULL;
         $this->tipo = NULL;
         $this->valor = NULL;
         */
            $this->amortizando = $this->str2bool($t['amortizando']);
            $this->coddivisa = $t['coddivisa'];
            $this->codserie = $t['codserie'];
            $this->cod_subcuenta_beneficios = $this->intval($t['codsubcuentabeneficios']);
            $this->cod_subcuenta_cierre = $this->intval($t['codsubcuentacierre']);
            $this->cod_subcuenta_debe = $this->intval($t['codsubcuentadebe']);
            $this->cod_subcuenta_haber = $this->intval($t['codsubcuentahaber']);
            $this->cod_subcuenta_perdidas = $this->intval($t['codsubcuentaperdidas']);
            $this->completada = $this->str2bool($t['completada']);
            $this->contabilizacion = $t['contabilizacion'];
            $this->descripcion = $t['descripcion'];
            $this->documento = $t['documento'];
            $this->fecha_fin = Date('d-m-Y', strtotime($t['fechafin']));
            $this->fecha_fin_vida_util = Date('d-m-Y', strtotime($t['fechafinvidautil']));
            $this->fecha_inicio = Date('d-m-Y', strtotime($t['fechainicio']));
            $this->fin_vida_util = $this->str2bool($t['finvidautil']);
            $this->id_amortizacion = $this->intval($t['idamortizacion']);
            $this->id_asiento_fin_vida = $this->intval($t['idasientofinvida']);
            $this->id_factura = $this->intval($t['idfactura']);
            $this->periodo_final = $t['periodofinal'];
            $this->periodos = $t['periodos'];
            $this->residual = $t['residual'];
            $this->tipo = $t['tipo'];
            $this->valor = $t['valor'];
            $this->vendida = $this->str2bool($t['vendida']);

            //str2bool() necesario para los valores booleanos
        } else {
            $this->amortizando = null;
            $this->coddivisa = null;
            $this->codserie = null;
            $this->cod_subcuenta_beneficios = null;
            $this->cod_subcuenta_cierre = null;
            $this->cod_subcuenta_debe = null;
            $this->cod_subcuenta_haber = null;
            $this->cod_subcuenta_perdidas = null;
            $this->completada = FALSE;
            $this->contabilizacion = null;
            $this->descripcion = null;
            $this->documento = null;
            $this->fecha_fin = Date('d-m-Y');
            $this->fecha_fin_vida_util = Date('d-m-Y');
            $this->fecha_inicio = Date('d-m-Y');
            $this->fin_vida_util = null;
            $this->id_amortizacion = null;
            $this->id_asiento_fin_vida = null;
            $this->id_factura = null;
            $this->periodo_final = 0;
            $this->periodos = 0;
            $this->residual = 0;
            $this->tipo = null;
            $this->valor = 0;
            $this->vendida = null;

        }
    }

    /**
     * @return bool
     */
    public function exists()
    {
        if (is_null($this->id_amortizacion)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM amortizaciones WHERE idamortizacion = " . $this->var2str($this->id_amortizacion) . ";");
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE amortizaciones SET 
                 codsubcuentabeneficios = " . $this->var2str($this->cod_subcuenta_beneficios) . ",
                 codsubcuentacierre = " . $this->var2str($this->cod_subcuenta_cierre) . ",
                 codsubcuentadebe = " . $this->var2str($this->cod_subcuenta_debe) . ",
                 codsubcuentahaber = " . $this->var2str($this->cod_subcuenta_haber) . ",
                 codsubcuentaperdidas = " . $this->var2str($this->cod_subcuenta_perdidas) . ",
                 contabilizacion = " . $this->var2str($this->contabilizacion) . ",    
                 descripcion = " . $this->var2str($this->descripcion) . ", 
                 fechafin = " . $this->var2str($this->fecha_fin) . ",
                 fechainicio = " . $this->var2str($this->fecha_inicio) . ",
                 idamortizacion = " . $this->var2str($this->id_amortizacion) . ",
                 idfactura = " . $this->var2str($this->id_factura) . ",
                 periodos = " . $this->var2str($this->periodos) . ",
                 residual = " . $this->var2str($this->residual) . ",
                 tipo = " . $this->var2str($this->tipo) . ",
                 valor = " . $this->var2str($this->valor) . " 
                 WHERE idamortizacion = " . $this->var2str($this->id_amortizacion) . ";";
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO amortizaciones (coddivisa,codserie,codsubcuentabeneficios,codsubcuentacierre,codsubcuentadebe,codsubcuentahaber,codsubcuentaperdidas,contabilizacion,descripcion,documento,fechafin,fechainicio,idfactura,periodofinal,periodos,residual,tipo,valor) VALUES ("
                . $this->var2str($this->coddivisa) . ","
                . $this->var2str($this->codserie) . ","
                . $this->var2str($this->cod_subcuenta_beneficios) . ","
                . $this->var2str($this->cod_subcuenta_cierre) . ","
                . $this->var2str($this->cod_subcuenta_debe) . ","
                . $this->var2str($this->cod_subcuenta_haber) . ","
                . $this->var2str($this->cod_subcuenta_perdidas) . ","
                . $this->var2str($this->contabilizacion) . ","
                . $this->var2str($this->descripcion) . ","
                . $this->var2str($this->documento) . ","
                . $this->var2str($this->fecha_fin) . ","
                . $this->var2str($this->fecha_inicio) . ","
                . $this->var2str($this->id_factura) . ","
                . $this->var2str($this->periodo_final) . ","
                . $this->var2str($this->periodos) . ","
                . $this->var2str($this->residual) . ","
                . $this->var2str($this->tipo) . ","
                . $this->var2str($this->valor) . ");";

            if ($this->db->exec($sql)) {
                $this->id_amortizacion = $this->db->lastval();
                //lastval() devulelve el ultimo id asignado
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->db->exec("DELETE FROM amortizaciones WHERE idamortizacion = " . $this->var2str($this->id_amortizacion) . ";");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function cancel($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET amortizando = FALSE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @return mixed
     */
    public function restart($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET amortizando = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function resurrect($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET finvidautil = FALSE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @return mixed
     */
    public function complete($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET completada = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @return mixed
     */
    public function sale($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET vendida = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function end_life($id)
    {
        return $this->db->exec("UPDATE amortizaciones SET finvidautil = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @param $id_asiento
     * @return mixed
     */
    public function end_life_count($id, $id_asiento)
    {
        return $this->db->exec("UPDATE amortizaciones SET idasientofinvida = " . $this->var2str($id_asiento) . " WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @param $fecha
     * @return mixed
     */
    public function date_end_life($id, $fecha)
    {
        return $this->db->exec("UPDATE amortizaciones SET fechafinvidautil = " . $this->var2str($fecha) . " WHERE idamortizacion = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @param $cod_subcuenta_beneficios
     * @param $cod_subcuenta_cierre
     * @param $cod_subcuenta_debe
     * @param $cod_subcuenta_haber
     * @param $cod_subcuenta_perdidas
     * @return mixed
     */
    public function update_counts($id, $cod_subcuenta_beneficios, $cod_subcuenta_cierre, $cod_subcuenta_debe, $cod_subcuenta_haber, $cod_subcuenta_perdidas)
    {
        return $this->db->exec("UPDATE amortizaciones SET 
                 codsubcuentabeneficios = " . $this->var2str($cod_subcuenta_beneficios) . ",
                 codsubcuentacierre = " . $this->var2str($cod_subcuenta_cierre) . ",
                 codsubcuentadebe = " . $this->var2str($cod_subcuenta_debe) . ",
                 codsubcuentahaber = " . $this->var2str($cod_subcuenta_haber) . ",
                 codsubcuentaperdidas = " . $this->var2str($cod_subcuenta_perdidas) . "
                WHERE idamortizacion = " . $this->var2str($id) . ";");
    }

    /**
     * @param int $offset
     * @param $limit
     * @return array
     */
    public function all($offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $lista = array();

        $sql = $this->db->select_limit("SELECT * FROM amortizaciones ORDER BY fechainicio DESC", $limit, $offset);
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id_amor
     * @return amortizacion|bool
     */
    public function get_by_amortizacion($id_amor)
    {
        $sql = $this->db->select("SELECT * FROM amortizaciones WHERE idamortizacion = " . $this->var2str($id_amor) . ";");
        if ($sql) {
            return new \amortizacion($sql[0]);
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    protected function install()
    {
        return '';
    }

}
