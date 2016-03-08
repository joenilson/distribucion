<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('factura_cliente');
require_model('linea_factura_cliente');
require_model('almacen');
require_model('agencia_transporte.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_ordenescarga.php');
require_model('distribucion_ordenescarga_facturas.php');
require_model('distribucion_lineasordenescarga.php');
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('distribucion_faltantes.php');

require_model('distribucion_subcuentas_faltantes.php');

require_model('cliente.php');
require_model('articulo.php');
require_model('asiento');
require_model('ejercicio');

require_once 'plugins/distribucion/vendors/asgard/asgard_PDFHandler.php';
require_once 'helper_ordencarga.php';
require_once 'helper_transportes.php';

/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_creacion extends fs_controller {

   public $distrib_transporte;
   public $distrib_lineastransporte;
   public $distrib_facturas;
   public $distrib_cliente;
   public $resultados;
   public $num_resultados;
   public $mostrar;
   public $order;
   public $desde;
   public $hasta;
   public $conductor;
   public $conductores;
   public $helper_ordencarga;
   public $helper_transportes;
   public $transporte;
   public $lineastransporte;
   public $facturastransporte;
   public $factura_cliente;
   public $faltantes;
   public $subcuentas_faltantes;
   public $asiento;
   public $ejercicio;
   public $faltante_transporte;

   public function __construct() {
      parent::__construct(__CLASS__, '5 - Transportes', 'distribucion');
   }

   public function private_core() {
      $this->share_extensions();
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      $this->distrib_transporte = new distribucion_transporte();
      $this->distrib_lineastransporte = new distribucion_lineastransporte();
      $this->distrib_facturas = new distribucion_ordenescarga_facturas();
      $this->factura_cliente = new factura_cliente();
      $this->faltante = new distribucion_faltantes();
      $this->subcuentas_faltantes = new distribucion_subcuentas_faltantes();
      $this->asiento = new asiento();
      $this->ejercicio = new ejercicio();
      $this->helper_transportes = new helper_transportes();
      $this->conductores = new distribucion_conductores();
      $type = \filter_input(INPUT_GET, 'type');
      $mostrar = \filter_input(INPUT_GET, 'mostrar');
      $order = \filter_input(INPUT_GET, 'order');
      $cliente = \filter_input(INPUT_GET, 'codcliente');
      $this->mostrar = "todo";
      if(isset($mostrar)){
         $this->mostrar = $mostrar;
         setcookie('distrib_transporte_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
      }elseif(isset($_COOKIE['distrib_transporte_mostrar']))
      {
         $this->mostrar = $_COOKIE['distrib_transporte_mostrar'];
      }

      $this->order = (isset($order)) ? str_replace('_', ' ', $order) : "fecha DESC";
      if (isset($cliente) AND ! empty($cliente)) {
         $cli0 = new cliente();
         $codcliente = $cli0->get($cliente);
      }
      $this->cliente = (isset($codcliente)) ? $codcliente : FALSE;
      $this->num_resultados = 0;
      if ($type === 'imprimir-transporte') {
         $this->imprimir_transporte();
      } elseif ($type == 'confirmar-transporte') {
         $this->confirmar_transporte();
      } elseif ($type == 'eliminar-transporte') {
         $this->eliminar_transporte();
      } elseif ($type == 'liquidar-transporte') {
         $this->liquidar_transporte();
      } elseif ($type == 'guardar-liquidacion') {
         $this->guardar_liquidacion();
      } elseif ($type == 'imprimir-liquidacion') {
         $this->imprimir_liquidacion();
      } elseif ($type == 'pagar-factura') {
         $this->pagar_factura();
      } elseif ($type == 'extornar-factura') {
         $this->extornar_factura();
      } elseif ($type == 'crear-faltante') {
         $this->crear_faltante();
      }
      if($this->mostrar == 'todo'){
         $this->resultados = $this->distrib_transporte->all($this->empresa->id);
      }elseif($this->mostrar == 'por_despacho'){
         $this->resultados = $this->distrib_transporte->all_pendiente($this->empresa->id,'despachado');
      }elseif($this->mostrar == 'por_liquidar'){
         $this->resultados = $this->distrib_transporte->all_pendiente($this->empresa->id,'liquidado');
      }elseif($this->mostrar == 'buscar'){
         setcookie('distrib_transportes_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
      }

      if( isset($_REQUEST['buscar_conductor']) )
      {
         var_dump($_REQUEST['buscar_conductor']);
         $this->buscar_conductor();
      }

      if( isset($_REQUEST['licencia']) )
      {
         if($_REQUEST['licencia'] != '')
         {
            $cli0 = new distribucion_conductores();
            $this->conductor = $cli0->get($this->empresa->id,$_REQUEST['licencia']);
         }
      }

      if( isset($_REQUEST['licencia']) )
      {
         if($_REQUEST['licencia'] != '')
         {
            $cli0 = new distribucion_conductores();
            $this->conductor = $cli0->get($this->empresa->id,$_REQUEST['licencia']);
         }
      }

      if( isset($_REQUEST['desde']) )
      {
         $this->desde = $_REQUEST['desde'];
         $this->hasta = $_REQUEST['hasta'];
      }
   }

   public function total_pendientes($tipo) {
      $this->resultados_pendientes = $this->distrib_transporte->all_pendientes($this->empresa->id, $tipo);
      return count($this->resultados_pendientes);
   }

   public function url($busqueda = FALSE)
   {
      if($busqueda)
      {
         $licencia = '';
         if($this->conductor)
         {
            $licencia = $this->conductor->licencia;
         }

         $url = $this->url()."&mostrar=".$this->mostrar
                 ."&query=".$this->query
                 ."&licencia=".$licencia
                 ."&desde=".$this->desde
                 ."&hasta=".$this->hasta;

         return $url;
      }
      else
      {
         return parent::url();
      }
   }

   private function buscar_conductor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;

      $cli0 = new distribucion_conductores();
      $json = array();
      foreach($cli0->search($_REQUEST['buscar_conductor']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->licencia);
      }

      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_conductor'], 'suggestions' => $json) );
   }

   public function imprimir_transporte(){
      $this->template = false;
      $this->helper_transportes = new helper_transportes();
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      $contador_transporte = 0;
      $pdfFile = new asgard_PDFHandler();
      $pdfFile->pdf_create();
      foreach ($lista_transporte as $linea) {
         if (!empty($linea)) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $contador_transporte++;
            $transporte = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $pdfFile->pdf_pagina($this->helper_transportes->cabecera_transporte($transporte), $this->helper_transportes->contenido_transporte($lineastransporte), $this->helper_transportes->pie_transporte($transporte));
         }
      }
      $pdfFile->pdf_mostrar();
   }

   public function confirmar_transporte(){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      foreach ($lista_transporte as $linea) {
         if ($linea) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $trans0->fechad = Date('d-m-Y');
            $trans0->despachado = TRUE;
            $trans0->fecha_modificacion = Date('d-m-Y H:i');
            $trans0->usuario_modificacion = $this->user->nick;
            if ($trans0->confirmar_despacho()) {
               $data['success'] = TRUE;
               $data['mensaje'] = 'Transporte ' . $idtransporte . ' confirmado!';
            } else {
               $data['success'] = FALSE;
               $data['mensaje'] = 'Transporte ' . $idtransporte . ' <b>NO</b> confirmado!';
            }
         }
      }
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function eliminar_transporte(){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      foreach ($lista_transporte as $linea) {
         if ($linea) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            if ($trans0->delete()) {
               $data['success'] = TRUE;
               $data['mensaje'] = 'Transporte ' . $idtransporte . ' eliminado!';
            } else {
               $data['success'] = FALSE;
               $data['mensaje'] = 'Transporte ' . $idtransporte . ' <b>NO</b> eliminado!';
            }
         }
      }
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function liquidar_transporte(){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $datos_transporte = explode('-', $value_transporte);
      $idtransporte = $datos_transporte[0];
      $codalmacen = $datos_transporte[1];
      $faltante_transporte = new distribucion_faltantes();
      $this->faltante_transporte = $faltante_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
      $this->transporte = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
      $this->lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen);
      $this->facturastransporte = $this->distrib_facturas->all_almacen_idtransporte($this->empresa->id, $codalmacen, $idtransporte);
      $this->template = 'extension/liquidar_transporte';
   }

   public function guardar_liquidacion(){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $datos_transporte = explode('-', $value_transporte);
      $idtransporte = $datos_transporte[0];
      $codalmacen = $datos_transporte[1];
      $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
      $trans0->fechal = Date('d-m-Y');
      $trans0->liquidado = TRUE;
      $trans0->fecha_modificacion = Date('d-m-Y H:i');
      $trans0->usuario_modificacion = $this->user->nick;
      if ($trans0->confirmar_liquidada()) {
         $data['success'] = TRUE;
         $data['mensaje'] = 'Transporte ' . $idtransporte . ' liquidado!';
      } else {
         $data['success'] = FALSE;
         $data['mensaje'] = 'Transporte ' . $idtransporte . ' <b>NO</b> liquidado!';
      }
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function imprimir_liquidacion(){
      $this->template = false;
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      $contador_transporte = 0;
      $pdfFile = new asgard_PDFHandler();
      $pdfFile->pdf_create();
      foreach ($lista_transporte as $linea) {
         if (!empty($linea)) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $contador_transporte++;
            $faltante = $this->faltante->get($this->empresa->id, $idtransporte, $codalmacen);
            $transporte = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $facturastransporte = $this->distrib_facturas->all_almacen_idtransporte($this->empresa->id, $codalmacen, $idtransporte);
            $pdfFile->pdf_pagina($this->helper_transportes->cabecera_liquidacion($transporte), $this->helper_transportes->contenido_liquidacion($facturastransporte), $this->helper_transportes->pie_liquidacion($transporte, $faltante));
         }
      }
      $pdfFile->pdf_mostrar();
   }

   public function pagar_factura(){
      $value_factura = \filter_input(INPUT_GET, 'factura');
      $lista_facturas = explode(',', $value_factura);
      $fact0 = new factura_cliente();
      $num = 0;
      foreach ($lista_facturas as $factura) {
         $datos_factura = explode('-', $factura);
         $factura = $fact0->get($datos_factura[0]);
         if ($factura) {
            $factura->pagada = TRUE;
            $factura->save();
            $num++;
         }
      }
      $data['success'] = TRUE;
      $data['facturas_procesadas'] = $num;
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function extornar_factura(){
      $value_factura = \filter_input(INPUT_GET, 'factura');
      $lista_facturas = explode(',', $value_factura);
      $fact0 = new factura_cliente();
      foreach ($lista_facturas as $factura) {
         $datos_factura = explode('-', $value_factura);
         $factura = $fact0->get($datos_factura[0]);
         $num = 0;
         if ($factura) {
            $factura->pagada = FALSE;
            $factura->save();
            $num++;
         }
      }
      $data['success'] = TRUE;
      $data['facturas_procesadas'] = $num;
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function crear_faltante(){
      $idtransporte = \filter_input(INPUT_GET, 'idtransporte');
      $codalmacen = \filter_input(INPUT_GET, 'codalmacen');
      $codtrans = \filter_input(INPUT_GET, 'codtrans');
      $conductor = \filter_input(INPUT_GET, 'conductor');
      $importe = \filter_input(INPUT_GET, 'monto');
      $tipo = \filter_input(INPUT_GET, 'tipo_faltante');
      $this->transporte = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
      $faltante = new distribucion_faltantes();
      $faltante->idempresa = $this->empresa->id;
      $faltante->idtransporte = $idtransporte;
      $faltante->codalmacen = $codalmacen;
      $faltante->coddivisa = $this->empresa->coddivisa;
      $faltante->idreciboref = NULL;
      $faltante->codtrans = $codtrans;
      $faltante->conductor = $conductor;
      $faltante->nombreconductor = $this->transporte->conductor_nombre;
      $faltante->fecha = Date('d-m-Y');
      $faltante->fechav = Date('d-m-Y', strtotime($faltante->fecha . ' +1month'));
      $faltante->fecha_creacion = Date('d-m-Y H:i');
      $faltante->usuario_creacion = $this->user->nick;
      $faltante->importe = $importe;
      $faltante->tipo = $tipo;
      $faltante->estado = "pendiente";
      if ($faltante->save()) {
         $ejercicio0 = $this->ejercicio->get_by_fecha($faltante->fecha);
         if ($faltante->generar_asiento_faltante($faltante, $ejercicio0->codejercicio)) {
            $data['success'] = TRUE;
            $data['ejercicio'] = $ejercicio0->codejercicio;
            $data['recibo'] = $faltante->idrecibo;
         }
         $data['success'] = FALSE;
         $data['ejercicio'] = $ejercicio0->codejercicio;
         $data['recibo'] = $faltante->idrecibo;
      } else {
         $data['success'] = FALSE;
         $data['recibo'] = NULL;
      }
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function share_extensions() {
      $extensiones = array(
         array(
            'name' => 'ordencarga_datepicker_es_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/jquery-ui.min.js"></script>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css1',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css2',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css3',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css4',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/distribucion.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css5',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css6',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css7',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js9',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js10',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css11',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/bootstrap-select.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js12',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/bootbox.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js13',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="plugins/distribucion/view/js/plugins/validator.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css12',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/font-awesome/css/font-awesome.min.css"/>',
            'params' => ''
         )
      );

      foreach ($extensiones as $ext) {
         $fsext0 = new fs_extension($ext);
         if (!$fsext0->save()) {
            $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
         }
      }
   }

}
