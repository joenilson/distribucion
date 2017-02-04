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
require_model('facturas_proveedor.php');
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
    public $ingresos_condpago;
    public $cobros_condpago;
    public $egresos;
    public $egresos_condpago;
    public $pagos_condpago;
    public $cobros;
    public $resultados_cobros;
    public $resultados_pendientes;
    public $resultados_ingresos;
    public $resultados_egresos;
    public $resultados_egresos_formas_pago;
    public $resultados_formas_pago;
    public $resultados_faltantes_cobrados;
    public $resultados_faltantes_pendientes;
    public $total_ingresos;
    public $total_cobros;
    public $total_pendientes_cobro;
    public $total_egresos;
    public $total_pagos;
    public $total_pendientes_pago;
    public $total_general;
    public $total_ventas;
    public $total_faltantes;
    public $total_compras;
    public $pagadas;
    public $pendientes;
    public $fp;
    public function __construct() {
        parent::__construct(__CLASS__, 'Caja', 'informes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->almacenes = new almacen();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->faltantes = new distribucion_faltantes();
        $this->fp = new forma_pago();
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
                    $this->pagadas = array();
                    $this->pendientes = array();
                    $this->generar_formas_pago();
                    $resultados = $this->resumen_movimientos();
                    $this->resultados_formas_pago = $resultados['formas_pago'];
                    $this->resultados_faltantes_cobrados = $resultados['faltantes_cobrados'];
                    $this->resultados_faltantes_pendientes = $resultados['faltantes_pendientes'];
                    $this->resultados_egresos_formas_pago = $resultados['egresos_formas_pago'];
                    $this->resultados_egresos = $resultados['egresos'];
                    $this->total_general = 0;
                    $this->ingresos();
                    $this->egresos();
                break;
            }
        }
    }

    private function movimientos(){

    }

    private function generar_formas_pago(){
        foreach($this->fp->all() as $fp){
            $this->ingresos_condpago[$fp->codpago] = 0;
            $this->cobros_condpago[$fp->codpago] = 0;
            $this->egresos_condpago[$fp->codpago] = 0;
            $this->pagos_condpago[$fp->codpago] = 0;
        }
    }

    /**
     * //Buscamos todos los ingresos, ya seán por ventas o por cobros de faltantes
     */
    private function ingresos(){
        $this->total_ingresos = 0;
        $this->total_cobros = 0;
        $this->total_pendientes_cobro = 0;
        $this->total_ventas = 0;
        $this->total_faltantes = 0;
        $this->pagadas['ventas'] = 0;
        $this->pagadas['faltantes'] = 0;
        $this->pendientes['ventas'] = 0;
        $this->pendientes['faltantes'] = 0;
        //Obtenemos las ventas que no estén anuladas y sacamos las que estén o no pagadas
        $query_ventas = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_ventas = "SELECT * FROM facturascli WHERE $query_ventas";
        $lista_ventas = $this->db->select($sql_ventas);
        if($lista_ventas){
            foreach($lista_ventas as $d){
                $factura = new factura_cliente($d);
                $factura->total = ($this->empresa->coddivisa == $factura->coddivisa)?$factura->total:$this->euro_convert($this->divisa_convert($factura->total, $factura->coddivisa, 'EUR'));
                if($factura->pagada){
                    $pago_venta = $factura->get_asiento_pago();
                    if($pago_venta){
                        if(\date('Y-m-d',strtotime($pago_venta->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($pago_venta->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){
                            //Esta pagada a la fecha buscada
                            $this->total_cobros += $factura->total;
                            $this->pagadas['ventas'] += $factura->total;
                            $this->cobros_condpago[$factura->codpago] += $factura->total;
                        }else{
                            //Esta pendiente a la fecha buscada
                            $this->total_pendientes_cobro += $factura->total;
                            $this->pendientes['ventas'] += $factura->total;
                        }
                    }else{
                        //Asumimos que va aparecer en esta fecha
                        $this->total_cobros += $factura->total;
                        $this->pagadas['ventas'] += $factura->total;
                        $this->cobros_condpago[$factura->codpago] += $factura->total;
                    }
                }else{
                    $this->total_pendientes_cobro += $factura->total;
                    $this->pendientes['ventas'] += $factura->total;
                }
                $this->total_ventas += $factura->total;
                $this->total_ingresos += $factura->total;
                $this->ingresos_condpago[$factura->codpago] += $factura->total;
            }
        }

        //Obtenemos los cobros de faltantes
        $recibos_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, FALSE);
        if($recibos_faltantes){
            foreach($recibos_faltantes as $faltante){
                if($faltante->estado == 'pagado' and ($faltante->fechap>=\date('Y-m-d',strtotime($this->f_desde)) AND $faltante->fechap>=\date('Y-m-d',strtotime($this->f_hasta)))){
                    $faltante->importe = ($this->empresa->coddivisa == $faltante->coddivisa)?$faltante->importe:$this->euro_convert($this->divisa_convert($faltante->importe, $faltante->coddivisa, 'EUR'));
                    $this->total_cobros += $faltante->importe;
                    $this->pagadas['faltantes'] += $faltante->importe;
                    $this->cobros_condpago['CONT'] += $faltante->importe;
                }else{
                    $this->total_pendientes_cobro += $faltante->importe;
                    $this->pendientes['faltantes'] += $faltante->importe;
                }
                $this->total_faltantes += $faltante->importe;
                $this->total_ingresos += $faltante->importe;
                $this->cobros_condpago['CONT'] += $faltante->importe;
            }
            $this->total_general += $this->total_ingresos;
            $this->ingresos_condpago['CONT'] += $faltante->importe;
        }
    }

    private function egresos(){
        $this->total_egresos = 0;
        $this->total_pagos = 0;
        $this->total_pendientes_pago = 0;
        $this->total_compras = 0;
        $this->total_faltantes = 0;
        $this->pagadas['compras'] = 0;
        $this->pagadas['faltantes_compras'] = 0;
        $this->pendientes['compras'] = 0;
        $this->pendientes['faltantes_compras'] = 0;
        //Obtenemos las compras que no estén anuladas y sacamos las que estén o no pagadas
        $query_compras = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_compras = "SELECT * FROM facturasprov WHERE $query_compras";
        $lista_compras = $this->db->select($sql_compras);
        //Obtenemos las facturas de compra por pagar
        if($lista_compras){
            foreach($lista_compras as $f){
                $factura = new factura_proveedor($f);
                $factura->total = ($this->empresa->coddivisa == $factura->coddivisa)?$factura->total:$this->euro_convert($this->divisa_convert($factura->total, $factura->coddivisa, 'EUR'));
                if($factura->pagada){
                    $pago_compra = $factura->get_asiento_pago();
                    if($pago_compra){
                        if(\date('Y-m-d',strtotime($pago_compra->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($pago_compra->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){
                            //Esta pagada a la fecha buscada
                            $this->total_pagos += $factura->total;
                            $this->pagadas['compras'] += $factura->total;
                            $this->pagos_condpago[$factura->codpago] += $factura->total;
                        }else{
                            //Esta pendiente a la fecha buscada
                            $this->total_pendientes_pago += $factura->total;
                            $this->pendientes['compras'] += $factura->total;
                        }
                    }else{
                        //Asumimos que va aparecer en esta fecha
                        $this->total_pagos += $factura->total;
                        $this->pagadas['compras'] += $factura->total;
                        $this->pagos_condpago[$factura->codpago] += $factura->total;
                    }
                }else{
                    $this->total_pendientes_pago += $factura->total;
                    $this->pendientes['compras'] += $factura->total;
                }
                $this->total_egresos += $factura->total;
                $this->total_compras += $factura->total;
                $this->egresos_condpago[$factura->codpago] += $factura->total;
            }
        }
        $this->total_general += $this->total_egresos;
    }

    private function resumen_movimientos(){
        $fp = new FacturaScripts\model\forma_pago;
        //Obtenemos las compras que no estén anuladas y sacamos las que estén o no pagadas
        $query_compras = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_compras = "SELECT * FROM facturasprov WHERE $query_compras";
        $lista_egresos = $this->db->select($sql_compras);
        $resultados_egresos_formas_pago = array();
        //Obtenemos las facturas de compra por pagar
        if($lista_egresos){
            $formas_pago = array();
            $facturasprov_formas_pago = array();
            $facturasprov_pagadas = array();
            $facturasprov_porpagar = array();
            foreach($lista_egresos as $f){
                $fac = new factura_proveedor($f);
                $pago_egreso = $fac->get_asiento_pago();
                if($pago_egreso){
                    if(\date('Y-m-d',strtotime($pago_egreso->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($pago_egreso->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){
                        //Esta pagada
                    }else{
                        //Esta pendiente
                    }
                }else{
                    //No tiene asiento de pago
                }
                if(!isset($formas_pago[$fac->codpago])){
                    $formas_pago[$fac->codpago] = array();
                    $facturasprov_formas_pago[$fac->codpago] = array();
                }
                if(!isset($formas_pago[$fac->codpago][$fac->coddivisa])){
                    $formas_pago[$fac->codpago][$fac->coddivisa] = 0;
                    $facturasprov_formas_pago[$fac->codpago][$fac->coddivisa] = 0;
                    $facturasprov_pagadas[$fac->coddivisa] = 0;
                    $facturasprov_porpagar[$fac->coddivisa] = 0;
                }
                if($fac->pagada){
                    $formas_pago[$fac->codpago][$fac->coddivisa] += $fac->total;
                    $facturasprov_formas_pago[$fac->codpago][$fac->coddivisa] += 1;
                    $facturasprov_pagadas[$fac->coddivisa] += 1;
                }else{
                    $facturasprov_porpagar[$fac->coddivisa] += 1;
                }
            }

            foreach($formas_pago as $codpago=>$list){
                foreach($list as $divisa=>$v){
                    if($facturasprov_formas_pago[$codpago][$divisa]){
                        $item = new stdClass();
                        $item->codpago = $codpago;
                        $item->descpago = $fp->get($codpago)->descripcion;
                        $item->cantidad = $facturasprov_formas_pago[$codpago][$divisa];
                        $item->divisa = $divisa;
                        $item->importe = $v;
                        $resultados_egresos_formas_pago[$divisa][] = $item;
                    }
                }
            }
        }

        //Obtenemos las ventas que no estén anuladas y sacamos las que estén o no pagadas
        $query_ventas = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_ventas = "SELECT * FROM facturascli WHERE $query_ventas";
        $lista_ingresos = $this->db->select($sql_ventas);

        //Obtenemos los cobros de faltantes
        $lista_cobro_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, TRUE);
        $resultados_faltantes_cobrados = array();
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

            foreach($pago_faltante['CONT'] as $divisa=>$valor){
                if($pago_faltante_contador['CONT'][$divisa]){
                    $item = new stdClass();
                    $item->codpago = 'CONT';
                    $item->descpago = $fp->get('CONT')->descripcion;
                    $item->cantidad = $pago_faltante_contador['CONT'][$divisa];
                    $item->divisa = $divisa;
                    $item->importe = $valor;
                    $resultados_faltantes_cobrados[$divisa][] = $item;
                }
            }
        }

        //Obtenemos los faltantes pendientes
        $lista_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, FALSE);
        $resultados_faltantes_pendientes = array();
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

            foreach($recibo_faltante['CONT'] as $divisa=>$valor){
                if($recibo_faltante_contador['CONT'][$divisa]){
                    $item = new stdClass();
                    $item->codpago = 'CONT';
                    $item->descpago = $fp->get('CONT')->descripcion;
                    $item->cantidad = $recibo_faltante_contador['CONT'][$divisa];
                    $item->divisa = $divisa;
                    $item->importe = $valor;
                    $resultados_faltantes_pendientes[$divisa][] = $item;
                }
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
            $resultados_egresos = array();
            return array('formas_pago'=>$resultados_formas_pago,
                'resumen_movimientos'=>$resultados_movimientos,
                'faltantes_cobrados'=>$resultados_faltantes_cobrados,
                'faltantes_pendientes'=>$resultados_faltantes_pendientes,
                'egresos_formas_pago'=>$resultados_egresos_formas_pago,
                'egresos'=>$resultados_egresos);
        }else{
            return false;
        }
    }

    public function shared_extensions(){

    }
}
