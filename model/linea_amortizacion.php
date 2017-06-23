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
class linea_amortizacion extends fs_model
{
    /**
     * @var int
     */
    public $ano;
    /**
     * @var int
     */
    public $contabilizada;
    /**
     * @var int
     */
    public $cantidad;
    /**
     * @var null
     */
    public $id_amortizacion;
    /**
     * @var null
     */
    public $id_linea;

    /**
     * linea_amortizacion constructor.
     * @param bool $t
     */
    public function __construct($t = false)
    {
        parent::__construct('lineasamortizaciones');
        if ($t) {
            $this->ano = $t['ano'];
            $this->contabilizada = $this->str2bool($t['contabilizada']);
            $this->cantidad = $t['cantidad'];
            $this->id_amortizacion = $t['idamortizacion'];
            $this->id_linea = $t['idlinea'];
        } else {
            $this->ano = 0;
            $this->contabilizada = 0;
            $this->cantidad = 0;
            $this->id_amortizacion = null;
            $this->id_linea = null;
        }
    }

    /**
     * @return bool
     */
    public function exists()
    {
        if (is_null($this->id_linea)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM lineasamortizaciones WHERE idlinea = " . $this->var2str($this->id_linea) . ";");
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE lineasamortizaciones SET 
                 ano = " . $this->var2str($this->ano) . ", 
                 cantidad = " . $this->var2str($this->cantidad) . "
                 WHERE idlinea = " . $this->var2str($this->id_linea) . ";";
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO lineasamortizaciones (ano,cantidad,idamortizacion) VALUES ("
                . $this->var2str($this->ano) . ","
                . $this->var2str($this->cantidad) . ","
                . $this->var2str($this->id_amortizacion) . ");";

            if ($this->db->exec($sql)) {
                //$this->id_linea = $this->db->lastval();
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
        return $this->db->exec("DELETE FROM lineasamortizaciones WHERE idamortizacion = " . $this->var2str($this->id_amortizacion) . ";");
    }

    /**
     * @return array
     */
    public function all()
    {
        $lista = array();
        $ano_fiscal = date('Y');

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones ORDER BY ano;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @return array
     */
    public function this_year()
    {
        $lista = array();
        $ano_fiscal = date('Y');

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE ano<=" . $this->var2str($ano_fiscal) . " AND contabilizada = '0' ORDER BY ano;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id_amor
     * @return array
     */
    public function get_by_amortizacion($id_amor)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE idamortizacion=" . $this->var2str($id_amor) . " ORDER BY ano;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function count($id)
    {
        return $this->db->exec("UPDATE lineasamortizaciones SET contabilizada = TRUE WHERE idlinea = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function sale($id)
    {
        return $this->db->exec("UPDATE lineasamortizaciones SET contabilizada = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id_amor
     * @param $ano
     * @return bool|linea_amortizacion
     */
    public function get_to_count($id_amor, $ano)
    {
        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE idamortizacion = " . $this->var2str($id_amor) . " AND ano = " . $this->var2str($ano) . ";");
        if ($sql) {
            return new \linea_amortizacion($sql[0]);
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