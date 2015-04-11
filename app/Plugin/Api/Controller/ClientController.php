<?php

App::uses('ApiAppController', 'Api.Controller');

/**
 * 
 * Classe de controle do cliente
 * 
 */
class ClientController extends ApiAppController {

	public $uses = array(
		'Cliente'
	);

	public function index(){
		throw new NotImplementedException('index');
	}// End action 'index'

	public function edit($id){
		throw new NotImplementedException('edit');
	}// End action 'edit'

	public function delete($id){
		throw new NotImplementedException('delete');
	}// End action 'delete'

	public function view($id = NULL){
		throw new NotImplementedException('view');
	}// End action 'view'

	/**
	 * Action que efetua o cadastro do usuario no sistem
	 */
	public function add() {

		// Extrai as informações da requisição
		$login     = $this->getRequestField('login');
		$password  = $this->getRequestField('password');
		$plataform = $this->getRequestField('plataform');
		$version   = $this->getRequestField('version');

		// Valida se os dados são válidos
		if (empty($login) || empty($password)) {
			throw new ApiException('Por favor, informe corretamente os dados', 400);
		}

		$password = $this->Cliente->passwordHash($password);

		$cliente = array('Cliente' => array(
			'tipo' => 'CLIENTE',
			'login' => $login,
			'senha' => $password,
			// 'plataforma' => $plataform
			// 'versao' => $version
		));

		$ds = $this->Cliente->getDataSource();

		$ds->begin();
		$clientId = null;

		try {
			$client = $this->Cliente->save($cliente);
			$clientId = $client['Cliente']['id'];
			$ds->commit();
		} catch (Exception $e) {
			$ds->rollback();
			$message = $e->getMessage();
			throw new ApiException('Ocorreu um erro ao efetuar o seu cadastro. Por favor tente novamente mais tarde.', 500);
		}

		$this->data['client_id'] = $clientId;
	}// End Method 'add'
}// End Class
