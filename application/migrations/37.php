<?php
class Migration_37 extends Doctrine_Migration_Base {

    public function up() {
        $this->addColumn('proceso', 'activo', 'boolean', null, array('notnull'=>1,'default'=>1));
    }

    public function postUp() {
        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
        $q->execute("UPDATE proceso SET activo=1");
    }

    public function down() {
        $this->removeColumn('proceso', 'activo');
    }
}
?>
