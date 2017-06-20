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
require_model('articulo.php');
require_model('articulo_unidadesmedida.php');
require_model('unidadesmedida.php');
/**
 * Description of distribucion_lineasordenescarga
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_lineasordenescarga extends fs_model {
    public $idempresa;
    public $idordencarga;
    public $codalmacen;
    public $fecha;
    public $referencia;
    public $cantidad;
    public $peso;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $articulo;
    public $articulo_unidadmedida;
    public $unidad_medida;
    public function __construct($t = false) {
        parent::__construct('distribucion_lineasordenescarga','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idordencarga = $t['idordencarga'];
            $this->fecha = $t['fecha'];
            $this->referencia = $t['referencia'];
            $this->cantidad = $t['cantidad'];
            $this->peso = $t['peso'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->idordencarga = null;
            $this->codalmacen = null;
            $this->fecha = null;
            $this->referencia = null;
            $this->cantidad = null;
            $this->peso = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = \Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion  = \Date('d-m-Y H:i');
        }

        $this->articulo = new articulo();
        $this->articulo_unidadmedida = new articulo_unidadmedida();
        $this->unidad_medida = new unidadmedida();
    }

    public function url(){
        return "index.php?page=distrib_ordencarga";
    }

    protected function install() {
        return "";
    }

    public function info_adicional($res){
        $aum = $this->articulo_unidadmedida->getBase($res->referencia);
        $res->codum = (isset($aum->codum))?$aum->codum:'UNIDAD';
        $um = $this->unidad_medida->get($res->codum);
        $descripcion_producto = $this->articulo->get($res->referencia);
        $res->descripcion = $descripcion_producto->descripcion;
        $res->descripcion_um = (isset($um->nombre))?$um->nombre:'UNIDAD';
        return $res;
    }

    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "referencia = ".$this->var2str($this->referencia)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";");
        if(count($data[0]) != 0){
            return true;
        }else{
            return false;
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_lineasordenescarga SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "cantidad = ".$this->var2str($this->cantidad).", ".
                    "peso = ".$this->intval($this->peso).", ".
                    "referencia = ".$this->var2str($this->referencia).", ".
                    "fecha = ".$this->var2str($this->fecha).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_lineasordenescarga ( idempresa, codalmacen, idordencarga, fecha, referencia, cantidad, peso, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idordencarga).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->referencia).", ".
                    $this->var2str($this->cantidad).", ".
                    $this->var2str($this->peso).", ".
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
        $sql = "DELETE FROM distribucion_lineasordenescarga WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga).";";
        return $this->db->exec($sql);
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_lineasordenescarga($d);
            }
        }
        return $lista;
    }

    public function get($idempresa,$idordencarga, $codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen).";");

        if($data)
        {
            foreach($data as $d)
            {
                $valor_linea = new distribucion_lineasordenescarga($d);
                $item = $this->info_adicional($valor_linea);
                //$descripcion_producto = $this->articulo->get($valor_linea->referencia);
                //$valor_linea->descripcion = $descripcion_producto->descripcion;
                $lista[] = $item;
            }
        }
        return $lista;
    }

    public function get_lineas_imprimir($idempresa,$idordencarga, $codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT referencia,cantidad FROM distribucion_lineasordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen).";");

        if($data)
        {
            foreach($data as $d)
            {
                $item = array();
                $descripcion_producto = $this->articulo->get($d['referencia']);
                $um = $this->articulo_unidadmedida->getBase($d['referencia']);
                $item[] = $d['referencia'].' '.$descripcion_producto->descripcion;
                $item[] = (isset($um->codum))?$um->codum:'UNIDAD';
                $item[] = $d['cantidad'];
                $lista[] = $item;
            }
        }
        return $lista;
    }
}
