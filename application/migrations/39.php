<?php
class Migration_38 extends Doctrine_Migration_Base {

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
            )
            'extra' => array(
                'type' => 'text',
                'notnull' => 1
            )
            'proceso_id' => array(
                'type' => 'int',
                'notnull' => 1
            )
        );

        $this->createTable('seguridad', $columns, array('primary' => array('id'))); 
        $this->createForeignKey( 'evento_externo', 'eetarea_foreign_key', array(
                'local'        => 'proceso_id',
                'foreign'      => 'id',
                'foreignTable' => 'tarea',
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



CREATE TABLE `seguridad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `institucion` varchar(128) NOT NULL,
  `servicio` varchar(128) NOT NULL,
  `extra` text,
  `proceso_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trigger_proceso1_idx` (`proceso_id`),
  CONSTRAINT `fk_trigger_proceso2` FOREIGN KEY (`proceso_id`) REFERENCES `proceso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

