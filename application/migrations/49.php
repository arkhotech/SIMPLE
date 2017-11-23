<?php
class Migration_49 extends Doctrine_Migration_Base {
    public function up(){
        $this->addColumn( 'accion', 'exponer_variable', 'integer' , null, array( 'notnull' => 1,'default'=>0));

    }

    public function down(){
        $this->removeColumn( 'accion', 'exponer_variable' );
    }
}