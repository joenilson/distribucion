<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
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
     * codum de la unidad de medida
     * @var type varchar(10)
     */
    public $codum;

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
     * Si esta unidad de medida es para compra se configura en true
     * puede tener en true el campo $se_vende.
     * @var type boolean
     */
    public $se_compra;
    /**
     * Si esta unidad de medida es para venta se configura en true
     * puede tener en true el campo $se_compra.
     * @var type boolean
     */
    public $se_vende;
    public $unidadmedida;
    public function __construct($t = FALSE) {
        parent::__construct('articulo_unidadmedida', 'plugins/distribucion/');
        if ($t) {
            $this->codum = $t['codum'];
            $this->referencia = $t['referencia'];
            $this->base = $this->str2bool($t['base']);
            $this->factor = floatval($t['factor']);
            $this->peso = floatval($t['peso']);
            $this->se_compra = $this->str2bool($t['se_compra']);
            $this->se_vende = $this->str2bool($t['se_vende']);
        } else {
            $this->codum = NULL;
            $this->referencia = NULL;
            $this->base = FALSE;
            $this->factor = NULL;
            $this->peso = NULL;
            $this->se_compra = FALSE;
            $this->se_vende = TRUE;
        }
        $this->unidadmedida = new unidadmedida();
    }

    public function install() {
        return '';
    }

    public function info_adicional($item){
        if($this->unidadmedida->get($item->codum)){
            $item->nombre_um = $this->unidadmedida->get($item->codum)->nombre;
        }else{
            $item->nombre_um = 'NO EXISTE';
        }
        return $item;
    }



    public function all() {
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY referencia,base DESC,codum";
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
        $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($referencia)." ORDER BY base,codum";
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

    public function getOne($codum,$referencia){
        $sql = "SELECT * FROM ".$this->table_name." WHERE codum = ".$this->var2str($codum)." AND referencia = ".$this->var2str($referencia)." ORDER BY base,codum";
        $data = $this->db->select($sql);
        if($data){
            $value = new articulo_unidadmedida($data[0]);
            $item = $this->info_adicional($value);
            return $item;
        }else{
            return false;
        }
    }

    public function getBase($referencia){
        $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($referencia)." and base = TRUE;";
        $data = $this->db->select($sql);
        if($data){
            $value = new articulo_unidadmedida($data[0]);
            $item = $this->info_adicional($value);
            return $item;
        }else{
            return false;
        }
    }

    public function getByTipo($referencia,$tipo='se_vende'){
        $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($referencia)." AND ".$tipo." = TRUE".";";
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

    public function exists() {
        if(!$this->getOne($this->codum, $this->referencia)){
            return false;
        }else{
            return $this->getOne($this->codum, $this->referencia);
        }
    }

    public function save(){
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    "peso = ".$this->var2str($this->peso).", ".
                    "factor = ".$this->var2str($this->factor).", ".
                    "base = ".$this->var2str($this->base).", ".
                    "se_compra = ".$this->var2str($this->se_compra).", ".
                    "se_vende = ".$this->var2str($this->se_vende).
                    " WHERE ".
                    "codum = ".$this->var2str($this->codum)." AND ".
                    "referencia = ".$this->var2str($this->referencia).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (codum, referencia, base, factor, peso, se_compra, se_vende) VALUES (".
                $this->var2str($this->codum).", ".
                $this->var2str($this->referencia).", ".
                $this->var2str($this->base).", ".
                $this->var2str($this->factor).", ".
                $this->var2str($this->peso).", ".
                $this->var2str($this->se_compra).", ".
                $this->var2str($this->se_vende).");";
        }
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE codum = ".$this->var2str($this->codum)." AND referencia = ".$this->var2str($this->referencia).";";
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }

}
