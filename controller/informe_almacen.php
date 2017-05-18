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
require_model('articulo.php');
require_model('cliente.php');
require_model('distribucion_conductores.php');
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('distribucion_ordenescarga_facturas.php');
/**
 * Description of informe_despachos
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_almacen extends fs_controller{
    public $desde;
    public $f_desde;
    public $hasta;
    public $f_hasta;
    public $almacen;
    public $articulo;
    public $albaran;
    public $factura;
    public $distribucion_conductores;
    public $distribucion_transporte;
    public $distribucion_lineastransporte;
    public $distribucion_ordenescarga_facturas;
    public $total_resultados;
    public $resultados;
    public $offset;
    public $fileName;
    public $fileNamePath;
    public function __construct() {
        parent::__construct(__CLASS__, 'Movimientos de Almacén', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->articulo = new articulo();
        $this->albaran = new albaran_cliente();
        $this->factura = new factura_cliente();
        $this->distribucion_transporte = new distribucion_transporte();
        $this->distribucion_lineastransporte = new distribucion_lineastransporte();
        $this->distribucion_ordenescarga_facturas = new distribucion_ordenescarga_facturas();
        
        $desde_p = \filter_input(INPUT_POST, 'desde');
        $desde_g = \filter_input(INPUT_GET, 'desde');
        $desde = ($desde_p)?$desde_p:$desde_g;      
        $this->desde = ($desde)?$desde:\date('01-m-Y');
        $this->f_desde = \date('Y-m-d',strtotime($this->desde));
        
        $hasta_p = \filter_input(INPUT_POST, 'hasta');
        $hasta_g = \filter_input(INPUT_GET, 'hasta');
        $hasta = ($hasta_p)?$hasta_p:$hasta_g;
        $this->hasta = ($hasta)?$hasta:\date('d-m-Y');
        $this->f_hasta = \date('Y-m-d',strtotime($this->hasta));
        
        $codalmacen_p = \filter_input(INPUT_POST, 'codalmacen');
        $codalmacen_g = \filter_input(INPUT_GET, 'codalmacen');
        $codalmacen = ($codalmacen_p)?$codalmacen_p:$codalmacen_g;
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        
        $accion_p = \filter_input(INPUT_POST, 'accion');
        $accion_g = \filter_input(INPUT_GET, 'accion');
        $accion = ($accion_p)?$accion_p:$accion_g;
        if($accion == 'buscar')
        {
            $this->buscar();
        }
        
    }
    
    public function buscar()
    {
        $this->resultados = array();
        $datos = array();
        if($this->codalmacen){
            $datos['codalmacen'] = $this->codalmacen;
        }
        $lineas_transportes = $this->distribucion_lineastransporte->lista($this->empresa->id, $datos, $this->f_desde, $this->f_hasta);
        $this->resultados = $lineas_transportes['resultados'];
        $this->total_resultados = $lineas_transportes['cantidad'];

    }
    
    public function shared_extensions()
    {
        
    }
    
    public function paginas()
    {
        
    }
}

