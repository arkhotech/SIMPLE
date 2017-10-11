<?php

class Suscriptor extends Doctrine_Record {

    function setTableDefinition() {
        $this->hasColumn('id');
        $this->hasColumn('institucion');
        $this->hasColumn('extra');
        $this->hasColumn('proceso_id');
    }

    function setUp() {
        parent::setUp();

        $this->hasOne('Proceso', array(
            'local' => 'proceso_id',
            'foreign' => 'id'
        ));
    }

    public function validateForm(){
        return;
    }

    public function displayFormSuscriptor($proceso_id) {
        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display='
                <label>Seguridad</label>
                <select id="tipoSeguridad" name="extra[idSeguridad]">';
                    $display.='<option value="-1">Sin seguridad</option>';
                foreach($conf_seguridad as $seg){
                    if ($this->extra->idSeguridad && $this->extra->idSeguridad == $seg->id){
                        $display.='<option value="'.$seg->id.'" selected>'.$seg->institucion.' - '.$seg->servicio.'</option>';
                    }else{
                        $display.='<option value="'.$seg->id.'">'.$seg->institucion.' - '.$seg->servicio.'</option>';
                    }
                }
        $display.='</select>';

        $display.='
            <div class="col-md-12">
                <label>Webhook</label>
                <input type="text" id="user" name="extra[webhook]" class="input-xxlarge" value="'.(isset($this->extra->webhook) ? $this->extra->webhook : '').'">
            </div>';

        $display.= '
            <p>
                Por defecto el contenido del request para el webhook será el siguiente:</br>
                <pre>{
    "idInstancia": "string",
    "idTarea": "string",
    "data": {
        "clave": "valor"
         ...
    }
 }</pre>
            </p>';

        $display.= '
            <p>
                Si se requiere algún reques personalizado, es posible hacerlo con la variable output, la cual contendrá la información mostrada en el parrafo anterior.</br>
                <pre>{
    "campoPersonalizado": "@@output"
 }</pre>
            </p>';

        $display.='
            <div class="col-md-12" id="divObject">
                <label>Request personalizado</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <span id="resultRequest" class="spanError"></span>
                <br /><br />
            </div>';
        return $display;
    }

    //Ejecuta la regla, de acuerdo a los datos del tramite tramite_id
    public function ejecutar($tramite_id){
        return;
    }

    public function setExtra($datos_array) {

        if ($datos_array) {
            $this->_set('extra' , json_encode($datos_array));
        } else {
            $this->_set('extra' , NULL);
        }
    }

    public function getExtra(){
        return json_decode($this->_get('extra'));
    }

    public function exportComplete()
    {
        $suscriptor = $this;
        $object = $suscriptor->toArray();

        return json_encode($object);
    }

    /**
     * @param $input
     * @return Suscriptores
     */
    public static function importComplete($input)
    {
        $json = json_decode($input);
        $suscriptor = new Suscriptor();

        try {

            //Asignamos los valores a las propiedades de la Seguridad
            foreach ($json as $keyp => $p_attr) {
                if ($keyp != 'id' && $keyp != 'proceso_id')
                    $suscriptor->{$keyp} = $p_attr;
            }
        } catch (Exception $ex) {
            throw new ApiException($ex->getMessage(), $ex->getCode());
        }

        return $suscriptor;
    }

    public function findSuscriptoresProceso($proceso_id){
        $sql = "select s.id, s.institucion from suscriptor s 
            where s.proceso_id = ".$proceso_id.";";

        $stmn = Doctrine_Manager::getInstance()->connection();
        $result = $stmn->execute($sql)
            ->fetchAll();
        return $result;
    }

}