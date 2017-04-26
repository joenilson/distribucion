<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('almacen.php');
require_model('distribucion_agente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_asignacion_cargos.php');
/**
 * Description of distrib_supervisores
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_supervisores extends fs_controller{
    public $almacen;
    public $codalmacen;
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
    public function __construct() {
        parent::__construct(__CLASS__, 'Configuración de Supervisores', 'distribucion', FALSE, FALSE, FALSE);
    }
    
    protected function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();
        $this->distribucion_asignacion_cargos = new distribucion_asignacion_cargos();
        
        $codalmacen = \filter_input(INPUT_POST, 'b_codalmacen');
        $this->codalmacen = ($codalmacen)?$codalmacen:false;

        $accion = \filter_input(INPUT_POST, 'accion');
        if($accion){
            $this->tratar_supervisor($accion);
        }
        
        $array_cargos_supervisores = $this->listado_cargos('SUP','array');
        $this->supervisores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'SUPERVISOR');
        $this->supervisores_libres = $this->distribucion_organizacion->get_noasignados_all($this->empresa->id,$array_cargos_supervisores,'SUPERVISOR');
        
        if($this->codalmacen){
            $this->supervisores_asignados = $this->distribucion_organizacion->all_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'SUPERVISOR');
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

    public function tratar_supervisor($accion){
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codagente = \filter_input(INPUT_POST, 'codagente');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $agente0 = new distribucion_organizacion();
        $agente0->idempresa = $this->empresa->id;
        $agente0->codalmacen = $codalmacen;
        $agente0->codagente = $codagente;
        $agente0->tipoagente = 'SUPERVISOR';
        $agente0->estado = $estado;
        $agente0->usuario_creacion = $this->user->nick;
        $agente0->fecha_creacion = \Date('d-m-Y H:i:s');
        if($accion=='eliminar'){
            $agente0->delete();
            $this->new_message("$agente0->tipoagente desasignado correctamente.");
        }else{
            if($agente0->save()){
                $agente = $this->agente->get($agente0->codagente);
                $this->new_message("$agente->nombreap tratado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del ".$agente0->tipoagente."!");
            }
        }
    }
    
    public function shared_extensions(){
        
    }
}
