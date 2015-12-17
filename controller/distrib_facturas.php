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
require_model('almacen.php');
require_model('articulo.php');
require_model('pais.php');
require_model('agencia_transporte.php');
require_model('distribucion_tipounidad.php');
require_model('asiento.php');
require_model('asiento_factura.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('forma_pago.php');
require_model('partida.php');
require_model('subcuenta.php');
require_model('distribucion_devoluciones.php');

$dirname = 'plugins/republica_dominicana/';
if(is_dir($dirname)){
    require_model('ncf_ventas.php');
    require_model('ncf_rango.php');
}
$tesoreria = 'plugins/tesoreria/';
if(is_dir($tesoreria)){
    require_model('recibo_cliente.php');
}
/**
 * Description of distrib_facturas
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_facturas extends fs_controller {

    public $distribucion_tipounidad;
    public $distribucion_devoluciones;
    public $almacen;
    public $asiento;
    public $asiento_factura;
    public $cliente;
    public $factura_cliente;
    public $factura;
    public $ncf_ventas;
    public $listado;
    public $resultados;
    public $devolucion;
    public $recibo;
    public $pago_recibo;
    public $tesoreria_plugin;
    public $rd_plugin;

    public function __construct() {
        parent::__construct(__CLASS__, '8 - Configuración', 'distribucion', FALSE, FALSE);
    }

    public function private_core() {
        $rd = 'plugins/republica_dominicana/';
        $tesoreria = 'plugins/tesoreria/';
        $this->rd_plugin = (is_dir($rd))?true:false;
        $this->tesoreria_plugin = (is_dir($tesoreria))?true:false;
        if($this->tesoreria_plugin){
            $this->recibo = new recibo_cliente();
            $this->pago_recibo = new pago_recibo_cliente();
        }
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extension();
        $this->factura_cliente = new factura_cliente();
        $this->distribucion_devoluciones = new distribucion_devoluciones();
        $id = \filter_input(INPUT_GET, 'id');
        $idfactura = \filter_input(INPUT_POST, 'id');
        if (!empty($id)) {
            $this->factura = $id;
            $buscar_dev = $this->distribucion_devoluciones->get_devolucion($this->factura);
            $buscar_fact = $this->factura_cliente->get($this->factura);
            $factura_elegida = ($buscar_dev)?$buscar_dev:$buscar_fact;
            if($this->rd_plugin){
                $ncf = new ncf_ventas();
                $ncf_factura = $ncf->get_ncf($this->empresa->id, $factura_elegida->idfactura, $factura_elegida->codcliente);
                $factura_elegida->ncf = $ncf_factura->ncf;
                $factura_elegida->ncf_modifica = $ncf_factura->ncf_modifica;
            }
            $this->resultados = $factura_elegida;
            $this->devolucion = ($buscar_dev)?TRUE:FALSE;
        } elseif (!empty($idfactura)) {
            $factura_original = $this->factura_cliente->get($idfactura);
            $this->crear_devolucion($factura_original);
        }
    }

    private function crear_devolucion($fact) {
        $factura_original = $fact->idfactura;
        $factura_original_info = &$fact;
        //Si esta activo el plugin de republica dominicana generamos el numero de NCF
        // TODO mover este código al plugin de república dominicana
        if($this->rd_plugin){
            /*
             * Verificación de disponibilidad del Número de NCF para Notas de Crédito
             */
            $tipo_comprobante = '04';
            $this->ncf_rango = new ncf_rango();
            $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
            if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
                return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', no se podrá generar la Nota de Crédito.');
            }
        }
        $fact_lineas = $fact->get_lineas();
        $fact->deabono = TRUE;
        $fact->idfacturarect = $fact->idfactura;
        $fact->codigorect = $fact->codigo;
        $fact->neto = 0;
        $fact->totaliva = 0;
        $fact->totalirpf = 0;
        $fact->totalrecargo = 0;
        $cantidad_devolucion = array();
        $monto_devolucion = array();
        /// Regresamos el stock al almacén de las cantidades ingresadas
        $art0 = new articulo();
        foreach ($fact_lineas as $key=>$linea) {
            $dev = \filter_input(INPUT_POST, "id_".$linea->referencia);
            $articulo = $art0->get($linea->referencia);
            if (!empty($dev) and isset($articulo)) {
                $valor = $dev * $linea->pvpunitario;
                $articulo->sum_stock($fact->codalmacen, $dev);
                //Guardamos los valores de cantidad ingresados
                $linea->cantidad = $dev;
                $linea->pvpsindto = $valor;
                $linea->pvptotal = ($valor * (100 - $linea->dtopor) / 100);
                $fact->neto += $linea->pvptotal;
                $fact->totaliva += $linea->pvptotal * $linea->iva / 100;
                $fact->totalirpf += $linea->pvptotal * $linea->irpf / 100;
                $fact->totalrecargo += $linea->pvptotal * $linea->recargo / 100;
                $cantidad_devolucion[$linea->referencia] = $dev;
                $monto_devolucion[$linea->referencia] = $valor;
            }elseif(empty($dev)){
                //Eliminamos lo que no devolveremos
                unset($fact_lineas[$key]);
            }
        }
        
        /*
         * Mantenemos los valores de la factura menos su id para no repetir toda la data
         */
        $fact->idfactura = NULL;
        $fact->codigo = NULL;
        $fact->idasiento = NULL;
        $fact->total = $fact->neto + $fact->totaliva + $fact->totalirpf + $fact->totalrecargo;
        $fact->fecha = Date('d-m-Y');
        $fact->vencimiento = Date('d-m-Y');
        $fact->codagente = $this->user->codagente;
        if ($fact->save()) {
            $linea_factura = new linea_factura_cliente();
            /// Guardamos la información sin modificar el stock
            foreach ($fact_lineas as $linea) {
                $linea->idfactura = $fact->idfactura;
                $linea->idlinea = NULL;
                $linea_factura = $linea;
                $linea_factura->save();
            }
            /*
             * Generamos el asiento de venta y le agregamos el parámetro de $tipo en este caso con el valor 'inverso'
             */
            $asiento_factura = new asiento_factura();
            $asiento_factura->soloasiento = TRUE;
            if ($asiento_factura->generar_asiento_venta($fact, 'inverso')) {
                $this->new_message("<a href='" . $asiento_factura->asiento->url() . "'>Asiento</a> generado correctamente.");
                $this->new_change('Nota de Crédito ' . $fact->codigo, $fact->url());
            }
            if($this->rd_plugin){
                /*
                 * Luego de que todo este correcto generamos el NCF la Nota de Credito
                 */
                //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
                $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
                $this->guardar_ncf($this->empresa->id, $fact, $tipo_comprobante, $numero_ncf);
                $this->new_message("Devolución ingresada correctamente, se generó la nota de crédito: " . $numero_ncf['NCF']);
            }else{
                $this->new_message("Devolución ingresada correctamente, se generó la nota de crédito: " . $fact->codigo);
            }
            $devolucion = new distribucion_devoluciones();
            $lineas_devolucion = $devolucion->get_devolucion($factura_original);
            if($this->rd_plugin){
                $ncf = new ncf_ventas();
                $ncf_factura = $ncf->get_ncf($this->empresa->id, $fact->idfactura, $fact->codcliente);
                $factura_elegida->ncf = $ncf_factura->ncf;
                $factura_elegida->ncf_modifica = $ncf_factura->ncf_modifica;
            }
            $this->resultados = ($lineas_devolucion)?$lineas_devolucion:NULL;
            $this->devolucion = ($lineas_devolucion)?TRUE:FALSE;
            
            if($this->tesoreria_plugin){
                $recibos = $this->recibo->all_from_factura($factura_original);
                $recibo0 = new recibo_cliente();
                $recibo = $recibo0->get($recibos[0]->idrecibo);
                if($recibo){
                    $recibo->fecha = $fact->fecha;
                    $recibo->importe = $recibo->importe - ($fact->neto + $fact->totaliva + $fact->totalirpf + $fact->totalrecargo);
                    $recibo->importeeuros = $recibo->importe;
                    $recibo->save();
                }
            }
        } else {
            $this->new_error_msg("¡Imposible agregar la devolución a esta factura!");
            
        }
    }
    
    private function guardar_ncf($idempresa,$factura,$tipo_comprobante,$numero_ncf){
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE'){
            return $this->new_error_msg('No hay números NCF disponibles del tipo '.$tipo_comprobante.', la factura '. $factura->idfactura .' se creo sin NCF.');
        }else{
            $ncf_orig = new ncf_ventas();
            $val_ncf = $ncf_orig->get_ncf($this->empresa->id, $factura->idfacturarect, $factura->codcliente);
            $ncf_factura = new ncf_ventas();
            $ncf_factura->idempresa = $idempresa;
            $ncf_factura->codalmacen = $factura->codalmacen;
            $ncf_factura->entidad = $factura->codcliente;
            $ncf_factura->cifnif = $factura->cifnif;
            $ncf_factura->documento = $factura->idfactura;
            $ncf_factura->documento_modifica = $factura->idfacturarect;
            $ncf_factura->ncf_modifica = $val_ncf->ncf;
            $ncf_factura->fecha = $factura->fecha;
            $ncf_factura->tipo_comprobante = $tipo_comprobante;
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            if(!$ncf_factura->save()){
                return $this->new_error_msg('Ocurrió un error al grabar la nota de credito '. $factura->idfactura .' con el NCF: '.$numero_ncf['NCF'].' Anule la factura e intentelo nuevamente. '.$factura->codalmacen);
            }else{
                $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
    }
   
    private function buscar_devolucion($factura){
        
    }
    
    private function share_extension() {
        $extensiones = array(
            array(
                'name' => 'devolucion_cliente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'tab',
                'text' => '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Parciales</span>',
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
