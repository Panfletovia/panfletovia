<?php

App::uses('ApiException', 'Lib/Error');
App::uses('AppController', 'Controller');

/**
 * AppController do plugin API
 */
class ApiAppController extends AppController {

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
    * Método para validar se o tipo de entrada de dados. Ex: GET, POST, PUT ou DELETE.
    *
    * @param $field Campo a ser verificado
    * @throws BadRequestException Campo a ser verificado seja inválido
    */
    protected function getRequestField($field){
        // Valida como deverá pegar os dados de acordo com tipo de entrada de dados
        // @ - Serve para não encontrar o campo no array,
        // retorna NULL ao invés de lançar exceção "Undefined Index"
        if($this->request->is('GET')){
            return @$this->request->query[$field];
        }else{
            return @$this->request->data[$field];
        }
    }// End 'getRequestField'
}