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
            $this->codigo = $this->getNextId();
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
        $data = $this->db->select("SELECT max(codigo) as max FROM distribucion_segmentos WHERE ".
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
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa).
                " AND tiposegmento = ".$this->var2str($tiposegmento)." ORDER BY codigo_padre, codigo;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_segmentos($d);
                $value->tiene_asignados = $this->tiene_asignados($idempresa, $tiposegmento, $value->codigo);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function all_codigopadre_tiposegmento($idempresa,$codigopadre,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codigo_padre = ".$this->var2str($codigopadre)." AND tiposegmento = ".$this->var2str($tiposegmento)." ORDER BY codigo;");

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
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY tiposegmento, codigo_padre, codigo;");

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

    public function activos_tiposegmento($idempresa,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND tiposegmento = ".$this->var2str($tiposegmento)." AND estado = true ORDER BY codigo_padre, codigo;");

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

    public function activos_codigopadre_tiposegmento($idempresa,$codigopadre,$tiposegmento)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codigo_padre = ".$this->var2str($codigopadre)." AND tiposegmento = ".$this->var2str($tiposegmento)." AND estado = true ORDER BY codigo_padre, codigo;");

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

    public function get($idempresa,$codigo, $tiposegmento)
    {
        $data = $this->db->select("SELECT * FROM distribucion_segmentos WHERE idempresa = ".$this->intval($idempresa)." AND codigo = ".$this->var2str($codigo)." AND tiposegmento = ".$this->var2str($tiposegmento).";");

        if($data)
        {
            $value = new distribucion_segmentos($data[0]);
            return $value;
        }else{
            return false;
        }

    }

    public function get_asignados($idempresa, $tiposegmento, $codigo){
        $lista = array();
        $sql = ($tiposegmento == 'CANAL')?" AND canal = ".$this->var2str($codigo):" AND subcanal = ".$this->var2str($codigo);
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa).$sql.";");
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $data_agente = $this->agente->get($value->codagente);
                $data_organizacion = $this->organizacion->get($value->idempresa, $value->codagente);
                $value->nombre = $data_agente->nombre." ".$data_agente->apellidos;
                $data_supervisor = ($data_organizacion->codsupervisor != null)?$this->agente->get($data_organizacion->codsupervisor):null;
                $value->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function tiene_asignados($idempresa,$tiposegmento, $codigo){
        $sql = ($tiposegmento == 'CANAL')?" AND canal = ".$this->var2str($codigo):" AND subcanal = ".$this->var2str($codigo);
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa).$sql.";");
        if($data)
        {
            return true;
        }else{
            return false;
        }
    }
}
