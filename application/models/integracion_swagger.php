<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author msilva
 */
class Swagger {
    //put your code here
    /**
     * @param $formulario array con campos del formulario de entrada para iniciar el proceso
     * @return string
     * @version 1.0
     */
    public function generar_swagger($formulario, $id_tramite, $id_etapa){
        if(!isset($formulario)){
            throw new Exception("Formulario no se puede recuper",404);
        }
        //log_message("info", "Input Generar Swagger: ".$this->varDump($formulario), FALSE);
        log_message("info", "Id trámite: ".$id_tramite, FALSE);
        log_message("info", "Id tarea: ".$id_etapa, FALSE);
        
        $output_vars = $this->getOuputVarsSwagger($formulario,$id_tramite);
         
        if(isset($formulario) && count($formulario) > 0){
            //log_message("info", "Formulario recuperado: ".$this->varDump($formulario), FALSE);
            $data_entrada = "";
            $data_salida = "";
            $form = $formulario[0];
            $campos = $form["form"];
            foreach($campos["campos"] as $campo){
                
                log_message('debug',$campo['nombre']." -> ".$campo['direccion']);
                //Campo tipo file será tratado como string asumiendo que el archivo viene en base64
                if($campo['direccion']=='IN' && $data_entrada != "") 
                    $data_entrada .= ",";
                if($campo['direccion']=='OUT' && $data_salida != "" )  
                    $data_salida .= ",";
              
                
                if($campo["tipo"] == "string" ){
                    ($campo['direccion']!='OUT') ? ($data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\"}"):
                        ($data_salida .= "\"".$campo["nombre"]."\": {\"type\": \"string\"}");
                }else if($campo["tipo"] == "base64"){
                    ($campo['direccion']!='OUT') ? ($data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\"}"):
                        ($data_salida .= "\"".$campo["nombre"]."\": {\"type\": \"string\",\"format\": \"base64\"}");
                }else if($campo["tipo_control"] == "checkbox"){
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}}";
                }else if($campo["tipo"] == "date"){
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\",\"format\": \"date\"}";
                }else if($campo["tipo"] == "grid"){
                    $data_entrada .= "";

                    $columnas = array();
                    $columnas = $campo["dominio_valores"];

                    $nombres_columnas = "";
                    foreach ($columnas["columns"] as $column){
                        if($nombres_columnas != "") $nombres_columnas .= ",";
                        $nombres_columnas .= "'".$column["header"]."'";
                    }
                    
                        $data_entrada .= "\"".$campo["nombre"]."\": {
                        \"description\": \"Formato de arreglo\n\nPrimera fila corresponde a nombres de columnas, los cuales son: [".$nombres_columnas."]\n\nFilas siguientes corresponden a los valores\nEjemplo:\n[\n  [nombre_columna_1, nombre_columna_2, .., nombre_columna_N],\n  [valor_1, valor_2, .., valor_N], .., [valor_1, valor_2, .., valor_N]\n]\n\",
                        \"type\": \"array\",\"items\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}},
                        \"default\": \"[[".$nombres_columnas."]]\",";

                        $data_entrada .= "\"minItems\": 1,";
                        $data_entrada .= "\"maxItems\": ".count($columnas["columns"])."}";
                    

                }
            }
        }
        //Agregar las variables globales de salida
        if(isset($output_vars)){
            foreach($output_vars['accionvar'] as $var){
                log_message('debug','var: .'.$var.'.');
                if($var != NULL && trim($var) != '' ){
                    if($data_salida != "") {
                        $data_salida .= ",";
                    }
                    $data_salida .= "\"".$var."\": {\"type\": \"string\"}";
                }
            }
        }
       
        $swagger = "";

        $nombre_host = gethostname();
        //($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');

        log_message("info", "HOST: ".$nombre_host, FALSE);

        if ($file = fopen("uploads/swagger/start_swagger.json", "r")) {
            log_message("debug", "Formulario recuperado", FALSE);
            while(!feof($file)) {
                $line = fgets($file);
                $line = str_replace("-DATA_ENTRADA-", $data_entrada, $line);
                $line = str_replace("-OUTPUT-",$data_salida,$line);
                $line = str_replace("-HOST-", $nombre_host, $line);
                $line = str_replace("-id_tramite-", $id_tramite, $line);
                $line = str_replace("-id_tarea-", $id_etapa, $line);
                $swagger .= $line;
            }
            fclose($file);
        }

        return $swagger;

    }
    
   
    
    private function getOuputVarsSwagger($formulario,$proceso_id){
        log_message('debug',"Recuperando variables de salida: ".$proceso_id);
 
        $variables = Campo::getVarsExpFromFormulario($formulario[0]['form']['idForm'],$proceso_id);
        $retval = null;
        //Estas variables son las exportables como output
        if(isset($variables)){
            foreach($variables as $var ){
                $retval['formvar'][]=$var->nombre;
            }
        }
        $accion_vars = Campo::getVarsAccionExpFromProceso($proceso_id);
        
        if(isset($accion_vars)){
            foreach($accion_vars as $var ){
                $retval['accionvar'][] = $var->extra->variable;
            }
        }
        return $retval;
    }
}
