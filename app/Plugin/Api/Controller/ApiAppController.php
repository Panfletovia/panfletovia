<?php

App::uses('ApiException', 'Lib/Error');
App::uses('AppController', 'Controller');

/**
 * AppController do plugin API
 */
class ApiAppController extends AppController {

    // public $components = array('Api.PeriodPurchase');
    // public $uses = array(
    //     'Equipamento', 
    //     'Parking.Area',
    //     'Parking.Ticket',
    //     'Parking.Servico',
    //     'Parking.Operador',
    //     'Parking.Eticket', // Utilizado na PrivateApiAppController
    //     'Entidade',
    //     'Movimento',
    //     'Direito',
    //     'Posto',
    //     'Comunicacao',
    //     'Log'
    // );

    /**
     * Dados de retorno da requisição
     */
    protected $data = array();
	
    /**
     * (non-PHPdoc)
     * @see AppController::beforeFilter()
     */
    public function beforeFilter() {
        // $this->Auth->allow();
        parent::beforeFilter();
    }

    public function appError() {
    }

    /**
    * Método que é executado após cada classe.
    * Foi criada para centralizar o envio dos dados de retorno da requisição
    * Foi criado no 'beforeRender' pois no 'afterFilter', o aplicativo não recebia os valores de resposta.
    */ 
    public function beforeRender(){
        parent::beforeRender();
        // Retorna as informações buscadas
        $this->output($this->data);
    }// End method 'beforeRender'


    /**
     * Processa dados de saída da requisição
     * 
     * @param $data Array de saída de dados
     */
    protected function output($data = array()) {
    	
    	if ($this->RequestHandler->isXml()) {
    		$data = array($data);
    	}
    	
        $this->set(array(
            'data' => $data,
            '_serialize' => 'data'
        ));
    }

    /**
    * Método que insere log's na tabela de log's.
    *
    * @param $descricao Log de debug
    * @param $origem Origem do log de debug. Por default: 'API'
    * @throws SQLException Ao inserir registro no banco de dados
    */
    protected function insertLog($descricao, $origem = 'API'){
        $this->Log->save(array('Log' => array('origem' => $origem, 'descricao' => $descricao)));
    }
    
}