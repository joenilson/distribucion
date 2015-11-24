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
 * Description of distribucion_clientes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_clientes extends fs_model {
    public $idempresa;
    public $codcliente;
    public $ruta;
    public $canal;
    public $subcanal;
    public $coordenadas;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_clientes','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codcliente = $t['codcliente'];
            $this->ruta = $t['ruta'];
            $this->canal = $t['canal'];
            $this->subcanal = $t['subcanal'];
            $this->coordenadas = $t['coordenadas'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codcliente = null;
            $this->ruta = null;
            $this->canal = null;
            $this->subcanal = null;
            $this->coordenadas = null;
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
            return $this->db->select("SELECT * FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_clientes SET ".
                    "canal = ".$this->var2str($this->canal).", ".
                    "subcanal = ".$this->var2str($this->subcanal).", ".
                    "ruta = ".$this->var2str($this->ruta).", ".
                    "coordenadas = ".$this->var2str($this->coordenadas).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_clientes ( idempresa, codcliente, ruta, canal, subcanal, coordenadas, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codcliente).", ".
                    $this->var2str($this->ruta).", ".
                    $this->var2str($this->canal).", ".
                    $this->var2str($this->subcanal).", ".
                    $this->var2str($this->coordenadas).", ".
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
        $sql = "DELETE FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." ORDER BY tiposegmento, codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function all_tiposegmento($idempresa,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND tiposegmento = ".$this->var2str($tiposegmento)." ORDER BY codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function all_codigopadre_tipoagente($idempresa,$codigopadre,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codigo_padre = ".$this->var2str($codigopadre)." AND tiposegmento = ".$this->var2str($tiposegmento)." ORDER BY codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
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
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY tiposegmento, codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function activos_tiposegmento($idempresa,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND tiposegmento = ".$this->var2str($tiposegmento)." AND estado = true ORDER BY codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function activos_codigopadre_tiposegmento($idempresa,$codigopadre,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codigo_padre = ".$this->var2str($codigopadre)." AND tiposegmento = ".$this->var2str($tiposegmento)." AND estado = true ORDER BY codigo_padre, codigo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$codigo, $tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codigo = ".$this->var2str($codigo)." AND tiposegmento = ".$this->var2str($tiposegmento).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
}
