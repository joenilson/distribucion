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
require_model('model/agente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_tiporuta.php');
/**
 * Description of distribucion_rutas
 * 
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_rutas extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codagente;
    public $codruta;
    public $ruta;
    public $descripcion;
    public $lunes;
    public $martes;
    public $miercoles;
    public $jueves;
    public $viernes;
    public $sabado;
    public $domingo;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $agente;
    public $organizacion;
    public $tiporuta;

    public function __construct($t = false) {
        parent::__construct('distribucion_rutas','plugins/distribucion/');
        if($t)
        {
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
        }
        else
        {
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
        $this->agente = new agente();
        $this->organizacion = new distribucion_organizacion();
        $this->tiporuta = new distribucion_tiporuta();
    }

    public function url(){
        return "index.php?page=distrib_clientes";
    }

    protected function install() {
        return "";
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
    
    public function info_adicional($res){
        $data_agente = $this->agente->get($res->codagente);
        $data_organizacion = $this->organizacion->get($res->idempresa, $res->codagente);
        $res->nombre = $data_agente->nombre." ".$data_agente->apellidos;
        $data_supervisor = (!empty($data_organizacion->codsupervisor))?$this->agente->get($data_organizacion->codsupervisor):null;
        $res->codsupervisor = ($data_supervisor != null)?$data_supervisor->codagente:null;
        $res->nombre_supervisor = ($data_supervisor != null)?$data_supervisor->nombre." ".$data_supervisor->apellidos:null;
        $res->tiene_asignados = $this->tiene_asignados($res->idempresa, $res->codalmacen, $res->ruta);
        $res->tipo_ruta = (!empty($res->codruta))?$this->tiporuta->get($res->codruta)->descripcion:"";
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
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;

            }
        }
        return $lista;
    }

    public function all_rutasporalmacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutaspordia($idempresa,$codalmacen,$dia)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND $dia = TRUE ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutasporagente($idempresa,$codalmacen,$codagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND codagente = ".$this->var2str($codagente)." ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function all_rutasporagentedias($idempresa,$codalmacen,$codagente, $dias)
    {
        $lista = array();
        $sql = "SELECT * FROM distribucion_rutas ".
                "WHERE idempresa = ".$this->intval($idempresa).
                " AND codalmacen = ".$this->var2str($codalmacen).
                " AND ($dias) AND codagente = ".$this->var2str($codagente).
                " ORDER BY codalmacen, codagente, ruta;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND estado = true ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos_rutasporalmacen($idempresa,$codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND estado = true ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function activos_rutasporagente($idempresa,$codalmacen,$codagente)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND codagente = ".$this->var2str($codagente)." AND estado = true ORDER BY codalmacen, codagente, ruta;");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function get($idempresa,$codalmacen,$ruta)
    {
        $sql = "SELECT * FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";";
        $data = $this->db->select($sql);
        if($data)
        {
            $value = new distribucion_rutas($data[0]);
            $value_final = $this->info_adicional($value);
            return $value_final;
        }else{
            return false;
        }
    }

    public function get_asignados($idempresa,$codalmacen,$ruta){
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";");

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }

    public function tiene_asignados($idempresa,$codalmacen,$ruta){
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($codalmacen)." AND ruta = ".$this->var2str($ruta).";");

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
        $sql = "SELECT * FROM ".$this->table_name." WHERE ";
        if($almacen){
            $sql .= "codalmacen = ".$this->var2str($almacen);
        }else{
            $sql .= "codalmacen != ''";
        }
        if(is_numeric($query)){
            $sql.= " AND CAST(codruta as CHAR) like '%".$query."%'";
            $sql.= " OR ruta like '%".$query."%'";
            $sql.="ORDER BY codalmacen, codruta";
        }else{
            $sql.= " AND lower(ruta) like '%".$query."%'";
            $sql.= " OR lower(descripcion) like '%".$query."%'";
            $sql.=" ORDER BY codalmacen, codruta";
        }
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                $value = new distribucion_rutas($d);
                $value_final = $this->info_adicional($value);
                $lista[] = $value_final;
            }
        }
        return $lista;
    }
}
