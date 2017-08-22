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
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/distribucion/extras/distribucion_controller.php';
/**
 * Description of dashboard_distribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class dashboard_distribucion extends distribucion_controller {
    public $almacenes;
    public $familias;
    public $articulos;
    public $articulos_top_cantidad;
    public $articulos_top_valor;
    public $cantidad_supervisores;
    public $cantidad_vendedores;
    public $cantidad_unidades;
    public $cantidad_articulos;
    public $cantidad_clientes;
    public $clientes_activos;
    public $clientes_inactivos;
    public $clientes_nuevos;
    public $clientes_debaja;
    public $clientes_grupo;
    public $clientes_visitados;
    public $clientes_por_visitar;
    public $clientes_top;
    public $clientes_rutas;
    public $grupos_clientes;
    public $grupos_clientes_lista;
    public $facturascli;
    public $facturaspro;
    public $transportes;
    public $conductores;
    public $unidades;
    public $distribucion_clientes;
    public $distribucion_facturas;
    public $organizacion;
    public $supervisores;
    public $vendedores;
    public $mesa_trabajo;
    public $rutas;
    public $faltantes;
    public $f_desde;
    public $f_hasta;
    public $codalmacen;
    public $total;
    public $total_ingresos;
    public $total_egresos;
    public $total_cobros;
    public $total_pendientes_cobro;
    public $fileNameXLS;
    public $fileNamePDF;
    public $pathNameXLS;
    public $pathNamePDF;
    public $fileNameXLSArticulos;
    public $pathNameXLSArticulos;
    public $pdf;
    public $procesado;
    public $lista_familia;
    public $cantidad_familia;
    public $importe_familia;
    public $cantidad_referencia;
    public $importe_referencia;
    public $total_cantidad_familia;
    public $total_importe_familia;
    public $lista_fecha;
    public $lista_referencia;
    public $suma_familia;
    public $suma_fecha;
    public $suma_referencia;
    public $resumen_familia;
    public $resumen_familia_cabecera;
    public $resumen_familia_datos;
    public $resumen_familia_final;
    public $resultados_tiempo;
    public $graficos_efectividad_data;
    public $graficos_fecha_labels;
    public $graficos_fecha;
    public $rango_fechas;
    public function __construct() {
        parent::__construct(__CLASS__,'Dashboard Distribución', 'informes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        parent::private_core();
        $this->shared_extensions();
        $this->init_variables();

        //Ragno de fechas según los datos enviados
        $desde = new DateTime($this->f_desde);
        $hasta_f = new DateTime($this->f_hasta);
        $hasta = $hasta_f->modify( '+1 day' );
        //intervalo de aumento es 1 día
        $intervalo = new \DateInterval('P1D');
        $this->rango_fechas = new \DatePeriod($desde, $intervalo, $hasta);

        $this->iniciarArrayInformacion();

        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $this->generar_resumen();
                    $this->cobertura_articulos();
                    $this->top_clientes();
                    $this->top_articulos();
                    $this->procesado = TRUE;
                break;
            }
        }
    }

    public function iniciarArrayInformacion()
    {
        //Llenamos el array de fechas para los graficos
        $this->clientes_rutas = array();
        $this->clientes_rutas['general_fecha_cantidad'] = array();
        $this->clientes_rutas['general_fecha_oferta'] = array();
        $this->clientes_rutas['general_fecha_importe'] = array();
        foreach($this->rango_fechas as $fecha){
            $this->graficos_fecha_labels[] = $fecha->format('d-m-Y');
            $this->clientes_rutas['general_fecha_cantidad'][$fecha->format('d-m-Y')] = 0;
            $this->clientes_rutas['general_fecha_oferta'][$fecha->format('d-m-Y')] = 0;
            $this->clientes_rutas['general_fecha_importe'][$fecha->format('d-m-Y')] = 0;
        }
    }

    public function init_variables()
    {
        $this->almacenes = new almacen();
        $this->articulos = new articulo();
        $this->familias = new familia();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_facturas = new distribucion_facturas();
        $this->organizacion = new distribucion_organizacion();
        $this->faltantes = new distribucion_faltantes();
        $this->unidades = new distribucion_unidades();
        $this->rutas = new distribucion_rutas();
        $this->fp = new forma_pago();
        $this->grupos_clientes = new grupo_clientes();
        $this->resultados_formas_pago = false;
        $this->procesado = false;

        //Inicializamos la variable del reporte
        $this->fileName = '';

        $this->f_desde = $this->confirmarValor(\filter_input(INPUT_POST, 'f_desde'),\date('01-m-Y'));
        $this->f_hasta = $this->confirmarValor(\filter_input(INPUT_POST, 'f_hasta'),\date('d-m-Y'));
        $this->codalmacen = $this->confirmarValor($this->user->codalmacen,\filter_input(INPUT_POST, 'codalmacen'));
    }

    public function cobertura_articulos(){
        $this->cantidad_familia = array();
        $this->importe_familia = array();
        $this->cantidad_referencia = array();
        $this->importe_referencia = array();
        $this->total_cantidad_familia = 0;
        $this->total_importe_familia = 0;

        $this->resumen_familia_cabecera = array('Familia','Cantidad','Importe','% Part Cantidad','% Cantidad Importe');

        //Buscamos los productos en la fecha dada y los agrupamos por familia
        //Esta pendiente sacar la información de ventas por vendedor
        $this->generar_informacion_familias();


        //Generamos el listado de familias
        $this->resumen_familia = array();

        $this->articulos_sin_familia();

        //Agregamos las sumas de todas las familias
        $this->resumen_familias();

        //Agregamos la ultima fila de total
        $item = new stdClass();
        $item->codigo = 'TOTAL';
        $item->descripcion = 'TOTAL GENERAL';
        $item->madre = '';
        $item->cantidad = $this->total_cantidad_familia;
        $item->cantidad_pct = 100;
        $item->importe = $this->total_importe_familia;
        $item->importe_pct = 100;
        $item->tipo = 'leaf';
        $this->resumen_familia[] = $item;
    }

    public function articulos_sin_familia()
    {
        //Agregamos la suma de articulos sin familia
        $this->agregar_item(array(
            'codigo'=>'NOFAMILIA',
            'descripcion'=>'SIN FAMILIA',
            'madre'=>'',
            'cantidad'=>(isset($this->cantidad_familia['NOFAMILIA']))?$this->cantidad_familia['NOFAMILIA']:0,
            'importe'=>(isset($this->importe_familia['NOFAMILIA']))?$this->importe_familia['NOFAMILIA']:0,
            'tipo'=>'branch'
        ));
        //Agregamos los articulos sin familia
        foreach($this->articulos->all(0,10000) as $art){
            if(!$art->codfamilia){
                $this->agregar_item(array(
                    'codigo'=>$art->referencia,
                    'descripcion'=>$art->referencia.' - '.$art->descripcion,
                    'madre'=>'NOFAMILIA',
                    'cantidad'=>(isset($this->cantidad_referencia[$art->referencia]))?$this->cantidad_referencia[$art->referencia]:0,
                    'importe'=>(isset($this->importe_referencia[$art->referencia]))?$this->importe_referencia[$art->referencia]:0,
                    'tipo'=>'leaf'
                ));
            }
        }
    }

    public function generar_informacion_familias()
    {
        //generamos un listado de todas las familias para hacer una sola llamada a la db
        $f = array();
        $f['NOFAMILIA'] = 'SIN FAMILIA';
        foreach($this->familias->all() as $familia){
            $f[$familia->codfamilia] = $familia->descripcion;
        }
        
        //Si no eligen un almacén, buscamos todo
        $sql_almacen = ($this->codalmacen)?" AND fc.codalmacen = ".$this->empresa->var2str($this->codalmacen):"";

        $sql = "SELECT a.codfamilia,lf.referencia,lf.descripcion,fc.fecha,sum(lf.cantidad) as cantidad,sum(lf.pvptotal) as importe  ".
                "FROM facturascli AS fc, articulos AS a, familias AS f, lineasfacturascli as lf ".
                "WHERE fecha between ".$this->empresa->var2str($this->f_desde)." AND ".$this->empresa->var2str($this->f_hasta)." ".
                $sql_almacen.
                " AND pvptotal != 0 AND fc.idfactura = lf.idfactura ".
                "AND lf.referencia = a.referencia AND f.codfamilia = a.codfamilia and fc.anulada = FALSE ".
                "GROUP BY a.codfamilia,lf.referencia,lf.descripcion,fc.fecha".
               ";";
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                if(empty($d['codfamilia'])){
                    $d['codfamilia'] = 'NOFAMILIA';
                }
                if(!isset($this->cantidad_familia[$d['codfamilia']])){
                    $this->cantidad_familia[$d['codfamilia']] = 0;
                    $this->importe_familia[$d['codfamilia']] = 0;
                }
                if(!isset($this->cantidad_referencia[$d['referencia']])){
                    $this->cantidad_referencia[$d['referencia']] = 0;
                    $this->importe_referencia[$d['referencia']] = 0;
                }
                $this->cantidad_familia[$d['codfamilia']] += $d['cantidad'];
                $this->importe_familia[$d['codfamilia']] += $d['importe'];
                $this->cantidad_referencia[$d['referencia']] += $d['cantidad'];
                $this->importe_referencia[$d['referencia']] += $d['importe'];
                $this->total_cantidad_familia += $d['cantidad'];
                $this->total_importe_familia += $d['importe'];
                //datos para el reporte por fecha
                $this->resultados_tiempo[] = array('familia'=>$f[$d['codfamilia']],'fecha'=>$d['fecha'],'articulo'=>$d['referencia'].' '.$d['descripcion'],'cantidad'=>$d['cantidad'],'importe'=>$d['importe']);
            }
        }

        //Buscamos en el arbol de familias para agregar los valores de sus hijos y así tener el arbol totalizado
        foreach($this->cantidad_referencia as $ref=>$cantidad){
            $art = $this->articulos->get($ref);
            $codfamilia = ($art->codfamilia)?$art->codfamilia:'NOFAMILIA';
            $this->sumar_valores_familias($codfamilia,$ref);
        }
    }

    /**
     * Funcion para controlar la información a ser procesada del resultado por familias de artículos
     * @param type $data
     */
    private function agregar_item($data){
        $item = new stdClass();
        $item->codigo = $data['codigo'];
        $item->descripcion = $data['descripcion'];
        $item->madre = $data['madre'];
        $item->cantidad = $data['cantidad'];
        $item->cantidad_pct = ($data['cantidad'])?round(($data['cantidad']/$this->total_cantidad_familia)*100,2):0;
        $item->importe = $data['importe'];
        $item->importe_pct = ($data['importe'])?round(($data['importe']/$this->total_importe_familia)*100,2):0;
        $item->tipo = $data['tipo'];
        $this->resumen_familia[] = $item;
        $this->resumen_familia_datos = array($item->descripcion,$item->cantidad, $item->importe, $item->cantidad_pct, $item->importe_pct);
    }

    /**
     * Sumamos los valores de cada familia de los productos para así tener
     * el resumen totalizado por cada familia
     * aqui llega el codigo de la familia y el codigo de de donde sacaremos la cantidad a sumar
     * y se busca la familia madre
     * @param type $cod codigo de familia
     * @param type $ref codigo del articulo
     * @return type boolean
     */
    public function sumar_valores_familias($cod,&$ref){
        $familia = $this->familias->get($cod);
        if($familia->madre){
            if(!isset($this->cantidad_familia[$familia->madre])){
                $this->cantidad_familia[$familia->madre] = 0;
                $this->importe_familia[$familia->madre] = 0;
            }
            $this->cantidad_familia[$familia->madre] += $this->cantidad_referencia[$ref];
            $this->importe_familia[$familia->madre] += $this->importe_referencia[$ref];
            $this->sumar_valores_familias($familia->madre,$ref);
        }else{
            return true;
        }
    }

    /**
     * Listamos las familias para llenar un treetable
     * @param type $madre
     * @return \stdClass
     */
    public function resumen_familias($madre = FALSE){
        $familias = $this->familias->madres();
        if($madre){
            $familias = $this->familias->hijas($madre);
        }
        if($familias){
            foreach($familias as $fam){
                $this->agregar_item(array(
                    'codigo'=>$fam->codfamilia,
                    'descripcion'=>$fam->descripcion,
                    'madre'=>$fam->madre,
                    'cantidad'=>(isset($this->cantidad_familia[$fam->codfamilia]))?$this->cantidad_familia[$fam->codfamilia]:0,
                    'importe'=>(isset($this->importe_familia[$fam->codfamilia]))?$this->importe_familia[$fam->codfamilia]:0,
                    'tipo'=>'branch'
                ));
                if($fam->get_articulos()){
                    foreach($fam->get_articulos() as $art){
                        $this->agregar_item(array(
                            'codigo'=>$art->referencia,
                            'descripcion'=>$art->referencia.' - '.$art->descripcion,
                            'madre'=>$art->codfamilia,
                            'cantidad'=>(isset($this->cantidad_referencia[$art->referencia]))?$this->cantidad_referencia[$art->referencia]:0,
                            'importe'=>(isset($this->importe_referencia[$art->referencia]))?$this->importe_referencia[$art->referencia]:0,
                            'tipo'=>'leaf'
                        ));
                    }
                }
                if($fam->hijas()){
                    $this->resumen_familias($fam->codfamilia);
                }
            }
        }else{
            return false;
        }
    }

    /**
     * Extraemos el arbol de familias para armar la pertenencia de una familia hacia atras
     * Esto nos sirve cuando queremos saber si la familia a la que pertenece un producto esta
     * vunculada a otras familias hasta llegar a la familia madre final
     * @param type $codfamilia string
     * @param type $resultado array
     * @return array
     */
    public function arbol_familia($codfamilia,&$resultado = array()){
        $data = $this->familias->get($codfamilia);
        if ($data) {
            $resultado[] = array('codigo' => $data->codfamilia, 'descripcion' => $data->descripcion);
            if ($data->madre) {
                $resultado[] = array('codigo' => $data->codfamilia, 'descripcion' => $data->descripcion);
                $this->arbol_familia($data->madre, $resultado);
            }else{
                return $resultado;
            }
        }
    }

    private function colorVariable($valor)
    {
        $color = 'success';
        if($valor <= 30){
            $color = 'danger';
        }elseif($valor > 30 and $valor < 65){
            $color = 'warning';
        }
        return $color;
    }

    public function resumen_clientes()
    {
        //Inicializamos los contadores de informacion
        $clientes_estado = $this->distribucion_clientes->clientes_totales_estado($this->codalmacen,$this->f_desde,$this->f_hasta);
        $this->clientes_activos = $clientes_estado['activos'];
        $this->clientes_inactivos = $clientes_estado['inactivos'];
        $this->clientes_nuevos = $clientes_estado['nuevos'];
        $this->clientes_debaja = $clientes_estado['debaja'];
        $this->clientes_visitados = $this->distribucion_facturas->clientes_visitados($this->codalmacen, FALSE, $this->f_desde, $this->f_hasta);
        $this->clientes_por_visitar = $this->clientes_activos-$this->clientes_visitados;
        $this->cantidad_clientes = $this->clientes_activos;
        $this->clientes_grupo = array();

        //Guardamos la cantidad de clientes por cada grupo
        $this->grupos_clientes_lista = array();
        foreach($this->grupos_clientes->all() as $gc){
            $gc->clientes = $gc->cantidad_clientes_total();
            $this->grupos_clientes_lista[] = $gc;
        }

        if($this->grupos_clientes->cantidad_clientes_singrupo()){
            $singrupo = new grupo_clientes();
            $singrupo->codgrupo = 'SG';
            $singrupo->nombre = 'Clientes in Grupo';
            $singrupo->clientes = $singrupo->cantidad_clientes_singrupo();
            $this->grupos_clientes_lista[] = $singrupo;
        }
    }

    public function resumen_clientes_variables()
    {
        //Generamos la efectividad de visitas
        //La efectividad es el porcentaje de clientes visitados entre la cantidad de clientes totales
        $this->clientes_rutas['total'] = array();
        $this->clientes_rutas['atendidos'] = array();
        $this->clientes_rutas['no_atendidos'] = array();
        $this->clientes_rutas['efectividad'] = array();
        $this->clientes_rutas['efectividad_vendedor'] = array();
        $this->clientes_rutas['total_rutas'] = array();
        $this->clientes_rutas['total_clientes'] = array();
        $this->clientes_rutas['total_atendidos'] = array();
        $this->clientes_rutas['total_no_atendidos'] = array();
        //La mesa es el grupo de vendedores que tiene un supervisor
        $this->clientes_rutas['mesa_rutas'] = array();
        $this->clientes_rutas['mesa_vendedores'] = array();
        $this->clientes_rutas['mesa_clientes'] = array();
        $this->clientes_rutas['mesa_atendidos'] = array();
        $this->clientes_rutas['mesa_no_atendidos'] = array();
        $this->clientes_rutas['mesa_efectividad'] = array();
        $this->clientes_rutas['general_total_cantidad'] = 0;
        $this->clientes_rutas['general_total_importe'] = 0;
        $this->clientes_rutas['general_total_oferta'] = 0;
        $this->clientes_rutas['general_clientes'] = 0;
        $this->clientes_rutas['general_atendidos'] = 0;
        $this->clientes_rutas['general_no_atendidos'] = 0;
        $this->clientes_rutas['general_efectividad'] = 0;
        $this->clientes_rutas['efectividad_general_color'] = false;
    }

    public function resumen_supervisores_variables()
    {
        //Generamos los totales por supervisor
        foreach($this->supervisores as $supervisor){
            $this->clientes_rutas['mesa_rutas'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_vendedores'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_clientes'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_atendidos'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_no_atendidos'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_efectividad'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_total_cantidad'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_total_importe'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_total_oferta'][$supervisor->codagente] = 0;
            foreach($this->rango_fechas as $fecha){
                $this->clientes_rutas['mesa_fecha_cantidad'][$supervisor->codagente][$fecha->format('d-m-Y')] = 0;
                $this->clientes_rutas['mesa_fecha_importe'][$supervisor->codagente][$fecha->format('d-m-Y')] = 0;
                $this->clientes_rutas['mesa_fecha_oferta'][$supervisor->codagente][$fecha->format('d-m-Y')] = 0;
            }
        }
    }

    public function resumen_vendedor_variables($vendedor, $rutasagente)
    {
        $this->clientes_rutas['total_rutas'][$vendedor->codagente] = count($rutasagente);
        $this->clientes_rutas['total_clientes'][$vendedor->codagente] = 0;
        $this->clientes_rutas['total_atendidos'][$vendedor->codagente] = 0;
        $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente] = 0;
        $this->clientes_rutas['total_cantidad'][$vendedor->codagente] = 0;
        $this->clientes_rutas['total_importe'][$vendedor->codagente] = 0;
        $this->clientes_rutas['total_oferta'][$vendedor->codagente] = 0;
        $this->clientes_rutas['mesa'][$vendedor->codsupervisor] = 0;
        foreach($this->rango_fechas as $fecha){
            $this->clientes_rutas['fecha_cantidad'][$vendedor->codagente][$fecha->format('d-m-Y')] = 0;
            $this->clientes_rutas['fecha_importe'][$vendedor->codagente][$fecha->format('d-m-Y')] = 0;
            $this->clientes_rutas['fecha_oferta'][$vendedor->codagente][$fecha->format('d-m-Y')] = 0;
        }
    }

    public function resumen_venta_rutas($ventas_ruta, $vendedor, $ruta)
    {
        foreach($ventas_ruta as $d){
            $fecha = \date('d-m-Y',strtotime($d->fecha));
            $this->clientes_rutas['cantidad'][$ruta->ruta] += $d->qdad_vendida;
            $this->clientes_rutas['importe'][$ruta->ruta] += $d->importe_vendido;
            $this->clientes_rutas['oferta'][$ruta->ruta] += $d->qdad_oferta;
            $this->clientes_rutas['total_cantidad'][$vendedor->codagente] += $d->qdad_vendida;
            $this->clientes_rutas['total_importe'][$vendedor->codagente] += $d->importe_vendido;
            $this->clientes_rutas['total_oferta'][$vendedor->codagente] += $d->qdad_oferta;
            $this->clientes_rutas['fecha_cantidad'][$vendedor->codagente][$fecha] += $d->qdad_vendida;
            $this->clientes_rutas['fecha_importe'][$vendedor->codagente][$fecha] += $d->importe_vendido;
            $this->clientes_rutas['fecha_oferta'][$vendedor->codagente][$fecha] += $d->qdad_oferta;
            $this->clientes_rutas['mesa_total_cantidad'][$vendedor->codsupervisor] += $d->qdad_vendida;
            $this->clientes_rutas['mesa_total_importe'][$vendedor->codsupervisor] += $d->importe_vendido;
            $this->clientes_rutas['mesa_total_oferta'][$vendedor->codsupervisor] += $d->qdad_oferta;
            $this->clientes_rutas['mesa_fecha_cantidad'][$vendedor->codsupervisor][$fecha] += $d->qdad_vendida;
            $this->clientes_rutas['mesa_fecha_importe'][$vendedor->codsupervisor][$fecha] += $d->importe_vendido;
            $this->clientes_rutas['mesa_fecha_oferta'][$vendedor->codsupervisor][$fecha] += $d->qdad_oferta;
            $this->clientes_rutas['general_fecha_cantidad'][$fecha] += $d->qdad_vendida;
            $this->clientes_rutas['general_fecha_importe'][$fecha] += $d->importe_vendido;
            $this->clientes_rutas['general_fecha_oferta'][$fecha] += $d->qdad_oferta;
            $this->clientes_rutas['general_total_cantidad'] += $d->qdad_vendida;
            $this->clientes_rutas['general_total_importe'] += $d->importe_vendido;
            $this->clientes_rutas['general_total_oferta'] += $d->qdad_oferta;
        }
    }

    public function resumen_rutas_vendedor($rutasagente, $vendedor)
    {
        foreach($rutasagente as $ruta){
            $clientes_ruta = $this->rutas->cantidad_asignados($this->empresa->id, $ruta->codalmacen, $ruta->ruta);
            $this->clientes_rutas['total'][$ruta->ruta] = $clientes_ruta;
            $this->clientes_rutas['total_clientes'][$vendedor->codagente] += $clientes_ruta;
            if(!isset($this->clientes_rutas['atendidos'][$ruta->ruta])){
                $this->clientes_rutas['atendidos'][$ruta->ruta] = 0;
                $this->clientes_rutas['cantidad'][$ruta->ruta] = 0;
                $this->clientes_rutas['importe'][$ruta->ruta] = 0;
                $this->clientes_rutas['oferta'][$ruta->ruta] = 0;
            }
            if(!isset($this->clientes_rutas['no_atendidos'][$ruta->ruta])){
                $this->clientes_rutas['no_atendidos'][$ruta->ruta] = $clientes_ruta;
            }

            $this->clientes_rutas['atendidos'][$ruta->ruta] = $this->distribucion_facturas->clientes_visitados($ruta->codalmacen, $ruta->ruta, $this->f_desde, $this->f_hasta);
            $this->clientes_rutas['no_atendidos'][$ruta->ruta] -= $this->clientes_rutas['atendidos'][$ruta->ruta];
            $efectividad = $this->porcentajeInformacion($this->clientes_rutas['atendidos'][$ruta->ruta],$clientes_ruta);
            $this->clientes_rutas['efectividad'][$ruta->ruta] =$efectividad;
            $this->clientes_rutas['efectividad_color'][$ruta->ruta] = $this->colorVariable($efectividad);
            $this->clientes_rutas['total_atendidos'][$vendedor->codagente] += $this->clientes_rutas['atendidos'][$ruta->ruta];
            $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente] += $this->clientes_rutas['no_atendidos'][$ruta->ruta];

            //Generamos la estadistica de ventas cantidad vendida, importe vendido, cantidad bonificada y devoluciones
            $ventas_ruta = $this->distribucion_facturas->ventas_ruta($ruta->codalmacen, $ruta->ruta, $this->f_desde, $this->f_hasta);
            if(!empty($ventas_ruta)){
                $this->resumen_venta_rutas($ventas_ruta, $vendedor, $ruta);
            }
        }
    }

    public function porcentajeInformacion($x, $y)
    {
        $z = 0;
        if($y != 0){
            $z = (($x/$y)*100);
        }
        return round($z, 0);
    }

    public function generar_resumen(){
        //Obtenemos la información de los supervisores
        //Verificamos el codigo de almacen para supervisores
        $this->supervisores = $this->organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'SUPERVISOR');
        $no_supervisor = new distribucion_organizacion();
        $no_supervisor->codagente = "NOSUPERVISOR";
        $no_supervisor->nombre = "SIN SUPERVISOR";
        $no_supervisor->tiene_asignados = 0;
        $no_supervisor->tiene_rutas_asignadas = 0;
        $this->supervisores[] = $no_supervisor;
        $this->cantidad_supervisores = count($this->supervisores);
        $this->mesa_trabajo = array();
        foreach($this->supervisores as $sup){
            $vendedores = $this->organizacion->get_asignados($this->empresa->id, $sup->codagente, $this->codalmacen);
            $this->mesa_trabajo[$sup->codagente] = $vendedores;
        }

        //Obtenemos la información de los vendedores
        //Verificamos el codigo de almacen para vendedores
        $this->vendedores = $this->organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        $this->cantidad_vendedores = count($this->vendedores);
        $unidades = $this->unidades->activos_almacen($this->empresa->id, $this->codalmacen);
        $this->cantidad_unidades = count($unidades);

        $articulos = $this->articulos->all();
        $this->cantidad_articulos = 0;
        foreach($articulos as $art){
            if($art->sevende AND !$art->nostock){
                $this->cantidad_articulos++;
            }
        }

        $this->resumen_clientes();

        $this->resumen_clientes_variables();

        $this->resumen_supervisores_variables();
        foreach($this->vendedores as $vendedor){
            $rutasagente = $this->rutas->all_rutasporagente($this->empresa->id, $vendedor->codalmacen, $vendedor->codagente);
            $vendedor->codsupervisor = $this->confirmarValor($vendedor->codsupervisor,"NOSUPERVISOR");
            $this->resumen_vendedor_variables($vendedor, $rutasagente);
            if(!empty($rutasagente)){
                $this->resumen_rutas_vendedor($rutasagente, $vendedor);
            }

            $efectividad_vendedor = $this->porcentajeInformacion($this->clientes_rutas['total_atendidos'][$vendedor->codagente],$this->clientes_rutas['total_clientes'][$vendedor->codagente]);

            $this->clientes_rutas['efectividad_vendedor'][$vendedor->codagente] = $efectividad_vendedor;
            $efectividad_color = $this->colorVariable($efectividad_vendedor);
            $this->clientes_rutas['efectividad_vendedor_color'][$vendedor->codagente] = $efectividad_color;
            $this->clientes_rutas['mesa_rutas'][$vendedor->codsupervisor] += $this->clientes_rutas['total_rutas'][$vendedor->codagente];
            $this->clientes_rutas['mesa_vendedores'][$vendedor->codsupervisor]++;
            $this->clientes_rutas['mesa_clientes'][$vendedor->codsupervisor] += $this->clientes_rutas['total_clientes'][$vendedor->codagente];
            $this->clientes_rutas['mesa_atendidos'][$vendedor->codsupervisor] += $this->clientes_rutas['total_atendidos'][$vendedor->codagente];
            $this->clientes_rutas['mesa_no_atendidos'][$vendedor->codsupervisor] += $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente];
            $this->clientes_rutas['general_clientes'] += $this->clientes_rutas['total_clientes'][$vendedor->codagente];
            $this->clientes_rutas['general_atendidos'] += $this->clientes_rutas['total_atendidos'][$vendedor->codagente];
            $this->clientes_rutas['general_no_atendidos'] += $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente];
        }

        //Generamos la estadistica por supervisor
        foreach($this->supervisores as $supervisor){
            $efectividad_supervisor = $this->porcentajeInformacion($this->clientes_rutas['mesa_atendidos'][$supervisor->codagente],$this->clientes_rutas['mesa_clientes'][$supervisor->codagente]);
            $this->clientes_rutas['mesa_efectividad'][$supervisor->codagente] = $efectividad_supervisor;
            $efectividad_color = $this->colorVariable($efectividad_supervisor);
            $this->clientes_rutas['efectividad_mesa_color'][$supervisor->codagente] = $efectividad_color;
        }

        if($this->clientes_rutas['general_clientes']){
            $efectividad_general = round(($this->clientes_rutas['general_atendidos']/$this->clientes_rutas['general_clientes'])*100,0);
            $this->clientes_rutas['general_efectividad'] = $efectividad_general;
            $efectividad_color = $this->colorVariable($efectividad_general);
            $this->clientes_rutas['efectividad_general_color'] = $efectividad_color;
        }

    }

    //Generamos el listado de los 10 clientes que más compran
    public function top_clientes($cantidad=10,$excluidos=false){
        $clientes = ($excluidos)?" AND codcliente NOT IN (".$excluidos.")":"";
        $sql_almacen = ($this->codalmacen)?" AND codalmacen = ".$this->empresa->var2str($this->codalmacen)." ":"";
        $sql = "SELECT codcliente,nombrecliente,sum(total) as suma ".
                " FROM facturascli ".
                " WHERE anulada = false $clientes and idfacturarect is null ".
                $sql_almacen.
                " AND fecha between '".\date('Y-m-d',strtotime($this->f_desde))."' AND '".\date('Y-m-d',strtotime($this->f_hasta))."' ".
                "GROUP BY codcliente,nombrecliente ORDER BY suma DESC LIMIT $cantidad;";
        $data = $this->db->select($sql);
        $this->clientes_top = array();
        $i=0;
        if($data){
            foreach($data as $d){
                $cliente_top = new stdClass();
                $cliente_top->codcliente = $d['codcliente'];
                $cliente_top->nombrecliente = $d['nombrecliente'];
                $cliente_top->totalventa = $d['suma'];
                $this->clientes_top[] = $cliente_top;
                $i++;
            }
        }
    }


    //Generamos el listado de los 10 productos mas vendidos
    public function top_articulos_oferta($cantidad=10,$excluidos=false){
        $this->articulos_oferta_top_cantidad = array();
        $this->articulos_oferta_top_valor = array();
        //Buscamos primero la suma por cantidad
        $sql_almacen = ($this->codalmacen)?" AND codalmacen = ".$this->empresa->var2str($this->codalmacen)." ":"";
        $referencias = ($excluidos)?" AND referencia NOT IN (".$excluidos.")":"";
        $sql1 = "select referencia, descripcion, sum(cantidad) as cantidad from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli ".
                "where fecha between ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->f_desde)))." and ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->f_hasta))).
                $sql_almacen.
                " and anulada = FALSE) AND pvptotal = 0".
                " $referencias group by referencia, descripcion order by cantidad DESC limit $cantidad;";
        $data1 = $this->db->select($sql1);

        $i=0;
        if($data1){
            foreach($data1 as $d){
                $this->articulos_top_cantidad[] = $this->articuloSimple($d);
                $i++;
            }
        }
    }

    public function articuloSimple($d)
    {
        $articulo_top = new stdClass();
        $articulo_top->referencia = $d['referencia'];
        $articulo_top->descripcion = $d['descripcion'];
        $articulo_top->totalventa = $d['cantidad'];
        return $articulo_top;
    }

    //Generamos el listado de los 10 productos mas vendidos
    public function top_articulos($cantidad=10,$excluidos=false){
        $this->articulos_top_cantidad = array();
        $this->articulos_top_valor = array();
        //Buscamos primero la suma por cantidad
        $sql_almacen = ($this->codalmacen)?" AND codalmacen = ".$this->empresa->var2str($this->codalmacen)." ":"";
        $referencias = ($excluidos)?" AND referencia NOT IN (".$excluidos.")":"";
        $sql1 = "select referencia, descripcion, sum(cantidad) as cantidad from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli ".
                "where fecha between ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->f_desde)))." and ".
                $this->empresa->var2str(\date('Y-m-d',strtotime($this->f_hasta))).
                $sql_almacen.
                " and anulada = FALSE) AND pvptotal != 0".
                " $referencias group by referencia, descripcion order by cantidad DESC limit $cantidad;";
        $data1 = $this->db->select($sql1);

        $i=0;
        if($data1){
            foreach($data1 as $d){
                $this->articulos_top_cantidad[] = $this->articuloSimple($d);
                $i++;
            }
        }

        //Buscamos la suma por previo de venta total
        $sql2 = "select referencia, descripcion, sum(pvptotal) as total from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli where ".
                " fecha between ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->f_desde)))." and "
                .$this->empresa->var2str(\date('Y-m-d',strtotime($this->f_hasta))).
                $sql_almacen." and anulada = FALSE) ".
                " $referencias group by referencia, descripcion order by total DESC limit $cantidad;";
        $data2 = $this->db->select($sql2);

        $ii=0;
        if($data2){
            foreach($data2 as $d){
                $articulo_top = new stdClass();
                $articulo_top->referencia = $d['referencia'];
                $articulo_top->descripcion = $d['descripcion'];
                $articulo_top->totalventa = $d['total'];
                $this->articulos_top_valor[] = $articulo_top;
                $ii++;
            }
        }

    }

    public function generar_excel($archivo = 'archivo',$cabecera=array(),$datos = array(), $final = array(),$nombre_hoja = 'Reporte FS'){
        //Revisamos que no haya un archivo ya cargado
        $pathName = $this->distribucionDir . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        $fileName = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        //Variables para cada parte del excel
        $estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $estilo_cuerpo = array( array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'center'),array('halign'=>'none'));
        $estilo_pie = array('border'=>'left,right,top,bottom','font-style'=>'bold','color'=>'#FFFFFF','fill'=>'#000000');

        //Inicializamos la clase
        $this->writer = new XLSXWriter();
        //Creamos la hoja
        $this->writer->writeSheetHeader($nombre_hoja, array(), true);
        //Agregamos la linea de titulo
        $this->writer->writeSheetRow($nombre_hoja, $cabecera,$estilo_cabecera);
        //Agregamos cada linea en forma de array
        foreach($datos as $linea){
            $this->writer->writeSheetRow($nombre_hoja, $linea,$estilo_cuerpo);
        }
        //Agregamos el final
        $this->writer->writeSheetRow($nombre_hoja, $final,$estilo_pie);
        //Escribimos
        $this->writer->writeToFile($pathName);
        //Devolvemos el nombre del archivo generado
        return $fileName;
    }

    public function shared_extensions(){
        $extensiones = array(

            array(
                'name' => 'dashboard_distribucion_chartjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'view/js/chart.bundle.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'css001_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-table.min.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'css002_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-table/extensions/group-by-v2/bootstrap-table-group-by.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'css003_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="'.FS_PATH.'plugins/distribucion/view/js/pivottable/pivot.min.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'css004_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="'.FS_PATH.'plugins/distribucion/view/js/jquery-treetable/jquery.treetable.min.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'css005_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="'.FS_PATH.'plugins/distribucion/view/js/jquery-treetable/jquery.treetable.theme.default.min.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'js001_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-table/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js002_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-table/bootstrap-table-locale-all.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js003_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/tableExport/libs/FileSaver/FileSaver.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js004_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/tableExport/libs/jsPDF/jspdf.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js005_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/tableExport/libs/jsPDF-AutoTable/jspdf.plugin.autotable.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js006_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/tableExport/libs/js-xlsx/xlsx.core.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js007_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/tableExport/tableExport.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js008_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-table/extensions/group-by-v2/bootstrap-table-group-by.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js009_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/pivottable/pivot.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js010_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/pivottable/pivot.es_DO.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'js011_dashboard_distribucion',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/jquery-treetable/jquery.treetable.min.js" type="text/javascript"></script>',
                'params' => ''
            ),

        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Error al guardar la extensión ' . $ext['name']);
            }
        }

        $extensiones2 = array(
        array(
                'name' => 'dashboard_distribucion_momentjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/moment-with-locales.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );
        foreach ($extensiones2 as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->delete()) {
                $this->new_error_msg('Error al eliminar la extensión ' . $ext['name']);
            }
        }
    }

    /**
     * @url http://snippets.khromov.se/convert-comma-separated-values-to-array-in-php/
     * @param $string - Input string to convert to array
     * @param string $separator - Separator to separate by (default: ,)
     *
     * @return array
     */
    private function comma_separated_to_array($string, $separator = ',') {
        //Explode on comma
        $vals = explode($separator, $string);

        //Trim whitespace
        foreach ($vals as $key => $val) {
            $vals[$key] = trim($val);
        }
        //Return empty array if no items found
        //http://php.net/manual/en/function.explode.php#114273
        return array_diff($vals, array(""));
    }
}
