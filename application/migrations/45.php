<?php
class Migration_45 extends Doctrine_Migration_Base {

    public function up() {
        $this->addColumn('proceso', 'version', 'integer', null, array('notnull'=>1,'default'=>1));
        $this->addColumn('proceso', 'root', 'integer', null, array());
        $this->addColumn('proceso', 'publicado', 'boolean', null, array('notnull'=>1,'default'=>1));
    }

    public function postUp() {
        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
        $q->execute("UPDATE proceso p SET p.version=1, p.publicado=1");
    }

    public function down() {
        $this->removeColumn('proceso', 'version');
        $this->removeColumn('proceso', 'root');
        $this->removeColumn('proceso', 'publicado');
    }
}
?>
