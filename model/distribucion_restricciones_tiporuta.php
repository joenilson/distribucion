<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson@gmail.com>
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
require_model('articulo.php');
/**
 * Description of distribucion_restricciones_tiporuta
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_restricciones_tiporuta extends fs_model {
    public $id;
    public $referencia;
    public $usuario_creacion;
    public $fecha_creacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_restricciones_tiporuta','plugins/distribucion/');
        if($t)
        {
            $this->id = $t['id'];
            $this->referencia = $t['referencia'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
        }
        else
        {
            $this->id = null;
            $this->referencia = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
        }
    }
    
    public function url(){
        return "index.php?page=admin_distribucion";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->id) AND is_null($this->referencia))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_restricciones_tiporuta WHERE ".
                    "id = ".$this->intval($this->id)." AND referencia = ".$this->var2str($this->referencia).";");
        }
    }
    
    public function save() {
        if (!$this->exists())
        {
            $sql = "INSERT INTO distribucion_restricciones_tiporuta ( id, referencia, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->id).", ".
                    $this->var2str($this->referencia).", ".
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
        $sql = "DELETE FROM distribucion_restricciones_tiporuta WHERE ".
                "id = ".$this->intval($this->id)." AND referencia = ".$this->var2str($this->referencia).";";
        return $this->db->exec($sql);
    }
       
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_restricciones_tiporuta ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_restricciones_tiporuta($d);
            }
        }
        return $lista;
    }
    
    public function activos()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_restricciones_tiporuta WHERE estado = true ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_restricciones_tiporuta($d);
            }
        }
        return $lista;
    }
    
    public function get($id,$referencia)
    {
        $data = $this->db->select("SELECT * FROM distribucion_restricciones_tiporuta WHERE id = ".$this->intval($id)
        ." AND referencia = ".$this->var2str($referencia).";");
        
        if($data)
        {
            $d = new distribucion_restricciones_tiporuta($data[0]);
            return $d;
        }else{
            return false;
        }
        
    }
    
    public function get_idruta($id)
    {
        $data = $this->db->select("SELECT * FROM distribucion_restricciones_tiporuta WHERE id = ".$this->intval($id).";");
        $lista = array();
        if($data)
        {
            foreach($data as $d){
                $valor = new distribucion_restricciones_tiporuta($d);
                $linea = $this->info_adicional($valor);
                $lista[] = $linea;
            }
            return $lista;
        }else{
            return false;
        }
        
    }
    
    public function info_adicional($valor){
        $articulo = new articulo();
        $art = $articulo->get($valor->referencia);
        $valor->descripcion = $art->descripcion;
        $valor->codfamilia = $art->codfamilia;
        $valor->tags = array('Articulo');
        return $valor;
    }
}
