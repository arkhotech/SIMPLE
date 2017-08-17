<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//Esta funcion debería estar en modelo

class FormNormalizer{
    
    private function mapType($campo){
        switch ($campo['tipo']){
            case "file": return "base64";
            case "checkbox": return "boolean";
            case "grid": return "grid";
            case "date" : return "date";
            case "subtitle" : return "string";
            default: return "string";
        }
    }
   
    private function getValueDomain($campo=NULL){
        
        return "0";
    }
            
    function normalizarFormulario($json,$id){
        
        if($id==NULL){
            throw new Exception("El formulario viene sin ID");
        }
       
        $retval['form'] = array('id' => $id, 'campos' => array() );
        //print_r($json);
        
        foreach( $json['Campos'] as $campo){
            if($campo['tipo'] == "subtitle"){
                continue;  //se ignoran los campos de tipo subtitle
            }
            array_push($retval['form']['campos'], 
            
                  array( 
                    "nombre" => $campo['nombre'],
                    "tipo_control" => $campo['tipo'],
                    "tipo" => $this->mapType($campo),  //$campo['dependiente_tipo'],
                    "obligatorio" => ($campo['readonly']==0) ? false : true,
                    "dominio_valores" => ($this->mapType($campo) == "grid") ? $campo["extra"] :$campo['datos'])
                    );
                
        }
        return $retval;
    }
    /**
     * 
     * 
     * @param type $proceso_id Identificador de tramite
     * @param type $id_tarea Identificador de tarea 
     * @param type $id_paso Identificador de paso (opcional)
     * @return array Retorna uno o varios formularios normalizados
     */
    function obtenerFormularios($proceso_id,$id_tarea, $id_paso = NULL){
        
        //Paso uno, obtener las tareas que son de inicio
        //Trae todos los formularios del proceso, si no se especifica tarea ni paso
        $result = array();
        if($id_tarea== NULL && $id_paso == NULL){
            $tramite = Doctrine::getTable('Proceso')->find($proceso_id);

            foreach($tramite->Formularios as $form){
                $formSimple = Doctrine::getTable('Formulario')->find($form->id)->exportComplete();
                $json = json_decode($formSimple,true);
                array_push($result,$this->normalizarFormulario($json,$form->id));

            }
            return $result;
        }else{
            $tarea = Doctrine::getTable("Tarea")->find($id_tarea);
            if( $tarea->proceso_id === $proceso_id ){  //Si pertenece al proceso
                foreach($tarea->Pasos as $paso ){ //Se extraen los pasos
                    //print_r($paso);
                    if( $id_paso != NULL && $paso->Formulario->id != $id_paso ){
                        continue;
                    }
                    $formSimple = 
                            Doctrine::getTable('Formulario') ->find($paso->Formulario->id)->exportComplete();
                    $json = json_decode($formSimple,true);
                    array_push($result,$this->normalizarFormulario($json,$paso->Formulario->id));
                }
                
                return $result;
            }
            
        }
        
    }
    /**
     * Obtiene directamente un formulario
     * @param type $form_id
     */
    function obtenerFormulario($form_id){
        $formSimple = Doctrine::getTable('Formulario') ->find($form_id)->exportComplete();
        if($formSimple == NULL){
            throw new Exception("Fomrulario $form_id no existe");
        }
        $data = json_decode($formSimple,true);
        return $this->normalizarFormulario($data,$form_id);
    }

    /**
     * @param $formulario array con campos del formulario de entrada para iniciar el proceso
     * @return string
     */
    function generar_swagger($formulario){

        log_message("info", "Input Generar Swagger: ".$this->varDump($formulario), FALSE);

        if(isset($formulario) && count($formulario) > 0){
            log_message("info", "Formulario recuperado: ".$this->varDump($formulario), FALSE);
            $data_entrada = "";
            $form = $formulario[0];
            $campos = $form["form"];
            foreach($campos["campos"] as $campo){
                //Campo tipo file será tratado como string asumiendo que el archivo viene en base64
                if($campo["tipo"] == "string" || $campo["tipo"] == "base64"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\"}";
                }else if($campo["tipo_control"] == "checkbox"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}}";
                }else if($campo["tipo"] == "date"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\",\"format\": \"date\"}";
                }else if($campo["tipo"] == "grid"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"array\",\"items\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}}}";
                }
            }
        }

        $swagger = "";

        $nombre_host = gethostname();
        //($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');

        log_message("info", "HOST: ".$nombre_host, FALSE);

        if ($file = fopen("uploads/swagger/start_swagger.json", "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                $line = str_replace("-DATA_ENTRADA-", $data_entrada, $line);
                $line = str_replace("-HOST-", $nombre_host, $line);
                $swagger .= $line;
            }
            fclose($file);
        }

        return $swagger;

    }

    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }
}

?>