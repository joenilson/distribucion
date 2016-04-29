<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('distribucion_clientes.php');
require_model('distribucion_subcuentas_faltantes.php');
require_model('distribucion_coordenadas_clientes.php');
require_model('distribucion_conductores.php');
require_model('distribucion_tipounidad.php');
require_model('distribucion_unidades.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_segmentos.php');
require_model('distribucion_rutas.php');
require_model('distribucion_ordenescarga.php');
require_model('distribucion_transporte.php');
require_model('distribucion_lineasordenescarga.php');
require_model('distribucion_ordenescarga_facturas.php');
require_model('distribucion_lineastransporte.php');
/**
 * Description of opciones_distribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class opciones_distribucion extends fs_controller {
   public $distribucion_setup;

   public function __construct() {
      parent::__construct(__CLASS__, 'Opciones Distribucion', 'distribucion', TRUE, FALSE);
   }

   protected function private_core() {
      //Cargamos las tablas en el orden correcto

      new distribucion_subcuentas_faltantes();
      new distribucion_coordenadas_clientes();
      new distribucion_conductores();
      new distribucion_tipounidad();
      new distribucion_unidades();
      new distribucion_organizacion();
      new distribucion_segmentos();
      new distribucion_rutas();
      new distribucion_clientes();
      new distribucion_ordenescarga();
      new distribucion_transporte();
      new distribucion_lineasordenescarga();
      new distribucion_ordenescarga_facturas();
      new distribucion_lineastransporte();

      $this->share_extensions();
      /// cargamos la configuración
      $fsvar = new fs_var();
      $this->distribucion_setup = $fsvar->array_get(
         array(
         'distrib_ordencarga' => "Orden de Carga",
         'distrib_ordenescarga' => "Ordenes de Carga",
         'distrib_transporte' => "Transporte",
         'distrib_transportes' => "Transportes",
         'distrib_agencia' => "Agencia",
         'distrib_agencias' => "Agencias",
         'distrib_unidad' => "Unidad",
         'distrib_unidades' => "Unidades",
         'distrib_conductor' => "Conductor",
         'distrib_conductores' => "Conductores",
         'distrib_liquidacion' => "Liquidación",
         'distrib_liquidaciones' => "Liquidaciones",
         'distrib_faltante' => "Faltante",
         'distrib_faltantes' => "Faltantes"
         ), FALSE
      );

      if (isset($_POST['distribucion_setup'])) {
         $this->distribucion_setup['distrib_ordencarga'] = $_POST['distrib_ordencarga'];
         $this->distribucion_setup['distrib_ordenescarga'] = $_POST['distrib_ordenescarga'];
         $this->distribucion_setup['distrib_transporte'] = $_POST['distrib_transporte'];
         $this->distribucion_setup['distrib_transportes'] = $_POST['distrib_transportes'];
         $this->distribucion_setup['distrib_agencia'] = $_POST['distrib_agencia'];
         $this->distribucion_setup['distrib_agencias'] = $_POST['distrib_agencias'];
         $this->distribucion_setup['distrib_unidad'] = $_POST['distrib_unidad'];
         $this->distribucion_setup['distrib_unidades'] = $_POST['distrib_unidades'];
         $this->distribucion_setup['distrib_conductor'] = $_POST['distrib_conductor'];
         $this->distribucion_setup['distrib_conductores'] = $_POST['distrib_conductores'];
         $this->distribucion_setup['distrib_liquidacion'] = $_POST['distrib_liquidacion'];
         $this->distribucion_setup['distrib_liquidaciones'] = $_POST['distrib_liquidaciones'];
         $this->distribucion_setup['distrib_faltante'] = $_POST['distrib_faltante'];
         $this->distribucion_setup['distrib_faltantes'] = $_POST['distrib_faltantes'];

         if ($fsvar->array_save($this->distribucion_setup)) {
            $this->new_message('Datos guardados correctamente.');
         } else
            $this->new_error_msg('Error al guardar los datos.');
      }

      $GLOBALS['DISTRIB_ORDENCARGA']=$fsvar->simple_get('distrib_ordencarga');
      $GLOBALS['DISTRIB_ORDENESCARGA']=$fsvar->simple_get('distrib_ordenescarga');
      $GLOBALS['DISTRIB_TRANSPORTE']=$fsvar->simple_get('distrib_transporte');
      $GLOBALS['DISTRIB_TRANSPORTES']=$fsvar->simple_get('distrib_transportes');
      $GLOBALS['DISTRIB_AGENCIA']=$fsvar->simple_get('distrib_agencia');
      $GLOBALS['DISTRIB_AGENCIAS']=$fsvar->simple_get('distrib_agencias');
      $GLOBALS['DISTRIB_UNIDAD']=$fsvar->simple_get('distrib_unidad');
      $GLOBALS['DISTRIB_UNIDADES']=$fsvar->simple_get('distrib_unidades');
      $GLOBALS['DISTRIB_CONDUCTOR']=$fsvar->simple_get('distrib_conductor');
      $GLOBALS['DISTRIB_CONDUCTORES']=$fsvar->simple_get('distrib_conductores');
      $GLOBALS['DISTRIB_LIQUIDACION']=$fsvar->simple_get('distrib_liquidacion');
      $GLOBALS['DISTRIB_LIQUIDACIONES']=$fsvar->simple_get('distrib_liquidaciones');
      $GLOBALS['DISTRIB_FALTANTE']=$fsvar->simple_get('distrib_faltante');
      $GLOBALS['DISTRIB_FALTANTES']=$fsvar->simple_get('distrib_faltantes');

   }

   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'opciones_distribucion';
      $fsext->from = __CLASS__;
      $fsext->to = 'admin_distribucion';
      $fsext->type = 'button';
      $fsext->text = '<span class="glyphicon glyphicon-cog" aria-hidden="true">'
              . '</span><span class="hidden-xs">&nbsp; Opciones</span>';
      $fsext->save();
   }

}
