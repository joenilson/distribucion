<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('unidadmedida.php');
/**
 * Description of articulo_unidadmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class articulo_unidadmedida extends fs_model {

    /**
     * Id de la unidad de medida
     * @var type integer
     */
    public $id;

    /**
     * Codigo del artículo
     * @var type varchar
     */
    public $referencia;

    /**
     * Se debe ingresar siempre la primera unidad de medida
     * como TRUE para que sea la unidad de medida base
     * @var type boolean
     */
    public $base;

    /**
     * Este factor de conversión se usará cuando el artículo
     * tenga más de una unidad de medida
     * @var type float
     */
    public $factor;
    /**
     * Para efectos de pesar la carga se pone un peso para
     * cada unidad de medida a utilizar
     * @var type float
     */
    public $peso;
    /**
     * Este es el tipo de unidad de medida configurado, puede ser de venta o de compra
     * para poder diferenciar al momento de imprimir un documento con unidad de medida
     * @var type varchar(12)
     */
    public $tipo;
    public $unidadmedida;
    public function __construct($t = FALSE) {
        parent::__construct('articulo_unidadmedida', 'plugins/distribucion/');
        if ($t) {
            $this->id = $t['id'];
            $this->referencia = $t['referencia'];
            $this->base = $this->str2bool($t['base']);
            $this->factor = floatval($t['factor']);
            $this->peso = floatval($t['peso']);
            $this->tipo = $t['tipo'];
        } else {
            $this->id = NULL;
            $this->referencia = NULL;
            $this->base = FALSE;
            $this->factor = NULL;
            $this->peso = NULL;
            $this->tipo = NULL;
        }
        $this->unidadmedida = new unidadmedida();
    }

    public function install() {
        return '';
    }

    public function info_adicional($item){
        if($this->unidadmedida->get($item->id)){
            $item->nombre_um = $this->unidadmedida->get($item->id)->name;
            $item->abrev_um = $this->unidadmedida->get($item->id)->abreviatura;
        }else{
            $item->nombre_um = 'NO EXISTE';
            $item->abrev_um = 'NE';
        }
        return $item;
    }


    public function all() {
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY referencia,base DESC,id";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $value = new articulo_unidadmedida($d);
                $item = $this->info_adicional($value);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get($referencia){
        $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($referencia)." ORDER BY base,id";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $value = new articulo_unidadmedida($d);
                $item = $this->info_adicional($value);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function getOne($id,$referencia){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id)." AND referencia = ".$this->var2str($referencia)." ORDER BY base,id";
        $data = $this->db->select($sql);
        if($data){
            $value = new articulo_unidadmedida($data[0]);
            $item = $this->info_adicional($value);
            return $item;
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->id) AND is_null($this->referencia)){
            return false;
        }else{
            return $this->getOne($this->id, $this->referencia);
        }
    }

    public function save(){
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    "peso = ".$this->var2str($this->peso).", ".
                    "factor = ".$this->var2str($this->factor).", ".
                    "base = ".$this->var2str($this->base).
                    "tipo = ".$this->var2str($this->tipo).
                    " WHERE ".
                    "id = ".$this->intval($this->id)." AND ".
                    "referencia = ".$this->var2str($this->referencia).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id, referencia, base, factor, peso, tipo) VALUES (".
                $this->intval($this->id).", ".
                $this->var2str($this->referencia).", ".
                $this->var2str($this->base).", ".
                $this->var2str($this->factor).", ".
                $this->var2str($this->peso).", ".
                $this->var2str($this->tipo).");";
        }
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->id)." AND referencia = ".$this->var2str($this->referencia).";";
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }

}
