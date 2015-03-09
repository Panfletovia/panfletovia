<?php

App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe de teste do controller HistoryMobileController
 */
class HistoryMobileControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.ParkPlaca',
		'Parking.Preco',
		'Produto',
		'Parking.Cobranca',
		'Parking.Historico',
		'Parking.Ticket',
		'Comunicacao',
		'Equipamento'
		);

	// Variável que recebe os campos defautl
	private $data      = NULL;
	// Variável que recebe o formato de dados da requisição
	private $extension = '.json';
	// Variável que recebe a URL para requisição
	private $URL       = '/api/history_mobile';

	/**
	 * Metódo que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria Registros necessários para teste
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		$this->parkPreco = $this->dataGenerator->getPreco();
		$this->dataGenerator->savePreco($this->parkPreco);
		$this->parkTarifa = $this->dataGenerator->getParkTarifa();
		$this->dataGenerator->saveParkTarifa($this->parkTarifa);
		$this->dataGenerator->saveCobranca();
		$this->parkArea = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($this->parkArea);
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveEquipamento(
			array(
				'Equipamento' => array(
					'tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,
					'no_serie' => '1234567890',
					'modelo' => 'ANDROID',
					'nsu' => 1,
				)
			)
		);
		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}
	/**
	 * Testa a validação do id do cliente não recebido
	 */
	public function testEmptyClienteId() {
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Id do cliente não recebido'
		);
	}// End Method 'testEmptyClienteId'
	
	/**
	 * Testa se a API irá retornar a lista de compra de períodos e irregularidades na ordem correta
	 */
	public function testListaDeTickets(){
		// Cria um cliente
		$clienteId = $this->dataGenerator->saveCliente();
		// Vincula uma placa ao cliente
		$parkPlaca = $this->dataGenerator->getPlaca();
		$this->dataGenerator->savePlaca($parkPlaca);
		// Compra um periodo
		$this->dataGenerator->venderTicketEstacionamentoDinheiro(
			$this->parkTarifa['ParkTarifa']['valor'], 
			$parkPlaca['Placa']['placa']
		);
		// Gera uma irregularidade
		$this->dataGenerator->emiteIrregularidade($parkPlaca['Placa']['placa'], 0 , 'FORA_DA_VAGA', true);
		// Adiciona o id do cliente nos parâmetros da requisição
		$this->data['client_id'] = $clienteId;
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida se recebeu resposta
		$this->assertNotEmpty($this->vars['data']['tickets']);
		$this->assertNotEmpty($this->vars['data']['now']);
		// Valida a quantidade
		$this->assertEquals(2, count($this->vars['data']['tickets']));
		// Contador
		$cont = 0;

		// Varre os registros para validar a ordem e os campos
		foreach ($this->vars['data']['tickets'] as $key => $value) {

			$this->assertEquals($this->parkArea['Area']['nome'], $value['Area']['nome']);
			// Primeiro registro deverá ser o da irregularidade, pois é do último para o primeiro
			if ($cont == 0){
				// Validações do registro de irregularidades
				$this->assertEquals($parkPlaca['Placa']['placa'], $value['Ticket']['placa']);
				$this->assertEquals('AGUARDANDO', $value['Ticket']['situacao']);
				$this->assertEquals($this->parkPreco['Preco']['valor_irregularidade'] * 100, $value['Ticket']['valor']);
				$this->assertEquals('IRREGULARIDADE', $value['Ticket']['tipo']);

				$this->assertEquals(0, $value['Ticket']['periodos']);
				$this->assertEquals(0, $value['Ticket']['vaga']);
				$this->assertEquals($this->dataGenerator->getDate('+1 day', 'Y-m-d'), $value['Ticket']['notificacao_vencimento']);
				
				$this->assertEquals('FORA_DA_VAGA', $value['Ticket']['motivo_irregularidade']);
				$this->assertEquals(null, $value['Ticket']['forma_pagamento']);
				$this->assertEquals(false, $value['Ticket']['interrompido']);
			// Registro de compra de período
			} else{
				$this->assertEquals($parkPlaca['Placa']['placa'], $value['Ticket']['placa']);
				$this->assertEquals('PAGO', $value['Ticket']['situacao']);
				$this->assertEquals($this->parkTarifa['ParkTarifa']['valor'] * 100, $value['Ticket']['valor']);
				$this->assertEquals('UTILIZACAO', $value['Ticket']['tipo']);

				$this->assertEquals(1, $value['Ticket']['periodos']);
				$this->assertEquals(0, $value['Ticket']['vaga']);
				$this->assertEquals(null, $value['Ticket']['notificacao_vencimento']);

				$this->assertEquals('NENHUM', $value['Ticket']['motivo_irregularidade']);
				$this->assertEquals('DINHEIRO', $value['Ticket']['forma_pagamento']);
				$this->assertEquals(false, $value['Ticket']['interrompido']);
			}
			// Incrementa contador
			$cont++;
		}
	}// End Method 'testListaDeTickets'
}// End Class