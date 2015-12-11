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
require_model('ncf_ventas.php');
require_model('ncf_rango.php');

/**
 * Description of distrib_facturas
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_facturas extends fs_controller {

    public $distribucion_tipounidad;
    public $almacen;
    public $asiento;
    public $asiento_factura;
    public $cliente;
    public $factura_cliente;
    public $factura;
    public $ncf_ventas;
    public $listado;
    public $resultados;

    public function __construct() {
        parent::__construct(__CLASS__, '8 - Configuración', 'distribucion', FALSE, FALSE);
    }

    public function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extension();
        $this->factura_cliente = new factura_cliente();
        $id = \filter_input(INPUT_GET, 'id');
        $idfactura = \filter_input(INPUT_POST, 'id');
        if (!empty($id)) {
            $this->factura = $id;
            $this->resultados = $this->factura_cliente->get($this->factura)->get_lineas();
        } elseif (!empty($idfactura)) {
            $factura_original = $this->factura_cliente->get($idfactura);
            $this->crear_devolucion($factura_original);
        }
    }
    /*
    private function crear_devolucion($factura) {
        $devoluciones = $factura->get_lineas();
        echo $factura->idfactura . " <br />";
        foreach ($devoluciones as $data) {
            $dev = \filter_input(INPUT_POST, $data->referencia);
            if (!empty($dev)) {
                $valor = $dev * $data->pvpunitario;
                echo $data->referencia . " - " . $dev . " - " . $valor;
            }
        }
    }
    */
    private function crear_devolucion($fact) {
        /*
         * Verificación de disponibilidad del Número de NCF para Notas de Crédito
         */
        $factura_original = $fact->idfactura;
        $tipo_comprobante = '04';
        $this->ncf_rango = new ncf_rango();
        $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            $continuar = FALSE;
            return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', no se podrá generar la Nota de Crédito.');
        }

        $fact_lineas = $fact->get_lineas();
        $fact->deabono = TRUE;
        $fact->idfacturarect = $fact->idfactura;
        $fact->codigorect = $fact->codigo;
        $fact->neto = 0;
        $fact->totaliva = 0;
        $fact->totalirpf = 0;
        $fact->totalrecargo = 0;
        /// Regresamos el stock al almacén de las cantidades ingresadas
        $art0 = new articulo();
        foreach ($fact_lineas as $key=>$linea) {
            $dev = \filter_input(INPUT_POST, $linea->referencia);
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
            }elseif(empty($dev)){
                //Eliminamos lo que no devolveremos
                unset($fact_lineas[$key]);
            }
            
        }
        //die();
        /*
         * Mantenemos los valores de la factura menos su id para no repetir toda la data
         */
        $fact->idfactura = NULL;
        $fact->codigo = NULL;
        $fact->idasiento = NULL;
        $fact->total = $fact->neto + $fact->totaliva + $fact->totalirpf + $fact->totalrecargo;
        $fact->fecha = date('Y-m-d');
        $fact->vencimiento = date('Y-m-d');
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
            /*
             * Luego de que todo este correcto generamos el NCF la Nota de Credito
             */
            //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
            $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
            $this->guardar_ncf($this->empresa->id, $fact, $tipo_comprobante, $numero_ncf);

            $this->new_message("Devolución ingresada correctamente, se generó la nota de crédito: " . $numero_ncf['NCF']);
            $lineas_factura_origen = $this->factura_cliente->get($factura_original)->get_lineas();
            foreach ($lineas_factura_origen as $items){
                if($fact_lineas[$items->referencia]){
                    $lineas_factura_origen->devolucion = $fact_lineas[$items->referencia]->cantidad;
                    $lineas_factura_origen->devolucionneto = $fact_lineas[$items->referencia]->pvptotal;
                }
            }
            $this->resultados = $lineas_factura_origen;
        } else
            $this->new_error_msg("¡Imposible agregar la devolución a esta factura!");
    }
    
       private function guardar_ncf($idempresa,$factura,$tipo_comprobante,$numero_ncf){
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE'){
            return $this->new_error_msg('No hay números NCF disponibles del tipo '.$tipo_comprobante.', la factura '. $factura->idfactura .' se creo sin NCF.');
        }else{
            $ncf_factura = new ncf_ventas();
            $ncf_factura->idempresa = $idempresa;
            $ncf_factura->codalmacen = $factura->codalmacen;
            $ncf_factura->entidad = $factura->codcliente;
            $ncf_factura->cifnif = $factura->cifnif;
            $ncf_factura->documento = $factura->idfactura;
            $ncf_factura->documento_modifica = NULL;
            $ncf_factura->NCF_modifica = NULL;
            $ncf_factura->fecha = $factura->fecha;
            $ncf_factura->tipo_comprobante = $tipo_comprobante;
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            if(!$ncf_factura->save()){
                return $this->new_error_msg('Ocurrió un error al grabar la factura '. $factura->idfactura .' con el NCF: '.$numero_ncf['NCF'].' Anule la factura e intentelo nuevamente. '.$factura->codalmacen);
            }else{
                $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
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
