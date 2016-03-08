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
require_model('factura_cliente');
require_model('cliente');
require_model('ncf_rango.php');
require_model('ncf_ventas.php');
require_model('ncf_rango.php');
/**
 * Description of distribucion_ordenescarga
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_ordenescarga_facturas extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $idordencarga;
    public $idfactura;
    public $idtransporte;
    public $fecha;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $factura_cliente;
    public $cliente;
    public $ncf_ventas;
    public $ncf_rango;

    public function __construct($t = false) {
        parent::__construct('distribucion_ordenescarga_facturas','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idordencarga = $t['idordencarga'];
            $this->idfactura = $t['idfactura'];
            $this->idtransporte = $t['idtransporte'];
            $this->fecha = $t['fecha'];
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
            $this->idfactura = null;
            $this->idtransporte = null;
            $this->fecha = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }

        $this->factura_cliente = new factura_cliente();
        $this->cliente = new cliente();
        if(class_exists('ncf_rango')){
            $this->ncf_ventas = new ncf_ventas();
        }
    }

    public function url(){
        return "index.php?page=distrib_ordencarga";
    }

    protected function install() {
        return "";
    }

    public function exists() {
        $datos = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "idfactura = ".$this->intval($this->idfactura).";");
        if($datos){
            return true;
        }else{
            return false;
        }
    }

    public function save() {
        if ($this->exists())
        {
            return false;
        }
        else
        {
            $sql = "INSERT INTO distribucion_ordenescarga_facturas ( idempresa, codalmacen, idordencarga, idfactura, fecha, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idordencarga).", ".
                    $this->intval($this->idfactura).", ".
                    $this->var2str($this->fecha).", ".
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
        $sql = "DELETE FROM distribucion_ordenescarga_facturas WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga).";";
        return $this->db->exec($sql);
    }

    public function asignar_transporte(){
        $sql = "UPDATE distribucion_ordenescarga_facturas SET ".
                    "idtransporte = ".$this->var2str($this->idtransporte).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";";
        if($this->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }

    public function info_factura($factura){
        $info_adicional = $this->factura_cliente->get($factura->idfactura);
        $facturasrect = $this->db->select("SELECT * FROM facturascli WHERE idfacturarect = ".$this->intval($factura->idfactura)." ORDER BY idfactura ASC;");
        $cliente_factura = $this->cliente->get($info_adicional->codcliente);
        $factura->nombrecliente = $cliente_factura->razonsocial;
        $factura->abono = 0;
        $factura->saldo = $info_adicional->total;
        if($facturasrect){
            foreach($facturasrect as $rectificativa){
                $factura->abono += ($rectificativa['total'] * -1);
                $factura->saldo += ($rectificativa['total'] * -1);
            }
        }

        $lineas_fact = $info_adicional->get_lineas();
        $totalCantidad = 0;
        foreach ($lineas_fact as $linea){
            $totalCantidad += $linea->cantidad;
        }
        $factura->cantidad = $totalCantidad;
        if(class_exists('ncf_rango')){
            $ncf_info = $this->ncf_ventas->get_ncf($factura->idempresa, $factura->idfactura, $info_adicional->codcliente);
            $factura->ncf = $ncf_info->ncf;
        }
        $factura->fecha_factura = $info_adicional->fecha;
        $factura->pagada = $info_adicional->pagada;
        $factura->monto = $info_adicional->total;
        $factura->enlace = $info_adicional->url();
        return $factura;
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE idempresa = ".$this->intval($idempresa)." ORDER BY fecha DESC, idordencarga DESC, codalmacen ASC;");

        if($data)
        {
            foreach($data as $d)
            {
                $info_factura = new distribucion_ordenescarga_facturas($d);
                $valor_lista = $this->info_factura($info_factura);
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }

    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, idordencarga, idfactura;");

        if($data)
        {
            foreach($data as $d)
            {
                $info_factura = new distribucion_ordenescarga_facturas($d);
                $valor_lista = $this->info_factura($info_factura);
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }

    public function all_almacen_ordencarga($idempresa,$codalmacen,$idordencarga)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, idordencarga, idfactura;");

        if($data)
        {
            foreach($data as $d)
            {
                $info_factura = new distribucion_ordenescarga_facturas($d);
                $valor_lista = $this->info_factura($info_factura);
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }

    public function all_almacen_idtransporte($idempresa,$codalmacen,$idtransporte)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE idempresa = ".$this->intval($idempresa)." AND idtransporte = ".$this->intval($idtransporte)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, idordencarga, idfactura;");

        if($data)
        {
            foreach($data as $d)
            {
                $info_factura = new distribucion_ordenescarga_facturas($d);
                $valor_lista = $this->info_factura($info_factura);
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }

    public function get($idempresa,$idfactura,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga_facturas WHERE idempresa = ".$this->intval($idempresa)." AND idfactura = ".$this->intval($idfactura)." AND codalmacen = ".$this->var2str($codalmacen).";");
        if($data)
        {
            foreach($data as $d)
            {
                $info_factura = new distribucion_ordenescarga_facturas($d);
                $valor_lista = $this->info_factura($info_factura);
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }
}
