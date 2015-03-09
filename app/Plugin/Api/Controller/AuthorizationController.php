<?php

App::uses('ApiAppController', 'Api.Controller');
// App::uses('AdvancedDBComponent', 'Api.Controller/Component');
// App::uses('AppController', 'Controller');

/**
 * 
 * Classe que efetua a autenticação do operador do sistema e efetua a abertura do caixa.
 * 
 */
class AuthorizationController extends ApiAppController {

	public $helpers = array('Html', 'Form');
    public $components = array('RequestHandler');

	// public $uses = array(
	// 	'Equipamento', 
	// 	'Parking.Area', 
	// 	'Parking.Cobranca', 
	// 	'Parking.Operador',
	// 	'Parking.Setor',
	// 	'Parking.Servico',
	// 	'Comunicacao',
	// 	'Recibo',
	// 	'Associado',
	// 	'Configuracao',
	// 	'Parking.ParkConfiguracao',
	// 	'Pagamento'
	// );

	// public $components = array('Api.AdvancedDB');

	public function index(){

		if (){

		}


		$this->data['aaaa'] = 'bbbb';
	}// End action 'index'

	public function edit($id){
		throw new NotImplementedException('');
	}// End action 'edit'

	public function delete($id){
		throw new NotImplementedException('');
	}// End action 'delete'

	public function view($id = NULL){
		throw new NotImplementedException('');
	}// End action 'view'
	public function add() {
		throw new NotImplementedException('');
	}// End Method 'add'

}// End Class