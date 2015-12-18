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
require_model('distribucion_conductores');
require_model('distribucion_unidades');
require_model('distribucion_transporte');
/**
 * Description of distribucion_faltantes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_faltantes extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $idtransporte;
    public $idrecibo;
    public $codtrans;
    public $conductor;
    public $nombreconductor;
    public $descripcion;
    public $fecha;
    public $fechav;
    public $fechap;
    public $tipo;
    public $importe;
    public $estado;
    public $coddivisa;
    public $codcuenta;
    public $cuenta;
    public $dc;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public $distribucion_conductores;
    public $distribucion_unidades;
    public $distribucion_transporte;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_transporte','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idtransporte = $t['idtransporte'];
            $this->idrecibo = $t['idrecibo'];
            $this->codtrans = $t['codtrans'];
            $this->conductor = $t['conductor'];
            $this->nombreconductor = $t['nombreconductor'];
            $this->descripcion = $t['descripcion'];
            $this->fecha = $t['fecha'];
            $this->fechav = $t['fechav'];
            $this->fechap = $t['fechap'];
            $this->tipo = $t['tipo'];
            $this->importe = $t['importe'];
            $this->estado = $t['estado'];
            $this->coddivisa = $t['coddivisa'];
            $this->codcuenta = $t['codcuenta'];
            $this->cuenta = $t['cuenta'];
            $this->dc = $t['dc'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->idtransporte = null;
            $this->idrecibo = null;
            $this->codtrans = null;
            $this->conductor = null;
            $this->nombreconductor = null;
            $this->descripcion = null;
            $this->fecha = null;
            $this->fechav = null;
            $this->fechap = null;
            $this->tipo = null;
            $this->importe = null;
            $this->estado = null;
            $this->coddivisa = null;
            $this->codcuenta = null;
            $this->cuenta = null;
            $this->dc = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
        
        $this->distribucion_conductores = new distribucion_conductores();
        $this->distribucion_unidades = new distribucion_unidades();
        $this->distribucion_transporte = new distribucion_transporte();
    }
    
    public function url(){
        if($this->idrecibo){
            return "index.php?page=distrib_creacion&type=faltantes";
        }else{
            return "index.php?page=distrib_creacion&recibo=".$this->idrecibo."-".$this->codalmacen."-".$this->idtransporte;
        }
    }
    
    protected function install() {
        return "";
    }
    
    public function getNextId(){
        $data = $this->db->select("SELECT max(idtransporte) FROM distribucion_faltantes WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen).";");
        $id = $data[0]['max'];
        $id++;
        return $id;
    }
    
    public function exists() {
        if(is_null($this->idtransporte))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_faltantes WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idordencarga).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_faltantes SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "idordencarga = ".$this->intval($this->idordencarga).", ".
                    "codalmacen_dest = ".$this->var2str($this->codalmacen_dest).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "unidad = ".$this->var2str($this->unidad).", ".
                    "tipounidad = ".$this->intval($this->tipounidad).", ".
                    "conductor = ".$this->var2str($this->conductor).", ".
                    "tipolicencia = ".$this->var2str($this->tipolicencia).", ".
                    "fecha = ".$this->var2str($this->fecha).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $this->idtransporte = $this->getNextId();
            $sql = "INSERT INTO distribucion_faltantes ( idempresa, codalmacen, idtransporte, idordencarga, codalmacen_dest, fecha, codtrans, unidad, tipounidad, conductor, tipolicencia, totalcantidad, totalpeso, estado, despachado, liquidado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idtransporte).", ".
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
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->despachado).", ".
                    $this->var2str($this->cargado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return $this->idtransporte;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_faltantes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idtransporte = ".$this->intval($this->idtransporte).";";
        return $this->db->exec($sql);
    }
    
    public function asignar_transporte(){
        $sql = "UPDATE distribucion_faltantes SET ".
                    "idordencarga = ".$this->var2str($this->idordencarga).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";";
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }
    
    public function confirmar_despacho(){
        $sql = "UPDATE distribucion_faltantes SET ".
                    "despachado = ".$this->var2str($this->despachado).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";";
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }
    
    public function confirmar_liquidada(){
        $sql = "UPDATE distribucion_faltantes SET ".
                    "liquidado = ".$this->var2str($this->liquidado).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";";
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." ORDER BY fecha DESC, idtransporte DESC, codalmacen ASC, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_faltantes($d);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$idtransporte,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_faltantes WHERE idempresa = ".$this->intval($idempresa)." AND idtransporte = ".$this->intval($idtransporte)." AND codalmacen = ".$this->var2str($codalmacen).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor_lista = new distribucion_faltantes($d);
                $datos_conductor = $this->distribucion_conductores->get($valor_lista->idempresa, $valor_lista->conductor);
                $valor_lista->conductor_nombre = $datos_conductor[0]->nombre;
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }
}
