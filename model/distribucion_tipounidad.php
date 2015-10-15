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
            $this->estado = $t['estado'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->id = null;
            $this->idempresa = null;
            $this->descripcion = null;
            $this->estado = false;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->idempresa) and is_null($this->entidad) and is_null($this->tipo_entidad))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM ncf_entidad_tipo WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "entidad = ".$this->var2str($this->entidad)." AND ".
                    "tipo_entidad = ".$this->var2str($this->tipo_entidad).
                    ";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE ncf_entidad_tipo SET ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "entidad = ".$this->var2str($this->entidad)." AND ".
                    "tipo_entidad = ".$this->var2str($this->tipo_entidad).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO ncf_entidad_tipo (idempresa, entidad, tipo_entidad,tipo_comprobante, estado, usuario_creacion, fecha_creacion ) VALUES ".
                    "(".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->entidad).", ".
                    $this->var2str($this->tipo_entidad).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    ($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                $this->entidad = $this->entidad;
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        return $this->db->exec("UPDATE ncf_entidad_tipo SET estado = ".$this->var2str($this->estado)." WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "entidad = ".$this->var2str($this->entidad)." AND ".
                "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
    }
}
