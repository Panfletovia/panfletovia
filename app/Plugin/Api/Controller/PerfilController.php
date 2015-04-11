<?php

// App::uses('PrivateApiAppController', 'Api.Controller');
// App::uses('AdvancedDBComponent', 'Api.Controller/Component');
App::uses('ApiAppController', 'Api.Controller');

/**
 * 
 * Classe que efetua a autenticação do operador do sistema e efetua a abertura do caixa.
 * 
 */
class PerfilController extends ApiAppController {

	public $uses = array(
		'Cliente',
		'UsuarioPerfil'
	);

	public function index(){
		throw new NotImplementedException('Index');
	}// End action 'index'

	public function edit($id){
		throw new NotImplementedException('Edit');
	}// End action 'edit'

	public function delete($id){
		throw new NotImplementedException('Delete');
	}// End action 'delete'

	public function view($id = NULL){
		throw new NotImplementedException('View');
	}// End action 'view'

	/**
	 * Action que realiza a autenticação do operador e efetua a abertura do caixa
	 * 
	 * @throws ForbiddenException Usuário Ou Senha não foram recebidos
	 * @throws NotFoundExcetpion Equipamento não possui um associado
	 * @throws ForbiddenException Equipamento não está ativo
	 * 
	 */
	public function add() {

		$username = $this->getRequestField('username');
		$password = $this->getRequestField('password');

		if (empty($username) || empty($password)) {
			throw new ApiException('Usuário ou senha inválidos', 400);
		}

		// $this->Cliente->recursive = 2;
		$fields = array(
			'Cliente.id'
		);
		
		$fullCliente = $this->Cliente->find('all', array(
			'conditions' => array(
				'ativo' => 1,
				''
			),
			'limit' => ,
			'order' => array(id => 'DESC', data => 'desc'),
			'joins' => array(
				'table' => 'Perfil',
		        'alias' => 'Perfil',
		        'type' => 'LEFT',
		        'conditions' => array(
		            'Cliente.id = Perfil.cliente_id',
		        )
			)
		));
		// die(var_dump($fullCliente));

		if(empty($fullCliente)){
			throw new ApiException('Usuário ou senha inválidos', 400);		
		}

		$cliente = $fullCliente['Cliente'];
		$clientePerfil = $fullCliente['UsuarioPerfil'][0]['Perfil'];

		// die(var_dump($cliente, $clientePerfil));
		$this->data = $fullCliente;
	}
}// End Class
