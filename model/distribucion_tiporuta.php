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

/**
 * Description of distribucion_tiporuta
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_tiporuta extends fs_model {
    public $id;
    public $descripcion;
    public $estado;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_tiporuta','plugins/distribucion/');
        if($t)
        {
            $this->id = $t['id'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
        }
        else
        {
            $this->id = null;
            $this->descripcion = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    public function url(){
        return "index.php?page=admin_distribucion";
    }
    
    protected function install() {
        return "INSERT INTO ".$this->table_name." (descripcion, estado, fecha_creacion, usuario_creacion) VALUES ".
            "('RUTA GENERICA',TRUE, '".\date('d-m-Y H:i:s')."','system');";
    }
    
    public function exists() {
        if(is_null($this->id))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM distribucion_tiporuta WHERE ".
                    "id = ".$this->intval($this->id).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE distribucion_tiporuta SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "id = ".$this->intval($this->id).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_tiporuta ( descripcion, estado, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->var2str($this->descripcion).", ".
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
        $sql = "DELETE FROM distribucion_tiporuta WHERE ".
                "id = ".$this->intval($this->id).";";
        return $this->db->exec($sql);
    }
    
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_tiporuta ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_tiporuta($d);
            }
        }
        return $lista;
    }
    
    public function activos()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_tiporuta WHERE estado = true ORDER BY id;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new distribucion_tiporuta($d);
            }
        }
        return $lista;
    }
    
    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM distribucion_tiporuta WHERE id = ".$this->intval($id).";");
        
        if($data)
        {
            $d = new distribucion_tiporuta($data[0]);
            return $d;
        }else{
            return false;
        }
        
    }
}
