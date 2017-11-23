<?php
class Migration_50 extends Doctrine_Migration_Base {

    public function up() {

        $columns = array(
            'id' => array(
                'type' => 'int(10) unsigned AUTO_INCREMENT',
                'notnull' => 1,
                'primary' => 1
            ),
            'institucion' => array(
                'type' => 'varchar(128)'
            ),
            'extra' => array(
                'type' => 'text'
            ),
            'proceso_id' => array(
                'type' => 'int'
            )
        );

        $this->createTable('suscriptor', $columns, array('primary' => array('id')));
    }

    public function postUp() {
        $this->createForeignKey( 'suscriptor', 'fk_trigger_proceso2', array(
                'local'        => 'proceso_id',
                'foreign'      => 'id',
                'foreignTable' => 'proceso',
                'onUpdate'     => 'CASCADE',
                'onDelete'     => 'CASCADE'
            )
        );
    }

    public function down() {
        $this->dropTable('suscriptor');
    }

}
?>
