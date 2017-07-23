<?php

class Seguridad extends Doctrine_Record { 

    function setTableDefinition() {
        $this->hasColumn('id');
        $this->hasColumn('institucion');
        $this->hasColumn('servicio');
        $this->hasColumn('extra');
        $this->hasColumn('proceso_id');
        // $this->setSubclasses(array(
        //         'SeguridadForm'  => array('tipo' => 'SeguridadForm'),
        //     )
        // );
    }

    function setUp() {
        parent::setUp();

        $this->hasOne('Proceso', array(
            'local' => 'proceso_id',
            'foreign' => 'id'
        ));
    }

    // public function displayForm(){
    //     return NULL;
    // }
    
    public function validateForm(){
        return;
    }
    
    public function displayForm() {
        $display='<label>Tipo de Seguridad</label>
                    <select id="tipoSeguridad" name="extra[tipoSeguridad]">
                        <option value="">Seleccione...</option>
                        <option value="HTTP_BASIC">HTTP_BASIC</option>
                        <option value="API_KEY">API_KEY</option>
                        <option value="OAUTH2">OAUTH2</option>';
                        if ($this->extra->tipoSeguridad){
                        $display.='<option value="'.($this->extra->tipoSeguridad).'" selected>'.($this->extra->tipoSeguridad).'</option>';
                    } 
        $display.='</select>';
         
        $display.='
            <div class="col-md-12" id="DivUser" style="display:none;">
                <label>Usuario</label>
                <input type="text" id="user" name="extra[user]" class="" value="'.(isset($this->extra->user) ? $this->extra->user : '').'">
            </div>';

        $display.='
            <div class="col-md-12" id="DivPass" style="display:none;">
                <label>Contraseña</label>
                <input type="text" id="pass" name="extra[pass]" class="" value="'.(isset($this->extra->pass) ? $this->extra->pass : '').'">
            </div>';

        $display.='
            <div class="col-md-12" id="DivKey" style="display:none;">
                <label>Llave de aplicacion</label>
                <input type="text" id="key" name="extra[key]" class="" value="'.(isset($this->extra->key) ? $this->extra->key : '').'">
            </div>';
        return $display;
    }

    // public function validateForm() {
    //     $CI = & get_instance();
    //     $CI->form_validation->set_rules('extra[institucion]', 'Tipo de Seguridad', 'required');
    //     $CI->form_validation->set_rules('extra[servicio]', 'Tipo de Seguridad', 'required');
    //     // $CI->form_validation->set_rules('extra[tema]', 'Tema', 'required');
    //     // $CI->form_validation->set_rules('extra[contenido]', 'Contenido', 'required');
    // }

    //Ejecuta la regla, de acuerdo a los datos del tramite tramite_id
    public function ejecutar($tramite_id){
        return;
    }
    
    public function setExtra($datos_array) {

        if ($datos_array) {
            $this->_set('extra' , json_encode($datos_array));
        } else {
            log_message('info','Seguridad.setExtra, $datos_array: NULL');
            $this->_set('extra' , NULL);
        }
    }
    
    public function getExtra(){
        return json_decode($this->_get('extra'));
    }
    
    public function exportComplete()
    {        
        $seguridad = $this;                
        $object = $seguridad->toArray();

        return json_encode($object);
    }
    
    /**
     * @param $input
     * @return Seguridad
     */
    public static function importComplete($input)
    {
        $json = json_decode($input);
        $seguridad = new Seguridad();
        
        try {
            
            //Asignamos los valores a las propiedades de la Seguridad
            foreach ($json as $keyp => $p_attr) {
                if ($keyp != 'id' && $keyp != 'proceso_id')
                    $seguridad->{$keyp} = $p_attr;
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage(), $ex->getCode());
        }                

        return $seguridad;
    }

}
