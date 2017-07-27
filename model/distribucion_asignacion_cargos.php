<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson@gmail.com>
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
require_model('hr_cargos.php');
/**
 * Description of distribucion_asignacion_cargos
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_asignacion_cargos extends fs_model {
    /**
     *
     * @var integer
     */
    public $idempresa;
    /**
     *
     * @var string
     */
    public $codcargo;
    /**
     *
     * @var string
     */
    public $tipo_cargo;
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
     * @var object
     */
    public $cargos;
    public function __construct($t = false) {
        parent::__construct('distribucion_asignacion_cargos','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codcargo = $t['codcargo'];
            $this->tipo_cargo = $t['tipo_cargo'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = \Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
        }
        else
        {
            $this->idempresa = null;
            $this->codcargo = null;
            $this->tipo_cargo = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = \Date('d-m-Y H:i:s');
        }
    }
    
    public function url(){
        return "index.php?page=admin_distribucion";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->idempresa) AND is_null($this->codcargo) AND is_null($this->tipo_cargo))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_asignacion_cargos WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND codcargo = ".$this->var2str($this->codcargo).";");
        }
    }
    
    public function save() {
        if (!$this->exists())
        {
            $sql = "INSERT INTO distribucion_asignacion_cargos ( idempresa, codcargo, tipo_cargo, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codcargo).", ".
                    $this->var2str($this->tipo_cargo).", ".
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
        $sql = "DELETE FROM distribucion_asignacion_cargos WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND codcargo = ".$this->var2str($this->codcargo)." AND tipo_cargo = ".$this->var2str($this->tipo_cargo).";";
        return $this->db->exec($sql);
    }
       
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_asignacion_cargos WHERE idempresa = ".$this->intval($idempresa)." ORDER BY tipo_cargo,codcargo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_asignacion_cargos($d);
                $lista[] = $this->info_adicional($valor);
            }
        }
        return $lista;
    }
    
    public function all_tipocargo($idempresa,$tipocargo)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_asignacion_cargos WHERE idempresa = ".$this->intval($idempresa)." AND tipo_cargo = ".$this->var2str($tipocargo)." ORDER BY tipo_cargo,codcargo;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $valor = new distribucion_asignacion_cargos($d);
                $lista[] = $this->info_adicional($valor);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$codcargo,$tipocargo)
    {
        $data = $this->db->select("SELECT * FROM distribucion_asignacion_cargos WHERE idempresa = ".$this->intval($idempresa)
        ." AND codcargo = ".$this->var2str($codcargo)." AND tipo_cargo = ".$this->var2str($tipocargo).";");
        if($data)
        {
            $valor = new distribucion_asignacion_cargos($data[0]);
            $d = $this->info_adicional($valor);
            return $d;
        }else{
            return false;
        }
        
    }
    
    public function info_adicional($valor){
        $cargos = new cargos();
        $cargo = $cargos->get($valor->codcargo);
        $valor->descripcion = $cargo->descripcion;
        return $valor;
    }
}
