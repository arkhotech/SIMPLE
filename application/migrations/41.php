<?php
class Migration_41 extends Doctrine_Migration_Base {
    public function up() {

        $this->addColumn('proceso', 'categoria_id', 'int(10) unsigned');
        $this->addColumn('proceso', 'destacado', 'int(2)');
        $this->addColumn('proceso', 'icon_ref', 'varchar(256)');

        $definition = array(
            'local'        => 'categoria_id',
            'foreign'      => 'id',
            'foreignTable' => 'categoria',
            'onDelete'     => 'CASCADE',
            'onUpdate'     => 'CASCADE'
        );
        $this->createForeignKey( 'proceso', 'fk_categoria', $definition );
    }

    public function down() {
        $this->dropForeignKey('proceso', 'fk_categoria');
        $this->removeColumn('proceso', 'categoria_id');
        $this->removeColumn('proceso', 'destacado');
    }
}
