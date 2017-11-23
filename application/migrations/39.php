<?php
class Migration_39 extends Doctrine_Migration_Base {

    public function up() {
        $this->addColumn('cuenta', 'ambiente', 'varchar', null, array('notnull'=>1, 'default'=>'prod'));
        $this->addColumn('cuenta', 'vinculo_produccion', 'int(10)' , null, array('notnull' => 0,'unsigned'=>1));

        $this->addIndex( 'cuenta', 'vinculo_produccion', array(
            'fields'=>array('vinculo_produccion')
        ));
    }

    public function postUp() {
        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
        $q->execute("UPDATE cuenta SET ambiente='prod';");
    }

    public function down() {
        $this->removeColumn('cuenta', 'ambiente');
        $this->removeColumn('cuenta', 'vinculo_produccion');
    }
}
?>
