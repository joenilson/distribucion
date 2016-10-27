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
require_model('distribucion_tipounidad.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_restricciones_tiporuta.php');
require_model('distribucion_tipovendedor.php');
require_model('distribucion_asignacion_cargos.php');

/**
 * Description of admin_distribucion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class admin_distribucion extends fs_controller {

    public $articulo;
    public $cargos_disponibles;
    public $distribucion_tipounidad;
    public $distribucion_tiporuta;
    public $distribucion_restricciones_tiporuta;
    public $distribucion_tipovendedor;
    public $distribucion_asignacion_cargos;
    public $listado_tipo_transporte;
    public $listado_tipo_ruta;
    public $listado_tipo_vendedor;
    public $listado_articulos_restringidos;
    public $listado_supervisores_asignados;
    public $listado_vendedores_asignados;
    public $familia;
    public $type;
    public $nomina;
    public $idtiporuta;
    public $descripciontiporuta;
    public $distribucion_setup;
    public $fsvar;
    public function __construct() {
        parent::__construct(__CLASS__, '1 - Configuración', 'distribucion');
    }

    public function private_core() {
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
        new distribucion_asignacion_cargos();
        
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extensions();
        /// cargamos la configuración
        $this->fsvar = new fs_var();
        $this->distribucion_setup = $this->fsvar->array_get(
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
        
        /*
         * Buscamos si está el plugin de nomina para la busqueda de los cargos de Supervisor y Vendedor
         */
        $this->nomina = in_array('nomina',$GLOBALS['plugins']);
        
        $this->distribucion_tipounidad = new distribucion_tipounidad();
        $this->distribucion_tiporuta = new distribucion_tiporuta();
        $this->distribucion_restricciones_tiporuta = new distribucion_restricciones_tiporuta();
        $this->distribucion_tipovendedor = new distribucion_tipovendedor();
        $this->distribucion_asignacion_cargos = new distribucion_asignacion_cargos();
        $type_p = \filter_input(INPUT_POST, 'type');
        $type_g = \filter_input(INPUT_GET, 'type');
        $type = (isset($type_p)) ? $type_p : $type_g;
        $this->type = $type;
        $this->idtiporuta = null;
        if ($type == 'tipo_transporte') {
            $this->tratar_tipounidad();
        } elseif ($type == 'tipo_vendedor') {
            $this->tratar_tipovendedor();
        } elseif ($type == 'tipo_ruta') {
            $this->tratar_tiporuta();
        } elseif($type == 'traducciones'){
            $this->tratar_traducciones();
        } elseif ($type == 'asignacion_cargos'){
            $this->tratar_asignacion_cargos();
        } elseif ($type=='restriccion_articulos'){
            $this->articulo = new articulo();
            $this->familia = new familia();
            $this->idtiporuta = \filter_input(INPUT_GET, 'idtiporuta');
            $this->descripciontiporuta = ucfirst(strtolower($this->distribucion_tiporuta->get($this->idtiporuta)->descripcion));
            $subtype = \filter_input(INPUT_GET, 'subtype');
            if($subtype=='arbol_articulos'){
                $this->get_arbol_articulos();
            }elseif($subtype=='arbol_restringidos'){
                $this->get_arbol_restringidos();
            }elseif($subtype=='restringir_articulos'){
                $this->restriccion_articulos(true);
            }elseif($subtype=='no_restringir_articulos'){
                $this->restriccion_articulos(false);
            }else{
                $this->template = 'admin/restriccion_articulos';
            }
        }
        
        $this->cargos_disponibles = $this->listado_cargos_disponibles();
        $this->listado_tipo_transporte = $this->distribucion_tipounidad->all($this->empresa->id);
        $this->listado_tipo_ruta = $this->distribucion_tiporuta->all();
        $this->listado_tipo_vendedor = $this->distribucion_tipovendedor->all();
        $this->listado_articulos_restringidos = $this->distribucion_restricciones_tiporuta->all();
        $this->listado_supervisores_asignados = $this->distribucion_asignacion_cargos->all_tipocargo($this->empresa->id, 'SUP');
        $this->listado_vendedores_asignados = $this->distribucion_asignacion_cargos->all_tipocargo($this->empresa->id, 'VEN');

    }
    
    public function restriccion_articulos($restringir = true){
        $articulos = json_decode(\filter_input(INPUT_GET, 'articulos'));
        
        $restricciones = new distribucion_restricciones_tiporuta();
        foreach($articulos as $art){
            $restricciones->id = $this->idtiporuta;
            $restricciones->referencia = $art;
            $restricciones->usuario_creacion = $this->user->nick;
            $restricciones->fecha_creacion = \Date('d-m-Y H:i:s');
            if($restringir){
                $restricciones->save();
            }else{
                $restricciones->delete();
            }
        }
        $respuesta['success']=true;
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($respuesta);
    }
    
    public function get_hojas($familias){
        $node = array();   
        foreach($familias as $values){
            $linea = new stdClass();
            if(!isset($linea->nodes)){
                $linea->nodes = array();
            }
            $linea->id = $values->codfamilia;
            $linea->text = $values->descripcion;
            $linea->tags = array("Familia");
            
            if($values->hijas()){
                $linea->nodes = $this->get_hojas($values->hijas());
            }
            foreach($values->get_articulos() as $articulo){
                if(!$this->distribucion_restricciones_tiporuta->get($this->idtiporuta,$articulo->referencia)){
                    $linea_art = new stdClass();
                    $linea_art->id = $articulo->referencia;
                    $linea_art->text = $articulo->descripcion;
                    $linea_art->tags = array("Articulo");
                    array_push($linea->nodes, $linea_art);
                }
            }
            $node[] = $linea;
        }
        return $node;
    }
    
    public function get_arbol_articulos(){
        $listaArticulosSinFamilia = array();
        foreach($this->articulo->all() as $values){
            if($values->codfamilia == '' and !$this->distribucion_restricciones_tiporuta->get($this->idtiporuta,$values->referencia)){
                $linea_art = new stdClass();
                $linea_art->id = $values->referencia;
                $linea_art->text = $values->descripcion;
                $linea_art->tags = array("Articulo");
                $listaArticulosSinFamilia[] = $linea_art;
            }
        }
        $listaFamilias = array();
        foreach($this->familia->all() as $values){
            if(empty($values->madre)){
                $listaFamilias[] = $values;
            }
        }
        $estructura = array_merge($listaArticulosSinFamilia,$this->get_hojas($listaFamilias));

        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($estructura);
    }

    public function get_restringidos($familias){
        $node = array();   
        foreach($familias as $values){
            $linea = new stdClass();
            if(!isset($linea->nodes)){
                $linea->nodes = array();
            }
            $linea->id = $values->codfamilia;
            $linea->text = $values->descripcion;
            $linea->tags = array("Familia");
            
            if($values->hijas()){
                $linea->nodes = $this->get_hojas($values->hijas());
            }
            foreach($values->get_articulos() as $articulo){
                $linea_art = new stdClass();
                $linea_art->id = $articulo->referencia;
                $linea_art->text = $articulo->descripcion;
                $linea_art->tags = array("Articulo");
                array_push($linea->nodes, $linea_art);
            }
            $node[] = $linea;
        }
        return $node;
    }
    
    public function get_arbol_restringidos(){
        $listaArticulosRestringidos = array();
        $datos = $this->distribucion_restricciones_tiporuta->get_idruta($this->idtiporuta);
        if($datos){
            foreach($datos as $values){
                $linea_art = new stdClass();
                $linea_art->id = $values->referencia;
                $linea_art->text = $values->descripcion;
                $linea_art->tags = $values->tags;
                $listaArticulosRestringidos[] = $linea_art;
            }
        }

        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($listaArticulosRestringidos);
    }

    private function tratar_tipounidad() {
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtu = new distribucion_tipounidad();
        $dtu->id = $id;
        $dtu->idempresa = $this->empresa->id;
        $dtu->descripcion = $descripcion;
        $dtu->estado = $estado;
        $dtu->idempresa = $this->empresa->id;
        $dtu->descripcion = ucwords(strtolower($descripcion));
        $dtu->estado = $estado;
        $dtu->usuario_creacion = $this->user->nick;
        $dtu->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtu->delete()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de unidad!");
            }
        } else {
            if(!empty($id)){
                $dtu->usuario_modificacion = $this->user->nick;
                $dtu->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtu->save()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de unidad!");
            }
        }
    }
    
    public function tratar_tiporuta(){
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtr = new distribucion_tiporuta();
        $dtr->id = $id;
        $dtr->descripcion = $descripcion;
        $dtr->estado = $estado;
        $dtr->idempresa = $this->empresa->id;
        $dtr->descripcion = strtoupper($descripcion);
        $dtr->estado = $estado;
        $dtr->usuario_creacion = $this->user->nick;
        $dtr->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtr->delete()) {
                $this->new_message("Tipo de Ruta " . $dtr->descripcion . " con el id " . $dtr->id . " eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de ruta!");
            }
        } else {
            if(!empty($id)){
                $dtr->usuario_modificacion = $this->user->nick;
                $dtr->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtr->save()) {
                $this->new_message("Tipo de Ruta " . $dtr->descripcion . " con el id " . $dtr->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de ruta!");
            }
        }   
    }
    
    public function tratar_tipovendedor(){
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtv = new distribucion_tipovendedor();
        $dtv->id = $id;
        $dtv->descripcion = $descripcion;
        $dtv->estado = $estado;
        $dtv->idempresa = $this->empresa->id;
        $dtv->descripcion = strtoupper($descripcion);
        $dtv->estado = $estado;
        $dtv->usuario_creacion = $this->user->nick;
        $dtv->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtv->delete()) {
                $this->new_message("Tipo de Vendedor " . $dtv->descripcion . " con el id " . $dtv->id . " eliminado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de vendedor!");
            }
        } else {
            if(!empty($id)){
                $dtv->usuario_modificacion = $this->user->nick;
                $dtv->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtv->save()) {
                $this->new_message("Tipo de Vendedor " . $dtv->descripcion . " con el id " . $dtv->id . " guardado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de vendedor!");
            }
        }
    }
    
    public function tratar_traducciones(){
        if (isset($_POST['distribucion_setup'])) {
            $this->distribucion_setup['distrib_ordencarga'] = trim(\filter_input(INPUT_POST, 'distrib_ordencarga'));
            $this->distribucion_setup['distrib_ordenescarga'] = trim(\filter_input(INPUT_POST, 'distrib_ordenescarga'));
            $this->distribucion_setup['distrib_transporte'] = trim(\filter_input(INPUT_POST, 'distrib_transporte'));
            $this->distribucion_setup['distrib_transportes'] = trim(\filter_input(INPUT_POST, 'distrib_transportes'));
            $this->distribucion_setup['distrib_agencia'] = trim(\filter_input(INPUT_POST, 'distrib_agencia'));
            $this->distribucion_setup['distrib_agencias'] = trim(\filter_input(INPUT_POST, 'distrib_agencias'));
            $this->distribucion_setup['distrib_unidad'] = trim(\filter_input(INPUT_POST, 'distrib_unidad'));
            $this->distribucion_setup['distrib_unidades'] = trim(\filter_input(INPUT_POST, 'distrib_unidades'));
            $this->distribucion_setup['distrib_conductor'] = trim(\filter_input(INPUT_POST, 'distrib_conductor'));
            $this->distribucion_setup['distrib_conductores'] = trim(\filter_input(INPUT_POST, 'distrib_conductores'));
            $this->distribucion_setup['distrib_liquidacion'] = trim(\filter_input(INPUT_POST, 'distrib_liquidacion'));
            $this->distribucion_setup['distrib_liquidaciones'] = trim(\filter_input(INPUT_POST, 'distrib_liquidaciones'));
            $this->distribucion_setup['distrib_faltante'] = trim(\filter_input(INPUT_POST, 'distrib_faltante'));
            $this->distribucion_setup['distrib_faltantes'] = trim(\filter_input(INPUT_POST, 'distrib_faltantes'));

            if ($this->fsvar->array_save($this->distribucion_setup)) {
                $this->new_message('Datos de Traducci&oacute;n guardados correctamente.');
            } else {
                $this->new_error_msg('Error al guardar los datos de traduccion.');
            }
        }
    }
    
    public function tratar_asignacion_cargos(){
        $id_cargo = false;
        $id_supervisor = \filter_input(INPUT_POST, 'cargos_disponibles_supervisores');
        $tipo_cargo = \filter_input(INPUT_POST, 'tipo_cargo');
        $id_vendedor = \filter_input(INPUT_POST, 'cargos_disponibles_vendedores');
        $accion = \filter_input(INPUT_POST, 'accion');

        if($accion == 'agregar'){
            $id_cargo=($id_supervisor)?$id_supervisor:$id_vendedor;
        }elseif($accion == 'eliminar'){
            $id_cargo=\filter_input(INPUT_POST, 'codcargo');
        }
        
        if($accion=='agregar' and !empty($id_cargo)){
            $ac0 = new distribucion_asignacion_cargos();
            $ac0->idempresa = $this->empresa->id;
            $ac0->codcargo = $id_cargo;
            $ac0->tipo_cargo = $tipo_cargo;
            $ac0->usuario_creacion = $this->user->nick;
            $ac0->fecha_creacion = \Date('d-m-Y H:i:s');
            if($ac0->save()){
                $this->new_message('Asignación de cargo realizada correctamente.');
            }
        }elseif($accion=='eliminar' and !empty($id_cargo)){
            $ac0 = $this->distribucion_asignacion_cargos->get($this->empresa->id, $id_cargo, $tipo_cargo);
            if($ac0){
                $ac0->delete();
                $this->new_message('Asignación de Supervisor eliminada correctamente.');
            }else{
                $this->new_message('Datos incompletos, por favor revise la información enviada');
            }
        }
    }
    
    public function listado_cargos_disponibles(){
        $listado = array();
        if($this->nomina){
            require_model('cargos.php');
            $cargos = new cargos();
            $listado = $cargos->all();
            $cargos_ocupados = array();
            foreach($this->distribucion_asignacion_cargos->all($this->empresa->id) as $cargo){
                $cargos_ocupados[]=$cargo->codcargo;
            }
            
            foreach($listado as $id=>$item){
                if(in_array($item->codcargo, $cargos_ocupados)){
                    unset($listado[$id]);
                }
            }
        }
        return $listado;
    }
    
    private function share_extensions()
    {
        $fsext = new fs_extension();
        $fsext->name = 'opciones_distribucion';
        $fsext->from = 'opciones_distribucion';
        $fsext->to = __CLASS__;
        $fsext->type = 'button';
        $fsext->text = '<span class="glyphicon glyphicon-cog" aria-hidden="true">'
                . '</span><span class="hidden-xs">&nbsp; Opciones</span>';
        $fsext->delete();
        
        $extensiones = array(
            array(
                'name' => 'treeview_admin_distribucion_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-treeview.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'treeview_admin_distribucion_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-treeview.min.css"/>',
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
