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
 * Description of distribucion_clientes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_clientes extends fs_model {
    public $idempresa;
    public $codcliente;
    public $ruta;
    public $canal;
    public $subcanal;
    public $coordenadas;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('distribucion_clientes','plugins/distribucion/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codcliente = $t['codcliente'];
            $this->ruta = $t['ruta'];
            $this->canal = $t['canal'];
            $this->subcanal = $t['subcanal'];
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
            $this->ruta = null;
            $this->canal = null;
            $this->subcanal = null;
            $this->coordenadas = null;
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
    
    public function exists() {
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
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
                    "canal = ".$this->var2str($this->canal).", ".
                    "subcanal = ".$this->var2str($this->subcanal).", ".
                    "ruta = ".$this->var2str($this->ruta).", ".
                    "coordenadas = ".$this->var2str($this->coordenadas).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO distribucion_clientes ( idempresa, codcliente, ruta, canal, subcanal, coordenadas, usuario_creacion, fecha_creacion ) VALUES (".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codcliente).", ".
                    $this->var2str($this->ruta).", ".
                    $this->var2str($this->canal).", ".
                    $this->var2str($this->subcanal).", ".
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
        $sql = "DELETE FROM distribucion_clientes WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }
    
    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." ORDER BY ruta, canal, subcanal, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
       
    public function clientes_ruta($idempresa,$ruta)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND ruta = ".$this->var2str($ruta)." ORDER BY ruta, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function clientes_canal($idempresa,$canal)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND canal = ".$this->var2str($canal)." ORDER BY canal, ruta, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }

    public function clientes_subcanal($idempresa,$subcanal)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND subcanal = ".$this->var2str($subcanal)." ORDER BY subcanal, ruta, codcliente;");
        
        if($data)
        {
            foreach($data as $d)
            {
                $value = new distribucion_clientes($d);
                $lista[] = $value;
            }
        }
        return $lista;
    }
    
    public function get($idempresa,$codcliente)
    {
        $data = $this->db->select("SELECT * FROM distribucion_clientes WHERE idempresa = ".$this->intval($idempresa)." AND codcliente = ".$this->var2str($codcliente).";");
        if($data)
        {
            $resultado = new distribucion_clientes($data[0]);
            return $resultado;
        }else{
            return false;
        }
    }
}
