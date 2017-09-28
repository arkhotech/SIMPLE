<?php
class Migration_36 extends Doctrine_Migration_Base {
    public function up(){
        
        $this->addColumn('evento_externo', 'opciones', 'TEXT', null, array('notnull'=>0));
    }
    
    public function down(){
        $this->removeColumn('evento_externo','opciones');
    }
}
?>
