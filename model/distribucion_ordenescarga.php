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
/**
 * Description of distribucion_ordenescarga
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_ordenescarga extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $idordencarga;
    public $idtransporte;
    public $codalmacen_dest;
    public $fecha;
    public $codtrans;
    public $unidad;
    public $tipounidad;
    public $conductor;
    public $tipolicencia;
    public $totalcantidad;
    public $totalpeso;
    public $observaciones;
    public $estado;
    public $despachado;
    public $cargado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $conductor_nombre;
    public $distribucion_conductores;
    public $distribucion_unidades;

    public function __construct($t = false) {
        parent::__construct('distribucion_ordenescarga','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->idordencarga = $t['idordencarga'];
            $this->idtransporte = $t['idtransporte'];
            $this->codalmacen_dest = $t['codalmacen_dest'];
            $this->fecha = $t['fecha'];
            $this->codtrans = $t['codtrans'];
            $this->unidad = $t['unidad'];
            $this->tipounidad = $t['tipounidad'];
            $this->conductor = $t['conductor'];
            $this->tipolicencia = $t['tipolicencia'];
            $this->totalcantidad = $t['totalcantidad'];
            $this->totalpeso = $t['totalpeso'];
            $this->observaciones = $t['observaciones'];
            $this->estado = $this->str2bool($t['estado']);
            $this->despachado = $this->str2bool($t['despachado']);
            $this->cargado = $this->str2bool($t['cargado']);
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
            $this->codtrans = null;
            $this->unidad = null;
            $this->tipounidad = null;
            $this->conductor = null;
            $this->tipolicencia = null;
            $this->totalcantidad = null;
            $this->totalpeso = null;
            $this->observaciones = null;
            $this->estado = false;
            $this->despachado = false;
            $this->cargado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }

        $this->distribucion_conductores = new distribucion_conductores();
        $this->distribucion_unidades = new distribucion_unidades();
    }

    public function url(){
        return "index.php?page=distrib_ordencarga";
    }

    protected function install() {
        return "";
    }

    public function getNextId(){
        $data = $this->db->select("SELECT max(idordencarga) as max FROM distribucion_ordenescarga WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen).";");
        $id = $data[0]['max'];
        $id++;
        return $id;
    }

    public function exists() {
        if(is_null($this->idordencarga))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "idordencarga = ".$this->intval($this->idordencarga).";");
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_ordenescarga SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "idtransporte = ".$this->var2str($this->idtransporte).", ".
                    "codalmacen_dest = ".$this->var2str($this->codalmacen_dest).", ".
                    "codtrans = ".$this->var2str($this->codtrans).", ".
                    "unidad = ".$this->var2str($this->unidad).", ".
                    "tipounidad = ".$this->intval($this->tipounidad).", ".
                    "conductor = ".$this->var2str($this->conductor).", ".
                    "tipolicencia = ".$this->var2str($this->tipolicencia).", ".
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
            $this->idordencarga = $this->getNextId();
            $sql = "INSERT INTO distribucion_ordenescarga ( idempresa, codalmacen, idordencarga, codalmacen_dest, fecha, codtrans, unidad, tipounidad, conductor, tipolicencia, totalcantidad, totalpeso, observaciones, estado, despachado, cargado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->intval($this->idordencarga).", ".
                    $this->var2str($this->codalmacen_dest).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->codtrans).", ".
                    $this->var2str($this->unidad).", ".
                    $this->intval($this->tipounidad).", ".
                    $this->var2str($this->conductor).", ".
                    $this->var2str($this->tipolicencia).", ".
                    $this->var2str($this->totalcantidad).", ".
                    $this->var2str($this->totalpeso).", ".
                    $this->var2str($this->observaciones).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->despachado).", ".
                    $this->var2str($this->cargado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return $this->idordencarga;
            }
            else
            {
                return false;
            }
        }
    }

    public function delete() {
        //Liberamos las facturas asociadas a la orden de carga
        $ford0 = new distribucion_ordenescarga_facturas();
        $ford0->idempresa = $this->idempresa;
        $ford0->idordencarga = $this->idordencarga;
        $ford0->codalmacen = $this->codalmacen;
        $ford0->delete();
        //Eliminamos las lineas de la orden de carga
        $lord0 = new distribucion_lineasordenescarga();
        $lord0->idempresa = $this->idempresa;
        $lord0->idordencarga = $this->idordencarga;
        $lord0->codalmacen = $this->codalmacen;
        $lord0->delete();
        //Por ultimo borramos la Orden de Carga
        // @to-do Cascade delete
        $sql = "DELETE FROM distribucion_ordenescarga WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "idordencarga = ".$this->intval($this->idordencarga).";";
        return $this->db->exec($sql);
    }

    public function asignar_transporte(){
        $sql = "UPDATE distribucion_ordenescarga SET ".
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

    public function confirmar_cargada(){
        $sql = "UPDATE distribucion_ordenescarga SET ".
                    "cargado = ".$this->var2str($this->cargado).", ".
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

    public function confirmar_despachada(){
        $sql = "UPDATE distribucion_ordenescarga SET ".
                    "despachado = ".$this->var2str($this->despachado).", ".
                    "idtransporte = ".$this->intval($this->idtransporte).", ".
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

    public function total_ordenescarga($idempresa=false, $codalmacen=false, $desde=false, $hasta=false, $conductor=false){
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

        if($conductor)
        {
            $query .= ' AND conductor = '.$this->var2str($conductor);
        }

        $sql = "SELECT count(*) as total FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa).$query.";";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['total'];
        }else{
            return 0;
        }
    }

    public function total_pendientes($idempresa, $tipo='cargado', $codalmacen, $desde, $hasta){
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

        if(!$tipo)
        {
            $tipo = 'cargado';
        }

        $sql = "SELECT count(*) as total FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa).$query." AND ".strip_tags(trim($tipo))." = FALSE;";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['total'];
        }else{
            return 0;
        }
    }

    public function pendientes($idempresa, $tipo='cargado', $codalmacen, $desde, $hasta){
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

        if(!$tipo)
        {
            $tipo = 'cargado';
        }

        $sql = "SELECT *  FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa).$query." AND ".strip_tags(trim($tipo))." = FALSE ORDER BY codalmacen,fecha,idordencarga;";
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d)
            {
                $item = new distribucion_ordenescarga($d);
                $this->info_adicional($item);
                $lista[] = $item;
            }
        }
        return $lista;
    }

    public function info_adicional($t){

        $con0 = $this->distribucion_conductores->get($t->idempresa, $t->conductor);

        if(!empty($con0 )){
        $t->conductor_nombre = $con0->nombre;

        }
        return $t;
    }

    public function search($idempresa, $datos, $desde, $hasta, $offset){
        $resultados = array();
        $contador = 1;
        $where = (!empty($datos))?" AND ":"";
        foreach($datos as $k=>$v){
            $and = (count($datos) > $contador)?" AND ":"";
            $value = (is_string($v))?$this->var2str($v):$this->intval($v);
            $where.=" $k = ".$value.$and;
            $contador++;
        }

        if($desde)
        {
            $where.=" AND fecha >= ".$this->var2str($desde);
        }

        if($hasta)
        {
            $where.=" AND fecha <= ".$this->var2str($hasta);
        }

        $sql_count = "SELECT count(*) as total FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." $where;";
        $conteo = $this->db->select($sql_count);
        $resultados['cantidad'] = $conteo[0]['total'];
        $sql = "SELECT * FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." $where ORDER BY fecha DESC, idordencarga DESC, codalmacen ASC, codtrans";
        $lista = array();
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        $resultados['resultados'] = $lista;
        return $resultados;
    }

    public function all($idempresa, $offset = 0)
    {
        $sql = "SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." ORDER BY fecha DESC, idordencarga DESC, codalmacen ASC, codtrans";
        $lista = array();
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_pendientes($idempresa, $codalmacen=false, $offset = 0)
    {
        $sql_extra = "";
        if($codalmacen)
        {
            $sql_extra = " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa).$sql_extra." AND cargado = FALSE ORDER BY fecha DESC, idordencarga ASC, codalmacen ASC";
        $lista = array();
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }


    public function all_almacen($idempresa,$codalmacen,$offset = 0)
    {
        $lista = array();
        $sql = "SELECT * FROM distribucion_ordenescarga ".
               " WHERE idempresa = ".$this->intval($idempresa).
               " AND codalmacen = ".$this->var2str($codalmacen).
               " ORDER BY fecha DESC, idordencarga DESC";
        $data = $this->db->select_limit($sql,FS_ITEM_LIMIT,$offset);

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function all_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_almacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_agencia($idempresa,$codtrans)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function activos_agencia_almacen($idempresa,$codtrans,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND codtrans = ".$this->var2str($codtrans)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, fecha, codtrans;");

        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_ordenescarga($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
        }
        return $lista;
    }

    public function get($idempresa,$idordencarga,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen).";");

        if($data)
        {
            foreach($data as $d)
            {
                $valor_lista = new distribucion_ordenescarga($d);
                $datos_conductor = $this->distribucion_conductores->get($valor_lista->idempresa, $valor_lista->conductor);
                $valor_lista->conductor_nombre = $datos_conductor->nombre;
                $lista[] = $valor_lista;
            }
        }
        return $lista;
    }

    public function getOne($idempresa,$idordencarga,$codalmacen)
    {
        $valor_lista = false;
        $data = $this->db->select("SELECT * FROM distribucion_ordenescarga WHERE idempresa = ".$this->intval($idempresa)." AND idordencarga = ".$this->intval($idordencarga)." AND codalmacen = ".$this->var2str($codalmacen).";");
        if($data)
        {
                $valor_lista = new distribucion_ordenescarga($data[0]);
                $datos_conductor = $this->distribucion_conductores->get($valor_lista->idempresa, $valor_lista->conductor);
                $valor_lista->conductor_nombre = $datos_conductor->nombre;
        }
        return $valor_lista;
    }
}
