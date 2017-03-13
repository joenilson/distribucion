<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
require_model('almacen.php');
require_model('agente.php');
require_model('distribucion_faltantes.php');
/**
 * Description of distrib_faltantes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_faltantes extends fs_controller{
    public $agente;
    public $almacenes;
    public $almacen;
    public $fecha_pago;
    public $distribucion_faltantes;
    public $resultados_faltantes;
    public $total_faltantes;
    public $total_resultados;
    public $offset;
    public $mostrar;
    public $query;
    public $desde;
    public $hasta;
    public $codalmacen;
    public $conductor;
    public $allow_delete;
    public function __construct() {
        parent::__construct(__CLASS__, 'Liquidar Faltantes', 'Caja', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->allow_delete = ($this->user->admin)?true:$this->user->allow_delete_on(__CLASS__);
        $this->mostrar = 'todo';
        
        $this->distribucion_faltantes = new distribucion_faltantes();
        $this->almacenes = new almacen();
        
        $this->fecha_pago = \date('d-m-Y');
        $fecha_pago = filter_input(INPUT_POST, 'fecha_pago');
        $this->fecha_pago = ($fecha_pago)?$fecha_pago:\date('d-m-Y');

        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if($this->user->admin){
            $this->listado_faltantes = $this->distribucion_faltantes->all($this->empresa->id);
        }else{
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $this->listado_faltantes = $this->distribucion_faltantes->all_almacen($this->empresa->id, $cod->codalmacen);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
            $this->codalmacen = $cod->codalmacen;
        }

        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            if($accion=='cobrar'){
                $this->cobrar_faltante();
            }
        }
        
        //Si se eligió un almacen o se proceso el listado se vuelve a cargar los faltantes del almacen
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = ($codalmacen)?$codalmacen:$this->codalmacen;
        if(!empty($this->codalmacen)){
            $this->listado_faltantes = $this->distribucion_faltantes->all_almacen($this->empresa->id, $this->codalmacen);
        }
        
        $fecha_inicio = \filter_input(INPUT_POST, 'fecha_desde');
        if($fecha_inicio){
            $this->desde = $fecha_inicio;
        }
        
        $fecha_fin = \filter_input(INPUT_POST, 'fecha_hasta');
        if($fecha_fin){
            $this->hasta = $fecha_fin;
        }
        
        if(isset($_REQUEST['mostrar'])){
            $this->mostrar = $_REQUEST['mostrar'];
            $this->listado_faltantes = $this->mostrar_informacion($_REQUEST['mostrar']);
        }
        
        //Totalizamos por Divisa los faltantes
        if($this->listado_faltantes){
            $total_faltantes = array();
            $total_pagos = array();
            $total_saldo = array();
            foreach($this->listado_faltantes as $faltante){
                if(!isset($total_faltantes[$faltante->coddivisa])){
                    $total_faltantes[$faltante->coddivisa]=0;
                    $total_pagos[$faltante->coddivisa]=0;
                    $total_saldo[$faltante->coddivisa]=0;
                }
                $total_faltantes[$faltante->coddivisa]+=$faltante->importe;
                $total_pagos[$faltante->coddivisa]+=$faltante->importe_pagos;
                $total_saldo[$faltante->coddivisa]+=$faltante->importe_saldo;
            }
            $lista = array();
            foreach($total_faltantes as $divisa=>$monto){
                $item = new stdClass();
                $item->divisa = $divisa;
                $item->faltante = $monto;
                $item->pago = $total_pagos[$divisa];
                $item->saldo = $total_saldo[$divisa];
                $lista[$divisa] = $item;
            }
            $this->total_faltantes = $lista;
        }else{
            $this->total_faltantes[$this->empresa->coddivisa]=0;
        }
        $this->total_resultados = count($this->listado_faltantes);
    }
    
    public function mostrar_informacion($solicitud){
        if($solicitud == 'buscar'){
            return $this->distribucion_faltantes->buscar($this->empresa->id, $this->codalmacen, $this->desde, $this->hasta, $this->conductor);
        }
    }
    
    public function cobrar_faltante(){
        $idrecibo = filter_input(INPUT_POST, 'idrecibo');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $monto_pago = filter_input(INPUT_POST, 'monto_pago');
        $tipo_pago = filter_input(INPUT_POST, 'tipo_pago');
        $fecha_pago = filter_input(INPUT_POST, 'fecha_pago');
        $recibo_origen = $this->distribucion_faltantes->get_by_recibo($this->empresa->id, $codalmacen, $idrecibo);
        if($recibo_origen){
            $recibo_pago = clone $recibo_origen;
            $recibo_pago->idreciboref = $idrecibo;
            $recibo_pago->idrecibo = null;
            $recibo_pago->fecha = \date('Y-m-d',strtotime($fecha_pago));
            $recibo_pago->fechap = \date('Y-m-d',strtotime($fecha_pago));
            $recibo_pago->estado = 'pagado';
            $recibo_pago->importe = floatval($monto_pago);
            $recibo_pago->usuario_creacion = $this->user->nick;
            $recibo_pago->fecha_creacion = \date('Y-m-d H:i:s');
            if($recibo_pago->save()){
                if(($recibo_origen->importe_saldo-$monto_pago)==0){
                    $recibo_origen->fechap = \date('Y-m-d',strtotime($fecha_pago));
                    $recibo_origen->estado = 'pagado';
                    $recibo_origen->usuario_modificacion = $this->user->nick;
                    $recibo_origen->fecha_modificacion = \date('Y-m-d H:i:s');
                    $recibo_origen->confirmar_pago();
                }
                $this->new_message('Pago del Faltante: '.$recibo_origen->idrecibo.' por '.$monto_pago.' hecho  al '.$tipo_pago.' en fecha '.\date('Y-m-d',strtotime($fecha_pago)).' Correctamente. queda pendiente: '.($recibo_origen->importe_saldo-$monto_pago));
            }
        }else{
            $this->new_error_msg('No se encontró un Faltante con la información proporcionada.'.$recibo_origen.' '.$idrecibo);
        }
    }
    
    public function paginas() {
        $url = $this->url()."&mostrar=".$this->mostrar
            ."&query=".$this->query
            ."&codalmacen=".$this->codalmacen
            ."&conductor=".$this->conductor
            ."&desde=".$this->desde
            ."&hasta=".$this->hasta;

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        if($this->mostrar == 'pendientes')
        {
            $total = $this->total_pendientes();
        }
        else if($this->mostrar == 'buscar')
        {
            $total = $this->total_resultados;
        }
        else
        {
            $total = $this->total_resultados;
        }

        /// añadimos todas la página
        while($num < $total)
        {
            $paginas[$i] = array(
                'url' => $url."&offset=".($i*FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num == $this->offset)
            );

            if($num == $this->offset)
            {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        return $paginas;
    }
    
    
    public function tratar_faltante(){
        
    }
    
    public function imprimir_recibo(){
        
    }
    
    public function share_extensions(){
        
    }
    
}
