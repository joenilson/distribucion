<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
require_model('almacenes.php');
require_model('distribucion_faltantes.php');
require_model('facturas_cliente.php');
require_model('facturas_cliente.php');
require_model('forma_pago.php');
/**
 * Description of informes_caja
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informes_caja extends fs_controller {
    public $almacenes;
    public $facturascli;
    public $facturaspro;
    public $faltantes;
    public $f_desde;
    public $f_hasta;
    public $codalmacen;
    public $total;
    public $ingresos;
    public $egresos;
    public $cobros;
    public $resultados_formas_pago;
    public function __construct() {
        parent::__construct(__CLASS__, 'Caja', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->almacenes = new almacen();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->faltantes = new distribucion_faltantes();
        $this->resultados_formas_pago = false;
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if(!$this->user->admin){
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
        }
        
        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('d-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');    
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = (isset($this->user->codalmacen))?$this->user->codalmacen:$codalmacen;
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $resultados = $this->resumen_movimientos();
                    $this->resultados_formas_pago = $resultados['formas_pago'];
                    break;
            }
        }
    }
   
    private function resumen_movimientos(){
        //Obtenemos las ventas que no estén anuladas y sacamos las que estén o no pagadas
        $fp = new FacturaScripts\model\forma_pago;
        $query_ventas = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_ventas = "SELECT * FROM facturascli WHERE $query_ventas";
        $lista_ingresos = $this->db->select($sql_ventas);
        
        //Obtenemos los cobros de faltantes
        $lista_cobro_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, TRUE);
        if($lista_cobro_faltantes){
            $pago_faltante['CONT'] = array();
            $pago_faltante_contador['CONT'] = array();
            foreach($lista_cobro_faltantes as $faltante){
                if(!isset($pago_faltante['CONT'][$faltante->coddivisa])){
                    $pago_faltante['CONT'][$faltante->coddivisa] = 0;
                    $pago_faltante_contador['CONT'][$faltante->coddivisa] = 0;
                }
                $pago_faltante['CONT'][$faltante->coddivisa] += $faltante->importe;
                $pago_faltante_contador['CONT'][$faltante->coddivisa] += 1;
            }
        }
        
        //Obtenemos los faltantes pendientes
        $lista_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, FALSE);
        if($lista_faltantes){
            $recibo_faltante['CONT'] = array();
            $recibo_faltante_contador['CONT'] = array();
            foreach($lista_faltantes as $faltante){
                if(!isset($recibo_faltante['CONT'][$faltante->coddivisa])){
                    $recibo_faltante['CONT'][$faltante->coddivisa] = 0;
                    $recibo_faltante_contador['CONT'][$faltante->coddivisa] = 0;
                }
                $recibo_faltante['CONT'][$faltante->coddivisa] += $faltante->importe;
                $recibo_faltante_contador['CONT'][$faltante->coddivisa] += 1;
            }
            $resultados_faltantes = array();
            foreach($recibo_faltante['CONT'] as $divisa=>$valor){
                $item = new stdClass();
                $item->codpago = 'CONT';
                $item->descpago = $fp->get('CONT')->descripcion;
                $item->cantidad = $recibo_faltante_contador['CONT'][$divisa];
                $item->divisa = $divisa;
                $item->importe = $valor;
                $resultados_faltantes[$divisa][] = $item;
            }
        }
        if($lista_ingresos){
            $formas_pago = array();
            $facturas_formas_pago = array();
            $facturascli_pagadas = array();
            $facturascli_porpagar = array();
            foreach($lista_ingresos as $f){
                $fac = new factura_cliente($f);
                if(!isset($formas_pago[$fac->codpago])){
                    $formas_pago[$fac->codpago] = array();
                    $facturas_formas_pago[$fac->codpago] = array();
                }
                if(!isset($formas_pago[$fac->codpago][$fac->coddivisa])){
                    $formas_pago[$fac->codpago][$fac->coddivisa] = 0;
                    $facturas_formas_pago[$fac->codpago][$fac->coddivisa] = 0;
                    $facturascli_pagadas[$fac->coddivisa] = 0;
                    $facturascli_porpagar[$fac->coddivisa] = 0;
                }
                if($fac->pagada){
                    $formas_pago[$fac->codpago][$fac->coddivisa] += $fac->total;
                    $facturas_formas_pago[$fac->codpago][$fac->coddivisa] += 1;
                    $facturascli_pagadas[$fac->coddivisa] += 1;
                }else{
                    $facturascli_porpagar[$fac->coddivisa] += 1;
                }
            }
            $resultados_formas_pago = array();
            foreach($formas_pago as $codpago=>$list){
                foreach($list as $divisa=>$v){
                    if($facturas_formas_pago[$codpago][$divisa]){
                        $item = new stdClass();
                        $item->codpago = $codpago;
                        $item->descpago = $fp->get($codpago)->descripcion;
                        $item->cantidad = $facturas_formas_pago[$codpago][$divisa];
                        $item->divisa = $divisa;
                        $item->importe = $v;
                        $resultados_formas_pago[$divisa][] = $item;
                    }
                }
            }
            $resultados_movimientos = array();
            return array('formas_pago'=>$resultados_formas_pago, 'resumen_movimientos'=>$resultados_movimientos,'faltantes_cobrados'=>'','faltantes_pendientes'=>'');
        }else{
            return false;
        }
    }
    
    public function shared_extensions(){
        
    }
}
