<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
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

/**
 * Description of distribucion_tipounidad
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_tipounidad extends fs_model {
    public $id;
    public $idempresa;
    public $descripcion;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_tipounidad','plugins/distribucion/');
        if($t)
        {
            $this->id = $t['id'];
            $this->idempresa = $t['idempresa'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:S', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
        }
        else
        {
            $this->id = null;
            $this->idempresa = null;
            $this->descripcion = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i:s');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=admin_distribucion";
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->id))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_tipounidad WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "id = ".$this->intval($this->id).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_tipounidad SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "id = ".$this->intval($this->id).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $this->id = $this->generate_id();
            $sql = "INSERT INTO distribucion_tipounidad (id, idempresa, descripcion, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->id).", ".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->descripcion).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                $this->id = $this->id;
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        $sql = "DELETE FROM distribucion_tipounidad WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "id = ".$this->intval($this->id).";";
        return $this->db->exec($sql);
    }
    
    public function generate_id(){
        $dataId = $this->db->select("SELECT max(id) AS id FROM distribucion_tipounidad WHERE idempresa = ".$this->intval($this->idempresa).";");
        $newId = $dataId[0]['id'];
        $newId++;
        return $newId;
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_tipounidad WHERE idempresa = ".$this->intval($idempresa)." ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_tipounidad($d);
            }
        }
        return $lista;
    }
    
    public function activos($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_tipounidad WHERE idempresa = ".$this->intval($idempresa)." and estado = true ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_tipounidad($d);
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$id)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_tipounidad WHERE idempresa = ".$this->intval($idempresa)." AND id = ".$this->intval($id)." ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_tipounidad($d);
            }
        }
        return $lista;
    }
}
