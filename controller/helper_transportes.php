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
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('distribucion_ordencarga_facturas.php');
require_model('distribucion_ordencarga.php');
require_model('distribucion_lineasordencarga.php');
require_model('articulo_unidadmedida.php');
require_model('unidadmedida.php');
require_model('fs_var.php');
/**
 * Description of helper_transportes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class helper_transportes extends fs_controller {
    public $distribucion_setup;
    public $transporte;
    public $devolucion;
    public $liquidacion;
    public $hojadevolucion;
    public function __construct() {
        parent::__construct(__CLASS__, 'Helper Transportes', 'distribucion', FALSE, FALSE);
        /// cargamos la configuración
        $fsvar = new fs_var();
        $this->distribucion_setup = $fsvar->array_get(
            array(
            'distrib_ordencarga' => "Orden de Carga",
            'distrib_ordenescarga' => "Ordenes de Carga",
            'distrib_transporte' => "Transporte",
            'distrib_transportes' => "Transportes",
            'distrib_devolucion' => "Devolución",
            'distrib_devoluciones' => "Devoluciones",
            'distrib_agencia' => "Agencia",
            'distrib_agencias' => "Agencias",
            'distrib_unidad' => "Unidad",
            'distrib_unidades' => "Unidades",
            'distrib_conductor' => "Conductor",
            'distrib_conductores' => "Conductores",
            'distrib_liquidacion' => "Liquidación",
            'distrib_liquidaciones' => "Liquidaciones",
            'distrib_faltante' => "Faltante",
            'distrib_faltantes' => "Faltantes",
            'distrib_hojadevolucion' => "Hoja de Devolución",
            'distrib_hojasdevolucion' => "Hojas de Devolución"
            ), FALSE
        );
        $this->transporte = ucfirst(strtolower($this->distribucion_setup['distrib_transporte']));
        $this->devolucion = ucfirst(strtolower($this->distribucion_setup['distrib_devolucion']));
        $this->liquidacion = ucfirst(strtolower($this->distribucion_setup['distrib_liquidacion']));
        $this->hojadevolucion = ucfirst(strtolower($this->distribucion_setup['distrib_hojadevolucion']));
    }

    public function private_core() {

       $this->articulo_unidadmedida = new articulo_unidadmedida();
    }

    public function cabecera_devolucion($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>'.$this->devolucion.' del '.$this->transporte.' '.$transporte->idtransporte.'</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 9px;" align="right">';
        $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>'.ucfirst(strtolower($this->distribucion_setup['distrib_transporte'])).':</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= str_pad($transporte->idtransporte,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }

    public function contenido_devolucion($lineastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="15%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="35%">';
        $table.= '<b>Articulo</b>';
        $table.= '</td>';
        $table.= '<td width="15%" align="right">';
        $table.= '<b>Salida</b>';
        $table.= '</td>';
        $table.= '<td width="15%" align="right">';
        $table.= '<b>Devolucion</b>';
        $table.= '</td>';
        $table.= '<td width="15%" align="right">';
        $table.= '<b>Saldo</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 34;

        foreach($lineastransporte as $key=>$linea){
            $table.= '<tr style="font-size: 9px;">';
            $table.= '<td width="15%">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="35%">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="15%" align="right">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '</td>';
            $table.= '<td width="15%" align="right">';
            $table.= number_format($linea->devolucion,2,".",",");
            $table.= '</td>';
            $table.= '<td width="15%" align="right">';
            $table.= number_format(($linea->cantidad+$linea->devolucion),2,".",",");
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr />';

        return $table;
    }

    public function pie_devolucion($transporte){
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 9px;" colspan="5">';
        $table.= '<b>Total Salidas:</b> &nbsp;'.number_format($transporte->totalcantidad,2,".",",");
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 9px;" colspan="5">';
        $table.= '<b>Total Devolucion:</b> &nbsp;'.number_format($transporte->totaldevolucion,2,".",",");
        $table.= '<br /><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Transportista</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Liquidador</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Almac&eacute;n</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }

    public function cabecera_transporte($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>Transporte '.$transporte->idtransporte.'</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 9px;" align="right">';
        $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Orden de Carga:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= str_pad($transporte->idordencarga,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }

    public function contenido_transporte($lineastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="34%">';
        $table.= '<b>Articulo</b>';
        $table.= '</td>';
        $table.= '<td width="7%" align="right">';
        $table.= '<b>UDM</b>';
        $table.= '</td>';
        $table.= '<td width="12%" align="right">';
        $table.= '<b>Cantidad</b>';
        $table.= '</td>';
        $table.= '<td width="17%" align="right">';
        $table.= '<b>Monto</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 34;
        //$lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen)
        foreach($lineastransporte as $key=>$linea){
            $art = new articulo_unidadmedida();
            $this->articulo_unidadmedida = $art->getBase($linea->referencia);
            $table.= '<tr style="font-size: 9px;">';
            $table.= '<td width="18%">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="36%">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="10%" align="right">';
            $table.=  !empty($this->articulo_unidadmedida->codum)? $this->articulo_unidadmedida->codum:'UNIDAD';
            $table.= '</td>';
            $table.= '<td width="9%" align="right">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '</td>';
            $table.= '<td width="17%" align="right">';
            $table.= number_format($linea->importe,2,".",",");
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr />';

        return $table;
    }

    public function pie_transporte($transporte){
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 9px;" colspan="5">';
        $table.= '<b>Total Cantidad:</b> &nbsp;'.number_format($transporte->totalcantidad,2,".",",");
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 9px;" colspan="5">';
        $table.= '<b>Total Monto:</b> &nbsp;'.number_format($transporte->totalimporte,2,".",",");
        $table.= '<br /><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 9px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Distribuci&oacute;n</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Seguridad</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Almac&eacute;n</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }

    public function cabecera_hojadevolucion($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>Hoja de Devolución del Transporte '.$transporte->idtransporte.'</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 9px;" align="right">';
        $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Orden de Carga:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= str_pad($transporte->idordencarga,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }

    public function contenido_hojadevolucion($lineastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="12%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="30%">';
        $table.= '<b>Articulo</b>';
        $table.= '</td>';
        $table.= '<td width="2%"></td>';
        $table.= '<td width="10%" align="center">';
        $table.= '<b>UDM</b>';
        $table.= '</td>';
        $table.= '<td width="2%"></td>';
        $table.= '<td width="12%" align="center">';
        $table.= '<b>Salida</b>';
        $table.= '</td>';
        $table.= '<td width="2%"></td>';
        $table.= '<td width="12%" align="center">';
        $table.= '<b>Devolucion</b>';
        $table.= '</td>';
        $table.= '<td width="2%"></td>';
        $table.= '<td width="12%" align="center">';
        $table.= '<b>Salida Neta</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 34;
        //$lineastransporte = $this->distrib_lineastransporte->get($this->empresa->id, $idtransporte, $codalmacen)
        foreach($lineastransporte as $key=>$linea){
            $art = new articulo_unidadmedida();
            $this->articulo_unidadmedida = $art->getBase($linea->referencia);
            $table.= '<tr>';
            $table.= '<td width="12%" style="font-size: 9px; border-bottom: 0.5px solid black;">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="30%" style="font-size: 9px; border-bottom: 0.5px solid black;">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="2%"></td>';
            $table.= '<td width="10%" align="left" style="font-size: 9px; line-height: 25px; border-bottom: 0.5px solid black;">';
            $table.=  !empty($this->articulo_unidadmedida->codum)? $this->articulo_unidadmedida->codum:'UNIDAD';
            $table.= '</td>';
            $table.= '<td width="2%"></td>';
            $table.= '<td width="12%" align="right" style="font-size: 9px; line-height: 25px; border-bottom: 0.5px solid black;">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
            $table.= '<td width="2%"></td>';
            $table.= '<td width="12%" align="right" style="font-size: 9px; height: 25px; border-bottom: 1px solid black;">';
            $table.= '</td>';
            $table.= '<td width="2%"></td>';
            $table.= '<td width="12%" align="right" style="font-size: 9px; border-bottom: 1px solid black;">';
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr />';

        return $table;
    }

    public function pie_hojadevolucion($transporte){
        $table= '<table style="width: 100%;" border="0">';
        $table.= '<tr>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td colspan="4">';
        $table.= '</td>';
        $table.= '<td align="left" style="font-size: 9px;" colspan="2">';
        $table.= '<b>Total Salida:</b> '.number_format($transporte->totalcantidad,2,".",",");
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '<td>';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td colspan="2" style="font-size: 9px;">';
        $table.= '<br /><br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '<td width="10%">&nbsp;</td>';
        $table.= '<td colspan="2" style="font-size: 9px;">';
        $table.= '<br /><br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '<td width="10%">&nbsp;</td>';
        $table.= '<td colspan="2" style="font-size: 9px;">';
        $table.= '<br /><br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td colspan="2" align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Conductor</b><br />';
        $table.= '</td>';
        $table.= '<td width="10%">&nbsp;</td>';
        $table.= '<td colspan="2" align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Liquidación</b><br />';
        $table.= '</td>';
        $table.= '<td width="10%">&nbsp;</td>';
        $table.= '<td colspan="2" align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Almac&eacute;n</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }

    public function cabecera_liquidacion($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>Liquidación del '.ucfirst(strtolower($this->distribucion_setup['distrib_transporte'])).' '.$transporte->idtransporte.' del '.strftime("%A %d, %B %Y", strtotime($transporte->fecha)).'</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 9px;" align="right">';
        $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Conduce:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= str_pad($transporte->idtransporte,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 9px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 9px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }

    public function contenido_liquidacion($facturastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 9px;">';
        $table.= '<td width="5%">';
        $table.= '<b>Id</b>';
        $table.= '</td>';
        $table.= '<td width="18%">';
        $table.= '<b>NCF</b>';
        $table.= '</td>';
        $table.= '<td width="25%">';
        $table.= '<b>Cliente</b>';
        $table.= '</td>';
        $table.= '<td width="10%">';
        $table.= '<b>Fecha Fac</b>';
        $table.= '</td>';
        $table.= '<td width="10%" align="right">';
        $table.= '<b>Cantidad</b>';
        $table.= '</td>';
        $table.= '<td width="10%" align="right">';
        $table.= '<b>Monto</b>';
        $table.= '</td>';
        $table.= '<td width="10%" align="right">';
        $table.= '<b>Abono</b>';
        $table.= '</td>';
        $table.= '<td width="10%" align="right">';
        $table.= '<b>Saldo</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 28;
        $sumMonto = 0;
        $sumAbono = 0;
        $sumSaldo = 0;
        foreach($facturastransporte as $key=>$linea){
            $table.= '<tr style="font-size: 8px;">';
            $table.= '<td>'.$linea->idfactura.'</td>';
            $table.= '<td width="18%">'.$linea->ncf.'</td>';
            $table.= '<td width="25%">'.$linea->nombrecliente.'</td>';
            $table.= '<td width="10%">'.$linea->fecha_factura.'</td>';
            $table.= '<td width="10%" align="right">'.$linea->cantidad.'</td>';
            $table.= '<td width="10%" align="right">'.$this->show_numero($linea->monto).'</td>';
            $table.= '<td width="10%" align="right">'.$this->show_numero($linea->abono).'</td>';
            $table.= '<td width="10%" align="right">'.$this->show_numero($linea->saldo).'</td>';
            $table.= '</tr>';
            $maxLineas--;
            $sumMonto += $linea->monto;
            $sumAbono += $linea->abono;
            $sumSaldo += $linea->saldo;
        }
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table .='<tr style="font-size: 9px;">'
            .'<td colspan="5" align="right"><b>Totales</b></td>'
            .'<td align="right"><b>'.$this->show_numero($sumMonto).'</b></td>'
            .'<td align="right"><b>'.$this->show_numero($sumAbono).'</b></td>'
            .'<td align="right"><b>'.$this->show_numero($sumSaldo).'</b></td>'
        .'</tr>';
        $table.= '</table>';
        $table.= '<hr />';
        return $table;
    }

    public function pie_liquidacion($transporte,$faltante){
        $table= '<table style="width: 100%;" cellpadding="5">';
        if($faltante){
            $table.= '<tr style="color: white; border-color: #000; background-color: #000; font-size: 9px;">';
            $table.= '<td align="center" width="20%">';
            $table.= '<b>Faltante generado por:</b>';
            $table.= '</td>';
            $table.= '<td align="right" width="80%">';
            $table.= $this->show_precio($faltante->importe, $faltante->coddivisa);
            $table.= '</td>';
            $table.= '</tr>';
            $table.= '<tr style="color: white; border-color: #000; background-color: #000; font-size: 9px;">';
            $table.= '<td height="30px" align="center" colspan="2">';
            $table.= 'Al firmar este documento acepto la responsabilidad de pagar este importe faltante de la liquidaci&oacute;n';
            $table.= '</td>';
            $table.= '</tr>';
        }
        $table.= '</table>';
        $table.= '<br /><br /><br /><br /><br /><br />';
        $table.= '<table style="width: 100%;">';
        $table.= '<tr style="margin-top: 30px;">';
        $table.= '<td width="25%" style="font-size: 9px;">';
        $table.= '<hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="20%" style="font-size: 9px;">';
        $table.= '<hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="25%" style="font-size: 9px;">';
        $table.= '<hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" width="25%" style="font-size: 8px;">';
        $table.= '<b>'.$transporte->conductor_nombre.'<br />'.$transporte->conductor.'</b>';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" width="20%" style="font-size: 9px;">';
        $table.= '<b>Firma Liquidador</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="25%" align="center" style="font-size: 9px;">';
        $table.= '<b>Firma Contabilidad</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }
}
