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
require_model('cliente.php');
require_model('almacen.php');
require_model('distribucion_agente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_asignacion_cargos.php');
require_model('distribucion_segmentos.php');
require_model('distribucion_clientes.php');
require_model('distribucion_coordenadas_clientes.php');
/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_clientes extends fs_controller {
    public $codcliente;
    public $cliente;
    public $info_cliente;
    public $almacen;
    public $supervisor;
    public $vendedor;
    public $agente;
    public $rutas;
    public $type;
    public $distrib_cliente;
    public $distrib_coordenadas_cliente;
    public $distribucion_asignacion_cargos;
    public $distribucion_coordenadas_cliente;
    public $distribucion_agente;
    public $distribucion_organizacion;
    public $distribucion_rutas;
    public $tiporuta;
    public $distribucion_segmentos;
    public $distribucion_clientes;
    public $supervisores_asignados;
    public $supervisores_libres;
    public $vendedores_asignados;
    public $vendedores_libres;
    public $canales;
    public $canales_activos;
    public $subcanales;
    public $tab_activa;

    public function __construct() {
        parent::__construct(__CLASS__, '6 - Distribución Clientes', 'distribucion');
    }

    public function private_core(){
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extension();
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();
        $this->distribucion_asignacion_cargos = new distribucion_asignacion_cargos();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_segmentos = new distribucion_segmentos();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_coordenadas_cliente = new distribucion_coordenadas_clientes();
        $this->tiporuta = new distribucion_tiporuta();
        $this->cliente = new cliente();
        $this->tab_activa = false;
        $type_p = \filter_input(INPUT_POST, 'type');
        $type_g = \filter_input(INPUT_GET, 'type');

        $type = (isset($type_p))?$type_p:$type_g;
        $this->type = $type;
        if($type == 'distrib_cliente'){
            $this->tab_activa = 'p_rutas';
            $this->tratar_cliente();
        }elseif($type == 'direccion_cliente'){
            $this->tab_activa = 'p_coordenadas';
            $this->tratar_direccion_cliente();
        }elseif($type == 'select-rutas'){
            $this->lista_rutas();
        }elseif($type == 'buscar-rutas'){
            $this->buscar_rutas();
        }elseif($type == 'select-subcanal'){
            $this->lista_subcanales();
        }

        $array_cargos_supervisores = $this->listado_cargos('SUP','array');
        $array_cargos_vendedores = $this->listado_cargos('VEN','array');
        
        $this->supervisores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'SUPERVISOR');
        $this->supervisores_libres = $this->agente->get_activos_por('codcargo',$array_cargos_supervisores,'SUPERVISOR');

        $this->vendedores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'VENDEDOR');
        $this->vendedores_libres = $this->agente->get_activos_por('codcargo',$array_cargos_vendedores,'VENDEDOR');

        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);

        $this->canales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'CANAL');
        $this->canales_activos = $this->distribucion_segmentos->activos_tiposegmento($this->empresa->id, 'CANAL');
        $this->subcanales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'SUBCANAL');

        $codcliente = \filter_input(INPUT_GET, 'cod');
        if(!empty($codcliente)){
            $this->codcliente = $codcliente;
            $this->info_cliente = $this->cliente->get($codcliente);
            $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
            $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
            $this->rutas_libres = $this->rutas_libres();
            $this->template = 'extension/distrib_cliente';
        }

    }
    
    public function listado_cargos($tipo, $respuesta = 'objeto'){
        $listado = $this->distribucion_asignacion_cargos->all_tipocargo($this->empresa->id, $tipo);
        $resultado = array();
        foreach($listado as $item){
            if($respuesta == 'array'){
                $resultado[] = $item->codcargo;
            }else{
                $resultado[] = $item;
            }
        }
        
        if($respuesta == 'json'){
            $this->template = FALSE;
            header('Content-Type: application/json');
            echo json_encode( array('success' => true, 'data' => $resultado) );
        }else{
            return $resultado;
        }
    }

    public function rutas_libres(){
        if($this->distrib_cliente){
            $ruta_tomada = array();
            foreach($this->distrib_cliente as $ruta){
                $ruta_tomada[$ruta->ruta] = "TRUE";
            }
            foreach($this->rutas as $id => $valores){
                if(isset($ruta_tomada[$valores->ruta])){
                    unset($this->rutas[$id]);
                }
            }
        }
        return $this->rutas;
    }

    public function tratar_cliente(){
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $iddireccion = \filter_input(INPUT_POST, 'iddireccion');
        $ruta = \filter_input(INPUT_POST, 'ruta');
        $canal = \filter_input(INPUT_POST, 'canal');
        $subcanal = \filter_input(INPUT_POST, 'subcanal');
        $accion = \filter_input(INPUT_POST, 'accion');
        $distcli0 = new distribucion_clientes();
        $distcli0->idempresa = $this->empresa->id;
        $distcli0->codcliente = $codcliente;
        $distcli0->codalmacen = $codalmacen;
        $distcli0->iddireccion = $iddireccion;
        $distcli0->ruta = $ruta;
        $distcli0->canal = $canal;
        $distcli0->subcanal = $subcanal;
        $distcli0->fecha_creacion = \Date('d-m-Y H:i:s');
        $distcli0->usuario_creacion = $this->user->nick;
        $distcli0->fecha_modificacion = \Date('d-m-Y H:i:s');
        $distcli0->usuario_modificacion = $this->user->nick;
        if($accion == 'eliminar'){
            $distcli0->delete();
            $this->new_message("Datos del cliente $distcli0->codcliente para la ruta $distcli0->ruta eliminados correctamente.");
        }elseif($accion=='agregar'){
            if($distcli0->save()){
                $this->new_message("Datos del cliente $distcli0->codcliente tratados correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        $this->codcliente = $codcliente;
        $this->info_cliente = $this->cliente->get($codcliente);
        $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
        $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
        $this->rutas_libres = $this->rutas_libres();
        $this->template = 'extension/distrib_cliente';
    }

    public function tratar_direccion_cliente(){
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $iddireccion = \filter_input(INPUT_POST, 'iddireccion');
        $coordenadas = \filter_input(INPUT_POST, 'coordenadas');
        $borrar = \filter_input(INPUT_POST, 'borrar');
        $distccli0 = new distribucion_coordenadas_clientes();
        $distccli0->idempresa = $this->empresa->id;
        $distccli0->codcliente = $codcliente;
        $distccli0->iddireccion = $iddireccion;
        $distccli0->coordenadas = $coordenadas;
        $distccli0->fecha_creacion = \Date('d-m-Y H:i:s');
        $distccli0->usuario_creacion = $this->user->nick;
        $distccli0->fecha_modificacion = \Date('d-m-Y H:i:s');
        $distccli0->usuario_modificacion = $this->user->nick;
        if($borrar){
            $distccli0->delete();
            $this->new_message("Coordenadas de la dirección del cliente $distccli0->codcliente eliminados correctamente.");
        }else{
            if($distccli0->save()){
                $this->new_message("Coordenadas del cliente $distccli0->codcliente tratadas correctamente.");
            }else{
                $this->new_error_msg("¡Ocurrio un error intentando guardar la coordenada ingresada, por favor revise la longitud o el tipo de dato ingresado!");
            }
        }
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        $this->codcliente = $codcliente;
        $this->info_cliente = $this->cliente->get($codcliente);
        $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
        $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
        $this->rutas_libres = $this->rutas_libres();
        $this->template = 'extension/distrib_cliente';
    }
    
    public function buscar_rutas()
    {
        $rutas = new distribucion_rutas();
        $query = \filter_input(INPUT_GET, 'q');
        $almacen = \filter_input(INPUT_GET, 'almacen');
        $data = $rutas->search($almacen,$query);
        $lista = array();
        foreach($data as $r){
            $lista[] = array('value' => $r->ruta.' - '.$r->descripcion, 'data' => $r->ruta);
        }
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode(array('query'=>$query,'suggestions'=>$lista));
    }

    public function lista_rutas(){
        $this->template = FALSE;
        $codalmacen = filter_input(INPUT_GET, 'codalmacen');
        $resultados = array();
        $data = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $codalmacen);
        foreach($data as $r){
            $item = new stdClass();
            $item->ruta = $r->ruta;
            $item->descripcion = $r->descripcion;
            $resultados[] = $item;
        }
        header('Content-Type: application/json');
        echo json_encode($resultados);
    }
    
    public function lista_subcanales(){
        $this->template = FALSE;
        $canal = filter_input(INPUT_GET, 'canal');
        $resultados = $this->distribucion_segmentos->activos_codigopadre_tiposegmento($this->empresa->id, $canal, 'SUBCANAL');
        header('Content-Type: application/json');
        echo json_encode($resultados);
    }

    private function share_extension() {
        $extensiones = array(
            array(
                'name' => 'distribucion_cliente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                'text' => '<span class="glyphicon glyphicon-transfer" aria-hidden="true"></span> &nbsp; Distribución',
                'params' => ''
            ),
            array(
                'name' => 'treeview_distribucion_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-treeview.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'treeview_distribucion_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-treeview.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => '010_distribucion_clientes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '011_distribucion_clientes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/plugins/ajax-bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '012_distribucion_clientes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/locale/ajax-bootstrap-select.es-ES.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_distribucion_clientes_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
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
                'name' => 'distribucion_cliente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                'text' => '<span class="glyphicon glyphicon-transfer" aria-hidden="true"></span> &nbsp; Distribución',
                'params' => ''
            ),
            array(
                'name' => '009_distribucion_clientes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-treeview.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            
            array(
                'name' => '001_distribucion_clientes_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-treeview.min.css"/>',
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
