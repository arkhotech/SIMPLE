<?php
class Migration_38 extends Doctrine_Migration_Base {

    public function up() {

        $columns = array(
            'id' => array(
                'type' => 'int(10) unsigned AUTO_INCREMENT',
                'notnull' => 1,
                'primary' => 1
            ),
            'usuario' => array(
                'type' => 'varchar(128)',
                'notnull' => 1
            ),
            'horario' => array(
                'type' => 'datetime',
                'notnull' => 1
            )
        );

        $this->createTable('login_erroneo', $columns, array('primary' => array('id'))); 
    }

    public function down() {
        $this->dropTable('login_erroneo');
    }

}
?>
