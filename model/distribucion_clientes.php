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
    }

    public function url(){
        return "index.php?page=distrib_clientes";
    }

    protected function install() {
        return "";
    }

    private function join_tablas(){
        $sql = " SELECT dc.*,c.nombre as nombre_cliente,c.razonsocial,c.cifnif,c.fechaalta,c.fechabaja,c.debaja,ds1.descripcion as canal_desc,".
                "ds2.descripcion as subcanal_desc,dir.direccion,dr.descripcion, dr.codagente, concat(a1.nombre,' ',a1.apellidos,' ',a1.segundo_apellido) as nombre".
                " FROM ".$this->table_name.' AS dc '.
                " JOIN clientes as c ON (dc.codcliente = c.codcliente) ".
                " JOIN dirclientes as dir ON (dc.iddireccion = dir.id AND dir.codcliente = dc.codcliente) ".
                " LEFT JOIN distribucion_rutas as dr on (dr.ruta = dc.ruta AND dr.codalmacen = dc.codalmacen)  ".
                " LEFT JOIN agentes as a1 on (a1.codagente = dr.codagente) ".
                " LEFT JOIN distribucion_segmentos as ds1 on (ds1.codigo = dc.canal AND ds1.tiposegmento = 'CANAL' AND ds1.idempresa = dc.idempresa) ".
                " LEFT JOIN distribucion_segmentos as ds2 on (ds2.codigo = dc.subcanal AND ds2.tiposegmento = 'SUBCANAL' AND ds1.idempresa = dc.idempresa) ";
        return $sql;
    }

    public function info_adicional($informacion,$d){
        $informacion->direccion =$d['direccion'];
        $informacion->ruta_descripcion = $d['descripcion'];
        $informacion->codagente = $d['codagente'];
        $informacion->nombre = $d['nombre'];
        $informacion->canal_descripcion = $d['canal_desc'];
        $informacion->subcanal_descripcion = $d['subcanal_desc'];
        $informacion->nombre_cliente = $d['nombre_cliente'];
        $informacion->razonsocial = $d['razonsocial'];
        $informacion->cifnif = $d['cifnif'];
        $informacion->fechaalta = $d['fechaalta'];
        $informacion->fechabaja = $d['fechabaja'];
        $informacion->debaja = $this->str2bool($d['debaja']);
        return $informacion;
    }

    //Agregando metodo para mostrar la informacion  de la ruta y las iniciales del agente.
    public function info_vendedor($factura) {
        $data = $this->db->select("SELECT  '('||dc.ruta || '/'|| substring(ag.nombre,1,1)||substring(ag.apellidos,1,1) ||')' as vendedor FROM facturascli f inner join distribucion_clientes dc  on dc.codcliente = f.codcliente
            inner join albaranescli a on a.idfactura = f.idfactura inner join pedidoscli p on p.idalbaran = a.idalbaran
           inner join agentes ag on ag.codagente = p.codagente  WHERE  f.idfactura=" . $this->var2str($factura) . ";");
        if ($data) {
            return $data[0]['vendedor'];
        } else {
            return false;
        }
    }

    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
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
                "codalmacen = ".$this->var2str($this->codalmacen).", ".
                "canal = ".$this->var2str($this->canal).", ".
                "subcanal = ".$this->var2str($this->subcanal).", ".
                "ruta = ".$this->var2str($this->ruta).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                " WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "iddireccion = ".$this->var2str($this->iddireccion)." AND ".
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
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa)." ORDER BY ruta, canal, subcanal, dc.codcliente;");
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value,$d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_sinruta($idempresa, $almacen){
        $sql = "SELECT c.codcliente as codcliente, c.nombre as nombre_cliente, d.id as iddireccion, d.direccion as direccion FROM  clientes as c, dirclientes as d ".
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
                $value->nombre_cliente = $d['nombre_cliente'];
                $value->iddireccion = $d['iddireccion'];
                $value->direccion = $d['direccion'];
                $value->ruta = 'noruta';
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function clientes_almacen($idempresa,$codalmacen)
    {
        $sql = "SELECT c.*, dc.* FROM clientes as c, distribucion_clientes as dc ".
                " WHERE idempresa = ".$this->intval($idempresa).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " AND c.codcliente = dc.codcliente ".
                " ORDER BY ruta, c.codcliente;";
        $lista = array();
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $info = new cliente($d);
                $info->codalmacen = $d['codalmacen'];
                $info->iddireccion = $d['iddireccion'];
                $info->ruta = $d['ruta'];
                $info->canal = $d['canal'];
                $info->subcanal = $d['subcanal'];
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_totales($idempresa)
    {
        $sql = "SELECT c.*, dc.* FROM clientes as c, distribucion_clientes as dc ".
                " WHERE idempresa = ".$this->intval($idempresa).
                " AND c.codcliente = dc.codcliente ".
                " ORDER BY ruta, c.codcliente;";
        $lista = array();
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $info = new cliente($d);
                $info->codalmacen = $d['codalmacen'];
                $info->iddireccion = $d['iddireccion'];
                $info->ruta = $d['ruta'];
                $info->canal = $d['canal'];
                $info->subcanal = $d['subcanal'];
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_totales_estado($codalmacen,$desde,$hasta)
    {
        $clientes = array();
        $clientes['activos'] = 0;
        $clientes['debaja'] = 0;
        $clientes['inactivos'] = 0;
        $clientes['nuevos'] = 0;
        $sql_almacen = '';
        if($codalmacen)
        {
            $sql_almacen = " and dc.codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT ( ".
            " SELECT count(c.codcliente) AS activos  ".
            " FROM clientes AS c, distribucion_clientes as dc  ".
            " WHERE fechabaja IS NULL and fechaalta < ".$this->var2str($desde)." and dc.codcliente = c.codcliente ".
            $sql_almacen.
            " ) as activos, ".
            " ( ".
            " SELECT count(c.codcliente) AS debaja  ".
            " FROM clientes AS c, distribucion_clientes as dc  ".
            " WHERE fechabaja IS NOT NULL and fechabaja between ".$this->var2str($desde)." and ".$this->var2str($hasta)." and dc.codcliente = c.codcliente ".
            $sql_almacen.
            " ) as debaja, ".
            " ( ".
            " SELECT count(c.codcliente) AS inactivos ".
            " FROM clientes AS c, distribucion_clientes as dc  ".
            " WHERE fechabaja IS NOT NULL and fechaalta between ".$this->var2str($desde)." and ".$this->var2str($hasta)." and dc.codcliente = c.codcliente ".
            $sql_almacen.
            " ) as inactivos, ".
            " ( ".
            " SELECT count(c.codcliente) AS nuevos ".
            " FROM clientes AS c, distribucion_clientes as dc  ".
            " WHERE fechabaja IS NULL and fechaalta between ".$this->var2str($desde)." and ".$this->var2str($hasta)." and dc.codcliente = c.codcliente ".
            $sql_almacen.
            " ) as nuevos;";
        $data = $this->db->select($sql);
        if($data)
        {
            $clientes['activos'] = $data[0]['activos'];
            $clientes['debaja'] = $data[0]['debaja'];
            $clientes['inactivos'] = $data[0]['inactivos'];
            $clientes['nuevos'] = $data[0]['nuevos'];
        }
        return $clientes;
    }

    public function clientes_ruta($idempresa,$codalmacen, $ruta)
    {
        $sql_select = $this->join_tablas();
        $sql = $sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.ruta = ".$this->var2str($ruta).
                " AND dc.codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY dc.ruta, dc.codcliente;";
        $lista = array();
        $data = $this->db->select($sql);

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value,$d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_ruta_imprimir($idempresa,$codalmacen, $ruta)
    {
        $sql_select = $this->join_tablas();
        $sql = $sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.ruta = ".$this->var2str($ruta).
                " AND dc.codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY dc.codcliente;";
        $lista = array();
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                $item = array();
                $item[] = $d['codcliente'];
                $item[] = (trim($d['nombre_cliente'])==trim($d['razonsocial']))?trim($d['nombre_cliente']):trim($d['razonsocial']).' - '.trim($d['nombre_cliente']);
                $item[] = $d['direccion'];
                $item[] = $d['canal_desc'];
                $item[] = $d['subcanal_desc'];
                $lista[] = $item;
            }
        }
        return $lista;
    }

    public function clientes_canal($idempresa,$codalmacen,$canal)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.canal = ".$this->var2str($canal).
                " AND dc.codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY dc.canal, dc.ruta, dc.codcliente;");

        if($data){
            foreach($data as $d){
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value,$d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function clientes_subcanal($idempresa,$codalmacen,$subcanal)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.subcanal = ".$this->var2str($subcanal).
                " AND dc.codalmacen = ".$this->var2str($codalmacen).
                " ORDER BY dc.subcanal, dc.ruta, dc.codcliente;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value,$d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function get($idempresa,$codcliente)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.codcliente = ".$this->var2str($codcliente).";");
        if($data)
        {
            foreach ($data as $d){
                $value = new distribucion_clientes($d);
                $info = $this->info_adicional($value,$d);
                $lista[] = $info;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function getOne($idempresa,$codcliente,$ruta)
    {
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa).
                " AND dc.ruta =".$this->var2str($ruta).
                " AND dc.codcliente = ".$this->var2str($codcliente).";");
        if($data)
        {
            $value = new distribucion_clientes($data[0]);
            $info = $this->info_adicional($value,$data[0]);
            return $info;
        }else{
            return false;
        }
    }

    public function ruta_cliente($idempresa,$codalmacen,$codcliente,$iddireccion,$ruta)
    {
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dc.idempresa = ".$this->intval($idempresa)." AND dc.codcliente = ".$this->var2str($codcliente)." AND dc.codalmacen = ".$this->var2str($codalmacen).
                " AND dc.iddireccion = ".$this->var2str($iddireccion).
                " AND dc.ruta = ".$this->var2str($ruta).";");
        if($data)
        {
            $value = new distribucion_clientes($data[0]);
            $info = $this->info_adicional($value,$data[0]);
            return $info;
        }else{
            return false;
        }
    }
}
