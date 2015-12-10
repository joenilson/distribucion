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
        $idfactura = \filter_input(INPUT_POST, 'idfactura');
        if(!empty($id)){
            $this->factura = $id;
            $this->resultados = $this->factura_cliente->get($this->factura)->get_lineas();
        }elseif(!empty($idfactura)){
            $devoluciones = $this->factura_cliente->get($idfactura)->get_lineas();
            echo $idfactura." <br />";
            foreach($devoluciones as $data){
                $dev = \filter_input(INPUT_POST, $data->referencia);
                if(!empty($dev)){
                    echo $dev." - ";
                }
            }
        }
        /*
        $delete = \filter_input(INPUT_GET, 'delete');
        $id_val = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $id = (!empty($delete)) ? $delete : $id_val;
        if (isset($id)) {
            $condicion = (!empty($delete)) ? 'delete' : 'update';
            $valor = (!empty($delete)) ? $delete : $id;
            $this->tratar_tipounidad($valor, $condicion, $descripcion, $estado);
        } elseif (isset($descripcion) and isset($estado)) {
            $tipounidad = new distribucion_tipounidad();
            $tipounidad->id = $id;
            $tipounidad->idempresa = $this->empresa->id;
            $tipounidad->descripcion = ucwords(strtolower($descripcion));
            $tipounidad->estado = $estado;
            $tipounidad->usuario_creacion = $this->user->nick;
            $tipounidad->fecha_creacion = \Date('d-m-Y H:i:s');
            if ($tipounidad->save()) {
                $this->new_message("Tipo de Unidad " . $tipounidad->descripcion . " con el id " . $tipounidad->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible guardar los datos del tipo de unidad!");
            }
        }
        $this->distribucion_tipounidad = new distribucion_tipounidad();
        $this->listado = $this->distribucion_tipounidad->all($this->empresa->id);
         * 
         */
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
