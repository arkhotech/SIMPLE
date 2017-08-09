<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//Esta funcion debería estar en modelo

class FormNormalizer{
    
    private function mapType($tipo){
        switch ($tipo){
            case "file": return "base64";
            case "checkbox": return "boolean";
            default: return "string";
        }
    }
   
    private function getValueDomain($campo=NULL){
        
        return "0";
    }
    
    function normalizarFormulario($json,$id){
        $retval['form'] = array('id' => $id, 'campos' => array() );
        //print_r($json);
        foreach( $json['Campos'] as $campo){
            array_push($retval['form']['campos'], 
            
                  array( 
                    "nombre" => $campo['nombre'],
                    "tipo_control" => $campo['tipo'],
                    "tipo" => $this->mapType($campo['tipo']),  //$campo['dependiente_tipo'],
                    "obligatorio" => ($campo['readonly']==0) ? false : true,
                    "dominio_valores" => $campo['datos'])
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
    
}

?>