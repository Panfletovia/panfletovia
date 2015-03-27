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

    /**
     * Método para buscar todos os perfils cadastrados
     */
    public function findAll(){
		return $this->find('all');
    }
    
}// End Class