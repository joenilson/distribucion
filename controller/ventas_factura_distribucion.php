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
require_model('factura_cliente.php');
require_model('serie.php');
require_model('cliente.php');
require_model('almacen.php');
require_model('distribucion_agente.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_transporte.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_segmentos.php');
require_model('distribucion_clientes.php');
require_model('distribucion_coordenadas_clientes.php');
require_model('distribucion_ordenescarga_facturas.php');
/**
 * Description of ventas_distribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ventas_factura_distribucion extends fs_controller {
    public $cliente;
    public $factura;
    public $rutas;
    public $organizacion;
    public $segmentos;
    public $distribucion_clientes;
    public $coordenadas_clientes;
    public $distribucion_agente;
    public $distribucion_conductores;
    public $distribucion_transporte;
    public $distribucion_ordenescarga_facturas;
    public $informacion;
    public function __construct() {
        parent::__construct(__CLASS__, 'Información de Distribución de Ventas', 'ventas', FALSE, FALSE);
    }
    
    protected function private_core() {
        $this->share_extension();
        $this->distribucion_agente = new distribucion_agente();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->coordenadas_clientes = new distribucion_coordenadas_clientes();
        $this->distribucion_conductores = new distribucion_conductores();
        $this->distribucion_transporte = new distribucion_transporte();
        $this->distribucion_ordenescarga_facturas = new distribucion_ordenescarga_facturas();
        $this->rutas = new distribucion_rutas();
        $fact0 = new factura_cliente();
        $this->factura = FALSE;
        if (\filter_input(INPUT_GET,'id')) {
            $this->factura = $fact0->get(\filter_input(INPUT_GET,'id'));
        }
        $accion = \filter_input(INPUT_POST,'accion');
        if ($this->factura) {
            if(isset($accion)){
                $this->actualizar_informacion();
            }
            $this->traer_informacion();
        } else {
            $this->new_error_msg('Factura no encontrada.', 'error', FALSE, FALSE);
        }
    }
    
    public function traer_informacion(){
        $cliente_info = $this->distribucion_clientes->get($this->empresa->id, $this->factura->codcliente);
        $ruta_info = $this->rutas->get($this->empresa->id, $this->factura->codalmacen, $cliente_info[0]->ruta);
        $factura_info = $this->distribucion_ordenescarga_facturas->get($this->empresa->id, $this->factura->idfactura, $this->factura->codalmacen);
        $transporte_info = ($factura_info)?$this->distribucion_transporte->get($this->empresa->id, $factura_info[0]->idtransporte, $this->factura->codalmacen):FALSE;
        $this->informacion = new stdClass();
        $this->informacion->transporte = ($transporte_info)?'<a href=\''.$transporte_info->url().'\'>'.$transporte_info->idtransporte.'</a>':'';
        $this->informacion->ordencarga = ($transporte_info)?$transporte_info->idordencarga:'';
        $this->informacion->fecha_transporte = ($transporte_info)?$transporte_info->fecha:'';
        $this->informacion->fechal_transporte = ($transporte_info)?$transporte_info->fechal:'';
        $this->informacion->fechad_transporte = ($transporte_info)?$transporte_info->fechad:'';
        $this->informacion->liquidado = ($transporte_info)?$transporte_info->liquidado:'';
        $this->informacion->conductor_nombre = ($transporte_info)?$transporte_info->conductor_nombre:'';
        $this->informacion->unidad_transporte = ($transporte_info)?$transporte_info->unidad:'';
        $this->informacion->ruta = ($cliente_info)?$cliente_info[0]->ruta.' '.$ruta_info->descripcion:'SIN RUTA';
        $this->informacion->vendedor = ($cliente_info)?$cliente_info[0]->nombre:'SIN VENDEDOR';
        $this->informacion->supervisor = ($ruta_info)?$ruta_info->nombre_supervisor:'SIN SUPERVISOR';
        $this->informacion->frecuencia_visita = ($ruta_info)?$ruta_info:false;
        
    }
    
    public function url(){
        if($this->factura){
            return 'index.php?page=ventas_factura&id='.$this->factura->idfactura;
        }else{
            return 'index.php?page=ventas_factura';
        }
    }
    
    private function share_extension() {
        $fsxet = new fs_extension();
        $fsxet->name = 'tab_distribucion';
        $fsxet->from = __CLASS__;
        $fsxet->to = 'ventas_factura';
        $fsxet->type = 'tab';
            $fsxet->text = '<span class="fa fa-truck" aria-hidden="true"></span>'
                . '<span class="hidden-xs">&nbsp; Distribucion</span>';
        $fsxet->save();
    }
}
