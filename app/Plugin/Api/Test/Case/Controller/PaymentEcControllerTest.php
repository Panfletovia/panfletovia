<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');
App::uses('AdvancedDBComponent', 'Api.Controller/Component');

/**
 * Classe que efetua os testes do comando de Pagamento do EC
 */
class PaymentEcControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;
	
	public $components = array(
		'Api.PeriodPurchase',
		'Api.AdvancedDB'
	);

	public $uses = array(
		'Produto',
		'Entidade',
		'Cliente',
		'Tarifa',
		'Posto',
		'Comissao',
		'Limite',
		'Equipamento',
		'Autorizacao',
		'Movimento',
		'Parking.Operador',
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.Preco',
		'Parking.Cobranca',
		'Parking.Servico',
		'Parking.Ticket',
		'Parking.ParkTarifa',
		'Parking.Historico',
	);

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a extensão a ser retornada pelo WebService
	private $extension = '.json';
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/payment_ec';
	// Variável auxiliar que guarda informações do cliente
	private $cliente = NULL;

	/**
	 * Método executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea(array('Area' => array('uteis_fim' => '23:59:59')));
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveAreaPonto();
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
			'tipo' 		=> EQUIPAMENTO_TIPO_SMARTPHONE,
			'no_serie' 	=> '1234567890',
			'modelo' 	=> 'ANDROID',
			'administrador_id' 	=> NULL,
			'posto_id' 	=> $this->dataGenerator->postoId
			)));
		$this->dataGenerator->saveTarifa(array('Tarifa' => array(
			'posto_id' => NULL
		)));

		$this->dataGenerator->saveComissao(array('Comissao' => array(
			'posto_id' => NULL
		)));
		$this->parkTarifa = $this->dataGenerator->getParkTarifa();
		$this->parkTarifa['ParkTarifa']['valor'] = 1.00;
		$this->parkTarifa['ParkTarifa']['minutos'] = 30;
		$this->parkTarifa['ParkTarifa']['codigo'] = 1;
		$this->dataGenerator->saveParkTarifa($this->parkTarifa);

		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();
		
		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();

		// Cria array com as formas de pagamentos possíveis, para enviar randômicamente aos testes
		$formaPagamento = array('DINHEIRO', 'PRE', 'PRE_PARCELADO', 'POS', 'POS_PARCELADO', 'CPF_CNPJ', 'DEBITO_AUTOMATICO');

		// Popula variável do comando
        $this->data['forma_pagamento'] 	= $formaPagamento[rand(0,6)];
        $this->data['valor_centavos'] 	= rand(0,9999);
        $this->data['codigo_pagamento'] = rand(0,9);
        
        // Popula campos com valores apenas para não cair nas validações de empty.
        // QUando for executar cada teste, deverá validar o que deve ser enviado nestes campos.
        $this->data['cpf_cnpj_pagamento'] = '';
        $this->data['cpf_cnpj_cliente'] = '';
        $this->data['senha'] 			= '';
        $this->data['rps'] 				= '';

        // Gera os limites para a recarga
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['versao_contrato'] = 1;
        $this->cliente['Cliente']['cpf_cnpj'] = '811.271.030-91';
        $this->dataGenerator->saveCliente($this->cliente);

        $clienteId = $this->Cliente->field('id', array('cpf_cnpj' => '811.271.030-91'));

        $this->assertNotEmpty($clienteId, 'Cliente id não encontrado');

        $this->dataGenerator->concedeLimitePre($clienteId, 66.66);

		$this->limitePosto = $this->Limite->findByEntidadeId($this->dataGenerator->postoId);
		$updateLimite = array('Limite' => array());
		$updateLimite['Limite']['id'] = $this->limitePosto['Limite']['id'];
		$updateLimite['Limite']['pos_liberado'] = 50000;

		$this->Limite->clear();


		$this->assertTrue(!!$this->Limite->save($updateLimite));
		
        // Salva placa do veículo
        $this->dataGenerator->savePlaca();

        // Salva plugin
        $this->dataGenerator->savePlugin();

		$this->data['phone']         = $this->cliente['Cliente']['telefone'];
		$this->data['qtde_periodos'] = 2;
		$this->data['area_id']       = $this->dataGenerator->areaId;
	}// End 'setUp'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX, pois na classe só deverá tratar a add
	*/
	public function testIndexError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testindexError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, pois na classe só deverá tratar a add
	*/
	public function testViewError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testViewError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, pois na classe só deverá tratar a add
	*/
	public function testEditError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testEditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, pois na classe só deverá tratar a add
	*/
	public function testDeleteError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'DELETE',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testDeleteError'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de que o código pagamento é inválido
	 */
	public function testCodigoPagamentoInvalido() {
		// Seta um código de pagamento inválido
		$this->data['codigo_pagamento'] = 99999;
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Código de pagamento inválido'
		);
	}// End 'testCodigoPagamentoInvalido'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de que o código pagamento não foi recebido
	 */
	public function testSemCodigoPagamento() {
		// Seta um código de pagamento inválido
		unset($this->data['codigo_pagamento']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Código de pagamento inválido'
		);
	}// End 'testSemCodigoPagamento'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro FormaPagamento está incorreto
	 */
	public function testSemFormaPagamento() {
		// Remove campo do array de envio
		unset($this->data['forma_pagamento']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Forma de pagamento inválida'
		);
	}// End 'testSemFormaPagamento'
	
	/**
	 * 
	 */
	public function testSemQtdePeriodos() {
		$this->getPeriodPurchaseFields();
		
		// Remove campo do array de envio
		unset($this->data['qtde_periodos']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Quantidade de períodos/minutos inválida'
		);
	}// End 'testSemFormaPagamento'
	
	/**
	 * 
	 */
	public function testQtdePeriodosZero() {
		
		$this->getPeriodPurchaseFields();
		
		// Remove campo do array de envio
		$this->data['qtde_periodos'] = 0;
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Quantidade de períodos/minutos inválida'
		);
	}// End 'testSemFormaPagamento'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro ValorCentavos está incorreto
	 */
	public function testSemValorCentavos() {
		// Remove campo do array de envio
		unset($this->data['valor_centavos']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Valor em centavos inválido'
		);
	}// End 'testSemValorCentavos'

	/**
	 * Testa acesso a API referente ao comando Compra de Período EC não esperando nenhuma exceção
	 */
	public function testPeriodPurchaseEC_fullTest(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Seta dados para pagamento
		$this->data['cpf_cnpj_pagamento'] 	= '811.271.030-91';
        $this->data['senha'] 				= $this->cliente['Cliente']['senha_site'];
        $this->data['vaga'] 				= '0';

        // Retira o campo telefone, pois não é uma compra via SMS
        unset($this->data['phone']);

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		$this->assertEquals($this->data['codigo_pagamento'], $this->vars['data']['pagamento']['codigo']);

		$this->assertEquals($this->data['forma_pagamento'], $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->data['valor_centavos'], $this->vars['data']['pagamento']['valor_centavos']);

		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->cliente['Cliente']['cpf_cnpj']);
		$this->assertEmpty($this->vars['data']['pagamento']['cpf_cnpj_cliente']);
		$this->assertEmpty($this->vars['data']['pagamento']['rps']);

		$this->assertEquals((int) $this->data['nsu'], $this->vars['data']['pagamento']['nsu']);

		$this->assertNotEmpty($this->vars['data']['entidade']['nome']);
		$this->assertNotEmpty($this->vars['data']['entidade']['cpf_cnpj']);

		$this->assertNotEmpty($this->vars['data']['autorizacao']['id']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['criado_em']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['pagamento']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['valor']);

		$this->assertNotEmpty($this->vars['data']['cliente']['saldo_atual']);
		$this->assertNotEmpty($this->vars['data']['cliente']['saldo_anterior']);

		$this->assertTrue($this->vars['data']['cliente']['saldo_atual'] < $this->vars['data']['cliente']['saldo_anterior']);

		$parkArea = $this->Area->find('first');

		$this->assertNotEmpty($parkArea);
		// Valida configuração da área para consumo de eticket
		if($parkArea['Area']['consumir_eticket']){
			$this->assertNotEmpty($this->vars['data']['compra_periodos']['e_ticket']);
		}


		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_inicio']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_fim']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_cobranca_id']);

		$this->assertNotNull($this->vars['data']['compra_periodos']['vaga']);

		$this->assertNotEmpty($this->vars['data']['compra_periodos']['placa']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['qtde_periodos']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['entidade_id']);
		
		
		//busca o ticket
		$ticket = $this->Ticket->findById($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertEqual($ticket['Ticket']['nsu_pagamento'], $this->data['nsu']);
				
	}// End method 'testPeriodPurchase_SemQuantity'
	
	/**
	 * Simula uma compra enviando minutos ao invés de períodos
	 */
	public function testPeriodPurchaseECQtdeMinutos(){
		
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 2.00,
				'minutos' => 60,
				'codigo' => 2
		)));
		$parkTarifaId = $this->dataGenerator->parktarifaId;
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 3.00,
				'minutos' => 90,
				'codigo' => 3
		)));
		
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Seta dados para pagamento
		$this->data['cpf_cnpj_pagamento'] 	= '811.271.030-91';
        $this->data['senha'] 				= '123456';
        
        unset($this->data['qtde_periodos']);
        $this->data['qtde_minutos'] = 60;
        $this->data['valor_centavos'] = 200;

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

-		$this->assertEquals($this->data['codigo_pagamento'], $this->vars['data']['pagamento']['codigo']);

		$this->assertEquals($this->data['forma_pagamento'], $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->data['valor_centavos'], $this->vars['data']['pagamento']['valor_centavos']);

		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->cliente['Cliente']['cpf_cnpj']);
		$this->assertEmpty($this->vars['data']['pagamento']['cpf_cnpj_cliente']);
		$this->assertEmpty($this->vars['data']['pagamento']['rps']);

		$this->assertEquals((int) $this->data['nsu'], $this->vars['data']['pagamento']['nsu']);

		$this->assertNotEmpty($this->vars['data']['entidade']['nome']);
		$this->assertNotEmpty($this->vars['data']['entidade']['cpf_cnpj']);

		$this->assertNotEmpty($this->vars['data']['autorizacao']['id']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['criado_em']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['pagamento']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']['valor']);

		$this->assertNotEmpty($this->vars['data']['cliente']['saldo_atual']);
		$this->assertNotEmpty($this->vars['data']['cliente']['saldo_anterior']);

		$this->assertTrue($this->vars['data']['cliente']['saldo_atual'] < $this->vars['data']['cliente']['saldo_anterior']);

		$parkArea = $this->Area->find('first');

		$this->assertNotEmpty($parkArea);
		// Valida configuração da área para consumo de eticket
		if($parkArea['Area']['consumir_eticket']){
			$this->assertNotEmpty($this->vars['data']['compra_periodos']['e_ticket']);
		}


		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_inicio']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_fim']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_cobranca_id']);

		$this->assertNotNull($this->vars['data']['compra_periodos']['vaga']);

		$this->assertNotEmpty($this->vars['data']['compra_periodos']['placa']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['qtde_minutos']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['entidade_id']);
	}
	
	
	public function testPeriodPurchaseECQtdeMinutosDinheiro(){
		
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
				'tipo' 		=> EQUIPAMENTO_TIPO_PARQUIMETRO,
				'no_serie' 	=> '789456789',
				'modelo' 	=> 'PARQUIMETRO',
				'posto_id' => $this->dataGenerator->postoId,
				'administrador_id' => NULL
		)));
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 2.00,
				'minutos' => 60,
				'codigo' => 2
		)));
		$parkTarifaId = $this->dataGenerator->parktarifaId;
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 3.00,
				'minutos' => 90,
				'codigo' => 3
		)));
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Seta dados para pagamento
		$this->data['cpf_cnpj_pagamento'] 	= '811.271.030-91';
        $this->data['senha'] 				= '123456';
        
        unset($this->data['qtde_periodos']);
        $this->data['qtde_minutos'] = 63;
        $this->data['valor_centavos'] = 123456;
        $this->data['forma_pagamento'] = 'DINHEIRO';
        
        $this->data['serial'] = '789456789';
        $this->data['type'] = EQUIPAMENTO_TIPO_PARQUIMETRO;
        $this->data['model'] = 'PARQUIMETRO';
        
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		$this->assertEquals($this->data['codigo_pagamento'], $this->vars['data']['pagamento']['codigo']);

		$this->assertEquals($this->data['forma_pagamento'], $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->data['valor_centavos'], $this->vars['data']['pagamento']['valor_centavos']);

		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->cliente['Cliente']['cpf_cnpj']);
		$this->assertEmpty($this->vars['data']['pagamento']['cpf_cnpj_cliente']);
		$this->assertEmpty($this->vars['data']['pagamento']['rps']);

		$this->assertEquals((int) $this->data['nsu'], $this->vars['data']['pagamento']['nsu']);

		$this->assertNotEmpty($this->vars['data']['entidade']['nome']);
		$this->assertNotEmpty($this->vars['data']['entidade']['cpf_cnpj']);


		$parkArea = $this->Area->find('first');

		$this->assertNotEmpty($parkArea);
		// Valida configuração da área para consumo de eticket
		if($parkArea['Area']['consumir_eticket']){
			$this->assertNotEmpty($this->vars['data']['compra_periodos']['e_ticket']);
		}


		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_inicio']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_fim']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_cobranca_id']);

		$this->assertNotNull($this->vars['data']['compra_periodos']['vaga']);

		$this->assertNotEmpty($this->vars['data']['compra_periodos']['placa']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['qtde_minutos']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['entidade_id']);
	}
	
	function testCompraMifare() {
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
				'tipo' 		=> EQUIPAMENTO_TIPO_PARQUIMETRO,
				'no_serie' 	=> '789456789',
				'modelo' 	=> 'PARQUIMETRO',
				'posto_id' => $this->dataGenerator->postoId,
				'administrador_id' => NULL
		)));
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 2.00,
				'minutos' => 60,
				'codigo' => 2
		)));
		$parkTarifaId = $this->dataGenerator->parktarifaId;
		
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 3.00,
				'minutos' => 90,
				'codigo' => 3
		)));
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();
		
		// Seta dados para pagamento
		unset($this->data['cpf_cnpj_pagamento']);
		unset($this->data['senha']);
		
		unset($this->data['qtde_periodos']);
		$this->data['qtde_minutos'] = 63;
		$this->data['valor_centavos'] = 123456;
		$this->data['forma_pagamento'] = 'CARTAO';
		
		$this->data['serial'] = '789456789';
		$this->data['type'] = EQUIPAMENTO_TIPO_PARQUIMETRO;
		$this->data['model'] = 'PARQUIMETRO';
		
		$this->data['codigo_cartao'] = '4328719360';
		
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		
		$this->assertEquals($this->data['codigo_pagamento'], $this->vars['data']['pagamento']['codigo']);
		
		$this->assertEquals($this->data['forma_pagamento'], $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->data['valor_centavos'], $this->vars['data']['pagamento']['valor_centavos']);
		
		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->cliente['Cliente']['cpf_cnpj']);
		$this->assertEmpty($this->vars['data']['pagamento']['cpf_cnpj_cliente']);
		$this->assertEmpty($this->vars['data']['pagamento']['rps']);
		
		$this->assertEquals((int) $this->data['nsu'], $this->vars['data']['pagamento']['nsu']);
		
		$this->assertNotEmpty($this->vars['data']['entidade']['nome']);
		$this->assertNotEmpty($this->vars['data']['entidade']['cpf_cnpj']);
		
		$parkArea = $this->Area->find('first');
		
		$this->assertNotEmpty($parkArea);
		// Valida configuração da área para consumo de eticket
		if($parkArea['Area']['consumir_eticket']){
			$this->assertNotEmpty($this->vars['data']['compra_periodos']['e_ticket']);
		}
		
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_inicio']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_fim']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_cobranca_id']);
		
		$this->assertNotNull($this->vars['data']['compra_periodos']['vaga']);
		
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['placa']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['qtde_minutos']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['entidade_id']);
	}
	
	function testCompraMifareHex() {
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
				'tipo' 		=> EQUIPAMENTO_TIPO_PARQUIMETRO,
				'no_serie' 	=> '789456789',
				'modelo' 	=> 'PARQUIMETRO',
				'posto_id' => $this->dataGenerator->postoId,
				'administrador_id' => NULL
		)));
	
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 2.00,
				'minutos' => 60,
				'codigo' => 2
		)));
		$parkTarifaId = $this->dataGenerator->parktarifaId;
	
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
				'valor' => 3.00,
				'minutos' => 90,
				'codigo' => 3
		)));
	
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();
	
		// Seta dados para pagamento
		unset($this->data['cpf_cnpj_pagamento']);
		unset($this->data['senha']);
	
		unset($this->data['qtde_periodos']);
		$this->data['qtde_minutos'] = 63;
		$this->data['valor_centavos'] = 123456;
		$this->data['forma_pagamento'] = 'CARTAO';
	
		$this->data['serial'] = '789456789';
		$this->data['type'] = EQUIPAMENTO_TIPO_PARQUIMETRO;
		$this->data['model'] = 'PARQUIMETRO';
	
		$this->data['codigo_cartao'] = '1';
	
		$this->data['area_id'] = $this->dataGenerator->areaId;

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
	
		$this->assertEquals($this->data['codigo_pagamento'], $this->vars['data']['pagamento']['codigo']);
	
		$this->assertEquals($this->data['forma_pagamento'], $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->data['valor_centavos'], $this->vars['data']['pagamento']['valor_centavos']);
	
		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->cliente['Cliente']['cpf_cnpj']);
		$this->assertEmpty($this->vars['data']['pagamento']['cpf_cnpj_cliente']);
		$this->assertEmpty($this->vars['data']['pagamento']['rps']);
	
		$this->assertEquals((int) $this->data['nsu'], $this->vars['data']['pagamento']['nsu']);
	
		$this->assertNotEmpty($this->vars['data']['entidade']['nome']);
		$this->assertNotEmpty($this->vars['data']['entidade']['cpf_cnpj']);
	
		$parkArea = $this->Area->find('first');
	
		$this->assertNotEmpty($parkArea);
		// Valida configuração da área para consumo de eticket
		if($parkArea['Area']['consumir_eticket']){
			$this->assertNotEmpty($this->vars['data']['compra_periodos']['e_ticket']);
		}
	
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_ticket_id']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_inicio']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['data_fim']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['park_cobranca_id']);
	
		$this->assertNotNull($this->vars['data']['compra_periodos']['vaga']);
	
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['placa']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['qtde_minutos']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertNotEmpty($this->vars['data']['compra_periodos']['entidade_id']);
	}

	/**
	 * Teste de recarga completo sem esperar erro.
	 * @return 
	 */
	public function testRechargeEc()	{
		
		$this->Tarifa->read(null, $this->dataGenerator->tarifaId);
		$this->Tarifa->set('posto_id', $this->dataGenerator->postoId);
		$this->Tarifa->save();

		$errorMessage = NULL;
		$finalTester = false;

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 1000.0;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento'] 	= 'DINHEIRO';
		
		try {
			$this->sendRequest($this->URL . $this->extension, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {    		   		
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}
		
		$this->assertFalse($finalTester, 'Unexpected exception: ' . $errorMessage);

		$autorizacao = $this->Autorizacao->find('first');
		$movimentos = $this->Movimento->find('all', array('order'=>'Movimento.operacao_id'));
		$this->assertNotEmpty($movimentos);
		$this->assertNotEmpty($autorizacao);

		$this->assertEquals(4,count($movimentos));

		$movimento1101 = $movimentos[0]['Movimento'];
		$movimento1109 = $movimentos[1]['Movimento'];
		$movimento1201 = $movimentos[2]['Movimento'];
		$movimento1209 = $movimentos[3]['Movimento'];

		$this->assertNotEmpty($movimento1101);
		$this->assertNotEmpty($movimento1109);
		$this->assertNotEmpty($movimento1201);
		$this->assertNotEmpty($movimento1209);

		$this->assertEquals((int)$movimento1101['operacao_id'], 1101);
		$this->assertEquals((int)$movimento1109['operacao_id'], 1109);
		$this->assertEquals((int)$movimento1201['operacao_id'], 1201);
		$this->assertEquals((int)$movimento1209['operacao_id'], 1209);

		$this->assertEquals($movimento1101['conta'], 'PRE');
		$this->assertEquals($movimento1201['conta'], 'POS');

		$this->assertEquals((float)$movimento1101['valor_original'], ($this->data['valor_centavos'] / 100));
		$this->assertEquals((float)$movimento1201['valor_original'], -($this->data['valor_centavos'] / 100));

		
		$limiteCliente = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		$limitePosto = $this->Limite->findByEntidadeId($this->dataGenerator->postoId);

		$this->assertEquals($movimento1101['limite_id'], $limiteCliente['Limite']['id']);
		$this->assertEquals($movimento1201['limite_id'], $limitePosto['Limite']['id']);

		$this->data['value'] = 50.0;

		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) 
		{    		   		
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}
		$this->assertTrue($finalTester, 'Expected exeption: ');
	} // End Method 'testRecharge'



	/**
	 * Teste de recarga esperando erro de cliente não encontrado
	 * @return 
	 */
	public function testRechargeEc_ClientNotFound()	{

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 1000.0;
		$this->data['cpf_cnpj_cliente'] 	= '123.456.789-90';
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL . $this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Cliente não encontrado'
		);
	}
	
		/**
	 * Teste da quitação de irregularidades.
	 */
	public function testPaymentDischargeIrregularities() {
		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
		// Salva serviço para inserir no ticket
		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();
		// Gera tickets irregulares de acordo com a quantidade criada randomicamente
		for($i = 0; $i < $qtdeTicketsIrregulares; $i++){
			// Cria um valor randômicamente para o ticket
			$valorRandom = rand(1,999);
			// Insere o ticket com os dados gerados
			$this->dataGenerator->saveTicket(array('Ticket' => array(
				'placa' 					=> $this->data['placa'],
				'situacao' 					=> 'AGUARDANDO',
				'valor'    					=> $valorRandom,
				'tipo'						=>'IRREGULARIDADE',
				'valor_original'			=> $valorRandom,
				'administrador_id' 			=> $this->dataGenerator->postoId
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;
		}
		// Popula código de pagamento para o código da Quitação de Irregularidades : 2 
		$this->data['valor_centavos'] 		= $valorTotalTickets * 100; // Multiplica para ter o valor em centavos
		$this->data['produto_id'] 			= $this->dataGenerator->produtoId;

		// Acessa o link da API
		$this->sendRequest('/api/PaymentEc/add.json', 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Adiciona campo a quantidade de tickets Irregulares que é o retorno esperado
		$this->data['qtde_tickets_irregulares'] = $qtdeTicketsIrregulares;
	
		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($this->vars['data']['pagamento']['forma']              , $this->data['forma_pagamento'] 	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->vars['data']['pagamento']['valor_centavos']     , $this->data['valor_centavos']	 	, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] . ' / Retornado: '. $this->vars['data']['pagamento']['valor_centavos']);
		$this->assertEquals($this->vars['data']['pagamento']['codigo']             , $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['codigo']);
		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'] , $this->data['cpf_cnpj_pagamento'] , 'O campo \'cpf_cnpj\' enviado é diferente do retornado. Enviado: '		 	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['cpf_cnpj_pagamento']);
		$this->assertEquals($this->vars['data']['pagamento']['rps']                , $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] . ' / Retornado: '. $this->vars['data']['pagamento']['rps']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['placa']				, $this->data['placa']	 			, 'O campo \'placa\' enviado é diferente do retornado. Enviado: '    			. $this->data['placa'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['placa']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['produto_id']		, $this->data['produto_id']	 		, 'O campo \'produto_id\' enviado é diferente do retornado. Enviado: '	 		. $this->data['produto_id'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['produto_id']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['qtde_tickets']		, $qtdeTicketsIrregulares	 	, 'O campo \'qtde_tickets\' enviado é diferente do retornado. Enviado: '	. $qtdeTicketsIrregulares . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['qtde_tickets']);
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['nsu'], 'Campo nsu vazio!');
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['pago_em'], 'Campo pago_em vazio!');

		//Busca os tickets na base.
		$ticketsIrregulares = $this->Ticket->find('all', array('conditions' => array('Ticket.tipo' => 'IRREGULARIDADE')));
		//Testa se os valores dos tickets na base fecham com os valores gerados do teste.
		$valorTotTickets = 0;
		foreach($ticketsIrregulares as $ticket){
			$valorTotTickets += $ticket['Ticket']['valor'];
			$this->assertEquals('PAGO', $ticket['Ticket']['situacao'], 'O campo situacao está diferente de PAGO.');
			if(!empty($ticket['Ticket']['equipamento_id_origem']) && !empty($ticket['Ticket']['equipamento_id_pagamento']) && !empty($ticket['Ticket']['entidade_id_pagamento'])){
				$this->assertTrue(true);
			}
		}
		$this->assertEquals($this->data['valor_centavos'], $valorTotTickets * 100, 'O campo valor_centavos está diferente do valor total recebidos da base.');
	}

	/**
	 * Teste de recarga esperando erro, pois será passado um CPF de um SAC
	 * @return 
	 */
	public function testRechargeEcComCpfSac()	{
		// Cria tarifa
		$this->Tarifa->id = $this->dataGenerator->tarifaId;
		$this->Tarifa->saveField('posto_id', $this->dataGenerator->postoId);

		// Cria um usuário SAC
		$sac = $this->dataGenerator->getSac();
		$sac['Sac']['versao_contrato'] = 1;
		$this->dataGenerator->saveSac($sac);

		$errorMessage = NULL;

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 12345.0;
		$this->data['cpf_cnpj_cliente'] 	= $sac['Sac']['cpf_cnpj'];
		
		try {
			$this->sendRequest($this->URL . $this->extension, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {    		   		
			$errorMessage = $e->getMessage();
		}
		
		$this->assertEquals('Cliente não encontrado', $errorMessage, 'Mensagem de erro não recebido.');
	} // End Method 'testRechargeEcComCpfSac'


	/**
	 * Testa o cadastro automático de um parquímetro na tabela entidade com o tipo = 'PARQUIMETRO', pois antes era cadastrado como 'POSTO'
	 */
	public function testRegisterNewParquimetro() {
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();
		// Altera parâmetros da requisição
        unset($this->data['qtde_periodos']);
		$this->data['qtde_minutos']       = $this->parkTarifa['ParkTarifa']['minutos'];
		$this->data['valor_centavos']     = $this->parkTarifa['ParkTarifa']['valor'];
		$this->data['forma_pagamento']    = 'DINHEIRO';
		$this->data['cpf_cnpj_pagamento'] = '811.271.030-91';
		$this->data['senha']              = '123456';
		$this->data['serial']             = '7894567890';
		$this->data['type']               = EQUIPAMENTO_TIPO_PARQUIMETRO;
		$this->data['model']              = EQUIPAMENTO_TIPO_PARQUIMETRO;
		// Executa uma action sem equipamento cadastrado, esperando erro
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Busca equipamento na base validando se o mesmo foi salvo com o tipo parquimetro
		$this->Equipamento->recursive = -1;
		$this->Entidade->recursive = -1;
		$equipamentoParq = $this->Equipamento->findByNoSerie($this->data['serial']);
		$entidadeParq    = $this->Entidade->findByCpfCnpj($this->data['serial']);
		// Valida se encontrou
		$this->assertNotNull($equipamentoParq);
		$this->assertNotNull($entidadeParq);
		// Extrai informação do objeto
		$equipamentoParq = $equipamentoParq['Equipamento'];
		$entidadeParq = $entidadeParq['Entidade'];
		// Valida o registro do equipamento criado
		$this->assertEquals($this->data['type'], $equipamentoParq['tipo']);
		$this->assertEquals($this->data['model'], $equipamentoParq['modelo']);
		$this->assertEquals($this->data['serial'], $equipamentoParq['no_serie']);
		// Valida o registro da entidade criado
		$this->assertEquals($this->data['serial'], $entidadeParq['cpf_cnpj']);
		$this->assertEquals($this->data['serial'], $entidadeParq['telefone']);
		$this->assertNull($entidadeParq['email']);
		$this->assertTrue(strstr($entidadeParq['nome'],'Parqu') !== false);
		$this->assertEquals($entidadeParq['nome'], $entidadeParq['fantasia']);
		$this->assertEquals(EQUIPAMENTO_TIPO_PARQUIMETRO, $entidadeParq['tipo']);
	}// End method 'testRegisterInvalidData'

// 	/**
// 	 ********************************************************************
// 	 ********************************************************************
// 	 *******                                                      *******
// 	 ******* CAMPOS DEFAULT PARA CADA TIPO DE CODIGO DE PAGAMENTO *******
// 	 *******                                                      *******
// 	 ********************************************************************
// 	 ********************************************************************
// 	 */

	
	/**
	 * Método auxiliar para popular os campos da compra de periodo em todos os testes deste tipo de codigo de pagamento.
	 */
	private function getPeriodPurchaseFields(){
		// Busca registro da área ponto para buscar o número da vaga inserido
		$parkAreaPonto = $this->AreaPonto->findById($this->dataGenerator->areapontoId);
		// Validação se o registro da área ponto não é nulo
		$this->assertNotNull($parkAreaPonto, 'Registro da Área Ponto é NULL!');

		// Popula os campos necessários para compra de período
		$this->data['codigo_pagamento'] = CODPAG_VENDA_PERIODO_EC;
		$this->data['placa']            = $this->getRandomPlace();
		$this->data['qtde_periodos']    = 1;
		// $this->data['tipo_veiculo']     = $this->getRandomTypeVehicle();
		$this->data['tipo_veiculo']     = 'CARRO';
		// $this->data['vaga']             = $parkAreaPonto['AreaPonto']['codigo'];
		$this->data['valor_centavos']	= $this->getValueByAmoutPurchasePeriod($this->data['qtde_periodos'], $this->dataGenerator->precoId);

		$this->data['forma_pagamento'] = 'PRE';
	}// End 'getPeriodPurchaseFields'



	/**
	 * Método auxiliar para popular os campos da quitação de irregularidades em todos os testes deste tipo de codigo de pagamento.
	 */
	private function getIrregularitiesFields(){
		// Popula os campos necessários para compra de período
		$this->data['codigo_pagamento'] = CODPAG_QUITACAO_IRREGULARIDADES;
		$this->data['placa']            = $this->getRandomPlace();
		$this->data['produto_id']		= '';

		//TODO: POR ENQUANTO MANDA SEMPRE DINHEIRO
		$this->data['forma_pagamento'] = 'DINHEIRO';
	}// End 'getIrregularitiesFields'
	
}// End Class