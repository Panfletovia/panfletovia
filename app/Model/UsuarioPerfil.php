<?php

App::uses('AppModel', 'Model');

/**
 * UsuarioPerfil Model
 *
 */
class UsuarioPerfil extends AppModel {

    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'usuario_perfil';

    // public $hasOne = 'Perfil';

      public $belongsTo = array(
        'Perfil' => array(
            'className' => 'Perfil',
            'foreignKey' => 'id'
        )
    );

}// End Class