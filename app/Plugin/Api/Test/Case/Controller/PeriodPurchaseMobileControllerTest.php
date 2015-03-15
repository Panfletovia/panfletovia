<?php

App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe de teste do controller PeriodPurchaseMobileController
 */
class PeriodPurchaseMobileControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.ParkPlaca',
		'Parking.Preco',
		'Parking.Placa',
		'Produto',
		'Comissao',
		'Tarifa',
		'Parking.Tarifa',
		'Parking.Cobranca',
		'Parking.Setor',
		'Parking.Historico',
		'Parking.Operador',
		'Parking.Servico',
		'Parking.Ticket',
		'Parking.Contrato',
		'Parking.ContratoPlaca',
		'Equipamento'
		);

	// Variável que recebe os campos defautl
	private $data      = NULL;
	// Variável que recebe o formato de dados da requisição
	private $extension = '.json';
	// Variável que recebe a URL para requisição
	private $URL       = '/api/period_purchase_mobile';

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
		$this->dataGenerator->saveArea(array('Area' => array('tempo_minimo_devolucao' => 0)));
		$this->dataGenerator->saveSetor();
		$this->parkAreaPonto = $this->dataGenerator->getAreaPOnto();
		$this->dataGenerator->saveAreaPonto($this->parkAreaPonto);

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
		// Salva o cliente e vincula o ID gerado para o objeto
		$this->cliente = $this->dataGenerator->getCliente();
		$clienteId = $this->dataGenerator->saveCliente($this->cliente);
		$this->cliente['Cliente']['id'] = $clienteId;
		// Dá limite pre para usuário
		$this->dataGenerator->concedeLimitePre($clienteId, 1000);
		// Salva uma placa vinculada ao cliente
		$this->parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlacaId = $this->dataGenerator->savePlaca($this->parkPlaca);
		$this->parkPlaca['Placa']['id'] = $parkPlacaId;
		$this->placa = $this->parkPlaca['Placa']['placa'];

		$this->periodos = $this->parkTarifa['ParkTarifa']['codigo'];

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}

	/**
	 * Testa a validação do id do cliente não recebido
	 */
	public function testEmptyClienteId() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Remove o campo para gerar o erro
		unset($this->data['cliente_id']);
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Verifique os parâmetros da requisição'
		);
	}// End Method 'testEmptyClienteId'

	/**
	 * Testa a validação do id da area não recebida
	 */
	public function testEmptyAreaId() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Remove o campo para gerar o erro
		unset($this->data['area_id']);
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Verifique os parâmetros da requisição'
		);
	}// End Method 'testEmptyAreaId'

	/**
	 * Testa a validação da placa não recebida
	 */
	public function testEmptyPlaca() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Remove o campo para gerar o erro
		unset($this->data['placa']);
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Verifique os parâmetros da requisição'
		);
	}// End Method 'testEmptyPlaca'

	/**
	 * Testa a validação dos periodos não recebidos
	 */
	public function testEmptyPeriodos() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Remove o campo para gerar o erro
		unset($this->data['periodos']);
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Verifique os parâmetros da requisição'
		);
	}// End Method 'testEmptyPeriodos'

	/**
	 * Testa a validação de cliente não encontrado
	 */
	public function testInvalidClientId() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Envia um id de cliente ĩnexistente
		$this->data['cliente_id'] = 999;
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Cliente não encontrado'
		);
	}// End Method 'testInvalidClientId'

	/**
	 * Testa a validação de placa inativa
	 */
	public function testInvalidPlate() {
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Atualiza a placa para inativa
		$this->Placa->id = $this->parkPlaca['Placa']['id'];
		$this->Placa->saveField('inativo', 1);
		// Acessa o link da API, sem o cliente Id esperando receber o erro 'Id do cliente não recebido'
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Placa não encontrada'
		);
	}// End Method 'testInvalidPassword'

	/**
	 * Testa a funcionalidade completa sem esperar erro
	 */
	public function testPeriodPurchaseMobile(){
		// Busca o saldo antes de efetuar a compra do cliente
		$oldSaldo = $this->dataGenerator->getSaldoPreUsuario();
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se recebeu resposta
		$this->assertNotEmpty($this->vars['data']['ticket']);
		$this->assertNotEmpty($this->vars['data']['saldo_pre']);
		// Valida a quantidade
		$this->assertEquals(1, count($this->vars['data']['ticket']));
		// Busca o saldo após a compra
		$newSaldo = $this->vars['data']['saldo_pre'];
		// Valida o saldo do cliente após a compra
		$valorPeriodo = $this->parkTarifa['ParkTarifa']['valor'];
		$saldoEsperado = ($oldSaldo - $valorPeriodo) * 100;
		$this->assertEquals($saldoEsperado, $newSaldo);
		// Extrai o ticket
		$ticketCompra = $this->vars['data']['ticket']['Ticket'];
		// Valida placa
		$this->assertEquals($this->placa                               , $ticketCompra['placa']);
		$this->assertEquals('UTILIZACAO'                               , $ticketCompra['tipo']);
		$this->assertEquals('PAGO'                                     , $ticketCompra['situacao']);
		$this->assertEquals(ADMIN_PARKING_ID                           , $ticketCompra['entidade_id_origem']);
		$this->assertEquals(ADMIN_PARKING_ID                           , $ticketCompra['entidade_id_pagamento']);
		$this->assertEquals($this->dataGenerator->precoId              , $ticketCompra['preco_id']);
		$this->assertEquals($this->dataGenerator->precoId              , $ticketCompra['preco_id_original']);
		$this->assertEquals($this->dataGenerator->areaId               , $ticketCompra['area_id']);
		$this->assertEquals($this->data['periodos']                    , $ticketCompra['periodos']);
		$this->assertEquals('CARRO'                                    , $ticketCompra['veiculo']);
		$this->assertEquals(ADMIN_PARKING_ID                           , $ticketCompra['administrador_id']);
		$this->assertEquals($this->dataGenerator->cobrancaId           , $ticketCompra['cobranca_id']);
		$this->assertEquals($this->dataGenerator->cobrancaId           , $ticketCompra['cobranca_id_original']);
		$this->assertEquals('CPF_CNPJ'                                 , $ticketCompra['forma_pagamento']);
		$this->assertEquals($valorPeriodo                              , $ticketCompra['valor_original']);
		$this->assertEquals($this->parkTarifa['ParkTarifa']['minutos'] , $ticketCompra['tempo_tarifa']);
	}// End Method 'testPeriodPurchaseMobile'

	/**
	 * Testa a funcionalidade esperando erro na chamada da movimenta conta.
	 * Erro gerado será por 'saldo insuficiente pré' do cliente;
	 */
	public function testPeriodPurchaseMobileWithErrorSaldoPre(){
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Zera saldo do usuário para gerar o erro
		$this->dataGenerator->concedeLimitePre($this->cliente['Cliente']['id'], 0);
		try {
			// Envia requisição esperando erro de saldo
			$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
			// Caso não ocorra exception na requisição, deverá falhar o teste
			$this->fail('Não ocorreu exceção esperada');
		} catch	(Exception $e){
			$this->assertEquals('Ocorreu um erro ao efetuar a compra de períodos: Saldo Insuficiente Pre', $e->getMessage());
		}
	}// End Method 'testPeriodPurchaseMobileWithErrorSaldoPre'

	/**
	 * Testa a funcionalidade esperando erro na validação do valor a ser pago.
	 * Erro gerado será por 'Nesta área sua placa nao necessita pagamento';
	 */
	public function testPeriodPurchaseMobileWithErrorIsento(){
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Cria contrato de isenção para placa
        $parkContrato = $this->dataGenerator->getContrato();
        $this->dataGenerator->saveContrato($parkContrato);
        $parkContratoPlaca = $this->dataGenerator->getContratoPlaca($this->placa);
        $parkContratoPlaca['Contrato'] = $parkContrato['Contrato'];
        $this->ContratoPlaca->save($parkContratoPlaca);
		try {
			// Envia requisição esperando erro de isenção
			$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
			// Caso não ocorra exception na requisição, deverá falhar o teste
			$this->fail('Não ocorreu exceção esperada');
		} catch	(Exception $e){
			$this->assertEquals('Nesta área sua placa não necessita pagamento', $e->getMessage());
		}
	}// End Method 'testPeriodPurchaseMobileWithErrorIsento'

	/**
	 * Testa para validar a interrupção de um ticket
	 */
	public function testInterruptTicket(){
		// Popula as variáveis do controller
		$this->populateParamsPeriodPurchase();
		// Cria os dados default
		$valorTicket  = $this->parkTarifa['ParkTarifa']['valor'];
		$placa        = $this->data['placa'];
		$vaga         = $this->parkAreaPonto['AreaPonto']['codigo'];
		$codigoTarifa = $this->data['periodos'];
		// Compra um periodo com cpf_cnpj
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj($valorTicket, $placa, $vaga, $codigoTarifa);
		// Busca id do ticket comprado
		$parkTicket = $this->Ticket->find('first');
		// Envia requisição
		$this->sendRequest($this->URL . '/delete/'. $parkTicket['Ticket']['id'] . $this->extension, 'DELETE', $this->data);
		// Valida se recebeu o campo esperado
		$this->assertTrue(isset($this->vars['data']['valor']));
		// Valida se o valor devolvido é igual ao valor total do ticket EM CENTAVOS
		$valorTicketCentavos = $valorTicket * 100;
		$valorRetornoCentavos = $this->vars['data']['valor'];
		$this->assertEquals($valorTicketCentavos, $valorRetornoCentavos);
		// Busca novamente o ticket validando se o mesmo está cancelado
		$newTicket = $this->Ticket->findById($parkTicket['Ticket']['id']);
		// Valida se o mesmo está cancelado
		$this->assertEquals('PAGO'       , $newTicket['Ticket']['situacao']);
		$this->assertEquals(1            , $newTicket['Ticket']['interrompido']);
		$this->assertEquals($valorTicket , $newTicket['Ticket']['desconto']);
		$this->assertEquals(0.00         , $newTicket['Ticket']['valor']);
	}// End Method 'testInterruptTicket'

	/**
	 * Método que popula os valores default do controller
	 */
	private function populateParamsPeriodPurchase() {
		$this->data['cliente_id'] = $this->cliente['Cliente']['id'];
		$this->data['password']   = $this->cliente['Cliente']['senha_site'];
		$this->data['area_id']    = $this->dataGenerator->areaId;
		$this->data['placa']      = $this->placa;
		$this->data['periodos']   = $this->periodos;
	}// End Method 'populateParamsPeriodPurchase'
}// End Class