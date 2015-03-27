<?php

App::uses('AppModel', 'Model');

/**
 * ClientePerfil Model
 *
 */
class ClientePerfil extends AppModel {

    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'cliente_perfil';

    /**
     * Busca todos os perfils vinculado a um determinado cliente
     */
    public function findProfilesByClientId($clientId = null){
    	if(empty($clientId)){
    		return null;
    	}

        return $this->find('all', array(
            'conditions' => array(
                'cliente_id' => $clientId
            )
        ));
    }// End Method 'findClient'

    //  public $hasOne = array(
    //     'Perfil' => array(
    //         'className' => 'Perfil',
    //         'conditions' => array('ClientePerfil.perfil_id' => 'Perfil.id')
    //     )
    // );

    public $belongsTo = array(
        'Perfil' => array(
            'className' => 'Perfil',
            'foreignKey' => 'perfil_id',
            // 'dependent' => true
        )
    );

}// End Class