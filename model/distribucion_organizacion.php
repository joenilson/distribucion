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
require_model('model/agente.php');
require_model('distribucion_rutas.php');
/**
 * Description of distribucion_organizacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_organizacion extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codagente;
    public $codsupervisor;
    public $tipoagente;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $agente;
    public $rutas;
    public function __construct($t = false) {
        parent::__construct('distribucion_organizacion','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codagente = $t['codagente'];
            $this->codsupervisor = $t['codsupervisor'];
            $this->tipoagente = $t['tipoagente'];
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
            $this->codagente = null;
            $this->codsupervisor = null;
            $this->tipoagente = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
        $this->agente = new agente();
        //$this->rutas = new distribucion_rutas();
    }

    public function url(){
        return "index.php?page=distrib_clientes";
    }

    protected function install() {
        return "";
    }

    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codagente = ".$this->var2str($this->codagente)." AND ".
                "tipoagente = ".$this->var2str($this->tipoagente).";");
        if(empty($data)){
            return false;
        }else{
            return true;
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_organizacion SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "codsupervisor = ".$this->var2str($this->codsupervisor).", ".
                    "tipoagente = ".$this->var2str($this->tipoagente).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codagente = ".$this->var2str($this->codagente)." AND ".
                    "tipoagente = ".$this->var2str($this->tipoagente).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_organizacion ( idempresa, codalmacen, codagente, codsupervisor, tipoagente, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->codagente).", ".
                    $this->var2str($this->codsupervisor).", ".
                    $this->var2str($this->tipoagente).", ".
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
        $sql = "DELETE FROM distribucion_organizacion WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codagente = ".$this->var2str($this->codagente)." AND ".
                "tipoagente = ".$this->var2str($this->tipoagente).";";
        return $this->db->exec($sql);
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, tipoagente, codagente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function all_tipoagente($idempresa,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND tipoagente = ".$this->var2str($tipoagente)." ORDER BY codalmacen, codagente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $value->tiene_asignados = $this->tiene_asignados($value->idempresa, $value->codagente);
                $value->tiene_rutas_asignadas = $this->tiene_rutas_asignadas($value->idempresa, $value->codagente);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function all_almacen_tipoagente($idempresa,$codalmacen,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND tipoagente = ".$this->var2str($tipoagente)." ORDER BY codagente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
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
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, codtrans, nombre;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $value->tiene_asignados = $this->tiene_asignados($value->idempresa, $value->codagente);
                $value->tiene_rutas_asignadas = $this->tiene_rutas_asignadas($value->idempresa, $value->codagente);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function activos_tipoagente($idempresa,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND tipoagente = ".$this->var2str($tipoagente)." AND estado = true ORDER BY codalmacen, codagente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $value->tiene_asignados = $this->tiene_asignados($value->idempresa, $value->codagente);
                $value->tiene_rutas_asignadas = $this->tiene_rutas_asignadas($value->idempresa, $value->codagente);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function activos_almacen_tipoagente($idempresa,$codalmacen,$tipoagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND tipoagente = ".$this->var2str($tipoagente)." AND estado = true ORDER BY codalmacen, codagente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
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
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND codagente = ".$this->var2str($codagente).";");

        if($data)
        {
            $value = new distribucion_organizacion($data[0]);
            $data_agente = $this->agente->get($value->codagente);
            $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
            $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
            $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
            return $value;
        }else{
            return false;
        }

    }

    public function get_asignados($idempresa,$codagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND codsupervisor = ".$this->var2str($codagente).";");

        if($data)
        {
            foreach($data as $d){
                $value = new distribucion_organizacion($data[0]);
                $data_agente = $this->agente->get($value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($value->codsupervisor != null)?$this->agente->get($value->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
                
            }
            return $lista;
        }else{
            return false;
        }

    }

    public function tiene_asignados($idempresa,$codagente)
    {
        $data = $this->db->select("SELECT count(*) as cantidad FROM distribucion_organizacion WHERE idempresa = ".$this->intval($idempresa)." AND codsupervisor = ".$this->var2str($codagente).";");

        if($data)
        {
            return $data[0]['cantidad'];
        }else{
            return false;
        }

    }

    public function tiene_rutas_asignadas($idempresa,$codagente)
    {
        $data = $this->db->select("SELECT count(*) as cantidad FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codagente = ".$this->var2str($codagente).";");

        if($data)
        {
            return $data[0]['cantidad'];
        }else{
            return false;
        }
    }
    
    public function tiene_clientes_asignados($idempresa,$codalmacen,$codagente)
    {
        //Buscamos las rutas de este agente
        $sql = "SELECT ruta FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND codagente = ".$this->var2str($codagente).";";
        $data = $this->db->select($sql);
        $cantidad = 0;
        if($data)
        {
            foreach($data as $d){
                $rutas = new distribucion_rutas();
                $cantidad += $rutas->cantidad_asignados($idempresa,$codalmacen,$d['ruta']);
            }
            return $cantidad;
        }else{
            return false;
        }
    }
}
