<?php

App::uses('AppModel', 'Model');

/**
 * Perfil Model
 *
 */
class Perfil extends AppModel {

    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'perfil';

    public $hasMany = array(
        'Perfil' => array(
            'className' => 'Perfil',
            'foreignKey' => 'id'
        )
    );

}// End Class