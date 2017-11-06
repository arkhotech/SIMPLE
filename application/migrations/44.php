<?php
class Migration_44 extends Doctrine_Migration_Base {

    public function up() {
        $this->addColumn('proceso', 'version', 'integer', null, array('notnull'=>1,'default'=>1));
        $this->addColumn('proceso', 'root', 'integer', null, array());
        $this->addColumn('proceso', 'estado', 'varchar', null, array('notnull'=>1,'default'=>'public'));
    }

    public function postUp() {
        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
        $q->execute("UPDATE proceso p SET p.version=1, p.estado='public', p.root=p.id");
    }

    public function down() {
        $this->removeColumn('proceso', 'version');
        $this->removeColumn('proceso', 'root');
        $this->removeColumn('proceso', 'estado');
    }
}
?>
