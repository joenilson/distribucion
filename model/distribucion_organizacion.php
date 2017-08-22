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
require_model('distribucion_rutas.php');
/**
 * Description of distribucion_organizacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_organizacion extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $codagente;
    public $codsupervisor;
    public $tipoagente;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;

    public $agente;
    public $rutas;
    public function __construct($t = false) {
        parent::__construct('distribucion_organizacion','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->codagente = $t['codagente'];
            $this->codsupervisor = $t['codsupervisor'];
            $this->tipoagente = $t['tipoagente'];
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
            $this->codsupervisor = null;
            $this->tipoagente = null;
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

    public function info_adicional($value, $d){
        $this->agente = new agente();
        $value->nombre = $d['nombre'];
        $value->nombre_supervisor = $d['nombre_supervisor'];
        $value->tiene_asignados = $d['total_asignados'];
        $value->tiene_rutas_asignadas = $d['total_rutas'];
        return $value;
    }

    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_organizacion WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codagente = ".$this->var2str($this->codagente)." AND ".
                "tipoagente = ".$this->var2str($this->tipoagente).";");
        if(empty($data)){
            return false;
        }else{
            return true;
        }
    }

    public function sql_select($idempresa, $codalmacen, $tipoagente, $codagente, $codsupervisor, $estado = false)
    {
        $sql_aux = $this->filterVariables($codalmacen, $tipoagente, $codagente, $codsupervisor, $estado);
        $sql = "SELECT dorg.*,concat(a1.nombre,' ',a1.apellidos,' ',a1.segundo_apellido) as nombre, ".
        " concat(a2.nombre,' ',a2.apellidos,' ',a2.segundo_apellido) as nombre_supervisor, sum1.total_asignados, sum2.total_rutas ".
        " FROM ".$this->table_name." as dorg".
        " left join agentes as a1 on (dorg.codagente = a1.codagente and dorg.codalmacen = a1.codalmacen) ".
        " left join agentes as a2 on (dorg.codsupervisor = a2.codagente and dorg.codalmacen = a2.codalmacen) ".
        " left join (SELECT idempresa,codalmacen,codsupervisor,count(*) as total_asignados FROM ".$this->table_name." GROUP BY idempresa,codalmacen,codsupervisor) as sum1 on (sum1.idempresa = dorg.idempresa AND sum1.codsupervisor = dorg.codagente and sum1.codalmacen = dorg.codalmacen) ".
        " left join (SELECT idempresa,codalmacen,codagente,count(*) as total_rutas FROM distribucion_rutas GROUP BY idempresa,codalmacen,codagente) as sum2 on (sum2.idempresa = dorg.idempresa AND sum2.codagente = dorg.codagente and sum2.codalmacen = dorg.codalmacen) ".
        " WHERE dorg.idempresa = ".$this->intval($idempresa).
        $sql_aux.
        " ORDER BY dorg.codalmacen, dorg.tipoagente, dorg.codagente;";
        return $sql;
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_organizacion SET ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "codsupervisor = ".$this->var2str($this->codsupervisor).", ".
                    "tipoagente = ".$this->var2str($this->tipoagente).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codagente = ".$this->var2str($this->codagente)." AND ".
                    "tipoagente = ".$this->var2str($this->tipoagente).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_organizacion ( idempresa, codalmacen, codagente, codsupervisor, tipoagente, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->codagente).", ".
                    $this->var2str($this->codsupervisor).", ".
                    $this->var2str($this->tipoagente).", ".
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
        $sql = "DELETE FROM distribucion_organizacion WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codagente = ".$this->var2str($this->codagente)." AND ".
                "tipoagente = ".$this->var2str($this->tipoagente).";";
        return $this->db->exec($sql);
    }

    public function filterVariables($codalmacen = false, $tipoagente = false, $codagente = false, $codsupervisor = false, $estado = false)
    {
        $sql = '';
        if($codalmacen){
            $sql.=' AND dorg.codalmacen = '.$this->var2str($codalmacen);
        }
        if($tipoagente){
            $sql.=' AND dorg.tipoagente = '.$this->var2str($tipoagente);
        }
        if($codagente){
            $sql.=' AND dorg.codagente = '.$this->var2str($codagente);
        }
        if($codsupervisor){
            $sql.=' AND dorg.codsupervisor = '.$this->var2str($codsupervisor);
        }
        if($estado){
            $sql.=' AND dorg.estado = TRUE ';
        }
        return $sql;
    }

    public function all($idempresa, $codalmacen = false, $tipoagente = false, $codagente = false, $codsupervisor = false)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, $codalmacen, $tipoagente, $codagente, $codsupervisor);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function all_tipoagente($idempresa,$tipoagente)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, false, $tipoagente, false, false);
        $data = $this->db->select($sql);

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function all_almacen_tipoagente($idempresa,$codalmacen,$tipoagente)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, $codalmacen, $tipoagente, false, false);
        $data = $this->db->select($sql);

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function activos($idempresa)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, false, false, false, false, true);
        $data = $this->db->select($sql);

        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function activos_tipoagente($idempresa,$tipoagente)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, false, $tipoagente, false, false);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function activos_almacen_tipoagente($idempresa, $codalmacen = false,$tipoagente)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, $codalmacen, $tipoagente, false, false, true);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
        }
        return $lista;
    }

    public function get($idempresa,$codagente)
    {
        $sql = $this->sql_select($idempresa, false, false, $codagente, false);
        $data = $this->db->select($sql);
        if($data)
        {
            $value = new distribucion_organizacion($data[0]);
            $info = $this->info_adicional($value, $data[0]);
            return $info;
        }else{
            return false;
        }

    }

    public function get_noasignados_all($idempresa,$cargos,$tipoagente){
        $cargos_valor = (is_array($cargos))?"('".implode("','", $cargos)."')":$this->var2str($cargos);
        $signo_cargos = (is_array($cargos))?" IN ":" = ";
        $lista = array();
        $sql = "SELECT * FROM agentes".
                " WHERE ".
                " codagente NOT IN (select codagente from ".$this->table_name." where idempresa = ".$this->intval($idempresa)." AND tipoagente=".$this->var2str($tipoagente).") and f_baja IS NULL ".
                " AND codcargo $signo_cargos $cargos_valor".
                " ORDER BY nombre, apellidos";
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                $lista[] = new agente($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get_asignados($idempresa,$codsupervisor, $codalmacen = false)
    {
        $lista = array();
        $sql = $this->sql_select($idempresa, $codalmacen, false, false, $codsupervisor);
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d){
                $value = new distribucion_organizacion($d);
                $info = $this->info_adicional($value, $d);
                $lista[] = $info;
            }
            return $lista;
        }else{
            return false;
        }

    }

    public function tiene_asignados($idempresa,$codsupervisor, $codalmacen = false)
    {
        $sql = $this->sql_select($idempresa, $codalmacen, false, false, $codsupervisor);
        $data = $this->db->select($sql);
        if($data)
        {
            return $data[0]['total_asignados'];
        }else{
            return false;
        }

    }

    public function tiene_rutas_asignadas($idempresa,$codagente)
    {
        $data = $this->db->select("SELECT count(*) as cantidad FROM distribucion_rutas WHERE idempresa = ".$this->intval($idempresa)." AND codagente = ".$this->var2str($codagente).";");

        if($data)
        {
            return $data[0]['total_rutas'];
        }else{
            return false;
        }
    }

    public function tiene_clientes_asignados($idempresa,$codalmacen,$codagente)
    {
        //Buscamos las rutas de este agente
        $sql = "SELECT count(dc.codcliente) as total_clientes ".
                " FROM distribucion_rutas as dr, distribucion_clientes as dc ".
                " WHERE dr.idempresa = ".$this->intval($idempresa)." AND dr.idempresa = dc.idempresa ".
                " AND dr.codalmacen = ".$this->var2str($codalmacen)." AND dr.codalmacen = dc.codalmacen ".
                " AND dr.codagente = ".$this->var2str($codagente)." AND dr.ruta = dc.ruta;";
        $data = $this->db->select($sql);
        $cantidad = 0;
        if($data)
        {
            $cantidad = $data[0]['total_clientes'];
        }
        return $cantidad;
    }
}
