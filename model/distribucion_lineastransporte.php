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
/**
 * Description of distribucion_lineastransporte
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_lineastransporte extends fs_model {
    public $idempresa;
    public $idtransporte;
    public $codalmacen;
    public $fecha;
    public $referencia;
    public $cantidad;
    public $devolucion;
    public $importe;
    public $peso;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public $articulo;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_lineastransporte','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idtransporte = $t['idtransporte'];
            $this->fecha = $t['fecha'];
            $this->referencia = $t['referencia'];
            $this->cantidad = $t['cantidad'];
            $this->devolucion = $t['devolucion'];
            $this->importe = $t['importe'];
            $this->peso = $t['peso'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
        }
        else
        {
            $this->idempresa = null;
            $this->idtransporte = null;
            $this->codalmacen = null;
            $this->fecha = null;
            $this->referencia = null;
            $this->cantidad = 0;
            $this->devolucion = 0;
            $this->importe = null;
            $this->peso = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = \Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion  = \Date('d-m-Y H:i');
        }
        
        $this->articulo = new articulo();
    }
    
    public function url(){
        if($this->idtransporte AND $this->codalmacen)
        {
            return "index.php?page=distrib_creacion&type=liquidar-transporte&transporte=".$this->idtransporte."-".$this->codalmacen;
        }
        else
        {
            return "index.php?page=distrib_creacion";
        }
    }
    
    protected function install() {
        return "";
    }
    
    public function info_adicional($valor_linea)
    {
        $descripcion_producto = $this->articulo->get($valor_linea->referencia);
        $valor_linea->descripcion = $descripcion_producto->descripcion;
        $valor_linea->total_final = $valor_linea->cantidad+$valor_linea->devolucion;
        $valor_linea->url_producto = $descripcion_producto->url();
        return $valor_linea;
    }
    
    public function exists() {
    if($this->getOne($this->idempresa, $this->idtransporte, $this->codalmacen, $this->referencia)){
            return true;
        }else{
            return false;
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_lineastransporte SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "cantidad = ".$this->var2str($this->cantidad).", ".
                    "devolucion = ".$this->var2str($this->devolucion).", ".
                    "importe = ".$this->var2str($this->importe).", ".
                    "peso = ".$this->intval($this->peso).", ".
                    "referencia = ".$this->var2str($this->referencia).", ".
                    "fecha = ".$this->var2str($this->fecha).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "referencia = ".$this->var2str($this->referencia)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_lineastransporte ( idempresa, codalmacen, idtransporte, fecha, referencia, cantidad, devolucion, importe, peso, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idtransporte).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->referencia).", ".
                    $this->var2str($this->cantidad).", ".
                    $this->var2str($this->devolucion).", ".
                    $this->var2str($this->importe).", ".
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
        $sql = "DELETE FROM distribucion_lineastransporte WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "referencia = ".$this->var2str($this->referencia)." AND ".
                "idtransporte = ".$this->intval($this->idtransporte).";";
        return $this->db->exec($sql);
    }
    
    public function search($idempresa, $datos, $desde, $hasta, $offset=0)
    {
        $resultados = array();
        $contador = 1;
        $where = (!empty($datos))?" AND ":"";
        foreach($datos as $k=>$v){
            $and = (count($datos) > $contador)?" AND ":"";
            $value = (is_string($v))?$this->var2str($v):$this->intval($v);
            $where.=" $k = ".$value.$and;
            $contador++;
        }

        if($desde){
            $where.=" AND fecha >= ".$this->var2str($desde);
        }
        
        if($hasta){
            $where.=" AND fecha <= ".$this->var2str($hasta);
        }
        
        $sql_count = "SELECT count(*) as total FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." $where;";
        $conteo = $this->db->select($sql_count);
        $resultados['cantidad'] = $conteo[0]['total'];
        $sql = "SELECT * FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." $where ORDER BY fecha DESC, referencia ASC";
        $lista = array();
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        $resultados['resultados'] = $lista;
        return $resultados;
    }
    
    public function lista($idempresa, $datos, $desde, $hasta)
    {
        $resultados = array();
        $contador = 1;
        $where = (!empty($datos))?" AND ":"";
        foreach($datos as $k=>$v){
            $and = (count($datos) > $contador)?" AND ":"";
            $value = (is_string($v))?$this->var2str($v):$this->intval($v);
            $where.=" dl.$k = ".$value.$and;
            $contador++;
        }

        if($desde){
            $where.=" AND dl.fecha >= ".$this->var2str($desde);
        }
        
        if($hasta){
            $where.=" AND dl.fecha <= ".$this->var2str($hasta);
        }
        
        $sql_count = "SELECT count(*) as total FROM ".$this->table_name." as dl WHERE dl.idempresa = ".$this->intval($idempresa)." $where;";
        $conteo = $this->db->select($sql_count);
        $resultados['cantidad'] = $conteo[0]['total'];
        $sql = "SELECT dt.fechal,dt.fechad,dl.* FROM ".$this->table_name." as dl ".
            " left join distribucion_transporte as dt ON (dl.idtransporte = dt.idtransporte AND dt.codalmacen = dl.codalmacen) ".
            " WHERE dl.idempresa = ".$this->intval($idempresa)." $where ORDER BY referencia, dl.fecha, dl.idtransporte";
        $lista = array();
        $data = $this->db->select($sql);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $linea = $this->info_adicional($valor);
                $linea->fechal = $d['fechal'];
                $linea->fechad = $d['fechad'];
                $lista[] = $linea;
            }
        }
        $resultados['resultados'] = $lista;
        return $resultados;
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    public function all_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;

            }
        }
        return $lista;
    }
    
    public function get($idempresa,$idtransporte, $codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND idtransporte = ".$this->intval($idtransporte)." AND codalmacen = ".$this->var2str($codalmacen).";");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_lineastransporte($d);
                $item = $this->info_adicional($valor);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    public function getOne($idempresa,$idtransporte, $codalmacen, $referencia)
    {
        $data = $this->db->select("SELECT * FROM distribucion_lineastransporte WHERE idempresa = ".$this->intval($idempresa)." AND idtransporte = ".$this->intval($idtransporte)." AND codalmacen = ".$this->var2str($codalmacen)." AND referencia = ".$this->var2str($referencia).";");
        $valor_linea = false;
        if($data)
        {
                $valor = new distribucion_lineastransporte($data[0]);
                $valor_linea = $this->info_adicional($valor);
        }
        return $valor_linea;
    }
}
