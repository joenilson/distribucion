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
require_model('distribucion_organizacion.php');
require_model('distribucion_tiporuta.php');
/**
 * Description of distribucion_rutas
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_rutas extends fs_model {
    /**
     *
     * @var integer
     */
    public $idempresa;
    /**
     *
     * @var string
     */
    public $codalmacen;
    /**
     *
     * @var string
     */
    public $codagente;
    /**
     *
     * @var string
     */
    public $codruta;
    /**
     *
     * @var string
     */    
    public $ruta;
    /**
     *
     * @var string
     */    
    public $descripcion;
    /**
     *
     * @var boolean
     */
    public $lunes;
    /**
     *
     * @var boolean
     */
    public $martes;
    /**
     *
     * @var boolean
     */
    public $miercoles;
    /**
     *
     * @var boolean
     */
    public $jueves;
    /**
     *
     * @var boolean
     */
    public $viernes;
    /**
     *
     * @var boolean
     */
    public $sabado;
    /**
     *
     * @var boolean
     */
    public $domingo;
    /**
     *
     * @var boolean
     */
    public $estado;
    /**
     *
     * @var string
     */
    public $usuario_creacion;
    /**
     *
     * @var string
     */
    public $fecha_creacion;
    /**
     *
     * @var string
     */
    public $usuario_modificacion;
    /**
     *
     * @var string
     */
    public $fecha_modificacion;

    public function __construct($t = false) {
        parent::__construct('distribucion_rutas','plugins/distribucion/');
        if($t){
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codagente = $t['codagente'];
            $this->codruta = $t['codruta'];
            $this->ruta = $t['ruta'];
            $this->descripcion = $t['descripcion'];
            $this->lunes = $this->str2bool($t['lunes']);
            $this->martes = $this->str2bool($t['martes']);
            $this->miercoles = $this->str2bool($t['miercoles']);
            $this->jueves = $this->str2bool($t['jueves']);
            $this->viernes = $this->str2bool($t['viernes']);
            $this->sabado = $this->str2bool($t['sabado']);
            $this->domingo = $this->str2bool($t['domingo']);
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        } else {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->codagente = null;
            $this->codruta = null;
            $this->ruta = null;
            $this->descripcion = null;
            $this->lunes = false;
            $this->martes = false;
            $this->miercoles = false;
            $this->jueves = false;
            $this->viernes = false;
            $this->sabado = false;
            $this->domingo = false;
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
    
    private function join_tablas()
    {
        $sql = "SELECT dr.*,concat(a1.nombre,' ',a1.apellidos,' ',a1.segundo_apellido) as nombre, ".
                "do1.codsupervisor,concat(a2.nombre,' ',a2.apellidos,' ',a2.segundo_apellido) as nombre_supervisor,".
                "dtr.descripcion as tipo_ruta ".
                " FROM ".$this->table_name." AS dr ".
                " LEFT JOIN distribucion_tiporuta as dtr on (dr.codruta = dtr.id) ".
                " LEFT JOIN distribucion_organizacion as do1 on (dr.codagente = do1.codagente and do1.tipoagente= 'VENDEDOR') ".
                " LEFT JOIN agentes as a1 on (a1.codagente = do1.codagente) ".
                " LEFT JOIN agentes as a2 on (a2.codagente = do1.codsupervisor) ";
        return $sql;
    }

    public function exists() {
        if(is_null($this->ruta)){
            return false;
        }else{
            return $this->db->select("SELECT * FROM distribucion_rutas WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ruta = ".$this->var2str($this->ruta).";");

        }
    }

    public function getNextId(){
        $data = $this->db->select("SELECT max(ruta) AS max FROM distribucion_rutas WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen).";");
        $id = $data[0]['max'];
        $id++;
        return str_pad($id,3,'0',STR_PAD_LEFT);
    }

    public function info_adicional($res,$info){
        $res->nombre = $info['nombre'];
        $res->codsupervisor = $info['codsupervisor'];
        $res->nombre_supervisor = $info['nombre_supervisor'];
        $res->tipo_ruta = $info['tipo_ruta'];
        $res->tiene_asignados = $this->tiene_asignados($res->idempresa, $res->codalmacen, $res->ruta);
        return $res;
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_rutas SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "codagente = ".$this->var2str($this->codagente).", ".
                    "codruta = ".$this->intval($this->codruta).", ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "lunes = ".$this->var2str($this->lunes).", ".
                    "martes = ".$this->var2str($this->martes).", ".
                    "miercoles = ".$this->var2str($this->miercoles).", ".
                    "jueves = ".$this->var2str($this->jueves).", ".
                    "viernes = ".$this->var2str($this->viernes).", ".
                    "sabado = ".$this->var2str($this->sabado).", ".
                    "domingo = ".$this->var2str($this->domingo).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "ruta = ".$this->var2str($this->ruta).";";
            return $this->db->exec($sql);
        }
        else
        {
            $this->ruta = $this->getNextId();
            $sql = "INSERT INTO distribucion_rutas ( idempresa, codalmacen, codagente, codruta, ruta, descripcion, lunes, martes, miercoles, jueves, viernes, sabado, domingo, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->codagente).", ".
                    $this->intval($this->codruta).", ".
                    $this->var2str($this->ruta).", ".
                    $this->var2str($this->descripcion).", ".
                    $this->var2str($this->lunes).", ".
                    $this->var2str($this->martes).", ".
                    $this->var2str($this->miercoles).", ".
                    $this->var2str($this->jueves).", ".
                    $this->var2str($this->viernes).", ".
                    $this->var2str($this->sabado).", ".
                    $this->var2str($this->domingo).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return $this->ruta;
            }
            else
            {
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM distribucion_rutas WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                "codagente = ".$this->var2str($this->codagente)." AND ".
                "ruta = ".$this->var2str($this->ruta).";";
        return $this->db->exec($sql);
    }

    public function all($idempresa)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;

            }
        }
        return $lista;
    }

    public function all_rutasporalmacen($idempresa,$codalmacen)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen)." ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutaspordia($idempresa,$codalmacen,$dia)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen)." AND $dia = TRUE ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutasporagente($idempresa,$codalmacen,$codagente)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen)." AND dr.codagente = ".$this->var2str($codagente).
                " ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutasporagentedias($idempresa,$codalmacen,$codagente, $dias)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $sql = $sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa).
                " AND dr.codalmacen = ".$this->var2str($codalmacen).
                " AND ($dias) AND dr.codagente = ".$this->var2str($codagente).
                " ORDER BY dr.codalmacen, dr.codagente, dr.ruta;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.estado = true ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos_rutasporalmacen($idempresa,$codalmacen)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen).
                " AND dr.estado = true ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos_rutasporagente($idempresa,$codalmacen,$codagente)
    {
        $lista = array();
        $sql_select = $this->join_tablas();
        $data = $this->db->select($sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen).
                " AND dr.codagente = ".$this->var2str($codagente)." AND dr.estado = true ORDER BY dr.codalmacen, dr.codagente, dr.ruta;");
        if($data){
            foreach($data as $d){
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function get($idempresa,$codalmacen,$ruta)
    {
        $sql_select = $this->join_tablas();
        $sql = $sql_select.
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.codalmacen = ".$this->var2str($codalmacen)." AND dr.ruta = ".$this->var2str($ruta).";";
        $data = $this->db->select($sql);
        if($data){
            $value = new distribucion_rutas($data[0]);
            $value_final = $this->info_adicional($value,$data[0]);
            return $value_final;
        }else{
            return false;
        }
    }

    /*
    public function get_asignados($idempresa,$codalmacen,$ruta){
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";");

        if($data){
            foreach($data as $d){
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }
     */

    public function tiene_asignados($idempresa,$codalmacen,$ruta){
        $data = $this->db->select("SELECT count(*) FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";");

        if($data)
        {
            return true;
        }else{
            return false;
        }
    }

    public function cantidad_asignados($idempresa,$codalmacen,$ruta){
        $data = $this->db->select("SELECT count(*) as total FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";");

        if($data){
            return $data[0]['total'];
        }else{
            return false;
        }
    }

    public function search($almacen,$query){
        $lista = array();
        $sql_select = $this->join_tablas();
        $sql = $sql_select." WHERE ";
        if($almacen){
            $sql .= "dr.codalmacen = ".$this->var2str($almacen);
        }else{
            $sql .= "dr.codalmacen != ''";
        }
        if(is_numeric($query)){
            $sql.= " AND CAST(dr.codruta as CHAR) like '%".$query."%'";
            $sql.= " OR dr.ruta like '%".$query."%'";
            $sql.="ORDER BY dr.codalmacen, dr.codruta";
        }else{
            $sql.= " AND lower(dr.ruta) like '%".$query."%'";
            $sql.= " OR lower(dr.descripcion) like '%".$query."%'";
            $sql.=" ORDER BY dr.codalmacen, dr.codruta";
        }
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value,$d);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }
}
