<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * Description of distribucion_unidades
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_conductores extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codtrans;
    public $nombre;
    public $licencia;
    public $tipolicencia;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_conductores','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codtrans = $t['codtrans'];
            $this->nombre = $t['nombre'];
            $this->licencia = $t['licencia'];
            $this->tipolicencia = $t['tipolicencia'];
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
            $this->nombre = null;
            $this->licencia = null;
            $this->tipolicencia = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=distrib_conductores";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->licencia))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_conductores WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "licencia = ".$this->var2str($this->licencia).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_conductores SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "nombre = ".$this->var2str($this->nombre).", ".
                    "tipolicencia = ".$this->var2str($this->tipolicencia).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "licencia = ".$this->var2str($this->licencia).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_conductores ( idempresa, codalmacen, codtrans, nombre, licencia, tipolicencia, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->codtrans).", ".
                    $this->var2str($this->nombre).", ".
                    $this->var2str($this->licencia).", ".
                    $this->var2str($this->tipolicencia).", ".
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
        $sql = "DELETE FROM distribucion_conductores WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "licencia = ".$this->var2str($this->licencia).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$licencia)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_conductores WHERE idempresa = ".$this->intval($idempresa)." AND licencia = ".$this->var2str($licencia).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_conductores($d);
            }
        }
        return $lista;
    }
}
