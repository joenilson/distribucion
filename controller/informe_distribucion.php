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
require_model('albaran_cliente.php');
require_model('factura_cliente.php');
require_model('distribucion_clientes.php');
require_model('distribucion_ordenescarga.php');
require_model('distribucion_transportes.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_facturas.php');
require_model('distribucion_faltantes.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('facturas_cliente.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
/**
 * Informe general de distribución
 * Cantidad de ordenes de carga procesadas
 * Cantidad de Transportes y su estado
 * Cantidad de Conduces sin Factura
 * Cantidad de Facturas sin Transporte asignado
 * Cantidad de Facturas pendientes de liquidar
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_distribucion extends fs_controller
{
    public $almacen;
    public $articulo;
    public $albaran;
    public $albaranes;
    public $factura;
    public $facturas_sin_cobrar;
    public $facturas_sin_transporte;
    public $distribucion_ordenescarga;
    public $distribucion_transportes;
    public $codalmacen;
    public $desde;
    public $hasta;
    public $f_desde;
    public $f_hasta;
    public $offset;
    public $ordenescarga;
    public $ordenescarga_no_cargada;
    public $ordenescarga_no_confirmada;
    public $total_ordenescarga;
    public $total_ordenescarga_no_cargada;
    public $total_ordenescarga_no_confirmada;
    public $transportes;
    public $transportes_no_despacho;
    public $transportes_no_devolucion;
    public $transportes_no_liquidacion;
    public $total_transportes;
    public $total_transportes_no_despacho;
    public $total_transportes_no_devolucion;
    public $total_transportes_no_liquidacion;
    public $total_albaranes;
    public $total_albaranes_sin_factura;
    public $total_facturas;
    public $total_facturas_sin_transporte;
    public $total_facturas_sin_liquidar;
    public function __construct() 
    {
        parent::__construct(__CLASS__, 'Distribución', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() 
    {
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->articulo = new articulo();
        $this->albaran = new albaran_cliente();
        $this->factura = new factura_cliente();
        $this->distribucion_ordenescarga = new distribucion_ordenescarga();
        $this->distribucion_transportes = new distribucion_transporte();
        
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
        
        $offset_p = \filter_input(INPUT_POST, 'offset');
        $offset_g = \filter_input(INPUT_GET, 'offset');
        $offset = ($offset_p)?$offset_p:$offset_g;
        $this->offset = ($offset)?$offset:0;
        
        $this->resumen_distribucion();
        
    }
    
    public function resumen_distribucion()
    {       
        
        $this->total_ordenescarga = $this->distribucion_ordenescarga->total_ordenescarga($this->empresa->id,$this->codalmacen,$this->f_desde,$this->f_hasta);
        $this->ordenescarga_no_cargada = $this->distribucion_ordenescarga->pendientes($this->empresa->id, 'cargado', $this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->total_ordenescarga_no_cargada = $this->distribucion_ordenescarga->total_pendientes($this->empresa->id, 'cargado', $this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->ordenescarga_no_confirmada = $this->distribucion_ordenescarga->pendientes($this->empresa->id, 'despachado', $this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->total_ordenescarga_no_confirmada = $this->distribucion_ordenescarga->total_pendientes($this->empresa->id, 'despachado', $this->codalmacen, $this->f_desde, $this->f_hasta);
        
        $this->total_transportes = $this->distribucion_transportes->total_transportes($this->empresa->id,$this->codalmacen,$this->f_desde,$this->f_hasta);
        $this->transportes_no_despacho = $this->distribucion_transportes->pendientes($this->empresa->id, 'despachado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->total_transportes_no_despacho = $this->distribucion_transportes->total_pendientes($this->empresa->id, 'despachado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->transportes_no_devolucion = $this->distribucion_transportes->pendientes($this->empresa->id, 'devolucionado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->total_transportes_no_devolucion = $this->distribucion_transportes->total_pendientes($this->empresa->id, 'devolucionado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->transportes_no_liquidacion = $this->distribucion_transportes->pendientes($this->empresa->id, 'liquidado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        $this->total_transportes_no_liquidacion = $this->distribucion_transportes->total_pendientes($this->empresa->id, 'liquidado',$this->codalmacen, $this->f_desde, $this->f_hasta);
        
        $this->albaranes();
        $this->facturas();
    }
    
    private function albaranes()
    {
        $lista = array();
        $query = '';
        if($this->codalmacen)
        {
            $query .= ' AND codalmacen = '.$this->almacen->var2str($this->codalmacen);
        }
        
        if($this->f_desde)
        {
            $query .= ' AND fecha >= '.$this->almacen->var2str($this->f_desde);
        }
        
        if($this->f_hasta)
        {
            $query .= ' AND fecha <= '.$this->almacen->var2str($this->f_hasta);
        }
        
        $sql_count = "SELECT count(*) as total from albaranescli WHERE ptefactura = TRUE ".$query.";";
        $data_count = $this->db->select($sql_count);
        $this->total_albaranes = $data_count[0]['total'];
        $sql = "SELECT * from albaranescli WHERE ptefactura = TRUE ".$query." ORDER BY codalmacen,fecha,idalbaran;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new albaran_cliente($d);
                $lista[] = $item;
            }
        }
        $this->albaranes = $lista;
        
    }
    
    private function facturas()
    {
        $lista_facturas_sin_cobrar = array();
        $lista_facturas_sin_transporte = array();
        $query = '';
        $query_2 = '';
        if($this->codalmacen)
        {
            $query .= ' AND codalmacen = '.$this->almacen->var2str($this->codalmacen);
            $query2 .= ' AND doc.codalmacen = '.$this->almacen->var2str($this->codalmacen);
        }
        
        if($this->f_desde)
        {
            $query .= ' AND fecha >= '.$this->almacen->var2str($this->f_desde);
            $query_2 .= ' AND doc.fecha >= '.$this->almacen->var2str($this->f_desde);
        }
        
        if($this->f_hasta)
        {
            $query .= ' AND fecha <= '.$this->almacen->var2str($this->f_hasta);
            $query_2 .= ' AND doc.fecha <= '.$this->almacen->var2str($this->f_hasta);
        }

        $sql_count = "SELECT count(*) as total from facturascli WHERE anulada = FALSE and idfacturarect IS NULL and pagada = FALSE and codpago = 'CONT' ".$query.";";
        $data_count = $this->db->select($sql_count);
        $this->total_facturas = $data_count[0]['total'];
        
        $sql = "SELECT * from facturascli WHERE anulada = FALSE and idfacturarect IS NULL and pagada = FALSE and codpago = 'CONT' ".$query." ORDER BY codalmacen,fecha,idfactura;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new factura_cliente($d);
                $lista_facturas_sin_cobrar[] = $item;
            }
        }
        $this->facturas_sin_cobrar = $lista_facturas_sin_cobrar;
        
        $sql_count_sin_transporte = "SELECT count(*) as total from facturascli WHERE ".
            "idfactura not in (SELECT dof.idfactura from distribucion_ordenescarga_facturas as dof, distribucion_ordenescarga as doc WHERE ".
            " doc.idordencarga = dof.idordencarga and doc.fecha = dof.fecha and estado = TRUE ".$query_2.")".
            " AND anulada = FALSE and idfacturarect IS NULL and pagada = FALSE and codpago = 'CONT' ".$query.";";
        $data_count_sin_transporte = $this->db->select($sql_count_sin_transporte);
        $this->total_facturas_sin_transporte = $data_count_sin_transporte[0]['total'];
        
        $sql_dof = "SELECT * from facturascli WHERE ".
            "idfactura not in (SELECT dof.idfactura from distribucion_ordenescarga_facturas as dof, distribucion_ordenescarga as doc WHERE ".
            " doc.idordencarga = dof.idordencarga and doc.fecha = dof.fecha and estado = TRUE ".$query_2.")".
            " AND anulada = FALSE and idfacturarect IS NULL and pagada = FALSE and codpago = 'CONT' ".$query." ORDER BY codalmacen,fecha,idfactura;";
        $data_dof = $this->db->select($sql_dof);
        if($data_dof)
        {
            foreach($data_dof as $d)
            {
                $item = new factura_cliente($d);
                $lista_facturas_sin_transporte[] = $item;
            }
        }
        $this->facturas_sin_transporte = $lista_facturas_sin_transporte;
        
    }
    
    public function shared_extensions() 
    {
        
    }
}
