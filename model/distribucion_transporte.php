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
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('articulo.php');
/**
 * Description of distribucion_transporte
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_transporte extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $idordencarga;
    public $idtransporte;
    public $codalmacen_dest;
    public $fecha;
    public $fechad;
    public $fechal;
    public $codtrans;
    public $unidad;
    public $tipounidad;
    public $conductor;
    public $tipolicencia;
    public $totalcantidad;
    public $totalimporte;
    public $totalpeso;
    public $estado;
    public $despachado;
    public $devolucionado;
    public $liquidado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $liquidacion_importe;
    public $liquidacion_faltante;

    public $distribucion_conductores;
    public $distribucion_unidades;
    public $articulo;
    public function __construct($t = false) {
        parent::__construct('distribucion_transporte','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idordencarga = $t['idordencarga'];
            $this->idtransporte = $t['idtransporte'];
            $this->codalmacen_dest = $t['codalmacen_dest'];
            $this->fecha = $t['fecha'];
            $this->fechad = $t['fechad'];
            $this->fechal = $t['fechal'];
            $this->codtrans = $t['codtrans'];
            $this->unidad = $t['unidad'];
            $this->tipounidad = $t['tipounidad'];
            $this->conductor = $t['conductor'];
            $this->tipolicencia = $t['tipolicencia'];
            $this->totalcantidad = $t['totalcantidad'];
            $this->totalimporte = floatval($t['totalimporte']);
            $this->liquidacion_importe = floatval($t['liquidacion_importe']);
            $this->liquidacion_faltante = floatval($t['liquidacion_faltante']);
            $this->totalpeso = $t['totalpeso'];
            $this->estado = $this->str2bool($t['estado']);
            $this->despachado = $this->str2bool($t['despachado']);
            $this->devolucionado = $this->str2bool($t['devolucionado']);
            $this->liquidado = $this->str2bool($t['liquidado']);
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
            $this->fechad = null;
            $this->fechal = null;
            $this->codtrans = null;
            $this->unidad = null;
            $this->tipounidad = null;
            $this->conductor = null;
            $this->tipolicencia = null;
            $this->totalcantidad = null;
            $this->totalimporte = null;
            $this->liquidacion_importe = null;
            $this->liquidacion_faltante = null;
            $this->totalpeso = null;
            $this->estado = false;
            $this->despachado = false;
            $this->devolucionado = false;
            $this->liquidado = false;
            $this->cargado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }

        $this->distribucion_conductores = new distribucion_conductores();
        $this->distribucion_unidades = new distribucion_unidades();
        $this->articulo = new articulo();
    }

    public function url(){
        if($this->idtransporte){
            if($this->liquidado){
                return "index.php?page=distrib_creacion&type=liquidar-transporte&transporte=".$this->idtransporte."-".$this->codalmacen;
            }else{
                return "index.php?page=distrib_creacion";
            }
        }else{
            return "index.php?page=distrib_creacion";
        }
    }

    protected function install() {
        return "";
    }

    public function getNextId(){
        $data = $this->db->select("SELECT max(idtransporte) as max FROM distribucion_transporte WHERE ".
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
            return $this->db->select("SELECT * FROM distribucion_transporte WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idtransporte = ".$this->intval($this->idtransporte).";");
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_transporte SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "idordencarga = ".$this->intval($this->idordencarga).", ".
                    "codalmacen_dest = ".$this->var2str($this->codalmacen_dest).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "unidad = ".$this->var2str($this->unidad).", ".
                    "tipounidad = ".$this->intval($this->tipounidad).", ".
                    "conductor = ".$this->var2str($this->conductor).", ".
                    "tipolicencia = ".$this->var2str($this->tipolicencia).", ".
                    "totalimporte = ".$this->var2str($this->totalimporte).", ".
                    "liquidacion_importe = ".$this->var2str($this->liquidacion_importe).", ".
                    "liquidacion_faltante = ".$this->var2str($this->liquidacion_faltante).", ".
                    "totalpeso = ".$this->var2str($this->totalpeso).", ".
                    "fecha = ".$this->var2str($this->fecha).", ".
                    "fechad = ".$this->var2str($this->fechad).", ".
                    "fechal = ".$this->var2str($this->fechal).", ".
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
            $sql = "INSERT INTO distribucion_transporte ( idempresa, codalmacen, idtransporte, idordencarga, codalmacen_dest, fecha, codtrans, unidad, tipounidad, conductor, tipolicencia, totalcantidad, totalimporte, totalpeso, estado, despachado, devolucionado, liquidado, usuario_creacion, fecha_creacion ) VALUES (".
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
                    $this->var2str($this->totalcantidad).", ".
                    $this->var2str($this->totalimporte).", ".
                    $this->var2str($this->totalpeso).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->despachado).", ".
                    $this->var2str($this->devolucionado).", ".
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

    public function info_adicional($t){
        $con0 = $this->distribucion_conductores->get($t->idempresa, $t->conductor);
        $t->conductor_nombre = (!empty($con0->nombre))?$con0->nombre:'';
        $t->liquidado_desc = ($t->liquidado)?"SI":"NO";
        $t->devolucionado_desc = ($t->devolucionado)?"SI":"NO";
        $t->despachado_desc = ($t->despachado)?"SI":"NO";
        return $t;
    }

    public function delete() {
        //Primero eliminamos la asignación del id de transporte a la orden de carga
        $sql1 = "UPDATE distribucion_ordenescarga_facturas SET idtransporte = NULL WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga)." AND ".
                "idtransporte = ".$this->intval($this->idtransporte).";";
        $this->db->exec($sql1);
        //Quitamos el estado de despachado y el idtransporte a distrib_ordenescarga
        $sql2 = "UPDATE distribucion_ordenescarga SET idtransporte = NULL, despachado = FALSE  WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga)." AND ".
                "idtransporte = ".$this->intval($this->idtransporte).";";
        $this->db->exec($sql2);
        //Luego de esto procedemos a borrar el transporte
        $sql3 = "DELETE FROM distribucion_transporte WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idtransporte = ".$this->intval($this->idtransporte).";";
        return $this->db->exec($sql3);
    }

    public function asignar_transporte(){
        $sql = "UPDATE distribucion_transporte SET ".
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
        $sql = "UPDATE distribucion_transporte SET ".
                    "despachado = ".$this->var2str($this->despachado).", ".
                    "fechad = ".$this->var2str($this->fechad).", ".
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

    public function confirmar_devolucion(){
        $sql = "UPDATE distribucion_transporte SET ".
                    "devolucionado = ".$this->var2str($this->devolucionado).", ".
                    "fechad = ".$this->var2str($this->fechad).", ".
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
        $sql = "UPDATE distribucion_transporte SET ".
                    "liquidacion_importe = ".$this->var2str($this->liquidacion_importe).", ".
                    "liquidacion_faltante = ".$this->var2str($this->liquidacion_faltante).", ".
                    "liquidado = ".$this->var2str($this->liquidado).", ".
                    "fechal = ".$this->var2str($this->fechal).", ".
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

    public function search($idempresa, $datos, $desde, $hasta, $offset = 0){
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
        $sql = "SELECT * FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." $where ORDER BY fecha DESC, idtransporte DESC, codalmacen ASC, codtrans";
        $lista = array();
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        $resultados['resultados'] = $lista;
        return $resultados;
    }

    public function get_lineas()
    {
        $lista = array();
        $sql = "SELECT * FROM distribucion_lineastransporte where idtransporte = ".$this->intval($this->idtransporte)
                ." AND codalmacen = ".$this->var2str($this->codalmacen)." AND idempresa = ".$this->intval($this->idempresa);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new distribucion_lineastransporte($d);
                $articulo = $this->articulo->get($item->referencia);
                $item->descripcion = $articulo->descripcion;
                $lista[] = $item;
            }
        }
        return $lista;
    }

    public function get_facturas()
    {
        $lista = array();
        $sql = "SELECT * FROM distribucion_ordenescarga_facturas where idtransporte = ".$this->intval($this->idtransporte)
                ." AND codalmacen = ".$this->var2str($this->codalmacen)." AND idempresa = ".$this->intval($this->idempresa);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_ordenescarga_facturas($d);
            }
        }
        return $lista;
    }

    public function total_transportes($idempresa, $codalmacen, $desde, $hasta){
        $query = '';
        if($codalmacen)
        {
            $query .= ' AND codalmacen = '.$this->var2str($codalmacen);
        }

        if($desde)
        {
            $query .= ' AND fecha >= '.$this->var2str($desde);
        }

        if($hasta)
        {
            $query .= ' AND fecha <= '.$this->var2str($hasta);
        }

        $sql = "SELECT count(*) as total FROM ".$this->table_name." where idempresa = ".$this->intval($idempresa).$query.";";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['total'];
        }else{
            return 0;
        }
    }

    public function total_pendientes($idempresa, $tipo, $codalmacen, $desde, $hasta){
        $query = '';
        if($codalmacen)
        {
            $query .= ' AND codalmacen = '.$this->var2str($codalmacen);
        }

        if($desde)
        {
            $query .= ' AND fecha >= '.$this->var2str($desde);
        }

        if($hasta)
        {
            $query .= ' AND fecha <= '.$this->var2str($hasta);
        }
        $sql = "SELECT count(*) as total FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa).$query." AND ".strip_tags(trim($tipo))." = FALSE;";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['total'];
        }else{
            return 0;
        }
    }

    public function pendientes($idempresa, $tipo, $codalmacen, $desde, $hasta){
        $lista = array();
        $query = '';
        if($codalmacen)
        {
            $query .= ' AND codalmacen = '.$this->var2str($codalmacen);
        }

        if($desde)
        {
            $query .= ' AND fecha >= '.$this->var2str($desde);
        }

        if($hasta)
        {
            $query .= ' AND fecha <= '.$this->var2str($hasta);
        }
        $sql = "SELECT * FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa).$query." AND ".strip_tags(trim($tipo))." = FALSE ORDER BY codalmacen ASC, fecha ASC, idtransporte ASC, codtrans;";
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all($idempresa, $offset = 0)
    {
        $lista = array();
        $data = $this->db->select_limit("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." ORDER BY fecha DESC, idtransporte DESC, codalmacen ASC, codtrans ", FS_ITEM_LIMIT, $offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_pendientes($idempresa, $tipo, $codalmacen=false, $offset = 0)
    {
        $sql_extra = "";
        if($codalmacen)
        {
            $sql_extra = " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $lista = array();
        $data = $this->db->select_limit("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa).$sql_extra." AND ".strip_tags(trim($tipo))." = FALSE ORDER BY fecha DESC, idtransporte DESC, codalmacen ASC, codtrans ", FS_ITEM_LIMIT, $offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_almacen($idempresa,$codalmacen, $offset = 0)
    {
        $lista = array();
        $data = $this->db->select_limit("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY fecha DESC, idtransporte DESC, codalmacen ASC",FS_ITEM_LIMIT, $offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_agencia($idempresa,$codtrans, $offset = 0)
    {
        $lista = array();
        $data = $this->db->select_limit("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans ",FS_ITEM_LIMIT, $offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_transporte($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function get($idempresa,$idtransporte,$codalmacen)
    {
        $lista = FALSE;
        $data = $this->db->select("SELECT * FROM distribucion_transporte WHERE idempresa = ".$this->intval($idempresa)." AND idtransporte = ".$this->intval($idtransporte)." AND codalmacen = ".$this->var2str($codalmacen).";");

        if($data)
        {
                $valor = new distribucion_transporte($data[0]);
                $linea = $this->info_adicional($valor);
                return $linea;
        }else{
            return true;
        }
    }
}
