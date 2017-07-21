<?php
class Migration_39 extends Doctrine_Migration_Base {

    public function up() {

        $columns = array(
            'id' => array(
                'type' => 'int(10) unsigned AUTO_INCREMENT',
                'notnull' => 1,
                'primary' => 1
            ),
            'institucion' => array(
                'type' => 'varchar(128)',
                'notnull' => 1
            ),
            'servicio' => array(
                'type' => 'varchar(128)',
                'notnull' => 1
            ),
            'extra' => array(
                'type' => 'text',
                'notnull' => 1
            ),
            'proceso_id' => array(
                'type' => 'int',
                'notnull' => 1
            )
        );

        $this->createTable('seguridad', $columns, array('primary' => array('id'))); 
        $this->createForeignKey( 'fk_trigger_proceso1_idx', 'fk_trigger_proceso2', array(
                'local'        => 'proceso_id',
                'foreign'      => 'id',
                'foreignTable' => 'proceso',
                'onUpdate'     => 'CASCADE',
                'onDelete'     => 'CASCADE',
            )
        );
    }  

    public function down() {
        $this->dropTable('seguridad');
    }

}
?>
