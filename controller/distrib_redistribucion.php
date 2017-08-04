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
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_segmentos.php');
/**
 * Description of distrib_redistribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_redistribucion extends fs_controller{
    public $almacen;
    public $codalmacen;
    public $rutas_almacen;
    public $total_rutas_almacen;
    public $distribucion_clientes;
    public $distribucion_rutas;
    public $distribucion_segmentos;
    public $rutas;
    public $ruta_origen;
    public $ruta_destino;
    public $cliente;
    public $clientes_origen;
    public $clientes_destino;
    public $canales;
    public $subcanales;
    public $selector_habilitado;
    public function __construct() {
        parent::__construct(__CLASS__, '7 - Redistribucion Clientes', 'distribucion', TRUE, TRUE);
    }
    
    public function private_core(){
        $this->almacen = new almacen();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_segmentos = new distribucion_segmentos();
        $this->rutas_almacen = '';
        $this->ruta_origen = '';
        $this->ruta_destino = '';
        $this->transferidos = array();
        $this->notransferidos = array();
        $this->canales = $this->distribucion_segmentos->activos_tiposegmento($this->empresa->id, 'CANAL');
        $this->subcanales = $this->distribucion_segmentos->activos_tiposegmento($this->empresa->id, 'SUBCANAL');
        $type = filter_input(INPUT_GET, 'type');
        $codigo_almacen = filter_input(INPUT_GET, 'codalmacen');
        $this->codalmacen = (isset($codigo_almacen))?$codigo_almacen:'';
        $codigo_rutaorigen = filter_input(INPUT_GET, 'ruta_origen');
        $this->ruta_origen = (isset($codigo_rutaorigen))?$codigo_rutaorigen:'';
        $codigo_rutadestino = filter_input(INPUT_GET, 'ruta_destino');
        $this->ruta_destino = (isset($codigo_rutadestino))?$codigo_rutadestino:'';
        if(isset($type)){
            switch($type){
               case "almacen":
                   $this->codalmacen = filter_input(INPUT_POST, 'codalmacen');
                   break;
               case "ruta_origen":
                   $this->ruta_origen = filter_input(INPUT_POST, 'ruta_origen');
                   break;
               case "ruta_destino":
                   $this->ruta_destino = filter_input(INPUT_POST, 'ruta_destino');
                   break;
               case "buscar-rutas":
                   $this->buscar_rutas();
                   break;
               case "transferir":
                   $this->transferir();
                   break;
               default:
                   
                   break;
            }
        }
        if(!empty($this->codalmacen)){
            $this->rutas_almacen = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
            $this->total_rutas_almacen = count($this->rutas_almacen);
            $this->selector_habilitado = ($this->total_rutas_almacen>1)?'':'disabled';
        }
        
        if($this->ruta_origen == 'noruta'){
            $this->clientes_origen = $this->distribucion_clientes->clientes_sinruta($this->empresa->id, $this->almacen->get($this->codalmacen));
        }else{
            $this->clientes_origen = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $this->codalmacen, $this->ruta_origen);
        }
        $this->clientes_destino = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $this->codalmacen, $this->ruta_destino);
        
    }
    
    public function buscar_rutas()
    {
        $rutas = new distribucion_rutas();
        $query = \filter_input(INPUT_GET, 'q');
        $almacen = \filter_input(INPUT_GET, 'codalmacen');
        $data = $rutas->search($almacen,$query);
        $lista = array();
        $lista[] = array('value'=>'Sin Ruta','data'=>'noruta');
        foreach($data as $r){
            $lista[] = array('value' => $r->ruta.' - '.$r->descripcion, 'data' => $r->ruta);
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode(array('query'=>$query,'suggestions'=>$lista));
    }
    
    public function transferir(){
        $clientes = explode(",",filter_input(INPUT_GET, 'clientes'));
        $canal = filter_input(INPUT_GET, 'canal');
        $subcanal = filter_input(INPUT_GET, 'subcanal');
        $codalmacen = filter_input(INPUT_GET, 'codalmacen');
        $distribucion_cliente = new distribucion_clientes();
        $exito = 0;
        $error = 0;
        $total = count($clientes);
        foreach($clientes as $c){
            $datos_cliente = explode('-',$c);
            $cliente = $datos_cliente[0];
            $cliente_direccion = $datos_cliente[1];
            if($this->ruta_origen == 'noruta'){
                $nuevo_reg = new distribucion_clientes();
                $nuevo_reg->idempresa = $this->empresa->id;
                $nuevo_reg->codalmacen = $codalmacen;
                $nuevo_reg->codcliente = $cliente;
                $nuevo_reg->iddireccion = $cliente_direccion;
                $nuevo_reg->ruta = $this->ruta_destino;
                $nuevo_reg->canal = $canal;
                $nuevo_reg->subcanal = $subcanal;
                $nuevo_reg->fecha_creacion = \Date('d-m-Y H:i:s');
                $nuevo_reg->usuario_creacion = $this->user->nick;
                if($nuevo_reg->save()){
                    $exito++;
                }else{
                    $error++;
                }
            }else{
                $cr0 = $distribucion_cliente->ruta_cliente($this->empresa->id, $codalmacen, $cliente, $cliente_direccion, $this->ruta_origen);
                if($cr0->transferir($this->ruta_destino)){
                    $exito++;
                }else{
                    $error++;
                }
            }
        }
        $mensaje = "Se transfirieron $exito de $total clientes";
        $valor = ($total == $exito)?TRUE:FALSE;
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode( array('success' => $valor, 'mensaje' => $mensaje) );
    }
    
}
