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
 * Description of distribucion_ordenescarga
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_ordenescarga extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $idordencarga;
    public $idtransporte;
    public $codalmacen_dest;
    public $fecha;
    public $codtrans;
    public $unidad;
    public $tipounidad;
    public $conductor;
    public $tipolicencia;
    public $totalcantidad;
    public $totalpeso;
    public $observaciones;
    public $estado;
    public $despachado;
    public $cargado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_ordenescarga','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idordencarga = $t['idordencarga'];
            $this->idtransporte = $t['idtransporte'];
            $this->codalmacen_dest = $t['codalmacen_dest'];
            $this->fecha = $t['fecha'];
            $this->codtrans = $t['codtrans'];
            $this->unidad = $t['unidad'];
            $this->tipounidad = $t['tipounidad'];
            $this->conductor = $t['conductor'];
            $this->tipolicencia = $t['tipolicencia'];
            $this->totalcantidad = $t['totalcantidad'];
            $this->totalpeso = $t['totalpeso'];
            $this->observaciones = $t['observaciones'];
            $this->estado = $this->str2bool($t['estado']);
            $this->despachado = $this->str2bool($t['despachado']);
            $this->cargado = $this->str2bool($t['cargado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->idordencarga = null;
            $this->idtransporte = null;
            $this->codalmacen_dest = null;
            $this->fecha = null;
            $this->codtrans = null;
            $this->unidad = null;
            $this->tipounidad = null;
            $this->conductor = null;
            $this->tipolicencia = null;
            $this->totalcantidad = null;
            $this->totalpeso = null;
            $this->observaciones = null;
            $this->estado = false;
            $this->despachado = false;
            $this->cargado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=distrib_ordencarga";
    }
    
    protected function install() {
        return "";
    }
    
    public function getNextId(){
        $data = $this->db->select("SELECT max(idordencarga) FROM distribucion_ordenescarga WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen).";");
        $id = $data[0]['max'];
        $id++;
        return $id;
    }
    
    public function exists() {
        if(is_null($this->idordencarga))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_ordenescarga SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "idtransporte = ".$this->var2str($this->idtransporte).", ".
                    "codalmacen_dest = ".$this->var2str($this->codalmacen_dest).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "unidad = ".$this->var2str($this->unidad).", ".
                    "tipounidad = ".$this->intval($this->tipounidad).", ".
                    "conductor = ".$this->var2str($this->conductor).", ".
                    "tipolicencia = ".$this->var2str($this->tipolicencia).", ".
                    "fecha = ".$this->var2str($this->fecha).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $this->idordencarga = $this->getNextId();
            $sql = "INSERT INTO distribucion_ordenescarga ( idempresa, codalmacen, idordencarga, codalmacen_dest, fecha, codtrans, unidad, tipounidad, conductor, tipolicencia, totalcantidad, totalpeso, observaciones, estado, despachado, cargado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idordencarga).", ".
                    $this->var2str($this->codalmacen_dest).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->codtrans).", ".
                    $this->var2str($this->unidad).", ".
                    $this->intval($this->tipounidad).", ".
                    $this->var2str($this->conductor).", ".
                    $this->var2str($this->tipolicencia).", ".
                    $this->intval($this->totalcantidad).", ".
                    $this->intval($this->totalpeso).", ".
                    $this->var2str($this->observaciones).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->despachado).", ".
                    $this->var2str($this->cargado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return $this->idordencarga;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_ordenescarga WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga).";";
        return $this->db->exec($sql);
    }
    
    public function asignar_transporte(){
        $sql = "UPDATE distribucion_ordenescarga SET ".
                    "idtransporte = ".$this->var2str($this->idtransporte).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";";
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$idordencarga,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga($d);
            }
        }
        return $lista;
    }
}