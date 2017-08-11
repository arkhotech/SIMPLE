<?php
class Migration_42 extends Doctrine_Migration_Base {
    public function up(){
        $this->addColumn( 'accion', 'exponer_varible', 'integer' , null, array( 'notnull' => 1,'default'=>0));
        
    }
    
    public function down(){
        $this->removeColumn( 'accion', 'exponer_varible' );
    }
}