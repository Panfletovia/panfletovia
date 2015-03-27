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
		'Perfil', 
		'ClientePerfil'
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
		$fields = array(
		  	'Cliente.*',
		);

		$this->Cliente->recursive = 2;
		$fullCliente = $this->Cliente->findClient($username, $password, $fields);

		// Valida se encontrou o cliente
		if(empty($fullCliente)){
			throw new ApiException('Usuário ou senha inválidos', 400);		
		}

		var_dump($fullCliente['ClientePerfil']);
		// Varre os profiles retirar a camada do vinculo
		foreach ($fullCliente['ClientePerfil'] as $key => $value) {

			$valueBKP = $value;
			unset($valueBKP['id']);
			unset($valueBKP['cliente_id']);
			unset($valueBKP['perfil_id']);

			$fullCliente['ClientePerfil'][$key] = $valueBKP;
		}
		var_dump('+++++++++++++++++++++++++++++');
		die(var_dump($fullCliente['ClientePerfil']));

		// Busca todos os perfils
		$allProfiles = $this->Perfil->findAll();
		// Extrai todos os perfils selecionados pelo usuario
		$clientProfiles = $this->ClientePerfil->findProfilesByClientId($fullCliente['Cliente']['id']);

		die(var_dump($fullCliente, $clientProfiles));
		// die(var_dump($fullCliente['ClientePerfil'][0]['Perfil'], $clientProfiles));

		// Retorna os dados encontrados
		$this->data['cliente'] = $fullCliente['Cliente'];
		$this->data['profiles'] = $allProfiles;
	}// End Method 'add'
}// End Class
