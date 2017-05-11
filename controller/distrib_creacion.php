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
require_model('cuenta_banco.php');
require_model('subcuenta.php');

require_once 'plugins/distribucion/vendors/asgard/asgard_PDFHandler.php';
require_once 'helper_ordencarga.php';
require_once 'helper_transportes.php';

/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_creacion extends fs_controller {

   public $almacen;
   public $codalmacen;
   public $distrib_transporte;
   public $distrib_lineastransporte;
   public $distrib_facturas;
   public $distrib_cliente;
   public $resultados;
   public $total_resultados;
   public $num_resultados;
   public $mostrar;
   public $offset;
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
   public $faltante;
   public $faltantes;
   public $subcuentas_faltantes;
   public $asiento;
   public $ejercicio;
   public $faltante_transporte;
   public $codsubcuenta_pago;
   public $cuenta_banco;
   public $fecha_pago;
   public $tesoreria;
   public function __construct() {
      parent::__construct(__CLASS__, '5 - Transportes', 'distribucion');
   }

   public function private_core() {
      $this->share_extensions();
      new distribucion_lineastransporte();
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      $this->almacen = new almacen();
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
      $this->tesoreria = FALSE;
      //revisamos si esta el plugin de tesoreria
      $disabled = array();
      if(defined('FS_DISABLED_PLUGINS'))
      {
         foreach(explode(',', FS_DISABLED_PLUGINS) as $aux)
         {
            $disabled[] = $aux;
         }
      }
      if(in_array('tesoreria',$GLOBALS['plugins']) and !in_array('tesoreria',$disabled)){
          $this->tesoreria = TRUE;
      }
      
      $type = \filter_input(INPUT_GET, 'type');
      $mostrar = \filter_input(INPUT_GET, 'mostrar');
      $cliente = \filter_input(INPUT_GET, 'codcliente');
      $offset = \filter_input(INPUT_GET, 'offset');
      $this->mostrar = "todo";
      $this->offset = (isset($offset))?$offset:0;
      
      $this->cuenta_banco = new cuenta_banco();
      $this->codsubcuenta_pago = FALSE;
      if(\filter_input(INPUT_GET,'codsubcuenta'))
      {
         $this->codsubcuenta_pago = \filter_input(INPUT_GET,'codsubcuenta');
      }  
      $this->fecha_pago = $this->today();
      if(\filter_input(INPUT_GET,'fecha_pago'))
      {
         $this->fecha_pago = \filter_input(INPUT_GET,'fecha_pago');
      }
      
      if(isset($mostrar)){
         $this->mostrar = $mostrar;
         setcookie('distrib_transporte_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
      }elseif(isset($_COOKIE['distrib_transporte_mostrar']))
      {
         $this->mostrar = $_COOKIE['distrib_transporte_mostrar'];
      }

      if( isset($_REQUEST['codalmacen']) )
      {
         if($_REQUEST['codalmacen'] != '')
         {
             $this->codalmacen = $_REQUEST['codalmacen'];
         }
      }

      if (isset($cliente) AND ! empty($cliente)) {
         $cli0 = new cliente();
         $codcliente = $cli0->get($cliente);
      }
      $this->cliente = (isset($codcliente)) ? $codcliente : FALSE;

      if( isset($_REQUEST['conductor']) )
      {
         if($_REQUEST['conductor'] != '')
         {
            $cli0 = new distribucion_conductores();
            $this->conductor = $cli0->get($this->empresa->id, $_REQUEST['conductor']);
         }
      }
      
      $desde_p = \filter_input(INPUT_POST, 'desde');
      $desde_g = \filter_input(INPUT_GET, 'desde');
      $this->desde = ($desde_p)?$desde_p:$desde_g;
      $hasta_p = \filter_input(INPUT_POST, 'hasta');
      $hasta_g = \filter_input(INPUT_GET, 'hasta');
      $this->hasta = ($hasta_p)?$hasta_p:$hasta_g;


      $this->num_resultados = 0;
      if ($type === 'imprimir-transporte') {
         $this->imprimir_transporte();
      } elseif ($type == 'confirmar-devolucion') {
         $this->confirmar_devolucion(TRUE);
      } elseif ($type == 'confirmar-transporte') {
         $this->confirmar_transporte(TRUE);
      } elseif ($type == 'eliminar-transporte') {
         $this->eliminar_transporte();
      } elseif ($type == 'eliminar-devolucion') {
         $this->confirmar_devolucion(FALSE);
      } elseif ($type == 'eliminar-liquidacion') {
         $this->eliminar_liquidacion();
      } elseif ($type == 'eliminar-despacho') {
         $this->confirmar_transporte(FALSE);
      } elseif ($type == 'liquidar-transporte') {
         $this->liquidar_transporte();
      } elseif ($type == 'guardar-liquidacion') {
         $this->guardar_liquidacion();
      } elseif ($type == 'imprimir-liquidacion') {
         $this->imprimir_liquidacion();
      } elseif ($type == 'imprimir-devolucion') {
         $this->imprimir_devolucion();
      } elseif ($type == 'pagar-factura') {
         $this->pagar_factura();
      } elseif ($type == 'extornar-factura') {
         $this->extornar_factura();
      } elseif ($type == 'crear-faltante') {
         $this->crear_faltante();
      }
      if($this->mostrar == 'todo'){
         $this->resultados = $this->distrib_transporte->all($this->empresa->id, $this->offset);
      }elseif($this->mostrar == 'por_despachar'){
         $this->resultados = $this->distrib_transporte->all_pendientes($this->empresa->id,'despachado', $this->offset);
      }elseif($this->mostrar == 'por_liquidar'){
         $this->resultados = $this->distrib_transporte->all_pendientes($this->empresa->id,'liquidado', $this->offset);
      }elseif($this->mostrar == 'buscar'){
         setcookie('distrib_transportes_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
         $this->buscador();
      }

      if( isset($_REQUEST['buscar_conductor']) )
      {
        $this->buscar_conductor();
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
            $busqueda = $this->distrib_transporte->search($this->empresa->id, $datos_busqueda, $this->desde, $this->hasta, $this->offset);
            $this->resultados = $busqueda['resultados'];
            $this->num_resultados = $busqueda['cantidad'];
        }
    }

   public function paginas() {
      $this->total_resultados = $this->distrib_transporte->total_transportes($this->empresa->id);

      $conductor = ($this->conductor)?$this->conductor->licencia:'';
      
      $url = $this->url()."&mostrar=".$this->mostrar
         ."&query=".$this->query
         ."&desde=".$this->desde
         ."&hasta=".$this->hasta
         ."&conductor=".$conductor
         ."&codalmacen=".$this->codalmacen
         ."&offset=".$this->offset;

      $paginas = array();
      $i = 0;
      $num = 0;
      $actual = 1;

      if($this->mostrar == 'por_despachar')
      {
         $total = $this->total_pendientes('despachado');
      }
      elseif($this->mostrar == 'por_liquidar')
      {
         $total = $this->total_pendientes('liquidado');
      }
      elseif($this->mostrar == 'buscar')
      {
         $total = $this->num_resultados;
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

   public function total_pendientes($tipo) {
      return $this->distrib_transporte->total_pendientes($this->empresa->id, $tipo);
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
                 ."&conductor=".$licencia
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

      $con0 = new distribucion_conductores();
      $json = array();
      foreach($con0->search($this->empresa->id, $_REQUEST['buscar_conductor']) as $con)
      {
         $json[] = array('label' => $con->nombre, 'value' => $con->licencia);
      }

      header('Content-Type: application/json');
      echo json_encode( $json );
   }
   
   public function imprimir_devolucion(){
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
            $sumaDevolucion = 0;
            foreach($lineastransporte as $l){
                $sumaDevolucion += $l->devolucion;
            }
            $transporte->totaldevolucion = $sumaDevolucion;
            $pdfFile->pdf_pagina($this->helper_transportes->cabecera_devolucion($transporte), $this->helper_transportes->contenido_devolucion($lineastransporte), $this->helper_transportes->pie_devolucion($transporte));
         }
      }
      $pdfFile->pdf_mostrar();
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

   public function confirmar_devolucion($confirmado=TRUE){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      $tipo_mensaje = ($confirmado)?"confirmado":"desconfirmado";
      $mensaje = (count($lista_transporte)>1)?"Devolución de Transportes ".$tipo_mensaje."s"." correctamente.":"Devolución de Transporte ".$tipo_mensaje." correctamente.";
      foreach ($lista_transporte as $linea) {
         if ($linea) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $this->facturastransporte = $this->distrib_facturas->all_almacen_idtransporte($this->empresa->id, $codalmacen, $idtransporte);
            $lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $articulo = array();
            foreach($this->facturastransporte as $fact){
                $rectif = $this->factura_cliente->get($fact->idfactura)->get_rectificativas();
                if($rectif){
                    foreach($rectif as $f){
                        foreach($f->get_lineas() as $linea){
                            $articulo[$linea->referencia] = (!isset($articulo[$linea->referencia]))?0:$articulo[$linea->referencia];
                            $articulo[$linea->referencia] += $linea->cantidad;
                        }
                    }
                }
            }
            $error=0;
            foreach($lineastransporte as $linea){
                if(isset($articulo[$linea->referencia])){
                    $lin0 = $this->distrib_lineastransporte->getOne($this->empresa->id, $idtransporte, $codalmacen, $linea->referencia);
                    $lin0->devolucion = $articulo[$linea->referencia];
                    if(!$lin0->save()){
                        $error++;
                    }
                }
            }
            if(!$error){
                $trans0->devolucionado = $confirmado;
                $trans0->fechad = \Date('d-m-Y');
                $trans0->fecha_modificacion = Date('d-m-Y H:i');
                $trans0->usuario_modificacion = $this->user->nick;
                if ($trans0->confirmar_devolucion()) {
                   $data['success'] = TRUE;
                   $data['mensaje'] = $mensaje;
                } else {
                   $data['success'] = FALSE;
                   $data['mensaje'] = '¡No se pudo confirmar la devolución del transporte pero se guardaron las cantidades de devolución!';
                }
            }else{
                $data['success'] = FALSE;
                $data['mensaje'] = '!No se lograron guardar las devoluciones de artículos en el Transporte!';
            }
         }
      }
      $this->template = false;
      header('Content-Type: application/json');
      echo json_encode($data);
   }

   public function confirmar_transporte($confirmado=TRUE){
      $value_transporte = \filter_input(INPUT_GET, 'transporte');
      $lista_transporte = explode(',', $value_transporte);
      $tipo_mensaje = ($confirmado)?"despachado":"desconfirmado";
      $mensaje = (count($lista_transporte)>1)?"Transportes ".$tipo_mensaje."s":"Transporte ".$tipo_mensaje;
      foreach ($lista_transporte as $linea) {
         if ($linea) {
            $datos_transporte = explode('-', $linea);
            $idtransporte = $datos_transporte[0];
            $codalmacen = $datos_transporte[1];
            $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
            $trans0->fechad = Date('d-m-Y');
            $trans0->despachado = $confirmado;
            $trans0->fecha_modificacion = Date('d-m-Y H:i');
            $trans0->usuario_modificacion = $this->user->nick;
            if ($trans0->confirmar_despacho()) {
               $data['success'] = TRUE;
               $data['mensaje'] = $mensaje;
            } else {
               $data['success'] = FALSE;
               $data['mensaje'] = 'Transporte <b>NO</b> procesado!';
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
      $this->facturastransporte = $this->distrib_facturas->all_almacen_idtransporte($this->empresa->id, $codalmacen, $idtransporte);
      $lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen);
      if(!$this->transporte->devolucionado){
        $articulo = array();
        foreach($this->facturastransporte as $fact){
            $rectif = $this->factura_cliente->get($fact->idfactura)->get_rectificativas();
            if($rectif){
                foreach($rectif as $f){
                    foreach($f->get_lineas() as $linea){
                        $articulo[$linea->referencia] = (!isset($articulo[$linea->referencia]))?0:$articulo[$linea->referencia];
                        $articulo[$linea->referencia] += $linea->cantidad;
                    }
                }
            }
        }
        
        foreach($lineastransporte as $linea){
            $linea->devolucion = (isset($articulo[$linea->referencia]))?$articulo[$linea->referencia]:0;
        }
      }
      $this->lineastransporte = $lineastransporte;
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
   
   public function eliminar_liquidacion(){
       $idtransporte = \filter_input(INPUT_GET, 'transporte');
       $codalmacen = \filter_input(INPUT_GET, 'almacen');
       $trans0 = $this->distrib_transporte->get($this->empresa->id, $idtransporte, $codalmacen);
       $trans0->fechal = NULL;
       $trans0->liquidado = FALSE;
       $trans0->devolucionado = FALSE;
       $trans0->fecha_modificacion = Date('d-m-Y H:i');
       $trans0->usuario_modificacion = $this->user->nick;
       $msg_aux = "";
       if ($trans0->confirmar_liquidada()) {
          $faltante = $this->faltante->get($this->empresa->id,$idtransporte,$codalmacen);
          if($faltante){
              if($faltante->pagos){
                  $pagos = $this->faltante = get_pagos_recibo($this->empresa->id,$codalmacen,$faltante->idrecibo);
                  foreach($pagos as $recibo){
                      $recibo->delete();
                  }
              }
              $faltante->delete();
              $msg_aux = " Un Faltante eliminado.";
          }else{
              $msg_aux = " No hay faltantes asociados a esta liquidacion.".$msg_aux;
          }
          $facturastransporte = $this->distrib_facturas->all_almacen_idtransporte($this->empresa->id, $codalmacen, $idtransporte);
          $num = 0;
          foreach($facturastransporte as $fac){
             $factura = $this->factura_cliente->get($fac->idfactura);
             if ($factura) {
                $factura->pagada = FALSE;
                $factura->save();
                $num++;
             }
          }
          $fac_procesadas = ($num)?" Se eliminó el pago a $num Facturas.":" No se desmarcaron pagos a ninguna factura.";
          $data['success'] = TRUE;
          $data['mensaje'] = '¡Liquidación del Transporte ' . $idtransporte . ' eliminado!'.$msg_aux.$fac_procesadas;
       } else {
          $data['success'] = FALSE;
          $data['mensaje'] = '¡<b>NO</b>No se pudo eliminar la Liquidación del Transporte ' . $idtransporte . '!';
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
      if($this->tesoreria){
          require_model('pago_recibo_cliente.php');
          require_model('recibo_cliente.php');
          require_model('recibo_factura.php');
          $rec0 = new recibo_cliente();
          $ref0 = new recibo_factura();
      }
      $num = 0;
      $error = 0;
      foreach ($lista_facturas as $factura) {
         $datos_factura = explode('-', $factura);
         $factura = $fact0->get($datos_factura[0]);
         if ($factura) {
            if($this->tesoreria){
                $recibos = $rec0->all_from_factura($factura->idfactura);
                
                foreach($recibos as $recibo){
                   if($recibo->estado != 'Pagado'){
                      if(!$ref0->nuevo_pago_cli($recibo, $this->codsubcuenta_pago, 'Pago', $this->fecha_pago) ){
                         $error = TRUE;
                         break;
                      }
                   }
                }
            }
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
   
   public function get_subcuentas_pago()
   {
      $subcuentas_pago = array();
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($this->today());
      if($ejercicio)
      {
         /// añadimos todas las subcuentas de caja
         $sql = "SELECT * FROM co_subcuentas WHERE idcuenta IN "
                 . "(SELECT idcuenta FROM co_cuentas WHERE codejercicio = "
                 . $ejercicio->var2str($ejercicio->codejercicio)." AND idcuentaesp = 'CAJA');";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $subcuentas_pago[] = new subcuenta($d);
            }
         }
      }
      
      return $subcuentas_pago;
   }   

   public function share_extensions() {
      $extensiones = array(
         array(
            'name' => 'ordencarga_datepicker_es_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css1',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css2',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css3',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css4',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/distribucion.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css5',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css6',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css7',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js9',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js10',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css11',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js12',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootbox.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_js13',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/validator.min.js" type="text/javascript"></script>',
            'params' => ''
         )
      );

      foreach ($extensiones as $ext) {
         $fsext0 = new fs_extension($ext);
         if (!$fsext0->delete()) {
            $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
         }
      }
      
      $extensiones2 = array(
          array(
            'name' => '001_ordencarga_jqueryui_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
            'params' => ''
         ),
         array(
            'name' => '002_distribucion__grid_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
            'params' => ''
         ),          
          array(
            'name' => '003_distribucion_gridlocale_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => '004_distribucion_bootstrap-select_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => '005_distribucion_validator_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/validator.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         array(
            'name' => '008_ordencarga_datepicker_es_js',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
            'params' => ''
         ),
         array(
            'name' => '009_distribucion_js10',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
            'params' => ''
         ),
         
         array(
            'name' => 'ordencarga_jqueryui_css1',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css2',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'ordencarga_jqueryui_css3',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css4',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="'.FS_PATH.'plugins/distribucion/view/css/distribucion.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css5',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
            'type' => 'head',
            'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
            'params' => ''
         ),
         array(
            'name' => 'distribucion_css11',
            'page_from' => __CLASS__,
            'page_to' => 'distrib_creacion',
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
