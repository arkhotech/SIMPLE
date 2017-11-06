<?php
class Migration_43 extends Doctrine_Migration_Base {

    public function up() {
        
        $columns = array(
            'id' => array(
                'type' => 'int(10) unsigned AUTO_INCREMENT',
                'notnull' => 1,
                'primary' => 1
            ),
            'id_cuenta_origen' => array(
                'type' => 'int(10)'
            ),
            'id_cuenta_destino' => array(
                'type' => 'int(10)'
            ),
            'id_proceso' => array(
                'type' => 'int(10)'
            )
        );

        $this->createTable('proceso_cuenta', $columns, array('primary' => array('id')));
    }

    public function postUp() {
        $this->createForeignKey( 'proceso_cuenta', 'fk_trigger_proceso2', array(
                'local'        => 'id_proceso',
                'foreign'      => 'id',
                'foreignTable' => 'proceso',
                'onUpdate'     => 'CASCADE',
                'onDelete'     => 'CASCADE'
            )
        );
    }

    public function down() {
        $this->dropTable('proceso_cuenta');
    }
}
?>
