<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Lesser General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('factura_cliente.php');
require_model('linea_factura_cliente.php');
require_model('almacen.php');
require_model('agente.php');
require_model('agencia_transporte.php');
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_facturas.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_ordenescarga.php');
require_model('distribucion_ordenescarga_facturas.php');
require_model('distribucion_lineasordenescarga.php');
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('cliente.php');
require_model('articulo.php');
require_model('articulo_unidadmedida.php');
require_once 'plugins/distribucion/vendors/FacturaScripts/PrintingManager.php';
require_once 'plugins/distribucion/extras/distribucion_controller.php';
use FacturaScripts\PrintingManager;

/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_ordencarga extends distribucion_controller {

    public $facturas_cliente;
    public $linea_factura_cliente;
    public $almacen;
    public $resultados;
    public $agencia_transporte;
    public $distrib_clientes;
    public $distrib_facturas;
    public $distrib_rutas;
    public $distrib_conductores;
    public $distrib_unidades;
    public $distrib_ordenescarga;
    public $distrib_ordenescarga_facturas;
    public $distrib_lineasordenescarga;
    public $distrib_transporte;
    public $distrib_lineastransporte;
    public $mostrar;
    public $order;
    public $offset;
    public $desde;
    public $hasta;
    public $conductor;
    public $codalmacen;
    public $total_resultados;
    public $total_pendientes;
    public $num_resultados;
    public $paginas;
    public $articulo;
    public $unidadmedida;
    public $articulo_unidadmedida;

    public function __construct() {
        parent::__construct(__CLASS__, '4 - Ordenes de Carga', 'distribucion');
    }

    public function private_core() {
        parent::private_core();
        $this->init_clases();
        $this->share_extensions();

        //Leemos las variables que nos manda el view
        $type = \filter_input(INPUT_GET, 'type');
        $type_post = \filter_input(INPUT_POST, 'type');
        $buscar_fecha = \filter_input(INPUT_GET, 'buscar_fecha');
        $rutas = \filter_input(INPUT_GET, 'rutas');
        $codtrans = \filter_input(INPUT_GET, 'codtrans');
        $offset = \filter_input(INPUT_GET, 'offset');
        $codalmacen = $this->filter_request('codalmacen');
        $this->codalmacen = ($this->user->codalmacen)?$this->user->codalmacen:$codalmacen;
        $this->desde = $this->filter_request('desde');
        $this->hasta = $this->filter_request('hasta');
        $mostrar = \filter_input(INPUT_GET, 'mostrar');
        $order = \filter_input(INPUT_GET, 'order');
        $conductor = $this->filter_request('conductor');
        $this->mostrar = (isset($mostrar)) ? $mostrar : "todo";
        $this->order = (isset($order)) ? str_replace('_', ' ', $order) : "fecha DESC";
        $this->offset = (isset($offset))?$offset:0;

        $buscar_conductor = \filter_input(INPUT_GET, 'buscar_conductor');
        if(isset($buscar_conductor)){
            $this->buscar_conductor();
        }

        $data_conductor = false;
        if (isset($conductor) AND ! empty($conductor)) {
            $data_conductor = $this->distrib_conductores->get($this->empresa->id,$conductor);
        }
        $this->conductor = $data_conductor;

        $this->router_acciones($buscar_fecha,$codtrans,$rutas,$type,$type_post);
        
        $this->total_resultados = $this->distrib_ordenescarga->total_ordenescarga($this->empresa->id, $this->codalmacen, $this->desde, $this->hasta, $conductor);
        $this->total_pendientes = $this->distrib_ordenescarga->total_pendientes($this->empresa->id, 'cargado', $this->codalmacen, $this->desde, $this->hasta, $conductor);
    }
    
    public function init_clases()
    {
        $this->almacen = new almacen();
        $this->facturas_cliente = new factura_cliente();
        $this->linea_factura_cliente = new linea_factura_cliente();
        $this->agencia_transporte = new agencia_transporte();
        $this->distrib_conductores = new distribucion_conductores();
        $this->distrib_unidades = new distribucion_unidades();
        $this->distrib_ordenescarga = new distribucion_ordenescarga();
        $this->distrib_ordenescarga_facturas = new distribucion_ordenescarga_facturas();
        $this->distrib_lineasordenescarga = new distribucion_lineasordenescarga();
        $this->distrib_transporte = new distribucion_transporte();
        $this->distrib_lineastransporte = new distribucion_lineastransporte();
        $this->distrib_rutas = new distribucion_rutas();
        $this->distrib_clientes = new distribucion_clientes();
        $this->distrib_facturas = new distribucion_facturas();
        $this->articulo = new articulo();
        $this->articulo_unidadmedida = new articulo_unidadmedida();
    }
    
    public function router_acciones($buscar_fecha,$codtrans,$rutas,$type,$type_post)
    {
        if ($type === 'buscar_facturas') {
            $this->buscar_informacion('buscar_facturas', $this->codalmacen, $buscar_fecha, $rutas);
        } elseif ($type === 'select-rutas') {
            $this->buscar_informacion('lista_rutas', $this->codalmacen);
        } elseif ($type === 'buscar-rutas') {
            $this->buscar_rutas();
        } elseif ($type === 'select-unidad') {
            $this->buscar_informacion('lista_unidades', $this->codalmacen, FALSE, FALSE, $codtrans);
        }elseif ($type === 'select-conductor') {
            $this->buscar_informacion('lista_conductores', $this->codalmacen, FALSE, FALSE, $codtrans);
        } elseif ($type === 'crear-carga') {
            $this->crear_carga(array('facturas'=>\filter_input(INPUT_GET, 'facturas')), 'json');
        } elseif (isset($type_post) AND $type_post == 'guardar-carga') {
            $this->guardar_carga();
        } elseif ($type === 'ver-carga') {
            $ordencarga = \filter_input(INPUT_GET, 'ordencarga');
            list($idordencarga, $codalmacen) = explode('-', $ordencarga);
            $this->visualizar_ordencarga($idordencarga, $codalmacen);
        } elseif ($type === 'imprimir-carga') {
            $this->imprimir_carga();
        } elseif ($type === 'eliminar-carga') {
            $this->eliminar_carga();
        } elseif ($type === 'imprimir-transporte') {
            $this->imprimir_transporte();
        } elseif ($type === 'confirmar-carga') {
            $this->confirmar_carga();
        } elseif ($type == 'generar-transporte') {
            $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
            $lista_ordenescargar = explode(',', $value_ordencarga);
            $this->crear_transporte($lista_ordenescargar);
        } elseif ($type === 'reversar-carga') {
            $this->reversar_carga();
        } else {
            $this->metodos_mostrar();
        }
    }
    
    public function metodos_mostrar()
    {
        if($this->mostrar == 'todo'){
            $this->resultados = ($this->codalmacen)?$this->distrib_ordenescarga->all_almacen($this->empresa->id, $this->codalmacen,$this->offset):$this->distrib_ordenescarga->all($this->empresa->id,$this->offset);
        }elseif($this->mostrar == 'pendientes'){
            $this->resultados = $this->distrib_ordenescarga->all_pendientes($this->empresa->id, $this->codalmacen, $this->offset);
        }elseif($this->mostrar == 'buscar'){
            $this->num_resultados = 0;
            $this->buscador();
        }
    }

    public function buscador(){
        $datos_busqueda = array();
        if($this->conductor){
            $datos_busqueda['conductor'] = $this->conductor->licencia;
        }
        if($this->codalmacen){
            $datos_busqueda['codalmacen'] = $this->codalmacen;
        }
        if(!empty($datos_busqueda)){
            $busqueda = $this->distrib_ordenescarga->search($this->empresa->id, $datos_busqueda, $this->desde, $this->hasta, $this->offset);
            $this->resultados = $busqueda['resultados'];
            $this->num_resultados = $busqueda['cantidad'];
        }
    }
    
    public function buscar_informacion($tipo,$codalmacen,$buscar_fecha=false,$rutas=false,$codtrans=false){
        $this->template = FALSE;
        $this->resultados = array();
        switch($tipo){
            case 'buscar_facturas':
                $this->resultados = $this->distrib_facturas->buscar_rutas($this->empresa->id, $buscar_fecha, $codalmacen, $rutas);
                break;
            case 'lista_rutas':
                $this->resultados = $this->distrib_rutas->all_rutasporalmacen($this->empresa->id, $codalmacen);
                break;
            case 'lista_unidades':
                $this->resultados = $this->distrib_unidades->activos_agencia_almacen($this->empresa->id, $codtrans, $codalmacen);
                break;
            case 'lista_conductores':
                $this->resultados = $this->distrib_conductores->activos_agencia_almacen($this->empresa->id, $codtrans, $codalmacen);
                break;
            default;
                break;
        }
        header('Content-Type: application/json');
        echo json_encode($this->resultados);
    }

    public function crear_transporte($lista) {
        $contador_transportes = 0;
        $contador_transportes_confirmados = 0;
        $data = array();
        foreach ($lista as $ordencarga_p) {
            if (!empty($ordencarga_p)) {
                list($idordencarga, $codalmacen) = explode('-', $ordencarga_p);
                $contador_transportes++;
                $ordencarga = $this->distrib_ordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
                $lineasordencarga = $this->distrib_lineasordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
                if (($ordencarga[0]->cargado) AND ( !$ordencarga[0]->despachado)) {
                    $array_facturas = $this->distrib_ordenescarga_facturas->all_almacen_ordencarga($this->empresa->id, $codalmacen, $idordencarga);
                    list($importe_resumen, $suma_importe) = $this->agrupar_facturas_ordencarga($array_facturas);

                    $trans0 = new distribucion_transporte();
                    $trans0->idempresa = $ordencarga[0]->idempresa;
                    $trans0->idordencarga = $ordencarga[0]->idordencarga;
                    $trans0->fecha = $ordencarga[0]->fecha;
                    $trans0->codalmacen = $ordencarga[0]->codalmacen;
                    $trans0->codalmacen_dest = $ordencarga[0]->codalmacen_dest;
                    $trans0->codtrans = $ordencarga[0]->codtrans;
                    $trans0->conductor = $ordencarga[0]->conductor;
                    $trans0->tipolicencia = $ordencarga[0]->tipolicencia;
                    $trans0->tipounidad = $ordencarga[0]->tipounidad;
                    $trans0->totalcantidad = $ordencarga[0]->totalcantidad;
                    $trans0->totalimporte = $suma_importe;
                    $trans0->totalpeso = $ordencarga[0]->totalpeso;
                    $trans0->unidad = $ordencarga[0]->unidad;
                    $trans0->estado = TRUE;
                    $trans0->usuario_creacion = $this->user->nick;
                    $trans0->fecha_cracion = Date('d-m-Y H:i');
                    if ($trans0->save()) {
                        $this->confirmar_despacho_oc($ordencarga,$trans0);
                        $this->guardar_lineas_transporte($importe_resumen,$lineasordencarga,$trans0);
                        $contador_transportes_confirmados++;
                    }
                }
            }
        }
        if ($contador_transportes_confirmados == $contador_transportes) {
           $data['success'] = TRUE;
           $data['mensaje'] = "Se generaron $contador_transportes_confirmados transporte(s) correctamente.";
        } elseif ($contador_transportes_confirmados == 0) {
           $data['success'] = FALSE;
           $data['mensaje'] = "No se pudieron procesar las ordenes de carga, por favor contacte a su administrador de sistemas..";
        } elseif ($contador_transportes_confirmados != 0 AND ( $contador_transportes_confirmados != $contador_transportes)) {
           $data['success'] = TRUE;
           $data['mensaje'] = "No se pudieron procesar todas las ordenes de carga, por favor contacte a su administrador de sistemas..";
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    public function agrupar_facturas_ordencarga($array_facturas)
    {
        $suma_importe = 0;
        $lineas_factura = array();
        if(!empty($array_facturas)){
            foreach ($array_facturas as $linea) {
                $lineas_factura[] = $this->linea_factura_cliente->all_from_factura($linea->idfactura);
            }
            $importe_resumen = array();
            foreach($lineas_factura as $linea_factura){
                foreach ($linea_factura as $key => $values){
                    if(!isset($importe_resumen[$values->referencia])){
                        $importe_resumen[$values->referencia] = 0;
                    }
                    $valor_venta = $values->pvptotal + ($values->pvptotal * ($values->iva / 100));
                    $importe_resumen[$values->referencia] += $valor_venta;
                    $suma_importe += $valor_venta;
                }
            }
        }
        return array($importe_resumen, $suma_importe);
    }
    
    /**
     * Confirmamos el despacho de la orden de carga y agregamos a las facturas el transporte con el que salieron
     * @param array $ordencarga
     * @param object $trans0
     */
    public function confirmar_despacho_oc($ordencarga,$trans0)
    {
        $actualizar_oc = $this->distrib_ordenescarga;
        $actualizar_oc->despachado = true;
        $actualizar_oc->idempresa = $ordencarga[0]->idempresa;
        $actualizar_oc->codalmacen = $ordencarga[0]->codalmacen;
        $actualizar_oc->idtransporte = $trans0->idtransporte;
        $actualizar_oc->idordencarga = $ordencarga[0]->idordencarga;
        $actualizar_oc->fecha = $ordencarga[0]->fecha;
        $actualizar_oc->usuario_creacion = $this->user->nick;
        $actualizar_oc->fecha_creacion = Date('d-m-Y H:i');
        $actualizar_oc->confirmar_despachada();
        $facturas_oc = $this->distrib_ordenescarga_facturas;
        $facturas_oc->idempresa = $ordencarga[0]->idempresa;
        $facturas_oc->idordencarga = $ordencarga[0]->idordencarga;
        $facturas_oc->codalmacen = $ordencarga[0]->codalmacen;
        $facturas_oc->idtransporte = $trans0->idtransporte;
        $facturas_oc->usuario_creacion = $this->user->nick;
        $facturas_oc->fecha_creacion = Date('d-m-Y H:i');
        $facturas_oc->asignar_transporte();
    }
    
    public function guardar_lineas_transporte($importe_resumen,$lineasordencarga,$trans0)
    {
        foreach ($lineasordencarga as $linea) {
            $ltrans0 = new distribucion_lineastransporte();
            $ltrans0->idempresa = $linea->idempresa;
            $ltrans0->idtransporte = $trans0->idtransporte;
            $ltrans0->codalmacen = $linea->codalmacen;
            $ltrans0->referencia = $linea->referencia;
            $ltrans0->estado = $linea->estado;
            $ltrans0->cantidad = $linea->cantidad;
            $ltrans0->importe = $importe_resumen[$linea->referencia];
            $ltrans0->fecha = $linea->fecha;
            $ltrans0->peso = $linea->peso;
            $ltrans0->fecha_creacion = Date('d-m-Y H:i');
            $ltrans0->usuario_creacion = $this->user->nick;
            $ltrans0->save();
        }
    }

    public function imprimir_transporte(){
        $this->template = false;
        $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
        $lista_ordenescargar = explode(',', $value_ordencarga);
        $contador_transporte = 0;
        $conf = array('file'=>'transporte.pdf', 'type'=>'pdf', 'page_size'=>'letter');
        $pdf_doc = new PrintingManager($conf);
        $pdf_doc->crearArchivo();
        foreach ($lista_ordenescargar as $transporte) {
            if (!empty($transporte)) {
                $datos_ordencarga = explode('-', $transporte);
                $codalmacen = $datos_ordencarga[1];
                $idtransporte = $datos_ordencarga[2];
                $contador_transporte++;
                $transporte = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
                $transporte->nombre = $this->transporte_nombre;
                $transporte->numero = str_pad($transporte->idtransporte,6,'0',STR_PAD_LEFT);
                $transporte->cabecera_lineas = $this->cabecera_transporte();
                $cabecera = array();
                $cabecera[] = array('size'=>30, 'label'=>'Orden de Carga:','valor'=>str_pad($transporte->idordencarga,6,0,STR_PAD_LEFT),'salto_linea'=>false);
                $cabecera[] = array('size'=>30, 'label'=>'Fecha de Reparto:','valor'=>$transporte->fecha,'salto_linea'=>true);
                $cabecera[] = array('size'=>30, 'label'=>'Almacén Origen:','valor'=>$transporte->codalmacen,'salto_linea'=>false);
                $cabecera[] = array('size'=>30, 'label'=>'Almacén Destino:','valor'=>$transporte->codalmacen_dest,'salto_linea'=>true);
                $cabecera[] = array('size'=>30, 'label'=>'Unidad:','valor'=>$transporte->unidad,'salto_linea'=>false);
                $cabecera[] = array('size'=>120, 'label'=>'Conductor:','valor'=>$transporte->conductor_nombre,'salto_linea'=>true);
                $pdf_doc->agregarCabecera($this->empresa, $transporte, $cabecera);
                $lineastransporte = $this->distrib_lineastransporte->get_lineas_imprimir($this->empresa->id, $idtransporte, $codalmacen, 'transporte');
                $pdf_doc->agregarLineas($lineastransporte);
                $totales_lineas = array('totalcantidad'=>$transporte->totalcantidad,'totalimporte'=>$transporte->totalimporte);
                $pdf_doc->agregarTotalesLineas($totales_lineas);
                $pdf_doc->agregarObservaciones(false);
                $firmas = array();
                $firmas[] = 'Firma Distribución';
                $firmas[] = 'Firma Seguridad';
                $firmas[] = 'Firma Almacén';
                $pdf_doc->agregarFirmas($firmas);
            }
        }
        $pdf_doc->mostrarDocumento();
    }

    public function visualizar_ordencarga($idordencarga, $codalmacen) {
        $datos = array();
        $ordencarga = $this->distrib_ordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
        $datos['totalCantidad'] = $ordencarga[0]->totalcantidad;
        $datos['totalPeso'] = $ordencarga[0]->totalpeso;
        $lineasOrdencarga = $this->distrib_lineasordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
        $detalleLineas = array();
        foreach ($lineasOrdencarga as $values) {

            $producto = $this->articulo->get($values->referencia);
            $medidas = $this->articulo_unidadmedida->getBase($values->referencia);
            $values->producto = $producto->descripcion;
            $values->medidas = ($medidas)?$medidas->nombre_um:"UNIDAD";
            $detalleLineas[] = $values;

        }
        $datos['resultados'] = $detalleLineas;
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode(array('cabecera' => $ordencarga[0], 'userData' => array('referencia' => "", 'producto' => 'Total', 'medidas'  =>'medidas' , 'cantidad' => $datos['totalCantidad']), 'rows' => $datos['resultados']));
    }

    public function guardar_facturas_ordencarga($ordencarga, $facturas) {
        $datos_fact = explode(",", $facturas);
        $facturasoc0 = new distribucion_ordenescarga_facturas();
        $facturasoc0->idempresa = $ordencarga->idempresa;
        $facturasoc0->codalmacen = $ordencarga->codalmacen;
        $facturasoc0->idordencarga = $ordencarga->idordencarga;
        $facturasoc0->fecha = $ordencarga->fecha;
        $facturasoc0->usuario_creacion = $ordencarga->usuario_creacion;
        $facturasoc0->fecha_creacion = $ordencarga->fecha_creacion;
        $erroresLinea = "";
        $contadorFacturas = 0;
        foreach ($datos_fact as $fact) {
            if (!empty($fact)) {
                $facturasoc0->idfactura = $fact;
                if (!$facturasoc0->save()) {
                    $coma = (isset($erroresLinea)) ? ", " : "";
                    $erroresLinea .= $coma . $fact;
                } else {
                    $contadorFacturas++;
                }
            }
        }
        if (empty($erroresLinea)) {
            $this->new_message($contadorFacturas . ' facturas de la Orden de carga ' . $ordencarga->idordencarga . ' guardadas correctamente');
        } else {
            $this->new_error_msg('Facturas de la Orden de carga ' . $ordencarga->idordencarga . ' con errores al guardar las siguientes facturas: ' . $erroresLinea . ' por favor revise la información enviada.');
        }
    }

    public function guardar_lineas_ordencarga($ordencarga, $lineas) {
        $this->template = 'distrib_ordencarga';
        $lineasOrdenCarga0 = new distribucion_lineasordenescarga();
        $erroresLinea = "";
        foreach ($lineas as $values) {
            $lineasOrdenCarga0->idempresa = $this->empresa->id;
            $lineasOrdenCarga0->codalmacen = $ordencarga->codalmacen;
            $lineasOrdenCarga0->idordencarga = $ordencarga->idordencarga;
            $lineasOrdenCarga0->fecha = $ordencarga->fecha;
            $lineasOrdenCarga0->referencia = $values['referencia'];
            $lineasOrdenCarga0->cantidad = $values['cantidad'];
            //a ser implementado el peso
            $lineasOrdenCarga0->peso = 0;
            $lineasOrdenCarga0->estado = true;
            $lineasOrdenCarga0->usuario_creacion = $this->user->nick;
            $lineasOrdenCarga0->fecha_creacion = $ordencarga->fecha_creacion;
            if (!$lineasOrdenCarga0->save()) {
                $coma = (isset($erroresLinea)) ? ", " : "";
                $erroresLinea .= $coma . $values['referencia'];
            }
        }
        if (empty($erroresLinea)) {
            $this->new_message('Orden de carga ' . $ordencarga->idordencarga . ' guardada correctamente');
        } else {
            $this->new_error_msg('Orden de carga ' . $ordencarga->idordencarga . ' guardada con errores en los siguientes articulos: ' . $erroresLinea . ' por favor revise la información enviada.');
        }
        $this->resultados = $this->distrib_ordenescarga->all($this->empresa->id);
    }

    public function paginas() {
        $conductor = ($this->conductor)?$this->conductor->licencia:'';
        $this->total_resultados = $this->distrib_ordenescarga->total_ordenescarga($this->empresa->id,$this->codalmacen,$this->desde,$this->hasta,$conductor);

        $url = $this->url()."&mostrar=".$this->mostrar
            ."&query=".$this->query
            ."&codalmacen=".$this->codalmacen
            ."&conductor=".$conductor
            ."&desde=".$this->desde
            ."&hasta=".$this->hasta;

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        if ($this->mostrar == 'pendientes') {
            $total = $this->total_pendientes();
        } else if ($this->mostrar == 'buscar') {
            $total = $this->num_resultados;
        } else {
            $total = $this->total_resultados;
        }

        /// añadimos todas la página
        while ($num < $total) {
            $paginas[$i] = array(
                'url' => $url . "&offset=" . ($i * FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num == $this->offset)
            );

            if ($num == $this->offset) {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        return $paginas;
    }

    public function total_pendientes(){
        $conductor = ($this->conductor)?$this->conductor->licencia:false;
        return $this->distrib_ordenescarga->total_pendientes($this->empresa->id,'cargado', $this->codalmacen, $this->desde, $this->hasta, $conductor);
    }

    public function buscar_rutas(){
        $rutas = new distribucion_rutas();
        $query = \filter_input(INPUT_POST, 'q');
        $almacen = \filter_input(INPUT_POST, 'almacen');
        $data = $rutas->search($almacen,$query);
        $lista = array();
        foreach($data as $r){
            $item = new stdClass();
            $item->ruta = $r->ruta;
            $item->descripcion = $r->descripcion;
            $lista[] = $item;
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($lista);
    }

    public function crear_carga($datos, $retorno) 
    {
        $array_facturas = explode(",", $datos['facturas']);
        $this->resultados = array();
        $lineas_factura = array();
        $lista_resumen = array();
        $data_resumen = array();
        $suma_cantidades = 0;
        $suma_peso = 0;
        foreach ($array_facturas as $key) {
            if ($key) {
               $lineas_factura[] = $this->linea_factura_cliente->all_from_factura($key);
            }
        }
        foreach ($lineas_factura as $linea_factura) {
            foreach ($linea_factura as $key => $values) {
                if (!isset($lista_resumen[$values->referencia])) {
                   $lista_resumen[$values->referencia] = 0;
                }
                if (!isset($data_resumen[$values->referencia])) {
                   $data_resumen[$values->referencia] = array();
                }
                $lista_resumen[$values->referencia] += $values->cantidad;
                $data_resumen[$values->referencia] = array('referencia' => $values->referencia, 'producto' => $values->descripcion, 'cantidad' => $lista_resumen[$values->referencia]);
                $suma_cantidades += $values->cantidad;
            }
        }
        foreach ($data_resumen as $key => $datos) {
            $this->resultados[] = $datos;
        }
        if ($retorno == 'json') {
            $this->template = FALSE;
            header('Content-Type: application/json');
            echo json_encode(array('userData' => array('referencia' => "", 'producto' => 'Total', 'cantidad' => $suma_cantidades), 'rows' => $this->resultados));
        } elseif ($retorno == 'array') {
            return array('resultados' => $this->resultados, 'totalCantidad' => $suma_cantidades, 'totalPeso' => $suma_peso);
        }
    }

    public function guardar_carga() {
        $almacenorig = \filter_input(INPUT_POST, 'carga_almacenorig');
        $almacendest = \filter_input(INPUT_POST, 'carga_almacendest');
        $codtrans = \filter_input(INPUT_POST, 'carga_codtrans');
        $codunidad = \filter_input(INPUT_POST, 'carga_unidad');
        $conductor = \filter_input(INPUT_POST, 'carga_conductor');
        $fecha_reparto = \filter_input(INPUT_POST, 'carga_fechareparto');
        $carga_facturas = \filter_input(INPUT_POST, 'carga_facturas');
        $resultados_facturas = $this->crear_carga(array('facturas' => $carga_facturas), 'array');
        $observaciones = \filter_input(INPUT_POST, 'carga_obs');
        $ordenCarga0 = new distribucion_ordenescarga();
        $ordenCarga0->idempresa = $this->empresa->id;
        $ordenCarga0->codalmacen = $almacenorig;
        $ordenCarga0->codalmacen_dest = $almacendest;
        $ordenCarga0->codtrans = $codtrans;
        $ordenCarga0->unidad = $codunidad;
        $ordenCarga0->tipounidad = $this->distrib_unidades->get($this->empresa->id, $codunidad)->tipounidad;
        $ordenCarga0->conductor = $conductor;
        $ordenCarga0->tipolicencia = $this->distrib_conductores->get($this->empresa->id, $conductor)->tipolicencia;
        $ordenCarga0->fecha = $fecha_reparto;
        $ordenCarga0->observaciones = $observaciones;
        $ordenCarga0->totalcantidad = $resultados_facturas['totalCantidad'];
        $ordenCarga0->totalpeso = $resultados_facturas['totalPeso'];
        $ordenCarga0->estado = true;
        $ordenCarga0->despachado = false;
        $ordenCarga0->cargado = false;
        $ordenCarga0->usuario_creacion = $this->user->nick;
        $ordenCarga0->usuario_modificacion = $this->user->nick;
        $ordenCarga0->fecha_creacion = \Date('d-m-Y H:i:s');
        if ($ordenCarga0->save()) {
            $this->guardar_facturas_ordencarga($ordenCarga0, $carga_facturas);
            $this->guardar_lineas_ordencarga($ordenCarga0, $resultados_facturas['resultados']);
        }
    }

    public function imprimir_carga() {
        $this->template = false;
        $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
        $lista_ordenescargar = explode(',', $value_ordencarga);
        $contador_ordenescarga = 0;
        $conf = array('file'=>'ordencarga.pdf', 'type'=>'pdf', 'page_size'=>'letter');
        $pdf_doc = new PrintingManager($conf);
        $pdf_doc->crearArchivo();
        foreach ($lista_ordenescargar as $ordencarga) {
            if (!empty($ordencarga)) {
                $datos_ordencarga = explode('-', $ordencarga);
                $idordencarga = $datos_ordencarga[0];
                $codalmacen = $datos_ordencarga[1];
                $contador_ordenescarga++;
                $ordencarga = $this->distrib_ordenescarga->getOne($this->empresa->id, $idordencarga, $codalmacen);
                $ordencarga->nombre = $this->ordencarga_nombre;
                $ordencarga->numero = str_pad($ordencarga->idordencarga,6,'0',STR_PAD_LEFT);
                $ordencarga->cabecera_lineas = $this->cabecera_ordencarga();
                $cabecera = array();
                $cabecera[] = array('size'=>30, 'label'=>'Fecha Reparto:','valor'=>$ordencarga->fecha,'salto_linea'=>false);
                $cabecera[] = array('size'=>30, 'label'=>'Almacén Origen:','valor'=>$ordencarga->codalmacen,'salto_linea'=>false);
                $cabecera[] = array('size'=>30, 'label'=>'Almacén Destino:','valor'=>$ordencarga->codalmacen_dest,'salto_linea'=>true);
                $cabecera[] = array('size'=>30, 'label'=>'Unidad:','valor'=>$ordencarga->unidad,'salto_linea'=>false);
                $cabecera[] = array('size'=>120, 'label'=>'Conductor:','valor'=>$ordencarga->conductor_nombre,'salto_linea'=>true);
                $pdf_doc->agregarCabecera($this->empresa, $ordencarga, $cabecera);
                $lineasordencarga = $this->distrib_lineasordenescarga->get_lineas_imprimir($this->empresa->id, $idordencarga, $codalmacen);
                $pdf_doc->agregarLineas($lineasordencarga);
                $pdf_doc->agregarObservaciones($ordencarga->observaciones);
                $firmas = array();
                $firmas[] = 'Firma Distribución';
                $firmas[] = 'Firma Almacén';
                $pdf_doc->agregarFirmas($firmas);
            }
        }
        $pdf_doc->mostrarDocumento();
    }

    private function cabecera_ordencarga(){
        $cabecera = array();
        $cabecera[] = array('size'=>125, 'descripcion'=>'Ref + Descripcion','align'=>'L');
        $cabecera[] = array('size'=>40, 'descripcion'=>'U. Med','align'=>'C');
        $cabecera[] = array('size'=>30, 'descripcion'=>'Cantidad','align'=>'R');
        return $cabecera;
    }

    private function cabecera_transporte(){
        $cabecera = array();
        $cabecera[] = array('size'=>125, 'descripcion'=>'Ref + Descripcion','align'=>'L','total'=>false);
        $cabecera[] = array('size'=>40, 'descripcion'=>'U. Med','align'=>'C','total'=>false);
        $cabecera[] = array('size'=>30, 'descripcion'=>'Cantidad','align'=>'R','total'=>true,'total_campo'=>'totalcantidad');
        $cabecera[] = array('size'=>30, 'descripcion'=>'Monto','align'=>'R','total'=>true,'total_campo'=>'totalimporte');
        return $cabecera;
    }

    public function confirmar_carga(){
        $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
        $lista_ordenescargar = explode(',', $value_ordencarga);
        $contador_ordenescarga = 0;
        $contador_ordenescarga_confirmadas = 0;
        foreach ($lista_ordenescargar as $ordencarga) {
            if (!empty($ordencarga)) {
                $datos_ordencarga = explode('-', $ordencarga);
                $idordencarga = $datos_ordencarga[0];
                $codalmacen = $datos_ordencarga[1];
                $contador_ordenescarga++;
                $oc0 = new distribucion_ordenescarga();
                $oc0->idempresa = $this->empresa->id;
                $oc0->idordencarga = $idordencarga;
                $oc0->codalmacen = $codalmacen;
                $oc0->usuario_modificacion = $this->user->nick;
                $oc0->fecha_modificacion = Date("d-m-Y H:i");
                $oc0->cargado = TRUE;
                if ($oc0->confirmar_cargada()) {
                   $contador_ordenescarga_confirmadas++;
                }
            }
        }
        if ($contador_ordenescarga_confirmadas == $contador_ordenescarga) {
            $data['success'] = TRUE;
            $data['mensaje'] = "Todas las ordenes fueron confirmadas correctamente.";
        } elseif ($contador_ordenescarga_confirmadas == 0) {
            $data['success'] = FALSE;
            $data['mensaje'] = "No se pudieron procesar las ordenes de carga, por favor contacte a su administrador de sistemas..";
        } elseif ($contador_ordenescarga_confirmadas != 0 AND ( $contador_ordenescarga_confirmadas != $contador_ordenescarga)) {
            $data['success'] = TRUE;
            $data['mensaje'] = "No se pudieron procesar todas las ordenes de carga, por favor contacte a su administrador de sistemas..";
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function eliminar_carga() {
        $this->template = false;
        $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
        $lista_ordenescargar = explode(',', $value_ordencarga);
        foreach ($lista_ordenescargar as $ordencarga) {
            if ($ordencarga) {
                $datos_ordencarga = explode('-', $ordencarga);
                $idordencarga = $datos_ordencarga[0];
                $codalmacen = $datos_ordencarga[1];
                $ord0 = new distribucion_ordenescarga();
                $ord0->idempresa = $this->empresa->id;
                $ord0->idordencarga = $idordencarga;
                $ord0->codalmacen = $codalmacen;
                if ($ord0->delete()) {
                    $data['success'] = TRUE;
                    $data['mensaje'] = "Orden de Carga " . $idordencarga . " eliminada correctamente.";
                } else {
                    $data['success'] = TRUE;
                    $data['mensaje'] = "No se pudieron procesar todas las ordenes de carga, por favor contacte a su administrador de sistemas..";
                }
            }
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function reversar_carga(){
        $value_ordencarga = \filter_input(INPUT_GET, 'ordencarga');
        $value_movimiento = \filter_input(INPUT_GET, 'movimiento');
        $datos_ordencarga = explode('-', $value_ordencarga);
        $idordencarga = $datos_ordencarga[0];
        $codalmacen = $datos_ordencarga[1];
        $oc0 = new distribucion_ordenescarga();
        $oc0->idempresa = $this->empresa->id;
        $oc0->idordencarga = $idordencarga;
        $oc0->codalmacen = $codalmacen;
        $oc0->usuario_modificacion = $this->user->nick;
        $oc0->fecha_modificacion = Date("d-m-Y H:i");
        if($value_movimiento == 'cargada'){
           $oc0->cargado = FALSE;
           $estado = $oc0->confirmar_cargada();
        }
        if ($estado) {
           $data['success'] = TRUE;
           $data['mensaje'] = "Orden de Carga ".$oc0->idordencarga." reversada correctamente.";
        } else {
           $data['success'] = TRUE;
           $data['mensaje'] = "No se pudo reversar la Orden de Carga, por favor verifique que otro usuario no la este utilizando..";
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function share_extensions() {
        $extensiones = array(
            array(
                'name' => 'ordencarga_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_css1',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_css2',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_css3',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css4',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/distribucion.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css5',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css6',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css7',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_js10',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_js9',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css11',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_js13',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_facturas',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/validator.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_distribucion_datepicker_locale_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_ordencarga_datepicker_locale_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );

        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->delete()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }

        $extensiones2 = array(
            array(
                'name' => '001_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_facturas',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/validator.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '006_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '007_ordencarga_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_1_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_2_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'ordencarga_jqueryui_3_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/distribucion.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_jqgrid_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_bootstrap-select_css',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),
        );

        foreach ($extensiones2 as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }

}
