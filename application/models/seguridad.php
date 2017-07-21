<?php

class Seguridad extends Doctrine_Record {

    function setTableDefinition() {        
        $this->hasColumn('id');
        $this->hasColumn('institucion');
        $this->hasColumn('servicio');
        $this->hasColumn('extra');
        $this->hasColumn('proceso_id'); 

    }

    function setUp() {
        parent::setUp();

        $this->hasOne('Proceso', array(
            'local' => 'proceso_id',
            'foreign' => 'id'
        ));
        
        // $this->hasMany('Evento as Eventos', array(
        //     'local' => 'id',
        //     'foreign' => 'seguridad_id'
        // ));
    }
    
    public function displayForm(){
        return NULL;
    }
    
    public function validateForm(){
        return;
    }
    
    //Ejecuta la regla, de acuerdo a los datos del tramite tramite_id
    public function ejecutar($tramite_id){
        return;
    }
    
    public function setExtra($datos_array) {
        if ($datos_array) 
            $this->_set('extra' , json_encode($datos_array));
        else 
            $this->_set('extra' , NULL);
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
     * @return seguridad
     */
    public static function importComplete($input)
    {
        $json = json_decode($input);
        $seguridad = new seguridad();
        
        try {
            
            //Asignamos los valores a las propiedades de la seguridad
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
