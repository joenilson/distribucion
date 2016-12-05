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
require_model('agente.php');
require_model('cliente.php');
require_model('direccion_cliente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_segmentos.php');
require_model('distribucion_rutas.php');
/**
 * Description of distribucion_clientes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_clientes extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codcliente;
    public $iddireccion;
    public $ruta;
    public $canal;
    public $subcanal;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $distrib_organizacion;
    public $distrib_rutas;
    public $distrib_segmentos;
    public $direccion_cliente;
    public $agente;
    public $nombre_cliente;
    public $cliente;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_clientes','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codcliente = $t['codcliente'];
            $this->iddireccion = $t['iddireccion'];
            $this->ruta = $t['ruta'];
            $this->canal = $t['canal'];
            $this->subcanal = $t['subcanal'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->codcliente = null;
            $this->iddireccion = null;
            $this->ruta = null;
            $this->canal = null;
            $this->subcanal = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
        $this->distrib_rutas = new distribucion_rutas();
        $this->distrib_segmentos = new distribucion_segmentos();
        $this->direccion_cliente = new direccion_cliente();
        $this->cliente = new cliente();
    }
    
    public function url(){
        return "index.php?page=distrib_clientes";
    }
    
    protected function install() {
        return "";
    }
    
    public function info_adicional($informacion){
        $datos_ruta = $this->distrib_rutas->get($informacion->idempresa,$informacion->codalmacen, $informacion->ruta);
        $datos_canal = $this->distrib_segmentos->get($informacion->idempresa, $informacion->canal, 'CANAL');
        $datos_subcanal = $this->distrib_segmentos->get($informacion->idempresa, $informacion->subcanal, 'SUBCANAL');
        $datos_direccion = $this->direccion_cliente->get($informacion->iddireccion);
        $datos_cliente = $this->cliente->get($informacion->codcliente);
        $informacion->direccion = ($datos_direccion)?$datos_direccion->direccion:'Completar Información';
        $informacion->ruta_descripcion = $datos_ruta->descripcion;
        $informacion->codagente = $datos_ruta->codagente;
        $informacion->nombre = $datos_ruta->nombre;
        $informacion->canal_descripcion = $datos_canal->descripcion;
        $informacion->subcanal_descripcion = $datos_subcanal->descripcion;
        $informacion->nombre_cliente = ($datos_direccion)?$datos_cliente->nombre:'Error de nombre del cliente';
        return $informacion;
    }
    
    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ruta = ".$this->var2str($this->ruta)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";");
        if($data){
            return true;
        }else{
            return false;
        }        
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_clientes SET ".
                "iddireccion = ".$this->intval($this->iddireccion).", ".
                "canal = ".$this->var2str($this->canal).", ".
                "subcanal = ".$this->var2str($this->subcanal).", ".
                "ruta = ".$this->var2str($this->ruta).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                " WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ruta = ".$this->var2str($this->ruta)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_clientes ( idempresa, codalmacen, codcliente, iddireccion, ruta, canal, subcanal, usuario_creacion, fecha_creacion ) VALUES (".
                $this->intval($this->idempresa).", ".
                $this->var2str($this->codalmacen).", ".
                $this->var2str($this->codcliente).", ".
                $this->intval($this->iddireccion).", ".
                $this->var2str($this->ruta).", ".
                $this->var2str($this->canal).", ".
                $this->var2str($this->subcanal).", ".
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
    
    public function transferir($ruta_destino){
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_clientes SET ".
                "ruta = ".$this->var2str($ruta_destino).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                " WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ruta = ".$this->var2str($this->ruta)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }else{
            return false;
        }
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ruta = ".$this->var2str($this->ruta)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." ORDER BY ruta, canal, subcanal, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value);
                $lista[] = $info;
            }
        }
        return $lista;
    }
    
    public function clientes_sinruta($idempresa, $almacen){
        $sql = "SELECT c.codcliente as codcliente, c.nombre as nombre, d.id as iddireccion, d.direccion as direccion FROM  clientes as c, dirclientes as d ".
                   "WHERE c.codcliente NOT IN (select distinct codcliente from ".$this->table_name.") ".
                   "AND lower(ciudad) like '%".strtolower($almacen->poblacion)."%' ".
                   "AND d.id NOT IN (select iddireccion from ".$this->table_name.") and c.codcliente = d.codcliente ORDER BY nombre";
        $lista = array();
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new stdClass();
                $value->idempresa = (int)$idempresa;
                $value->codalmacen = $almacen->codalmacen;
                $value->codcliente = $d['codcliente'];
                $value->nombre_cliente = $d['nombre'];
                $value->iddireccion = $d['iddireccion'];
                $value->direccion = $d['direccion'];
                $value->ruta = 'noruta';
                $lista[] = $value;
            }
        }
        return $lista;
    }
       
    public function clientes_ruta($idempresa,$codalmacen, $ruta)
    {
        $sql = "SELECT * FROM distribucion_clientes ".
                "WHERE idempresa = ".$this->intval($idempresa).
                " AND ruta = ".$this->var2str($ruta).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY ruta, codcliente;";
        $lista = array();
        $data = $this->db->select($sql);
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_canal($idempresa,$codalmacen,$canal)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa).
                " AND canal = ".$this->var2str($canal).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY canal, ruta, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_subcanal($idempresa,$codalmacen,$subcanal)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa).
                " AND subcanal = ".$this->var2str($subcanal).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY subcanal, ruta, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value);
                $lista[] = $info;
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$codcliente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa).
                " AND codcliente = ".$this->var2str($codcliente).";");
        if($data)
        {
            foreach ($data as $d){
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value);
                $lista[] = $info;
            }
            return $lista;
        }else{
            return false;
        }
    }
    
    public function ruta_cliente($idempresa,$codalmacen,$codcliente,$iddireccion,$ruta)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idempresa = ".$idempresa.
                " AND codcliente = ".$this->var2str($codcliente).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " AND iddireccion = ".$this->var2str($iddireccion).
                " AND ruta = ".$this->var2str($ruta).";");
        if($data)
        {
            $value = new distribucion_clientes($data[0]);
            $info = $this->info_adicional($value);
            return $info;
        }else{
            return false;
        }
    }
}
