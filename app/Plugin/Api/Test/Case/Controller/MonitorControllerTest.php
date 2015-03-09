<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe MonitorControllerTest
 */
class MonitorControllerTest extends ApiBaseControllerTestCase {
	
	/**
	 * 
	 * @var unknown
	 */
	public $mockUser = false;
	
	/**
	 * Imports
	 * 
	 * @var array
	 */
	public $uses = array('Acompanhamento',
		                 'Equipamento',
		                 'Taxi.Regra',
		                 'Monitor');
	
	/**
	 * (non-PHPdoc)
	 * @see ApiBaseControllerTestCase::setUp()
	 */
	public function setUp() 
	{
		parent::setUp();
	}
	
	/**
	 * Monta a URL
	 * 
	 * @param unknown $controller
	 * @param unknown $action
	 * @return string
	 */
	public function url($controller, $action)
	{
		return '/api/' . $controller . '/' . $action . '.json';
	}
	
	/**
	 * 
	 */
	public function testDataBehavior() {
		
		//Cria um equipamento para taxi
		$this->dataGenerator->saveEquipamento(
				array(
						'Equipamento' => array(
								'no_serie' 	=> 'PHPUNIT',
								'situacao' 	=> 'ATIVO',
								'servico' 	=> 'TAXI',
								'modelo' 	=> 'VX520',
								'tipo' 		=> 'POS'
						)
				)
		);
		
		//Cria um cliente
		$this->dataGenerator->saveCliente();
		
		//Cria uma regra
		$this->dataGenerator->saveRegra();
		
		//Inicializa os objetos
		$acompanhamento 	= array();
		$monitor 			= array();
		$rule 				= array();
		
		//Busca última regra criada
		$rule = $this->Regra->find('first', array('recursive' => -1));
		
		//Testa se a regra foi criada
		$this->assertNotEmpty($rule, 'Must have a rule');
		
		//Testa se a quilometragem para busca inicial é maior do que zero
		$this->assertGreaterThan(0, $rule['Regra']['km_busca_inicial'], 'Must have a value to query geo info');
		
		//Testa se a quilometragem para busca adicional é maior do que zero
		$this->assertGreaterThan(0, $rule['Regra']['km_busca_adicional'], 'Must have a value to query geo info');
		
		//Testa se o número de tentativas de busca é maior do que zero
		$this->assertGreaterThan(0, $rule['Regra']['no_tentativas_busca'], 'Must have a value to query geo info');
		
		//busca o monitor
		$monitor = $this->Monitor->find('first', array('recursive' => -1));
		
		//Testa se o monitor foi criado
		$this->assertNotEmpty($monitor, 'Adding an equipament must create a monitor');
		
		//Altera os dados do monitor
		$monitor['Monitor']['longitude'] 	= TEST_TAXI_PROVIDER_LONGITUDE;
		$monitor['Monitor']['latitude'] 	= TEST_TAXI_PROVIDER_LATITUDE;
		$monitor['Monitor']['situacao'] 	= 'LIVRE';
		
		$this->Monitor->save($monitor);
		
		//busca acompanhamento
		$acompanhamento = $this->Acompanhamento->find('first', array('recursive' => -1));
		
		//testa o acompanhamento foi criado durante a troca de status
		$this->assertNotEmpty($acompanhamento, 'Changing monitor status must create an acompanhamento');
		//testa se o equipamento foi informado
		$this->assertEquals($acompanhamento['Acompanhamento']['equipamento_id'], $monitor['Monitor']['equipamento_id'], 'Must be created with the same equipament');
		//testa se o status foi alterado
		$this->assertEquals('LIVRE', $acompanhamento['Acompanhamento']['situacao'], 'Keep the last status');
	}
	
	/**
	 * Testa o envio de um report pra taxi
	 */
	public function testMonitorReport()
	{
		//Cria um equipamento para taxi
		$this->dataGenerator->saveEquipamento(
				array(
						'Equipamento' => array(
								'no_serie' 	=> 'PHPUNIT',
								'situacao' 	=> 'ATIVO',
								'servico' 	=> 'TAXI',
								'modelo' 	=> 'VX520',
								'tipo' 		=> 'POS'
						)
				)
		);
		
		//Cria um cliente
		$this->dataGenerator->saveCliente();
		
		//Cria uma regra
		$this->dataGenerator->saveRegra();
		
		
		$data = array();
		
		$errorMessage 	= NULL;
		$finalTester 	= false;
		$url 			= $this->url('monitor', 'report');
		
		$data['serial_device'] 	= 'PHPUNIT';
		$data['longitude'] 		= TEST_TAXI_PROVIDER_LONGITUDE;
		$data['latitude'] 		= TEST_TAXI_PROVIDER_LATITUDE;
		$data['velocity'] 		= 25;
		$data['odometer'] 		= 100000;
		$data['outputs'] 		= 129;
		$data['status'] 		= 'INDISPONIVEL';
		$data['inputs'] 		= 129;
		
		try {
			// Try changing the status
			$this->sendRequest($url, 'POST', $data);
		} catch(Exception $e) {
			$finalTester 	= true;
			$errorMessage 	= $e->getMessage();
		}
		
		$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertEquals($this->vars['data']['report']['status'], 'INDISPONIVEL', 'Status must be returned as sent');
		
		$data['status'] = 'LIVRE';
		
		try	{
			// Try changing the status
			$this->sendRequest($url, 'POST', $data);
		} catch(Exception $e) {
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}
		
		$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertEquals($this->vars['data']['report']['status'], 'LIVRE', 'Status must be returned as sent');
		
		unset($data['status']);
		
		try	{
			// Try without changing the status
			$this->sendRequest($url, 'POST', $data);
		} catch(Exception $e) {
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}
		
		$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertEquals($this->vars['data']['report']['status'], 'LIVRE', 'Status must be returned as last');
	}
	
	/**
	 * Testa monitoramento de operadores via GPS.
	 * O aplicativo android fica monitorando os operadores e enviando ao webservice suas coordenadas
	 * geográficas a cada alteração. Este método testa o envio destes dados e sua resposta.
	 */
	function testGPSOperador()
	{
		//preparação
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		
		//Cria um equipamento
		$this->dataGenerator->saveEquipamento(
				array(
						'Equipamento' => array(
								'no_serie' => 'PHPUNIT',
								'situacao' => 'ATIVO',
								'modelo' => 'ANDROID',
								'tipo' => EQUIPAMENTO_TIPO_SMARTPHONE
						)
				)
		);
		
		//cria uma área
		$area = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($area);
		
		//cria um operador
		$operador = $this->dataGenerator->getOperador();
		$this->dataGenerator->saveOperador($operador);
		
		//abre um serviço
		$servico = $this->dataGenerator->getServico();
		$servico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveServico($servico);
		
		//dados para report
		$status = 'OCUPADO';
		$data['serial_device'] 	= 'PHPUNIT';
		$data['longitude'] 		= '-51.146';
		$data['latitude'] 		= '-29.691';
		$data['status'] 		= $status;
		$data['operador_id'] 	= $this->dataGenerator->operadorId;
		
		$errorMessage 	= NULL;
		$finalTester 	= false;
		$url 			= $this->url('monitor', 'report');
		
		//envia um report
		try {
			// Try changing the status
			$this->sendRequest($url, 'POST', $data);
		} catch(Exception $e) {
			$finalTester 	= true;
			$errorMessage 	= $e->getMessage();
		}
		
		//testa resposta
		$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertEquals($this->vars['data']['report']['status'], $status, 'Status must be returned as last');
		
		//busca o monitor do banco de dados
		$monitor = $this->Monitor->find('first', array('recursive' => -1));
		
	}
}
