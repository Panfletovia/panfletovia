<?php

// App::uses('PrivateApiAppController', 'Api.Controller');
// App::uses('AdvancedDBComponent', 'Api.Controller/Component');
App::uses('ApiAppController', 'Api.Controller');

/**
 * 
 * Classe que efetua a autenticação do usuário na API
 * 
 */
class AuthorizationController extends ApiAppController {

	public $uses = array(
		'Cliente',
		'UsuarioPerfil'
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
	 * Action que efetua o login do usuario n
	 */
	public function add() {

		// Extrai as informações da requisição
		$username = $this->getRequestField('username');
		$password = $this->getRequestField('password');
		// Valida se os dados são válidos
		if (empty($username) || empty($password)) {
			throw new ApiException('Usuário ou senha inválidos', 400);
		}
		// Busca o cliente de acordo com os dados recebidos
		$fullCliente = $this->Cliente->findClient($username, $password);

		// Valida se encontrou o cliente
		if(empty($fullCliente)){
			throw new ApiException('Usuário ou senha inválidos', 400);		
		}
		// Retorna os dados encontrados
		$this->data = $fullCliente;
	}// End Method 'add'
}// End Class
