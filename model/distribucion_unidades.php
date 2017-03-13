<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Lesser General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * Description of distribucion_unidades
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_unidades extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codtrans;
    public $placa;
    public $capacidad;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_unidades','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codtrans = $t['codtrans'];
            $this->placa = $t['placa'];
            $this->tipounidad = $t['tipounidad'];
            $this->capacidad = $t['capacidad'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->codtrans = null;
            $this->placa = null;
            $this->tipounidad = null;
            $this->capacidad = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=distrib_unidades";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->placa))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_unidades WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "placa = ".$this->var2str($this->placa).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_unidades SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "capacidad = ".$this->intval($this->capacidad).", ".
                    "tipounidad = ".$this->intval($this->tipounidad).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "placa = ".$this->var2str($this->placa).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_unidades ( idempresa, codalmacen, codtrans, placa, capacidad, tipounidad, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->codtrans).", ".
                    $this->var2str($this->placa).", ".
                    $this->intval($this->capacidad).", ".
                    $this->intval($this->tipounidad).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_unidades WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "placa = ".$this->var2str($this->placa).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, codtrans, placa;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$placa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_unidades WHERE idempresa = ".$this->intval($idempresa)." AND placa = ".$this->var2str($placa).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista = new distribucion_unidades($d);
            }
        }
        return $lista;
    }
}
