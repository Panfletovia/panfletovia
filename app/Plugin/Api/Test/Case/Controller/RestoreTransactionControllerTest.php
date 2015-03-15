<?php

/**
 *
 */
App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe RestoreTransactionControllerTest
 */
Class RestoreTransactionControllerTest extends ApiBaseControllerTestCase {
	
	public $mockUser = false;
	public $uses = array('Parking.Ticket', 'Equipamento', 'Cliente', 'Limite', 'Autorizacao');
	private $URL = '/api/restoreTransaction.json';
	
	public function setUp () {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveTarifa(array('Tarifa' => array('posto_id' => null)));
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveSetor();
		$this->areaPonto = $this->dataGenerator->getAreaPonto();
		$this->dataGenerator->saveAreaPonto($this->areaPonto);
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890')));
		$servico = $this->dataGenerator->getServico();
		$servico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveServico($servico);
		$this->dataGenerator->saveCliente();
		$this->dataGenerator->saveParkTarifa();
	
		//adiciona limite de 1000 reais
		$limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		$limite['Limite']['pre_creditado'] = 100000;
		$this->Limite->save($limite);
	
	
		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}
	
	function testRestoreTransaction() {
        $this->dataGenerator->saveComunicacao();
        
		//acessa restoreAPI com o mesmo nsu
		$this->sendRequest($this->URL, 'POST', array('nsu' => '1', 'serial' => '1234567890'));
		
		//testa se os dados são os mesmos
		$this->assertEqual($this->vars['data']->ping, "pong");
	}// End Method 'testRestoreTransaction'

function testRestoreTransactionNotFound() {
		//acessa restoreAPI com o mesmo nsu
		try {
			$this->sendRequest($this->URL, 'POST', array('nsu' => '1', 'serial' => '1234567890'));
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}
		
		$this->assertEquals('A transação não foi encontrada. Tente refazer a transação original com o mesmo NSU', $errorMessage);

	}// End Method 'testRestoreTransaction'

}// End Class