<?php
require APPPATH.'/core/REST_Controller.php';
class Especificacion extends REST_Controller{//MY_BackendController {
    
    
        /**
     * Operación que despliega lista de servicios.
     */
    public function procesos_get(){
        try{
            //print_r(Cuenta::cuentaSegunDominio());die;
            $tarea=Doctrine::getTable('Proceso')->findProcesosExpuestos(Cuenta::cuentaSegunDominio()->id);
            log_message('debug','Recuperando procesos expuestos: '.count($tarea));
            $result = array();
            $host = $this->input->server('HTTP_HOST');
            //$nombre_host = gethostname();
            (isset($_SERVER['HTTPS']) ? $protocol = 'https://' : $protocol = 'http://');
            foreach($tarea as $res ){
                array_push($result, array(
                    "id" => $res['id'],
                    "nombre" => $res['nombre'],
                    "tarea" => $res['tarea'],
                    "version" => "1.0",
                    "institucion" => $res['nombre_cuenta'],
                    "descripcion" => $res['previsualizacion'],
                    "URL" => $protocol.$host.'/integracion/especificacion/servicio/proceso/'.$res['id'].'/tarea/'.$res['id_tarea']
                ));
            }
            $retval["catalogo"] = $result;

            $this->response($retval);
        }catch(Exception $e){
            $this->response(
                array("message" => "Problemas internos al recuperar especificación de procesos",
                    "code" => 500),500);
        }

    }
    
    
    /**
     * Llamadas de la API
     * Tramote id es el identificador del proceso
     *
     * 
     * @param type $operacion Operación que se ejecutara. Corresponde al tercer segmebto de la URL
     * @param type $id_proceso
     * @param type $id_tarea
     * @param type $id_paso
     */

    public function servicio_get(){
        try{
            $param = $this->get();
            $id_proceso = $param['proceso'];
            $id_tarea = $param['tarea'];

            if($id_proceso == NULL || $id_tarea == NULL ){
                $this->response(array('status' => false, 'error' => 'Parámetros no validos'), 400);
            }

            $this->load->helper('download');

            $integrador = new IntegracionMediator();
            $swagger = new Swagger();
                /* Siempre obtengo el paso número 1 para generar el swagger de la opracion iniciar trámite */
            $formulario = $integrador->obtenerFormularios($id_proceso, $id_tarea, 1);
            $swagger_file = $swagger->generar_swagger($formulario, $id_proceso, $id_tarea);

            force_download("start_simple.json", $swagger_file);
            exit;
        }catch(Exception $e){
               $this->response(
                       array("code"=> $e->getCode(),
                           "message"=>$e->getMessage()),
                       $e->getCode());
        
        }
    }
    /**
     * Para obtener la especificación de formularios
     */
    public function formularios_get(){
        $param = $this->get();
        
        if(!isset($param['proceso'])){
            $this->response(
                    array('codigo' => 400, 
                        'message' => 'Parámetros obligatorios no enviados'), 400);
       
        }
        $id_proceso = $param['proceso'];
        $id_tarea = isset($param['tarea']) ? $param['tarea'] : null;
        $id_paso = isset($param['paso']) ? $param['paso'] : null; 
        
        if(!is_null($id_proceso) && !is_numeric($id_proceso)){
             $this->response(array("code"=> 400,
                "message"=> 'Proceso debe ser un valor numérico')
                    ,400);
        }
        
        if(!is_null($id_tarea) && !is_numeric($id_tarea)){
            $this->response(array("code"=> 400,
                "message"=> 'Terea debe ser distinto a null y un valor numérico')
                    ,400);
        }
        
        if(isset($id_paso) && !is_null($id_paso) && $id_paso < 1 ){
            $this->response(array("code"=> 400,
                "message"=> 'El valor de paso no es válido, debe ser número >= 1')
                    ,400);
        }
        
        try{
            $integrador = new IntegracionMediator();
            $response = $integrador->obtenerFormularios($id_proceso, $id_tarea, $id_paso);
            $this->response($response);
        }catch(Exception $e){
            log_message('error',$e->getMessage());
            $this->response(array("code"=> $e->getCode(),"message"=>$e->getMessage()),$e->getCode());
        }
    }
    
}
