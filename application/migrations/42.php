<?php
class Migration_42 extends Doctrine_Migration_Base {
    public function up() {
        $this->dropForeignKey('proceso', 'fk_categoria');
    }

    public function down() {
        $this->createForeignKey( 'proceso', 'fk_categoria', array(
            'local'        => 'categoria_id',
            'foreign'      => 'id',
            'foreignTable' => 'categoria',
            'onUpdate'     => 'NO_ACTION',
            'onDelete'     => 'NO_ACTION',
        ));
    }
}
