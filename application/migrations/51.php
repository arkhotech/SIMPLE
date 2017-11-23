<?php
class Migration_51 extends Doctrine_Migration_Base {

    public function up() {

        $columns = array(
            'id' => array(
                'type' => 'int(10) unsigned AUTO_INCREMENT',
                'notnull' => 1,
                'primary' => 1
            ),
            'tramite_id' => array(
                'type' => 'int'
            ),
            'tarea_id' => array(
                'type' => 'int'
            ),
            'request' => array(
                'type' => 'text'
            ),
            'procesado' => array(
                'type' => 'tinyint'
            )
        );

        $this->createTable('cola_continuar_tramite', $columns, array('primary' => array('id')));
    }

    public function postUp() {
    }

    public function down() {
        $this->dropTable('cola_continuar_tramite');
    }

}
?>
