<?php
class Migration_45 extends Doctrine_Migration_Base {

    public function up() {
        $this->addColumn('tarea', 'es_final', 'boolean', null, array('notnull'=>1,'default'=>0));
    }

    public function postUp() {
        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
        $q->execute("UPDATE tarea SET es_final=0");
    }

    public function down() {
        $this->removeColumn('tarea', 'es_final');
    }
}
?>
