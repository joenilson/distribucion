<?php
/*
 * Copyright (C) 2017 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU Lesser General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
require_model('direccion_cliente.php');
/**
 * Tabla para almacenar las coordenadas de los clientes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_coordenadas_clientes extends fs_model {
    /**
     * El id de la empresa
     * @var integer
     */
    public $idempresa;
    /**
     * El codidgo del cliente
     * @var varchar(6)
     */
    public $codcliente;
    /**
     * El id de la tabla direccion_cliente
     * @var integer
     */
    public $iddireccion;
    /**
     * Las coordenadas del cliente en formato LAT,LON
     * ejemplo 24.8240156,-75.4925647
     * @var varchar(64)
     */
    public $coordenadas;
    /**
     * Fecha de creación
     * @var date(y-m-d)
     */
    public $fecha_creacion;
    /**
     * Fecha de modificacion
     * @var date(y-m-d)
     */
    public $fecha_modificacion;
    /**
     * Usuario que crea la entrada
     * @var varchar(10)
     */
    public $usuario_creacion;
    /**
     * Usuario que modifica la entrada
     * @var varhcar(10)
     */
    public $usuario_modificacion;
    /**
     * Variable auxiliar para cargar el model direccion_cliente
     * @var object model direccion_cliente
     */
    public $direccion_cliente;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_coordenadas_clientes','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codcliente = $t['codcliente'];
            $this->iddireccion = $t['iddireccion'];
            $this->coordenadas = $t['coordenadas'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codcliente = null;
            $this->iddireccion = null;
            $this->coordenadas = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
        
    }
    
    public function url(){
        return "index.php?page=distrib_clientes&codcliente=".$this->codcliente;
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "iddireccion = ".$this->intval($this->iddireccion)." AND ".
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
            $sql = "UPDATE ".$this->table_name." SET ".
                    "coordenadas = ".$this->var2str($this->coordenadas).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codcliente = ".$this->var2str($this->codcliente)." AND ".
                    "iddireccion = ".$this->intval($this->iddireccion).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO ".$this->table_name." ( idempresa, codcliente, iddireccion, coordenadas, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codcliente).", ".
                    $this->intval($this->iddireccion).", ".
                    $this->var2str($this->coordenadas).", ".
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
        $sql = "DELETE FROM ".$this->table_name." WHERE " .
                "iddireccion = " . $this->intval($this->iddireccion) . " AND " .
                "idempresa = " . $this->intval($this->idempresa) . " AND " .
                "codcliente = " . $this->var2str($this->codcliente) . ";";
        return $this->db->exec($sql);
    }
    
    public function info_adicional($direccion){
        $this->direccion_cliente = new direccion_cliente();
        $datos_direccion = $this->direccion_cliente->get($direccion->iddireccion);
        $direccion->direccion = $datos_direccion->direccion;
        return $direccion;
    }

    public function all($idempresa) {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idempresa = " . $this->intval($idempresa) . " ORDER BY codcliente;");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new distribucion_coordenadas_clientes($d);
            }
        }
        return $lista;
    }
    
    public function all_cliente($idempresa,$codcliente){
        $lista = array();
        //echo "SELECT id,direccion,".$this->table_name.".* FROM dirclientes LEFT JOIN ".$this->table_name." ON (id = iddireccion) WHERE "."idempresa = ".$this->intval($idempresa)." AND dirclientes.codcliente = ".$this->var2str($codcliente)." AND dirclientes.domenvio = TRUE order by direccion;";
        $data = $this->db->select("SELECT id,direccion,".$this->table_name.".* FROM dirclientes LEFT JOIN ".$this->table_name." ON (id = iddireccion) WHERE "."dirclientes.codcliente = ".$this->var2str($codcliente)." AND dirclientes.domenvio = TRUE order by direccion;");
        if($data){
            foreach($data as $d){
                if((!empty($d['idempresa']) AND $d['idempresa']==$idempresa) OR empty($d['idempresa'])){
                    $direccion = new distribucion_coordenadas_clientes($d);
                    $direccion->iddireccion = $d['id'];
                    $direccion->direccion = $d['direccion'];
                    $lista[] = $direccion;
                }
            }
            return $lista;
        }else{
            return false;
        }
    }
    
    public function get($idempresa,$codcliente,$iddireccion){
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE "."idempresa = ".$this->intval($idempresa)." AND codcliente = ".$this->var2str($codcliente)." AND iddireccion = ".$this->intval($iddireccion).";");
        if ($data) {
            $direccion = distribucion_coordenadas_clientes($data[0]);
            $resultados = $this->info_adicional($direccion);
            return $resultados;
        }else{
            return false;
        }
    }
}
