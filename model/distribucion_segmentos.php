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
 * Description of distribucion_segmentos
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_segmentos extends fs_model {
    public $idempresa;
    public $codigo;
    public $codigo_padre;
    public $descripcion;
    public $tiposegmento;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_segmentos','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codigo = $t['codigo'];
            $this->descripcion = $t['descripcion'];
            $this->codigo_padre = $t['codigo_padre'];
            $this->tiposegmento = $t['tiposegmento'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codigo = null;
            $this->descripcion = null;
            $this->codigo_padre = null;
            $this->tiposegmento = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=distrib_clientes";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
         if(is_null($this->codigo)){
            return false;
        }else{
            return $this->db->select("SELECT * FROM distribucion_segmentos WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codigo = ".$this->var2str($this->codigo)." AND ".
                "tiposegmento = ".$this->var2str($this->tiposegmento).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_segmentos SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "codigo_padre = ".$this->var2str($this->codigo_padre).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codigo = ".$this->var2str($this->codigo)." AND ".
                    "tiposegmento = ".$this->var2str($this->tiposegmento).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_segmentos ( idempresa, codigo, descripcion, codigo_padre, tiposegmento, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codigo).", ".
                    $this->var2str($this->descripcion).", ".
                    $this->var2str($this->codigo_padre).", ".
                    $this->var2str($this->tiposegmento).", ".
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
    
    
    public function getNextId(){
        $data = $this->db->select("SELECT max(codigo) FROM distribucion_segmentos WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "tiposegmento = ".$this->var2str($this->tiposegmento).";");
        $id = $data[0]['max'];
        $id++;
        return str_pad($id,3,'0',STR_PAD_LEFT);
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_segmentos WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codigo = ".$this->var2str($this->codigo)." AND ".
                "tiposegmento = ".$this->var2str($this->tiposegmento).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." ORDER BY tiposegmento, codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function all_tiposegmento($idempresa,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND tipoagente = ".$this->var2str($tipoagente)." ORDER BY codalmacen, codagente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function all_almacen_tipoagente($idempresa,$codalmacen,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND tipoagente = ".$this->var2str($tipoagente)." ORDER BY codagente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function activos_tipoagente($idempresa,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND tipoagente = ".$this->var2str($tipoagente)." AND estado = true ORDER BY codalmacen, codagente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function activos_almacen_tipoagente($idempresa,$codalmacen,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND tipoagente = ".$this->var2str($tipoagente)." AND estado = true ORDER BY codalmacen, codagente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$codagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codagente = ".$this->var2str($codagente).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }
}