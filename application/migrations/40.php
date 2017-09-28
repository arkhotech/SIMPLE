<?php
class Migration_40 extends Doctrine_Migration_Base {
    
    public function up() {
    
        $columns = array(
                'id' => array(
                        'type' => 'int(10) unsigned AUTO_INCREMENT',
                        'notnull' =>true
                ),
                'nombre' => array(
                        'type' => 'varchar(80)',
                        'notnull' => true
                ),
                'descripcion' => array(
                        'type' => 'varchar(80)',
                        'notnull' => true
                ),
                'icon_ref' => array(
                        'type' => 'varchar(256)',
                        'notnull' => false
                )
        );
        $this->createTable('categoria', $columns, array('primary' => array('id')));
    }
    public function down() {
        $this->dropTable('categoria');
    }
}
