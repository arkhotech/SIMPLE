<?php 
	class Migration_40 extends Doctrine_Migration_Base {

	    public function up() {
	        $this->addColumn('tarea', 'exponer_tramite', 'tinyint', null, array('notnull'=>1,'default'=>0));
	    }

	    public function postUp() {
	        $q = Doctrine_Manager::getInstance()->getCurrentConnection();
	        $q->execute("UPDATE tarea SET exponer_tramite=1");
	    }

	    public function down() {
	        $this->removeColumn('tarea', 'exponer_tramite');
	    }
	}
 ?>
