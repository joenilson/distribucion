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
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/distribucion/extras/distribucion_controller.php';
/**
 * Description of informe_despachos
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_almacen extends distribucion_controller{
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
    public $referencia;
    public $resultados;
    public $offset;
    public $fileName;
    public $fileNamePath;
    public $tablas;
    public function __construct() {
        parent::__construct(__CLASS__, 'Movimientos de Almacén', 'informes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        parent::private_core();
        $this->shared_extensions();
        $this->init_variables();

        $accion = $this->filter_request('accion');
        if ($accion == 'buscar') {
            $this->buscar();
        } elseif ($accion == 'buscar-articulos') {
            $this->buscar_articulo();
        }
    }

    public function init_variables()
    {
        $this->almacen = new almacen();
        $this->articulo = new articulo();
        $this->albaran = new albaran_cliente();
        $this->factura = new factura_cliente();
        $this->distribucion_transporte = new distribucion_transporte();
        $this->distribucion_lineastransporte = new distribucion_lineastransporte();
        $this->distribucion_ordenescarga_facturas = new distribucion_ordenescarga_facturas();
        $this->tablas = $this->db->list_tables();

        $desde = $this->filter_request('desde');
        $this->desde = $this->confirmarValor($desde,\date('01-m-Y'));
        $this->f_desde = \date('Y-m-d',strtotime($this->desde));

        $hasta = $this->filter_request('hasta');
        $this->hasta = $this->confirmarValor($hasta,\date('d-m-Y'));
        $this->f_hasta = \date('Y-m-d',strtotime($this->hasta));
        $codalmacen =  $this->filter_request('codalmacen');
        $this->codalmacen = $this->confirmarValor($codalmacen,false);
        $referencia = $this->filter_request('referencia');
        $this->referencia = $this->confirmarValor($referencia,false);
    }

    public function buscar_articulo()
    {
        $articulos = new articulo();
        $query = \filter_input(INPUT_GET, 'q');
        $data = $articulos->search($query);
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }


    public function buscar()
    {
        $this->resultados = array();
        $this->template = false;
        $datos = array();
        $almacenes = $this->almacen->all();
        if($this->codalmacen){
            $datos['codalmacen'] = $this->codalmacen;
            $almacenes = array($this->almacen->get($this->codalmacen));
        }

        $articulos = $this->articulos();
        if($this->referencia){
            $datos['referencia'] = $this->referencia;
            $articulos = array($this->articulo->get($this->referencia));
        }

        $resultado = array();
        $this->saldo_inicial($almacenes,$articulos,$resultado);

        $this->ingresos_articulos($resultado);

        $this->articulos_transportados($datos, $resultado);

        $this->articulos_regularizados($resultado);

        $this->articulos_trasladados($resultado);

        $this->procesar_resultado($resultado);

        $this->generar_excel();
        $data = array();
        $data['rows'] = $this->resultados;
        $data['filename'] = $this->fileNamePath;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function procesar_datos_resultado($datos, $referencia)
    {
        $saldo_anterior = array();
        $listado_final = array();
        foreach($datos as $fecha=>$lineas)
        {
            foreach($lineas as $lin)
            {
                if(!isset($saldo_anterior[$referencia]))
                {
                    $saldo_anterior[$referencia] = $lin->saldo;
                }
                $lin->saldo = $saldo_anterior[$referencia]+($lin->ingresos-$lin->total_final);
                $saldo_anterior[$referencia] += ($lin->ingresos-$lin->total_final);
                $descripcion = $lin->descripcion;
                $almacen = $lin->codalmacen;
            }
            $listado_final = array_merge($lineas,$listado_final);
        }
        $linea_nueva = new StdClass();
        $linea_nueva->codalmacen = $almacen;
        $linea_nueva->idtransporte = 'Saldo Final';
        $linea_nueva->referencia = $referencia;
        $linea_nueva->descripcion = $descripcion;
        $linea_nueva->fecha = $this->f_hasta;
        $linea_nueva->fechal = '';
        $linea_nueva->fechad = '';
        $linea_nueva->hora = '23:59:59';
        $linea_nueva->fecha_creacion = strtotime($this->f_hasta.' '.'23:59:59');
        $linea_nueva->cantidad = 0;
        $linea_nueva->devolucion = 0;
        $linea_nueva->total_final = 0;
        $linea_nueva->ingresos = 0;
        $linea_nueva->saldo = $saldo_anterior[$referencia];
        $listado_final[] = $linea_nueva;
        $this->resultados = array_merge($listado_final,$this->resultados);
    }

    public function procesar_resultado($resultado)
    {
        foreach($resultado as $referencia=>$datos)
        {
            ksort($datos);
            $this->procesar_datos_resultado($datos, $referencia);

        }
    }

    public function articulos_trasladados(&$resultado)
    {
        $lineas_traslados = $this->traslados();
        if(!empty($lineas_traslados)){
            foreach($lineas_traslados as $linea)
            {
                if(!isset($resultado[$linea->referencia][$linea->fecha]))
                {
                    $resultado[$linea->referencia][$linea->fecha] = array();
                }
                $linea->saldo = 0;
                $linea->fechal = '';
                $linea->fechad = '';
                $resultado[$linea->referencia][$linea->fecha_creacion][] = $linea;
            }
        }
    }

    public function articulos_regularizados(&$resultado)
    {
        $lineas_regularizaciones = $this->regularizaciones();
        if(!empty($lineas_regularizaciones)){
            foreach($lineas_regularizaciones as $linea){
                if(!isset($resultado[$linea->referencia][$linea->fecha]))
                {
                    $resultado[$linea->referencia][$linea->fecha] = array();
                }
                $linea->saldo = 0;
                $linea->fechal = '';
                $linea->fechad = '';
                $resultado[$linea->referencia][$linea->fecha_creacion][] = $linea;
            }
        }
    }

    public function articulos_transportados($datos, &$resultado)
    {
        $lineas_transportes = $this->distribucion_lineastransporte->lista($this->empresa->id, $datos, $this->f_desde, $this->f_hasta);
        if(!empty($lineas_transportes)){
            foreach($lineas_transportes['resultados'] as $linea){
                $hora = \date('H:i:s',strtotime($linea->fecha_creacion));
                if(!isset($resultado[$linea->referencia][strtotime($linea->fecha.' '.$hora)])){
                    $resultado[$linea->referencia][strtotime($linea->fecha.' '.$hora)] = array();
                }
                $linea_nueva = new StdClass();
                $linea_nueva->codalmacen = $linea->codalmacen;
                $linea_nueva->idtransporte = $linea->idtransporte;
                $linea_nueva->referencia = $linea->referencia;
                $linea_nueva->descripcion = $linea->descripcion;
                $linea_nueva->fecha = $linea->fecha;
                $linea_nueva->fechal = $linea->fechal;
                $linea_nueva->fechad = $linea->fechad;
                $linea_nueva->hora = $hora;
                $linea_nueva->fecha_creacion = strtotime($linea->fecha.' '.$hora);
                $linea_nueva->cantidad = $linea->cantidad;
                $linea_nueva->devolucion = ($this->fecha_rango($this->f_desde,$this->f_hasta,$linea->fechad,'dentro'))?$linea->devolucion:0;
                $linea_nueva->total_final = ($this->fecha_rango($this->f_desde,$this->f_hasta,$linea->fechad,'dentro'))?$linea->total_final:$linea->cantidad;
                $linea_nueva->ingresos = 0;
                $linea_nueva->saldo = 0;
                $resultado[$linea->referencia][$linea_nueva->fecha_creacion][] = $linea_nueva;
            }
        }
    }

    public function ingresos_articulos(&$resultado)
    {
        $lineas_ingresos = $this->ingresos();
        if(!empty($lineas_ingresos)){
            foreach($lineas_ingresos as $linea){
                if(!isset($resultado[$linea->referencia][$linea->fecha_creacion])){
                    $resultado[$linea->referencia][$linea->fecha_creacion] = array();
                }
                $linea->saldo = 0;
                $resultado[$linea->referencia][$linea->fecha_creacion][] = $linea;
            }
        }
    }

    public function saldo_inicial($almacenes,$articulos,&$resultado)
    {
        foreach($almacenes as $almacen){
            foreach($articulos as $art){
                //Saldo Inicial
                $saldo_ini = $this->saldo_articulo($art->referencia, $almacen->codalmacen);
                $linea_nueva = new StdClass();
                $linea_nueva->codalmacen = $almacen->codalmacen;
                $linea_nueva->idtransporte = 'Saldo Inicial';
                $linea_nueva->referencia = $art->referencia;
                $linea_nueva->descripcion = $art->descripcion;
                $linea_nueva->fecha = $this->f_desde;
                $linea_nueva->fechal = '';
                $linea_nueva->fechad = '';
                $linea_nueva->hora = '00:00:00';
                $linea_nueva->fecha_creacion = strtotime($this->f_desde.' '.'00:00:00');
                $linea_nueva->cantidad = 0;
                $linea_nueva->devolucion = 0;
                $linea_nueva->total_final = 0;
                $linea_nueva->ingresos = 0;
                $linea_nueva->saldo = $saldo_ini;
                if(!isset($resultado[$art->referencia][$linea_nueva->fecha_creacion])){
                    $resultado[$art->referencia][$linea_nueva->fecha_creacion] = array();
                }
                $resultado[$art->referencia][$linea_nueva->fecha_creacion][] = $linea_nueva;
            }
        }
    }

    public function articulos()
    {
        $lista = array();
        $sql = "SELECT referencia,descripcion FROM articulos where nostock = FALSE and bloqueado = FALSE order by referencia;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new StdClass();
                $item->referencia = $d['referencia'];
                $item->descripcion = $d['descripcion'];
                $lista[] = $item;
            }
        }
        return $lista;
    }

    public function ingresos()
    {
        $sql_aux = '';
        $sql_aux2 = '';
        $sql_aux3 = '';
        if($this->codalmacen)
        {
            $sql_aux .= 'AND codalmacen = '.$this->empresa->var2str($this->codalmacen);
            $sql_aux2 .= 'AND codalmacen = '.$this->empresa->var2str($this->codalmacen);
            $sql_aux3 .= 'AND codalmadestino = '.$this->empresa->var2str($this->codalmacen);
        }
        if($this->referencia)
        {
            $sql_aux .= 'AND referencia = '.$this->empresa->var2str($this->referencia);
            $sql_aux2 .= 'AND a.referencia = '.$this->empresa->var2str($this->referencia);
            $sql_aux3 .= 'AND a.referencia = '.$this->empresa->var2str($this->referencia);
        }
        $movimientos = array();
        //Facturas de compra sin albaran
        $sql_compras1 = "SELECT codalmacen,codigo,fecha,hora,referencia,descripcion,cantidad FROM lineasfacturasprov as lfp".
                " JOIN facturasprov as fp on (fp.idfactura = lfp.idfactura)".
                " WHERE anulada = FALSE and idalbaran IS NULL ".
                " AND fecha between ".$this->empresa->var2str($this->f_desde).' AND '.$this->empresa->var2str($this->f_hasta).$sql_aux.
                " ORDER BY fecha,hora,codalmacen,referencia,fp.idfactura";
        $data_Compras1 = $this->db->select($sql_compras1);
        if($data_Compras1)
        {
            foreach($data_Compras1 as $item)
            {
                $linea_nueva = new StdClass();
                $linea_nueva->codalmacen = $item['codalmacen'];
                $linea_nueva->idtransporte = 'Fact Compra '.$item['codigo'];
                $linea_nueva->referencia = $item['referencia'];
                $linea_nueva->descripcion = $item['descripcion'];
                $linea_nueva->fecha = $item['fecha'];
                $linea_nueva->fechal = '';
                $linea_nueva->fechad = '';
                $linea_nueva->hora = $item['hora'];
                $linea_nueva->fecha_creacion = strtotime($item['fecha'].' '.$item['hora']);
                $linea_nueva->cantidad = 0;
                $linea_nueva->devolucion = 0;
                $linea_nueva->total_final = 0;
                $linea_nueva->ingresos = $item['cantidad'];
                $movimientos[] = $linea_nueva;
            }
        }

        //Albaranes de compra
        $sql_compras2 = "SELECT codalmacen,codigo,fecha,hora,referencia, descripcion, cantidad FROM lineasalbaranesprov as lap".
                " JOIN albaranesprov as ap on (ap.idalbaran = lap.idalbaran)".
                " WHERE fecha between ".$this->empresa->var2str($this->f_desde).' AND '.$this->empresa->var2str($this->f_hasta).$sql_aux.
                " ORDER BY fecha,hora,ap.idalbaran";
        $data_Compras2 = $this->db->select($sql_compras2);
        if($data_Compras2)
        {
            foreach($data_Compras2 as $item)
            {
                $linea_nueva = new StdClass();
                $linea_nueva->codalmacen = $item['codalmacen'];
                $linea_nueva->idtransporte = 'Conduce Compra '.$item['codigo'];
                $linea_nueva->referencia = $item['referencia'];
                $linea_nueva->descripcion = $item['descripcion'];
                $linea_nueva->fecha = $item['fecha'];
                $linea_nueva->fechal = '';
                $linea_nueva->fechad = '';
                $linea_nueva->hora = $item['hora'];
                $linea_nueva->fecha_creacion = strtotime($item['fecha'].' '.$item['hora']);
                $linea_nueva->cantidad = 0;
                $linea_nueva->devolucion = 0;
                $linea_nueva->total_final = 0;
                $linea_nueva->ingresos = $item['cantidad'];
                $movimientos[] = $linea_nueva;
            }
        }

        //Si existen estas tablas se genera la información de las transferencias de stock
        if ($this->db->table_exists('transstock', $this->tablas) AND $this->db->table_exists('lineastransstock', $this->tablas)) {
            /*
             * Generamos la informacion de las transferencias por ingresos entre almacenes que se hayan hecho a los stocks
             */
            $sql_transstock1 = "select codalmadestino as codalmacen,ls.referencia,a.descripcion,l.idtrans,fecha,hora,cantidad FROM lineastransstock AS ls".
            " JOIN transstock as l ON(ls.idtrans = l.idtrans) JOIN articulos as a ON (ls.referencia = a.referencia) ".
            " WHERE fecha between ".$this->empresa->var2str($this->f_desde).' AND '.$this->empresa->var2str($this->f_hasta).$sql_aux3.
            " ORDER BY fecha,hora,l.idtrans";
            $data_transstock1 = $this->db->select($sql_transstock1);
            if ($data_transstock1) {
                foreach($data_transstock1 as $item)
                {
                    $linea_nueva = new StdClass();
                    $linea_nueva->codalmacen = $item['codalmacen'];
                    $linea_nueva->idtransporte = 'Transf. '.$item['idtrans'];
                    $linea_nueva->referencia = $item['referencia'];
                    $linea_nueva->descripcion = $item['descripcion'];
                    $linea_nueva->fecha = $item['fecha'];
                    $linea_nueva->hora = $item['hora'];
                    $linea_nueva->fechal = '';
                    $linea_nueva->fechad = '';
                    $linea_nueva->fecha_creacion = strtotime($item['fecha'].' '.$item['hora']);
                    $linea_nueva->cantidad = 0;
                    $linea_nueva->devolucion = 0;
                    $linea_nueva->total_final = 0;
                    $linea_nueva->ingresos = $item['cantidad'];
                    $movimientos[] = $linea_nueva;
                }
            }
        }
        return $movimientos;
    }

    public function regularizaciones()
    {
        $sql_aux = '';
        if($this->codalmacen)
        {
            $sql_aux .= 'AND codalmacen = '.$this->empresa->var2str($this->codalmacen);
        }
        if($this->referencia)
        {
            $sql_aux .= 'AND a.referencia = '.$this->empresa->var2str($this->referencia);
        }
        $movimientos = array();
        //Si existe esta tabla se genera la información de las regularizaciones de stock y se agrega como salida el resultado
        if ($this->db->table_exists('lineasregstocks', $this->tablas)) {
            $sql_regstocks = "select codalmacen,a.referencia,descripcion,ls.id,fecha,hora,(cantidadini-cantidadfin) as cantidad from lineasregstocks AS ls ".
            " JOIN stocks as l ON(ls.idstock = l.idstock) JOIN articulos as a ON (l.referencia = a.referencia) ".
            " WHERE fecha between ".$this->empresa->var2str($this->f_desde).' AND '.$this->empresa->var2str($this->f_hasta).$sql_aux.
            " ORDER BY fecha,hora,referencia,ls.id";
            $data_regstocks = $this->db->select($sql_regstocks);
            if ($data_regstocks) {
                foreach($data_regstocks as $item)
                {
                    $linea_nueva = new StdClass();
                    $linea_nueva->codalmacen = $item['codalmacen'];
                    $linea_nueva->idtransporte = 'Regularización '.$item['id'];
                    $linea_nueva->referencia = $item['referencia'];
                    $linea_nueva->descripcion = $item['descripcion'];
                    $linea_nueva->fecha = $item['fecha'];
                    $linea_nueva->fechal = '';
                    $linea_nueva->fechad = '';
                    $linea_nueva->hora = $item['hora'];
                    $linea_nueva->fecha_creacion = strtotime($item['fecha'].' '.$item['hora']);
                    $linea_nueva->cantidad = 0;
                    $linea_nueva->devolucion = 0;
                    $linea_nueva->total_final = $item['cantidad'];
                    $linea_nueva->ingresos = 0;
                    $movimientos[] = $linea_nueva;
                }
            }
        }
        return $movimientos;
    }

    public function traslados()
    {
        $sql_aux = '';
        if($this->codalmacen)
        {
            $sql_aux .= ' AND codalmaorigen = '.$this->empresa->var2str($this->codalmacen);
        }
        if($this->referencia)
        {
            $sql_aux .= ' AND a.referencia = '.$this->empresa->var2str($this->referencia);
        }
        $movimientos = array();
        //Si existe esta tabla se genera la información de los traslados entre almacenes y se agrega como salida el resultado
        if ($this->db->table_exists('lineastransstock', $this->tablas)) {
            $sql_traslados = "select codalmaorigen as codalmacen,a.referencia,ls.descripcion,ls.idtrans,fecha,hora,cantidad from lineastransstock AS ls ".
            "  JOIN articulos as a ON (ls.referencia = a.referencia) JOIN transstock as l ON(ls.idtrans = l.idtrans) ".
            " WHERE fecha between ".$this->empresa->var2str($this->f_desde).' AND '.$this->empresa->var2str($this->f_hasta).$sql_aux.
            " ORDER BY fecha,hora,referencia,ls.idtrans";
            $data_traslados = $this->db->select($sql_traslados);
            if ($data_traslados) {
                foreach($data_traslados as $item)
                {
                    $linea_nueva = new StdClass();
                    $linea_nueva->codalmacen = $item['codalmacen'];
                    $linea_nueva->idtransporte = 'Salida por Traslado '.$item['idtrans'];
                    $linea_nueva->referencia = $item['referencia'];
                    $linea_nueva->descripcion = $item['descripcion'];
                    $linea_nueva->fecha = $item['fecha'];
                    $linea_nueva->fechal = '';
                    $linea_nueva->fechad = '';
                    $linea_nueva->hora = $item['hora'];
                    $linea_nueva->fecha_creacion = strtotime($item['fecha'].' '.$item['hora']);
                    $linea_nueva->cantidad = 0;
                    $linea_nueva->devolucion = 0;
                    $linea_nueva->total_final = $item['cantidad'];
                    $linea_nueva->ingresos = 0;
                    $movimientos[] = $linea_nueva;
                }
            }
        }
        return $movimientos;
    }

    public function saldo_articulo($ref,$almacen)
    {
        $total_ingresos = 0;
        //Facturas de compra sin albaran
        $sql_compras1 = "SELECT sum(cantidad) as total FROM lineasfacturasprov as lfp".
                " JOIN facturasprov as fp on (fp.idfactura = lfp.idfactura)".
                " WHERE anulada = FALSE and idalbaran IS NULL and fecha < ".$this->empresa->var2str($this->f_desde).
                " AND codalmacen = ".$this->empresa->var2str($almacen).
                " AND referencia = ".$this->empresa->var2str($ref);
        $data_Compras1 = $this->db->select($sql_compras1);
        if($data_Compras1)
        {
            $total_ingresos += $data_Compras1[0]['total'];
        }

        //Albaranes de compra
        $sql_compras2 = "SELECT sum(cantidad) as total FROM lineasalbaranesprov as lap".
                " JOIN albaranesprov as ap on (ap.idalbaran = lap.idalbaran)".
                " WHERE fecha < ".$this->empresa->var2str($this->f_desde).
                " AND codalmacen = ".$this->empresa->var2str($almacen).
                " AND referencia = ".$this->empresa->var2str($ref);
        $data_Compras2 = $this->db->select($sql_compras2);
        if($data_Compras2)
        {
            $total_ingresos += $data_Compras2[0]['total'];
        }

        $total_salidas = 0;
        //Facturas de venta sin albaran
        $sql_ventas1 = "SELECT sum(cantidad) as total FROM lineasfacturascli as lfc".
                " JOIN facturascli as fc on (fc.idfactura = lfc.idfactura)".
                " WHERE anulada = FALSE and idalbaran IS NULL and fecha < ".$this->empresa->var2str($this->f_desde).
                " AND codalmacen = ".$this->empresa->var2str($almacen).
                " AND referencia = ".$this->empresa->var2str($ref);
        $data_Ventas1 = $this->db->select($sql_ventas1);
        if($data_Ventas1)
        {
            $total_salidas += $data_Ventas1[0]['total'];
        }

        //Albaranes de venta
        $sql_ventas2 = "SELECT sum(cantidad) as total FROM lineasalbaranescli as lac".
                " JOIN albaranescli as ac on (ac.idalbaran = lac.idalbaran)".
                " WHERE fecha < ".$this->empresa->var2str($this->f_desde).
                " AND codalmacen = ".$this->empresa->var2str($almacen).
                " AND referencia = ".$this->empresa->var2str($ref);
        $data_Ventas2 = $this->db->select($sql_ventas2);
        if($data_Ventas2)
        {
            $total_salidas += $data_Ventas2[0]['total'];
        }

        //Si existen estas tablas se genera la información de las transferencias de stock
        if ($this->db->table_exists('transstock', $this->tablas) AND $this->db->table_exists('lineastransstock', $this->tablas)) {
            /*
             * Generamos la informacion de las transferencias por ingresos entre almacenes que se hayan hecho a los stocks
             */
            $sql_transstock1 = "select sum(cantidad) as total FROM lineastransstock AS ls".
            " JOIN transstock as l ON(ls.idtrans = l.idtrans) ".
            " WHERE codalmadestino = ".$this->empresa->var2str($almacen).
            " AND fecha < ".$this->empresa->var2str($this->f_desde).
            " AND referencia = ".$this->empresa->var2str($ref);
            $data_transstock1 = $this->db->select($sql_transstock1);
            if ($data_transstock1) {
                $total_ingresos += $data_transstock1[0]['total'];
            }

            /*
             * Generamos la informacion de las transferencias por salidas entre almacenes que se hayan hecho a los stocks
             */
            $sql_transstock2 = "select sum(cantidad) as total FROM lineastransstock AS ls ".
            " JOIN transstock as l ON(ls.idtrans = l.idtrans) ".
            " WHERE  codalmaorigen = ".$this->empresa->var2str($almacen).
            " AND fecha < ".$this->empresa->var2str($this->f_desde).
            " AND referencia = ".$this->empresa->var2str($ref);
            $data_transstock2 = $this->db->select($sql_transstock2);
            if ($data_transstock2) {
                $total_salidas += $data_transstock2[0]['total'];
            }
        }

        //Si existe esta tabla se genera la información de las regularizaciones de stock y se agrega como salida el resultado
        if ($this->db->table_exists('lineasregstocks', $this->tablas)) {
            $sql_regstocks = "select sum(cantidadini-cantidadfin) as total from lineasregstocks AS ls ".
            " JOIN stocks as l ON(ls.idstock = l.idstock) ".
            " WHERE fecha < ".$this->empresa->var2str($this->f_desde).
            " AND codalmacen = ".$this->empresa->var2str($almacen).
            " AND referencia = ".$this->empresa->var2str($ref);
            $data_regstocks = $this->db->select($sql_regstocks);
            if ($data_regstocks) {
                $cantidad = $data_regstocks[0]['total'];
                $total_salidas += $cantidad;
            }
        }

        $total_saldo = $total_ingresos - $total_salidas;
        return $total_saldo;
    }

    public function generar_excel(){
        //Revisamos que no haya un archivo ya cargado
        $archivo = 'MovimientoAlmacen';
        $this->fileName = $this->distribucionDir . DIRECTORY_SEPARATOR . $archivo . "_x_" . $this->user->nick . ".xlsx";
        $this->fileNamePath = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_x_" . $this->user->nick . ".xlsx";
        if (file_exists($this->fileName)) {
            unlink($this->fileName);
        }
        //Variables para cada parte del excel
        $estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $estilo_cuerpo = array( array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'));

        //Inicializamos la clase
        $this->writer = new XLSXWriter();
        //Verificamos si es un solo almacén o si son todos
        $nombre_hoja = 'Movimiento General';
        if($this->codalmacen)
        {
            $almacen = $this->almacen->get($this->codalmacen);
            $nombre_hoja = $almacen->nombre;
        }
        $this->writer->writeSheetHeader($nombre_hoja, array(), true);
        //Agregamos la linea de titulo
        $cabecera = array('Almacén','Fecha','Liquidado en','Devolucionado en','Transporte','Referencia','Descripción','Salida','Devolución','Salida Neta','Ingresos','Saldo');
        $this->writer->writeSheetRow($nombre_hoja, $cabecera,$estilo_cabecera);
        //Agregamos cada linea en forma de array
        foreach($this->resultados as $linea){
            $item = array();
            $item[] = $linea->codalmacen;
            $item[] = $linea->fecha;
            $item[] = $linea->fechal;
            $item[] = $linea->fechad;
            $item[] = $linea->idtransporte;
            $item[] = $linea->referencia;
            $item[] = $linea->descripcion;
            $item[] = $linea->cantidad;
            $item[] = $linea->devolucion;
            $item[] = $linea->total_final;
            $item[] = $linea->ingresos;
            $item[] = $linea->saldo;
            $this->writer->writeSheetRow($nombre_hoja, $item, $estilo_cuerpo);
        }
        //Escribimos
        $this->writer->writeToFile($this->fileNamePath);
    }

    /**
     * Obtenemos el rango de fechas a procesar
     * @return \DatePeriod
     */
    public function rango_fechas($desde,$hasta) {
        $begin = new \DateTime($desde);
        $end = new \DateTime($hasta);
        $end->modify("+1 day");
        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $end);
        return $daterange;
    }

    public function shared_extensions()
    {

    }
}

