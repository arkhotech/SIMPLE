<?php
class Migration_48 extends Doctrine_Migration_Base {
    public function up(){
        $this->addColumn( 'campo', 'exponer_campo', 'integer' , null, array( 'notnull' => 1,'default'=>0));
        
    }
    
    public function down(){
        $this->removeColumn( 'campo', 'exponer_campo' );
    }
}