<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes do comando de Pagamento do Parking
 */
class PaymentParkingControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Operador',
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.Preco',
		'Produto',
		'Parking.Cobranca',
		'Parking.Servico',
		'Parking.Ticket',
		'Parking.Historico',
		'Parking.ParkTarifa',
		'Parking.Eticket',
		'Parking.OperadorCliente',
		'Tarifa',
		'Entidade',
		'Comissao',
		'Limite',
		'Equipamento',
		'Autorizacao',
		'Cliente',
		'Parking.ParkAutorizacao',
		'Movimento',
		'Pendente',
		'Pedido',
		'Item',
		'Pagamento',
		'Transacao',
		'Configuracao',
		'Posto');

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a extensão a ser retornada pelo WebService
	private $extension = '.json';
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/payment_parking';

	/**
	 * Método executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->parkArea = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($this->parkArea);
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveAreaPonto();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
			'tipo' 		=> EQUIPAMENTO_TIPO_SMARTPHONE,
			'no_serie' 	=> '1234567890',
			'modelo' 	=> 'ANDROID')));
		$this->dataGenerator->saveOperador(array('Operador' => array(
			'usuario' 	=> '1234567890',
			'senha' 	=> '1234567890')));
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveServico(array('Servico' => array(
			'data_fechamento' => NULL
			)));
		$this->dataGenerator->saveTarifa();

		$this->dataGenerator->saveComissao(array('Comissao' => array(
			'posto_id' => NULL
			)));

		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();

		// Cria array com as formas de pagamentos possíveis, para enviar randomicamente aos testes
		$formaPagamento = array('DINHEIRO', 'PRE', 'PRE_PARCELADO', 'POS', 'POS_PARCELADO', 'CPF_CNPJ', 'DEBITO_AUTOMATICO', 'CARTAO');

		// Popula variável do comando
		$this->data['forma_pagamento']    = $formaPagamento[rand(0,6)];
		$this->data['valor_centavos']     = rand(0,9999);
		$this->data['codigo_pagamento']   = rand(0,9);
		// Popula campos com valores apenas para não cair nas validações de empty.
		// QUando for executar cada teste , deverá validar o que deve ser enviado nestes campos.
		$this->data['cpf_cnpj_pagamento'] = '';
		$this->data['cpf_cnpj_cliente']   = '';
		$this->data['senha']              = '';
		$this->data['rps']                = '';
		$this->data['area_id']            = $this->dataGenerator->areaId;

		// Salva cliente
		$this->cliente = $this->dataGenerator->getCliente();
		$this->cliente['Cliente']['versao_contrato'] = 1;
		$this->cliente['Cliente']['cpf_cnpj'] = '811.271.030-91';
		$this->dataGenerator->saveCliente($this->cliente);

        // Gera os limites para a recarga
		$this->limiteCliente   = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		$this->limitePosto     = $this->Limite->findByEntidadeId($this->dataGenerator->postoId);
		$this->limiteAssociado = $this->Limite->findByEntidadeId(ADMIN_PARKING_ID);

		// Libera limite para as entidades necessários
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 10000);
		$this->dataGenerator->concedeLimitePos(ADMIN_PARKING_ID, 50000);

		$this->assertTrue(!!$this->ParkTarifa->save(array(
			'codigo' => 1,
			'preco_id' => $this->dataGenerator->precoId,
			'valor' => 1.00,
			'minutos' => 30,
			'vender_associado' => 1,
			'vender_posto' => 1,
			'vender_internet' => 1
			)));
		$this->postoIdCielo = $this->Posto->field('id', array('nome' => 'CIELO'));
	}// End 'setUp'


	public function url($controller, $action)
	{
		return '/api/' . $controller . '/' . $action . '.json';
	}

	// PRESTENÇÃO! TUDU!
	// Criar um teste da lógica da métrica de cadastro + primeira recarga (ranking operadores)
	// simulando um erro nesta recarga (tipo saldo pós insuficiente), para verificar
	// se a recarga é contabilizada nas métricas do ranking

	/**
	 * Testa a seguinte situação:
	 * - Comprei um período placa 'AAA-6666' para área 1. Após comprei um período para mesma placa na área dois.
	 * Na compra de período, o ticket antigo da área um, deverá ser transformado para área 2 (com um novo ticket),
	 * através da chamada da procedure que transfere os veículos de área (park_transfere_veiculo_area). Então, o ticket comprado
	 * deverá acumular o tempo em cima do ticket que a procedure criou. Total tickets 3.
	 * 
	 */
	public function testCompraPeriodoOutraArea(){

		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		$placa = $this->data['placa'];
		$nsu = intval($this->data['nsu']);

		
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		
		$recebidoPagamentoForma            = $this->vars['data']['pagamento']['forma'];
		$recebidoPagamentoValorCentavos    = $this->vars['data']['pagamento']['valor_centavos'];
		$recebidoPagamentoCodigo           = $this->vars['data']['pagamento']['codigo'];
		$recebidoPagamentoCpfCnpjPagamento = $this->vars['data']['pagamento']['cpf_cnpj_pagamento'];
		$recebidoPagamentoCpfCnpjCliente   = $this->vars['data']['pagamento']['cpf_cnpj_cliente'];
		$recebidoPagamentoRps              = $this->vars['data']['pagamento']['rps'];
		$recebidoPagamentoNsu              = $this->vars['data']['pagamento']['nsu'];

		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($recebidoPagamentoForma				, $this->data['forma_pagamento']	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoForma);
		$this->assertEquals($recebidoPagamentoValorCentavos		, $this->data['valor_centavos']		, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] 	. ' / Retornado: '. $recebidoPagamentoValorCentavos);
		$this->assertEquals($recebidoPagamentoCodigo			, $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoCodigo);
		$this->assertEquals($recebidoPagamentoCpfCnpjPagamento	, $this->data['cpf_cnpj_pagamento']	, 'O campo \'cpf_cnpj_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $recebidoPagamentoCpfCnpjPagamento);
		$this->assertEquals($recebidoPagamentoCpfCnpjCliente	, $this->data['cpf_cnpj_cliente']	, 'O campo \'cpf_cnpj_cliente\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_cliente'] 	. ' / Retornado: '. $recebidoPagamentoCpfCnpjCliente);
		$this->assertEquals($recebidoPagamentoRps				, $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] 				. ' / Retornado: '. $recebidoPagamentoRps);
		$this->assertEquals($recebidoPagamentoNsu				, $this->data['nsu']			 	, 'O campo \'nsu\' enviado é diferente do retornado. Enviado: '			 		. $this->data['nsu'] 				. ' / Retornado: '. $recebidoPagamentoNsu);
		
		$this->assertEquals($this->vars['data']['compra_periodos']['placa']				, $this->data['placa']			 ,   'O campo \'placa\' enviado é diferente do retornado. Enviado: '    		 . $this->data['placa'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['placa']);
		$this->assertEquals($this->vars['data']['compra_periodos']['qtde_periodos']		, $this->data['qtde_periodos']	 ,   'O campo \'qtde_periodos\' enviado é diferente do retornado. Enviado: '	 . $this->data['qtde_periodos'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['qtde_periodos']);
		$this->assertEquals($this->vars['data']['compra_periodos']['tipo_veiculo']		, $this->data['tipo_veiculo']	 ,   'O campo \'tipo_veiculo\' enviado é diferente do retornado. Enviado: '	 . $this->data['tipo_veiculo'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertEquals($this->vars['data']['compra_periodos']['vaga']				, $this->data['vaga']	 		 ,   'O campo \'vaga\' enviado é diferente do retornado. Enviado: '	 		 . $this->data['vaga'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['vaga']);
		
		// Faz uma busca no ticket e verifica se os campos 'servico_id_origem' e 'operador_id_origem' estão preenchidos corretamente
		$parkTicket = $this->Ticket->find('first', array('conditions' => array('placa' => $this->data['placa'])));
		// Valida se o ticket não é nulo
		$this->assertNotNull($parkTicket, 'Ticket não foi inserido corretamente no pagamento da compra de períodos.');
		// Valida se o ticket possui dados válidos
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_origem']		, 'Serviço de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_pagamento']	, 'Serviço de pagamento do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_origem']	, 'Operador de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_pagamento']	, 'Operador de pagamento do ticket não deve ser nulo.');

		// Busca registro antigo do veículo
		$oldParkHistorico = $this->Historico->find('first', array('conditions' => array('placa' => $this->data['placa'], 'area_id' => $this->dataGenerator->areaId, 'situacao' => 'LANCADO')));
		// Valida se encontrou registro na park_historico
		$this->assertNotNull($oldParkHistorico, 'ParkHistorico após compra de ticket não encontrado');

		// Guarda id da área anterior
		$oldAreaId = $this->dataGenerator->areaId;

		// Salva novos registros necessários para o segundo serviço
		$newParkAreaSave = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($newParkAreaSave);
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveAreaPonto();
		
		// Busca registro da área ponto para buscar o número da vaga inserido
		$parkAreaPonto = $this->AreaPonto->findById($this->dataGenerator->areapontoId);
		// Validação se o registro da área ponto não é nulo
		$this->assertNotNull($parkAreaPonto, 'Registro da Área Ponto é NULL!');
		// Cria nova área
		$newParkArea = $this->Area->find('first', array('conditions' => array('Area.nome' => $newParkAreaSave['Area']['nome'])));
		$this->assertNotNull($newParkArea);

		// Setá os valores para os campos padrões
		$this->data = null;
		$this->data = $this->getApiDefaultParams();

		// salva id do equipamento antigo 
		$oldEquipamentoId = $this->dataGenerator->equipamentoId;

		// Seta dados para o novo equipamento
		$newSerial = '1111111111';
		$newEquipamentSave = $this->dataGenerator->getEquipamento();
		$newEquipamentSave['Equipamento']['no_serie'] = $newSerial;
		$newEquipamentSave['Equipamento']['api_key'] = md5(API_KEY.$this->data['nsu'].$newSerial);
		$newEquipamentSave['Equipamento']['tipo'] = 'ANDROID';
		$newEquipamentSave['Equipamento']['modelo'] = 'ANDROID';
		$this->dataGenerator->saveEquipamento($newEquipamentSave);
		// Busca novo equipamento, afim de buscar seu id
		$newEquipament = $this->Equipamento->findByNoSerie($newSerial);
		// Valida se o mesmo foi salvo com sucesso
		$this->assertNotNull($newEquipament);
		// Salva novo operador
		$newParkOperadorSave = $this->dataGenerator->getOperador();
		$this->dataGenerator->saveOperador($newParkOperadorSave);
		// Valida novo operador
		$newParkOperador = $this->Operador->find('first', array('order' => array('id' => 'desc')));
		$this->assertNotNull($newParkOperador);

		// Guarda id do serviço antigo
		$oldServicoId = $this->dataGenerator->servicoId;

		// Salva novo serviço
		$newParkServico = $this->dataGenerator->getServico();
		$newParkServico['Servico']['equipamento_id'] = $newEquipament['Equipamento']['id'];
		$newParkServico['Servico']['operador_id'] = $newParkOperador['Operador']['id'];
		$newParkServico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveServico($newParkServico);

		// Salva uma nova tarifa 
		$this->ParkTarifa->clear();
		$newParkTarifa = $this->dataGenerator->getParkTarifa();
		$newParkTarifa['ParkTarifa']['codigo'] = 2;
		$newParkTarifa['ParkTarifa']['preco_id'] = $this->dataGenerator->precoId;
		$newParkTarifa['ParkTarifa']['valor'] = 2.00;
		$newParkTarifa['ParkTarifa']['minutos'] = 60;
		$newParkTarifa['ParkTarifa']['vender_associado'] = 1;
		$newParkTarifa['ParkTarifa']['vender_posto'] = 1;
		$newParkTarifa['ParkTarifa']['vender_internet'] = 1;
		$this->ParkTarifa->save($newParkTarifa);

		// Calcula valor em reais a serem comprados
		$valorTicket = ($this->getValueByAmoutPurchasePeriod($newParkTarifa['ParkTarifa']['codigo'] , $this->dataGenerator->precoId) / 100);
		// Compra ticket
		$this->dataGenerator->venderTicketEstacionamentoDinheiro($valorTicket, $placa, $parkAreaPonto['AreaPonto']['codigo'], $newParkTarifa['ParkTarifa']['codigo']);

		// Busca informações da park_historico
		$listHistorico = $this->Historico->find('all', array(
			'conditions' => array(
				'placa' => $placa),
			'order' => array('Historico.id')
		));

		// Valida quantidade de registros encontrados.
		$this->assertEquals(2, count($listHistorico) , 'Quantidade de registros da park_historico está errado');
		// Separa historicos
		$parkHistorico1 = $listHistorico[0]['Historico'];
		$parkHistorico2 = $listHistorico[1]['Historico'];

		// Valida primeiro registro
		$this->assertNotNull($parkHistorico1['removido_em'], 'Registro antigo não está removido');
		$this->assertEquals('REMOVIDO', $parkHistorico1['situacao'],'Registro antigo não está removido');
		$this->assertEquals($oldEquipamentoId, $parkHistorico1['equipamento_id'], 'Equipamento do registro antigo não é o esperado.');
		$this->assertEquals($oldAreaId, $parkHistorico1['area_id'], 'Area esperada diferente da recebida');

		// Valida segundo registro
		$this->assertNull($parkHistorico2['removido_em'], 'Registro novo está removido');
		$this->assertEquals('LANCADO', $parkHistorico2['situacao'],'Registro novo não está removido');
		$this->assertEquals($newEquipament['Equipamento']['id'], $parkHistorico2['equipamento_id'], 'Equipamento do registro novo não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkHistorico2['area_id'], 'Area esperada diferente da recebida');


		// BUsca informações dos tickets criados
		$listTickets = $this->Ticket->find('all', array(
			'conditions'=> array('placa' => $placa),
			'order' => array('Ticket.id')
			));

		// Valida existência de tickets
		$this->assertNotNUll($listTickets);

		// Valida quantidade de tickets criados
		$this->assertEquals(3, count($listTickets), 'Quantidade de tickets esperado diferente do recebido');
		// Extrai os tickets
		$parkTicket1 = $listTickets[0]['Ticket'];
		$parkTicket2 = $listTickets[1]['Ticket'];
		$parkTicket3 = $listTickets[2]['Ticket'];


		// Valida placa
		$this->assertEquals($placa, $parkTicket1['placa'], 'Placa do ticket1 diferente do esperado');
		$this->assertEquals($placa, $parkTicket2['placa'], 'Placa do ticket2 diferente do esperado');
		$this->assertEquals($placa, $parkTicket3['placa'], 'Placa do ticket3 diferente do esperado');

		// Valida valor
		$this->assertEquals('0.00', $parkTicket1['valor'], 'Valor do ticket1 diferente do esperado');
		$this->assertEquals('1.00', $parkTicket2['valor'], 'Valor do ticket2 diferente do esperado');
		$this->assertEquals('2.00', $parkTicket3['valor'], 'Valor do ticket3 diferente do esperado');

		// Valida situação
		$this->assertEquals('PAGO', $parkTicket1['situacao'], 'Situação do ticket1 diferente de PAGO');
		$this->assertEquals('PAGO', $parkTicket2['situacao'], 'Situação do ticket2 diferente de PAGO');
		$this->assertEquals('PAGO', $parkTicket3['situacao'], 'Situação do ticket3 diferente de PAGO');

		// Valida tipo
		$this->assertEquals('UTILIZACAO', $parkTicket1['tipo'], 'Tipo do ticket1 diferente de UTILIZACAO');
		$this->assertEquals('UTILIZACAO', $parkTicket2['tipo'], 'Tipo do ticket2 diferente de UTILIZACAO');
		$this->assertEquals('UTILIZACAO', $parkTicket3['tipo'], 'Tipo do ticket3 diferente de UTILIZACAO');

		// Valida área id
		$this->assertEquals($oldAreaId, $parkTicket1['area_id'], 'AreaId do ticket1 não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkTicket2['area_id'], 'AreaId do ticket2 não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkTicket3['area_id'], 'AreaId do ticket3 não é o esperado');

		// Valida serviçoId
		$this->assertEquals($oldServicoId, $parkTicket1['servico_id_origem'], 'ServicoId do ticket1 diferente do esperado.');
		$this->assertEquals($oldServicoId, $parkTicket2['servico_id_origem'], 'ServicoId do ticket2 diferente do esperado.');
		$this->assertEquals($this->dataGenerator->servicoId, $parkTicket3['servico_id_origem'], 'ServicoId do ticket3 diferente do esperado.');

		// Validação da autorização
		$this->assertNotNull($parkTicket1['autorizacao_id'], 'Autorização do ticket1 está null');
		$this->assertNotNull($parkTicket2['autorizacao_id'], 'Autorização do ticket2 não está null');
		$this->assertNotNull($parkTicket3['autorizacao_id'], 'Autorização do ticket3 não está null');
		$this->assertNotEquals($parkTicket1['autorizacao_id'], $parkTicket3['autorizacao_id'], 'Id das autorizações dos tickets 1 e 3 não são diferentes.');

		// Validação do historico Id
		$this->assertEquals($parkHistorico1['id'], $parkTicket1['historico_id'], 'HistoricoId do ticket1, diferente do esperado');
		$this->assertEquals($parkHistorico2['id'], $parkTicket2['historico_id'], 'HistoricoId do ticket2, diferente do esperado');
		$this->assertEquals($parkHistorico2['id'], $parkTicket3['historico_id'], 'HistoricoId do ticket3, diferente do esperado');

		// Validação do valor original
		$this->assertEquals('1.00', $parkTicket1['valor_original'], 'Valor Original do ticket1 diferente do esperado');
		$this->assertEquals('1.00', $parkTicket2['valor_original'], 'Valor Original do ticket2 diferente do esperado');
		$this->assertEquals('2.00', $parkTicket3['valor_original'], 'Valor Original do ticket3 diferente do esperado');

		// Validação do id da cobrança
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket1['cobranca_id'], 'CobrancaId do ticket1, diferente do esperado');
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket2['cobranca_id'], 'CobrancaId do ticket2, diferente do esperado');
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket3['cobranca_id'], 'CobrancaId do ticket3, diferente do esperado');
	}// End Method '_testCompraPeriodoOutraArea'

	/**
	 * Testa recarga para um cliente sem esperar erro
	 */
	public function testRecharge()	{
		$errorMessage = NULL;
		$finalTester = false;

		$url = $this->url('PaymentParking', 'add');

		$valorRecargaCentavos = 3333.0;

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'DINHEIRO';

		// Busca limite antes da recarga
		$limiteAntes = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		
		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}

		$this->assertFalse($finalTester, 'Unexpected exception: ' . $errorMessage);

		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();

		// Busca limite depois da recarga
		$limiteDepois = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);

		// Valida se a diferença dos limites antes e depois da recarga é exatamente o valor da recarga
		$diferencaLimite = $limiteDepois['Limite']['saldo_pre'] - $limiteAntes['Limite']['saldo_pre'];
		// Valida igualmente do valor da recarga
		$this->assertEquals(($valorRecargaCentavos / 100), $diferencaLimite);

		$autorizacao = $this->Autorizacao->find('first');
		$movimentos = $this->Movimento->find('all', array('order'=>'Movimento.operacao_id'));

		$this->assertNotEmpty($movimentos);
		$this->assertNotEmpty($autorizacao);
		$this->assertEquals(count($movimentos), 2);

		$movimento1101 = $movimentos[0]['Movimento'];
		$movimento1201 = $movimentos[1]['Movimento'];

		$this->assertNotEmpty($movimento1101);
		$this->assertNotEmpty($movimento1201);

		$this->assertEquals((int)$movimento1101['operacao_id'], 1101);
		$this->assertEquals((int)$movimento1201['operacao_id'], 1201);

		$this->assertEquals($movimento1101['conta'], 'PRE');
		$this->assertEquals($movimento1201['conta'], 'POS');

		$this->assertEquals((float)$movimento1101['valor_original'], ($this->data['valor_centavos'] / 100));
		$this->assertEquals((float)$movimento1201['valor_original'], -($this->data['valor_centavos'] / 100));

		$this->assertEquals($movimento1101['limite_id'], $this->limiteCliente['Limite']['id']);
		$this->assertEquals($movimento1201['limite_id'], $this->limiteAssociado['Limite']['id']);
	} // End Method 'testRecharge'

	/**
	 * Testa a criação de um pedido quando a recarga é efetuada via cartão; 
	 * a autorização somente será criada na confirmação do pagamento
	 */
	public function testRechargeCreditCard() {
		$url = $this->url('PaymentParking', 'add');
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);

		$valorRecargaCentavos = 3333.0;		

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		// Busca limite antes da recarga
		$limiteAntes = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		
		$this->sendRequest($url, 'POST', $this->data);
		// Testa de alguma autorização foi criada
		$autorizacao = $this->Autorizacao->find('first');
		$this->assertEmpty($autorizacao);

		// Busca limite depois da chamada do método
		$limiteDepois = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		// Testa se o limite foi alterado
		$this->assertEquals($limiteAntes, $limiteDepois, 'Limite foi indevidamente alterado.');

		// Testa se o pedido foi criado
		$pedidoRecarga = $this->Pedido->find('first');
		$this->assertNotEmpty($pedidoRecarga, 'Pedido de recarga não foi criado.');
		
		// Recupera o id de pagamento 'Cielo'
		$pagamentoIdCielo = $this->Pagamento->field('id', array('descricao' => 'CIELO'));
		$postoIdCielo = $this->Posto->field('id', array('nome' => 'CIELO'));
		
		// Testa os campos mais importantes do pedido
		$this->assertEquals($this->dataGenerator->clienteId, $pedidoRecarga['Pedido']['entidade_id'], 'Tipo do pedido está incorreto.');
		$this->assertEquals($valorRecargaCentavos / 100, $pedidoRecarga['Pedido']['total'], 'Valor do pedido está incorreto.');
		$this->assertEquals('RECARGA', $pedidoRecarga['Pedido']['tipo'], 'Tipo do pedido está incorreto.');
		
		$this->assertEquals($pagamentoIdCielo, $pedidoRecarga['Pedido']['pagamento_id'], 'Pagamento_id do pedido está incorreto.');
		$this->assertEquals('EM_PROCESSAMENTO', $pedidoRecarga['Pedido']['situacao'], 'Situação do pedido está incorreto.');
		$this->assertEquals($this->dataGenerator->equipamentoId, $pedidoRecarga['Pedido']['equipamento_id'], 'Equipamento_id do pedido está incorreto.');
		$this->assertEquals($postoIdCielo, $pedidoRecarga['Pedido']['posto_id'], 'Posto_id do pedido está incorreto.');
		$this->assertNull($pedidoRecarga['Pedido']['associado_id'], 'Associado_id foi incorretamente preenchido.');
		$this->assertNotEmpty($pedidoRecarga['Pedido']['nsu'], 'NSU do pedido está incorreto.');

		// Testa se o item foi criado
		$item = $this->Item->find('first');
		$this->assertNotEmpty($item, 'Item de recarga não foi criado.');
		
		// Testa os campos do item
		$this->assertEquals($limiteDepois['Limite']['id'], $item['Item']['limite_id'], 'Limite_id do item está incorreto.');
		$this->assertEquals($valorRecargaCentavos / 100, $item['Item']['valor'], 'Valor do item está incorreto.');
		$this->assertEquals('EM_PROCESSAMENTO', $item['Item']['situacao'], 'Situação do item está incorreto.');
		$this->assertEquals($pedidoRecarga['Pedido']['id'], $item['Item']['pedido_id'], 'Pedido_id do item está incorreto.');

		// Testa o retorno da API
		$this->assertEquals($pedidoRecarga['Pedido']['id'], $this->vars['data']['pedido']['id'], 'Id do pedido retornado está incorreto.');
		$this->assertEquals($pedidoRecarga['Pedido']['criado_em'], $this->vars['data']['pedido']['criado_em'], 'Criado_em do pedido retornado está incorreto.');
		$this->assertEquals('CARTAO', $this->vars['data']['pedido']['pagamento'], 'Pagamento do pedido retornado está incorreto.');
		$this->assertEquals($pedidoRecarga['Pedido']['total'], $this->vars['data']['pedido']['valor'] / 100, 'valor do pedido retornado está incorreto.');
	}

	/**
	 * Testa se a api irá negar um pedido de recarga com valor inferior ao configurado
	 */
	public function testRechargeCreditCardSemPostoCielo() {
		$url = $this->url('PaymentParking', 'add');

		// Descaracteriza o posto cielo
		$this->Posto->recursive = -1;
		$postoCielo = $this->Posto->findByNome('CIELO');
		$this->Posto->id = $postoCielo['Posto']['id'];
		$this->Posto->saveField('nome', 'NERSO');

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 333;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		$errorMessage = null;
		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Posto Cielo não configurado. Contate o administrador.', $errorMessage, 'API lançou exceção errada!');

		// Testa se o pedido foi criado
		$pedidoRecarga = $this->Pedido->find('first');
		$this->assertEmpty($pedidoRecarga, 'Pedido de recarga foi indevidamente criado.');
	}

	/**
	 * Testa se a api irá negar um pedido de recarga com valor inferior ao configurado
	 */
	public function testRechargeCreditCardPostoCieloSemLimitePos() {
		$url = $this->url('PaymentParking', 'add');

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 3333;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		$errorMessage = null;
		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Posto Cielo não possui limite suficiente para esta transação. Contate o admininstrador.', $errorMessage, 'API lançou exceção errada!');

		// Testa se o pedido foi criado
		$pedidoRecarga = $this->Pedido->find('first');
		$this->assertEmpty($pedidoRecarga, 'Pedido de recarga foi indevidamente criado.');
	}

	/**
	 * Testa se a api irá negar um pedido de recarga com valor inferior ao configurado
	 */
	public function testRechargeCreditCardValorInvalido() {
		$url = $this->url('PaymentParking', 'add');
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);

		// Salva a configuração de valor mínimo de recarga para 5 reais
		$this->Configuracao->updateAll(array('valor' => 5), array('chave' => 'VALOR_MINIMO_RECARGA_CARTAO'));

		$valorRecargaCentavos = 333;

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('O valor desta recarga é inferior ao valor mínimo de recarga configurado. A solicitação não foi processada.', $errorMessage, 'API lançou exceção errada!');

		// Testa se o pedido foi criado
		$pedidoRecarga = $this->Pedido->find('first');
		$this->assertEmpty($pedidoRecarga, 'Pedido de recarga foi indevidamente criado.');
	}

	/**
	 * Testa a baixa de um pedido de recarga já processado
	 **/
	public function testRechargeCardProcessedOrder() {
		$url = $this->url('PaymentParking', 'edit');
		$valorRecarga = 5.55;
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);

		// Cria pedido + item de recarga a ser aprovado
		$pedido = array(
			'entidade_id' => $this->dataGenerator->clienteId,
			'pagamento_id' => 3,
			'tipo' => 'RECARGA',
			'posto_id' => $this->postoIdCielo,
			'equipamento_id' => $this->dataGenerator->equipamentoId,
			'nsu' => 100
		);

		$this->dataGenerator->savePedido(array('Pedido' => $pedido));

		$item = array(
			'limite_id' => $this->limiteCliente['Limite']['id'],
			'pedido_id' => $this->dataGenerator->pedidoId,
			'valor' => $valorRecarga
		);

		$this->dataGenerator->saveItem(array('Item' => $item));

		// Processa o pedido
		$this->Item->id = $this->dataGenerator->itemId;
		$this->Item->saveField('situacao', 'PROCESSADO');
		
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecarga * 100;
		$this->data['pedido_id']	 		= $this->dataGenerator->pedidoId;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'PUT', $this->data);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Pedido de recarga não pode ser processado.', $errorMessage, 'Erro esperado não foi lançado.');
	}

	/**
	 * Testa a baixa de um pedido de recarga já processado
	 **/
	public function testRechargeCardCancelledOrder() {
		$url = $this->url('PaymentParking', 'edit');
		$valorRecarga = 5.55;

		// Cria pedido + item de recarga a ser aprovado
		$pedido = array(
			'entidade_id' => $this->dataGenerator->clienteId,
			'pagamento_id' => 3,
			'tipo' => 'RECARGA',
			'posto_id' => $this->postoIdCielo,
			'equipamento_id' => $this->dataGenerator->equipamentoId,
			'nsu' => 100
		);

		$this->dataGenerator->savePedido(array('Pedido' => $pedido));

		$item = array(
			'limite_id' => $this->limiteCliente['Limite']['id'],
			'pedido_id' => $this->dataGenerator->pedidoId,
			'valor' => $valorRecarga
		);

		$this->dataGenerator->saveItem(array('Item' => $item));

		// Processa o pedido
		$this->Item->id = $this->dataGenerator->itemId;
		$this->Item->saveField('situacao', 'CANCELADO');
		
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecarga * 100;
		$this->data['pedido_id']	 		= $this->dataGenerator->pedidoId;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'PUT', $this->data);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Pedido de recarga não pode ser processado.', $errorMessage, 'Erro esperado não foi lançado.');
	}

	/**
	 * Testa a baixa de um pedido de recarga após a aprovação do crédito pela Cielo
	 */
	public function testRechargeCreditCardApproved() {
		$url = $this->url('PaymentParking', 'edit');
		$valorRecarga = 5.55;
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);

		// Cria pedido + item de recarga a ser aprovado
		$pedido = array(
			'entidade_id' => $this->dataGenerator->clienteId,
			'pagamento_id' => 3,
			'tipo' => 'RECARGA',
			'posto_id' => $this->postoIdCielo,
			'equipamento_id' => $this->dataGenerator->equipamentoId,
			'nsu' => 100
		);

		$this->dataGenerator->savePedido(array('Pedido' => $pedido));

		$item = array(
			'limite_id' => $this->limiteCliente['Limite']['id'],
			'pedido_id' => $this->dataGenerator->pedidoId,
			'valor' => $valorRecarga
		);

		$this->dataGenerator->saveItem(array('Item' => $item));
		
		$transacao = $this->dataGenerator->getTransacao();

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecarga * 100;
		$this->data['pedido_id']	 		= $this->dataGenerator->pedidoId;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		$transacao['Transacao']['valor'] = $valorRecarga;

		$this->data['transaction'] = json_encode($transacao['Transacao']);

		// Busca limite antes da recarga
		$limiteAntes = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		
		$this->sendRequest($url, 'PUT', $this->data);
		$this->dataGenerator->clearPendente();
		
		// Testa a autorização
		$autorizacao = $this->Autorizacao->find('first');
		$this->assertNotEmpty($autorizacao, 'Autorização não foi criada!');
		$this->assertEquals('APROVADO', $autorizacao['Autorizacao']['situacao']);

		// Testa o pedido
		$pedidoRecarga = $this->Pedido->find('first');
		$this->assertNotEmpty($pedidoRecarga, 'Pedido de recarga não foi criado.');
		$this->assertEquals('PROCESSADO', $pedidoRecarga['Pedido']['situacao'],'Pedido de recarga não foi criado.');

		// Busca limite depois da chamada do método
		$limiteDepois = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		// Testa se o limite foi alterado
		$this->assertEquals($limiteAntes['Limite']['pre_creditado'] + $valorRecarga, $limiteDepois['Limite']['pre_creditado'], 'Valor do limite do cliente está incorreto');

		// Testa se a transação foi salva na tabela
		$this->Transacao->recursive = -1;
		$transacaoSalva = $this->Transacao->find('first');

		$this->assertNotEmpty($transacaoSalva);

		$this->assertEquals($valorRecarga, $transacaoSalva['Transacao']['valor'], 'Valor da recarga incorreto na tabela transacao');
		$this->assertEquals($this->dataGenerator->pedidoId, $transacaoSalva['Transacao']['pedido_id'], 'Pedido id incorreto na tabela transacao');


		// Testa se os campos do retorno estão presentes
		$this->assertNotEmpty($this->vars['data']['cliente']);
		$this->assertNotEmpty($this->vars['data']['pagamento']);
		$this->assertNotEmpty($this->vars['data']['entidade']);
		$this->assertNotEmpty($this->vars['data']['autorizacao']);
	}

	/**
	 * Testa se uma exception será lançada caso o tipo pagamento Cielo não esteja cadastrado
	 */
	public function testEfetuaRecargaCartaoSemPagamentoId() {
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);
		$errorMessage;
		// Deleta o pagamento tipo Cielo
		$this->Pagamento->delete(array('id' => 3));

		$url = $this->url('PaymentParking', 'add');

		$valorRecargaCentavos = 3333.0;

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'POST', $this->data);
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Código de pagamento Cielo não cadastrado. Contate o administrador.', $errorMessage, 'Erro esperado não foi lançado.');
	}

	/**
	 * Testa se uma exceção será lançada caso um erro aconteça ao gravar o pedido
	 */
	public function testEfetuaRecargaCartaoErroPedido() {
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);
		// Cria uma regra de validação falsa para forçar o erro no save() do Pedido
		$this->controller->Pedido->validator()->add('pagamento_id', array(
	        	'rule' => array('comparison', '==', 1)
	    	)
		);

		$errorMessage = null;

		$url = $this->url('PaymentParking', 'add');

		$valorRecargaCentavos = 3333.0;		

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'POST', $this->data);
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Não foi possível criar o pedido desta recarga. Tente novamente em alguns instantes.', $errorMessage, 'Erro esperado não foi lançado.');
	}

	/**
	 * Testa se uma exceção será lançada caso um erro aconteça ao gravar o item
	 */
	public function testEfetuaRecargaCartaoErroItem() {
		$this->dataGenerator->concedeLimitePos($this->postoIdCielo, 100);
		// Cria uma regra de validação falsa para forçar o erro no save() do Item
		$this->controller->Item->validator()->add('pedido_id', array(
	        	'rule' => array('comparison', '==', 0)
	    	)
		);

		$errorMessage = null;

		$url = $this->url('PaymentParking', 'add');

		$valorRecargaCentavos = 3333.0;		

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		try {
			$this->sendRequest($url, 'POST', $this->data);
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
		}

		$this->assertEquals('Não foi possível criar o pedido desta recarga. Tente novamente em alguns instantes.', $errorMessage, 'Erro esperado não foi lançado.');
	}

	public function testRechargeClientBlocked()	{
		$errorMessage = NULL;
		$finalTester = false;    	

		$url = $this->url('PaymentParking', 'add');
		$cli = $this->Cliente->findById($this->dataGenerator->clienteId);
		$cli['Cliente']['erros_senha_site'] = 3;
		$cli['Cliente']['check_email'] = 1;
		$this->Cliente->save($cli);

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= 1000.0;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'DINHEIRO';
		
		try {
			$this->sendRequest($url, 'POST', $this->data); // Try placing the order
		} catch(Exception $e) {
			$finalTester = true;
			$errorMessage = $e->getMessage();
		}

		$this->assertFalse($finalTester, 'Unexpected exception: ' . $errorMessage);

		$this->Pendente->deleteAll(array('operacao_id' => 1101));
		$limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		$this->assertNotNull($limite);
		$this->assertTrue($limite['Limite']['pre_creditado'] > 0);

		$autorizacao = $this->Autorizacao->find('first');
		$movimentos = $this->Movimento->find('all', array('order'=>'Movimento.operacao_id'));

		$this->assertNotEmpty($movimentos);
		$this->assertNotEmpty($autorizacao);
		$this->assertEquals(count($movimentos), 2);

		$movimento1101 = $movimentos[0]['Movimento'];
		$movimento1201 = $movimentos[1]['Movimento'];

		$this->assertNotEmpty($movimento1101);
		$this->assertNotEmpty($movimento1201);

		$this->assertEquals((int)$movimento1101['operacao_id'], 1101);
		$this->assertEquals((int)$movimento1201['operacao_id'], 1201);

		$this->assertEquals($movimento1101['conta'], 'PRE');
		$this->assertEquals($movimento1201['conta'], 'POS');

		$this->assertEquals((float)$movimento1101['valor_original'], ($this->data['valor_centavos'] / 100));
		$this->assertEquals((float)$movimento1201['valor_original'], -($this->data['valor_centavos'] / 100));

		$this->assertEquals($movimento1101['limite_id'], $this->limiteCliente['Limite']['id']);
		$this->assertEquals($movimento1201['limite_id'], $this->limiteAssociado['Limite']['id']);

	} // End Method 'testRecharge'


	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX, pois na classe só deverá tratar a add
	*/
	public function testindexError() {
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

		$newCliente = $this->dataGenerator->getCliente();
		$this->dataGenerator->saveCliente($newCliente);

		// Seta um código de pagamento inválido
		$this->data['cpf_cnpj_pagamento'] = $newCliente['Cliente']['cpf_cnpj'];
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
	************************************************************************ 
	************************************************************************ 
	*******                                                           ****** 
	******* DAQUI PRA BAIXO SÃO OS TESTES DE CADA CODIGO DE PAGAMENTO ******
	*******                                                           ****** 
	************************************************************************ 
	************************************************************************ 
	*/

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'BadRequest' e a mensagem que a placa não foi recebida.
	 */
	public function testPeriodPurchaseSemPlaca(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Remove a placa dos dados a serem enviados
		unset($this->data['placa']);

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Placa não recebida'
			);
	}// End method 'testPeriodPurchaseSemPlaca'

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'BadRequest' 
	 * e a mensagem que a quantidade de períodos não foi recebida.
	 */
	public function testPeriodPurchaseSemQtdePeriodo(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Remove a quantidade de períodos dos dados a serem enviados
		unset($this->data['qtde_periodos']);

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Quantidade de períodos não recebida'
			);
	}// End method 'testPeriodPurchaseSemQtdePeriodo'

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'BadRequest' 
	 * e a mensagem que o tipo de veículo não foi recebida.
	 */
	public function testPeriodPurchaseSemTipoVeiculo(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Remove a quantidade de períodos dos dados a serem enviados
		unset($this->data['tipo_veiculo']);

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Tipo de veículo não recebido'
			);
	}// End method 'testPeriodPurchaseSemTipoVeiculo'

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'NotFound' 
	 * e a mensagem de que o código da vaga informado a procedure 'park_calcula_periodos' é inválido.
	 */
	public function testPeriodPurchaseParkCalculaPeriodosVagaInvalida(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera número da vaga para uma não existente
		$this->data['vaga'] = 50000;
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Vaga não encontrada'
		);
	}// End method 'testPeriodPurchaseParkCalculaPeriodosVagaInvalida'

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'InternalError' 
	 * e a mensagem de que os valores enviados e calculados são diferentes.
	*/ 
	public function testPeriodPurchaseCheckChangePrice(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera o valor calculado da compra de período para lançar exceção
		$this->data['valor_centavos'] = 999999;

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Houve uma atualização nos valores de cobrança. Por favor, tente novamente.'
			);
	}// End method 'testPeriodPurchaseCheckChangePrice'

	/**
	 * Testa se a API vai conseguir comparar com sucesso a tarifa 
	 * no valor de R$ 2,01, problema que foi detectado em 15/07/14
	 * com o associado do Maique'
	 */ 
	public function testPeriodPurchaseCheckChangePriceBisonho(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera o valor calculado da compra de período para lançar exceção
	$this->data['valor_centavos'] = '201';

		$tarifa = $this->ParkTarifa->find('all');
		$tarifa['ParkTarifa']['valor'] = 2.01;
		$this->ParkTarifa->save($tarifa);

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
	}// End method '_testPeriodPurchaseCheckChangePriceBisonho'

	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'InternalErrorException' 
	 * e a mensagem que o veículo possui irregularidades pendentes e por isso não pode concluir a compra.
	 */
	public function testPeriodPurchaseBloqueiaCompraVeiculoComIrregularidade(){
		
		$placa = 'FOO-9876';
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera configuração da área 'bloquear_compra_apos_irregularidade'
		$newParkArea = array('Area' => array( 'id' => $this->dataGenerator->areaId, 'bloquear_compra_apos_irregularidade' =>  1));
		$this->Area->save($newParkArea);

		$this->dataGenerator->verificaVeiculo($placa, 0);
		
		$historico = $this->Historico->find('first', array('conditions' => array('placa' => $placa)));
		
		// Gera um ticket irregular para o veículo
		$irregularidade = $this->dataGenerator->getTicket();
		$irregularidade['Ticket']['placa'] = $placa;
		$irregularidade['Ticket']['historico_id'] = $historico['Historico']['id'];
		$irregularidade['Ticket']['tipo'] = 'IRREGULARIDADE';
		$irregularidade['Ticket']['situacao'] = 'AGUARDANDO';
		$irregularidade['Ticket']['criado_em'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_inicio'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_fim'] = $this->dataGenerator->getDateTime('+10 minutes');

		$this->dataGenerator->saveTicket($irregularidade);
		
		$this->data['placa'] = $placa;
		
		//$this->testAction($this->URL, array('data' => $this->data, 'method' => 'POST'));
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Compra bloqueada: veículo possui irregularidade vigente'
			);


		//verifica se a compra foi efetuada
		$conditions = array(
			'Ticket.tipo' => 'UTILIZACAO',
			'placa' => $this->data['placa']
		);
		
		$tickets = $this->Ticket->find('all', array('conditions' => $conditions));
		$this->assertEmpty($tickets);

	}// End method 'testPeriodPurchaseBloqueiaCompraVeiculoComIrregularidade'

	public function testPeriodPurchaseBloqueiaCompraVeiculoComIrregularidade2(){
		
		$placa = 'FOO-9876';
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();
		
		// Altera configuração da área 'bloquear_compra_apos_irregularidade'
		$newParkArea = array('Area' => array( 'id' => $this->dataGenerator->areaId, 'bloquear_compra_apos_irregularidade' =>  1));
		$this->Area->save($newParkArea);
		
		$this->dataGenerator->verificaVeiculo($placa, 0);
		
		$historico = $this->Historico->find('first', array('conditions' => array('placa' => $placa)));
		
		// Gera um ticket irregular para o veículo
		$irregularidade = $this->dataGenerator->getTicket();
		$irregularidade['Ticket']['placa'] = $placa;
		$irregularidade['Ticket']['historico_id'] = $historico['Historico']['id'];
		$irregularidade['Ticket']['tipo'] = 'IRREGULARIDADE';
		$irregularidade['Ticket']['situacao'] = 'AGUARDANDO';
		$irregularidade['Ticket']['criado_em'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_inicio'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_fim'] = $this->dataGenerator->getDateTime('-1 minutes');
		
		$this->dataGenerator->saveTicket($irregularidade);
		
		$this->data['placa'] = $placa;
		
		$this->testAction($this->URL, array('data' => $this->data, 'method' => 'POST'));
		
		//verifica se a compra foi efetuada
		$conditions = array(
			'Ticket.tipo' => 'UTILIZACAO',
			'placa' => $this->data['placa']
		);
		
		$tickets = $this->Ticket->find('all', array('conditions' => $conditions));
		$this->assertNotEmpty($tickets);
		
	}// End method 'testPeriodPurchaseBloqueiaCompraVeiculoComIrregularidade'
	
	public function testPeriodPurchaseBloqueiaCompraVeiculoComIrregularidadeCancelada(){
		
		$placa = 'FOO-9876';
		
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();
		
		// Altera configuração da área 'bloquear_compra_apos_irregularidade'
		$newParkArea = array('Area' => array( 'id' => $this->dataGenerator->areaId, 'bloquear_compra_apos_irregularidade' =>  1));
		$this->Area->save($newParkArea);
		
		$this->dataGenerator->verificaVeiculo($placa, 0);
		
		$historico = $this->Historico->find('first', array('conditions' => array('placa' => $placa)));
		
		// Gera um ticket irregular para o veículo
		$irregularidade = $this->dataGenerator->getTicket();
		$irregularidade['Ticket']['placa'] = $placa;
		$irregularidade['Ticket']['historico_id'] = $historico['Historico']['id'];
		$irregularidade['Ticket']['tipo'] = 'IRREGULARIDADE';
		$irregularidade['Ticket']['situacao'] = 'CANCELADO';
		$irregularidade['Ticket']['criado_em'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_inicio'] = $this->dataGenerator->getDateTime('-10 minutes');
		$irregularidade['Ticket']['data_fim'] = $this->dataGenerator->getDateTime('+10 minutes');
		
		$this->dataGenerator->saveTicket($irregularidade);
		
		$this->data['placa'] = $placa;
		
		$this->testAction($this->URL, array('data' => $this->data, 'method' => 'POST'));
		
		//verifica se a compra foi efetuada
		$conditions = array(
			'Ticket.tipo' => 'UTILIZACAO',
			'placa' => $this->data['placa']
		);
		
		$tickets = $this->Ticket->find('all', array('conditions' => $conditions));
		$this->assertNotEmpty($tickets);
		
	}
	
	/**
	 * Testa acesso a API referente ao comando Compra de Período, esperando a exceção 'InternalErrorException' 
	 * e a mensagem que o veículo possui irregularidades pendentes mas permite a compra de períodos.
	*/
	public function testPeriodPurchasePermiteCompraVeiculoComIrregularidade(){
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera configuração da área 'bloquear_compra_apos_irregularidade'
		$newParkArea = array('Area' => array( 'id' => $this->dataGenerator->areaId, 'bloquear_compra_apos_irregularidade' => 0));
		$this->Area->save($newParkArea);

		// Gera um ticket irregular para o veículo
		$this->dataGenerator->saveTicket(array('Ticket' => array(
			'placa'     => $this->data['placa'],
			'tipo' 		=> 'IRREGULARIDADE',
			'situacao' 	=> 'AGUARDANDO'
			)));

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);		

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Valida se a mensagem de comunicado importante está preenchida com a mensagem esperada
		$this->assertEquals($this->vars['data']['compra_periodos']['comunicado_importante'], 'Comunicado importante : existe 1 irregularidade pendente.');
	}// End method 'testPeriodPurchasePermiteCompraVeiculoComIrregularidade'


	/**
	* Testa acesso a API referente ao comando Compra de Período, esperando todos os dados sem nenhum erro.
	*/
	public function testPeriodPurchaseTesteCompleto() {
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		$recebidoPagamentoForma            = $this->vars['data']['pagamento']['forma'];
		$recebidoPagamentoValorCentavos    = $this->vars['data']['pagamento']['valor_centavos'];
		$recebidoPagamentoCodigo           = $this->vars['data']['pagamento']['codigo'];
		$recebidoPagamentoCpfCnpjPagamento = $this->vars['data']['pagamento']['cpf_cnpj_pagamento'];
		$recebidoPagamentoCpfCnpjCliente   = $this->vars['data']['pagamento']['cpf_cnpj_cliente'];
		$recebidoPagamentoRps              = $this->vars['data']['pagamento']['rps'];
		$recebidoPagamentoNsu              = $this->vars['data']['pagamento']['nsu'];

		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($recebidoPagamentoForma				, $this->data['forma_pagamento']	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoForma);
		$this->assertEquals($recebidoPagamentoValorCentavos		, $this->data['valor_centavos']		, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] 	. ' / Retornado: '. $recebidoPagamentoValorCentavos);
		$this->assertEquals($recebidoPagamentoCodigo			, $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoCodigo);
		$this->assertEquals($recebidoPagamentoCpfCnpjPagamento	, $this->data['cpf_cnpj_pagamento']	, 'O campo \'cpf_cnpj_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $recebidoPagamentoCpfCnpjPagamento);
		$this->assertEquals($recebidoPagamentoCpfCnpjCliente	, $this->data['cpf_cnpj_cliente']	, 'O campo \'cpf_cnpj_cliente\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_cliente'] 	. ' / Retornado: '. $recebidoPagamentoCpfCnpjCliente);
		$this->assertEquals($recebidoPagamentoRps				, $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] 				. ' / Retornado: '. $recebidoPagamentoRps);
		$this->assertEquals($recebidoPagamentoNsu				, $this->data['nsu']			 	, 'O campo \'nsu\' enviado é diferente do retornado. Enviado: '			 		. $this->data['nsu'] 				. ' / Retornado: '. $recebidoPagamentoNsu);

		$this->assertEquals($this->vars['data']['compra_periodos']['placa']				, $this->data['placa']			 ,   'O campo \'placa\' enviado é diferente do retornado. Enviado: '    		 . $this->data['placa'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['placa']);
		$this->assertEquals($this->vars['data']['compra_periodos']['qtde_periodos']		, $this->data['qtde_periodos']	 ,   'O campo \'qtde_periodos\' enviado é diferente do retornado. Enviado: '	 . $this->data['qtde_periodos'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['qtde_periodos']);
		$this->assertEquals($this->vars['data']['compra_periodos']['tipo_veiculo']		, $this->data['tipo_veiculo']	 ,   'O campo \'tipo_veiculo\' enviado é diferente do retornado. Enviado: '	 . $this->data['tipo_veiculo'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertEquals($this->vars['data']['compra_periodos']['vaga']				, $this->data['vaga']	 		 ,   'O campo \'vaga\' enviado é diferente do retornado. Enviado: '	 		 . $this->data['vaga'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['vaga']);

		// Faz uma busca no ticket e verifica se os campos 'servico_id_origem' e 'operador_id_origem' estão preenchidos corretamente
		$parkTicket = $this->Ticket->find('first', array('conditions' => array('placa' => $this->data['placa'])));
		// Valida se o ticket não é nulo
		$this->assertNotNull($parkTicket, 'Ticket não foi inserido corretamente no pagamento da compra de períodos.');
		// Valida se o ticket possui dados válidos
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_origem']		, 'Serviço de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_pagamento']	, 'Serviço de pagamento do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_origem']	, 'Operador de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_pagamento']	, 'Operador de pagamento do ticket não deve ser nulo.');

	}// End method 'testPeriodPurchaseTesteCompleto' 

	/**
	* Testa os saldos do cliente enviados pela API na compra de um período via conta pré
	*/
	public function testPeriodPurchaseSaldosClientePre() {
		// Concede limite para o cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 100);
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Armazena infos importantes para os testes posteriores
		$saldoAnterior = $this->dataGenerator->getSaldoPreUsuario($this->dataGenerator->clienteId);

		// Sobrescreve a forma de pagamento
		$this->data['forma_pagamento'] = 'PRE';
		$this->data['cpf_cnpj_pagamento'] = $this->cliente['Cliente']['cpf_cnpj'];
		$this->data['senha'] = $this->cliente['Cliente']['senha_site'];

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		$this->dataGenerator->clearPendente();

		// Recupera o saldo do usário após a compra
		$saldoAtual = $this->dataGenerator->getSaldoPreUsuario($this->dataGenerator->clienteId);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		$this->assertEquals($saldoAnterior, $this->vars['data']['cliente']['saldo_anterior'] / 100, 'Saldo anterior incorreto');
		$this->assertEquals($saldoAtual, $this->vars['data']['cliente']['saldo_atual'] / 100, 'Saldo anterior incorreto');

	}// End method 'testPeriodPurchaseTesteCompleto' 

	/**
	 * Testa a inclusão e retorno de um eticket na compra de período por token
	 */
	public function testPeriodPurchaseWithETicketToken() {
		// Atualiza a área para que consuma etickets
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('consumir_eticket', 1);

		// Gera etickets para o teste
		$this->dataGenerator->geraEtickets(10, true);

		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recupera o código do e-ticket
		$eTicketToken = $this->vars['data']['compra_periodos']['e_ticket'];

		// Testa se o e-ticket foi retornado
		$this->assertNotNull($eTicketToken, 'E-ticket não foi retornado.');

		// Por padrão, o lote é gerado para ser utilizado o hash como código do e-ticket
		// Busca um e-ticket com o token igual ao código retornado pelo WS
		$eTicket = $this->Eticket->findByToken($eTicketToken);

		// Testa se o código é realmente o token
		$this->assertTrue(!empty($eTicket), 'Valor retornado não é o token do e-ticket.');
	}// End method 'testPeriodPurchaseWithETicketToken'


	/**
	 * Testa a inclusão e retorno de um eticket na compra de período por sequencia
	 */
	public function testPeriodPurchaseWithETicketSequencia() {
		// Atualiza a área para que consuma etickets
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('consumir_eticket', 1);

		// Gera etickets para o teste
		$this->dataGenerator->geraEtickets(10, true);

		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		// Altera a configuração do lote para usar o número sequencial em lugar do hash
		$this->Eticket->Lote->id = $this->dataGenerator->loteId;
		$this->Eticket->Lote->saveField('usar_numeracao', 1);

		// Incrementa o NSU
		$this->data['nsu']++;

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recupera o código do e-ticket
		$eTicketSequencia = $this->vars['data']['compra_periodos']['e_ticket'];

		// Testa se o e-ticket foi retornado
		$this->assertNotNull($eTicketSequencia, 'E-ticket não foi retornado.');

		// Busca um e-ticket com o token igual ao código retornado pelo WS
		$eTicket = $this->Eticket->findBySequencia($eTicketSequencia);

		// Testa se o código é realmente o token
		$this->assertTrue(!empty($eTicket), 'Valor retornado não é o token do e-ticket.');
	}// End method 'testPeriodPurchaseWithETicketSequencia' 

	/**
	* Testa o acesso a API referente ao comando Compra de Período, esperando a exceção 'InternalServerError' 
	* e a mensagem de que a compra foi bloqueada pois excede o limite do horário de funcionamento da área
	*
	* Esse erro ocorre quando o tampo do ticket que esta tentando-se comprar excede o horario limite de operacao da area
	* acrescido do tempo da	primeira tarifa
	*/ 
	public function testPeriodPurchaseAreaLimitOperationExceeded(){

		// Cria a segunda tarifa do preco
		$this->ParkTarifa->clear();
		$this->ParkTarifa->save(array(
			'codigo' => 2,
			'preco_id' => $this->dataGenerator->precoId,
			'valor' => 2.00,
			'minutos' => 60,
			'vender_associado' => 1,
			'vender_posto' => 1,
			'vender_internet' => 1
		));		

		$horaAtual = $this->dataGenerator->getTime();

		// Configura o horario de operacao da area para iniciar exatamente no momento da execucao do teste e terminar 1
		// minuto depois. Dessa forma, a soma do o horario limite de operacao da area e do tempo da	primeira tarifa sera 
		// igual a 31 minutos
		$this->parkArea['Area']['id'] = $this->dataGenerator->areaId;
		$this->parkArea['Area']['uteis_inicio'] = $horaAtual;
		$this->parkArea['Area']['sabado_inicio'] = $horaAtual;
		$this->parkArea['Area']['domingo_inicio'] = $horaAtual;
		$this->parkArea['Area']['duracao_uteis'] = 1;
		$this->parkArea['Area']['duracao_sabado'] = 1;
		$this->parkArea['Area']['duracao_domingo'] = 1;
		$this->dataGenerator->saveArea($this->parkArea);

		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();		

		// Tenta comprar um ticket cujo periodo e igual a 60 minutos
		$this->data['qtde_periodos']    = 2;
		$this->data['valor_centavos']	= $this->getValueByAmoutPurchasePeriod($this->data['qtde_periodos'], $this->dataGenerator->precoId);
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Compra bloqueada: excede o limite do horário de funcionamento da área'
			);
	}// End method 'testPeriodPurchaseCheckChangePrice'

	/**
	* Testa acesso a API do código de Quitação de Irregularidades, esperando exceção de "BadRequest" e a mensagem de parâmetro 'placa' não foi recebido
	*/
	public function testQuitacaoIrregularidadesSemPlaca() {
		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();
		// Remove campo do array de envio
		unset($this->data['placa']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Placa não recebida'
			);
	}// End method 'testQuitacaoIrregularidadesSemPlaca'


	/**
	* Testa acesso a API do código de Quitação de Irregularidades, esperando exceção de "InternalError" e a mensagem de que não foi encontrado um serviço aberto
	*/
	public function testQuitacaoIrregularidadesSemServicoAberto() {

		// Encerra o serviço aberto
		$newParkServico = array('Servico' => array(
			'id'				=> $this->dataGenerator->servicoId,
			'data_fechamento' 	=> $this->dataGenerator->getDateTime()
			));
		// Atualiza o registro do serviço atual
		$this->Servico->save($newParkServico);
		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Comando não processado: o caixa foi encerrado manualmente. Por favor verifique com a sua operação.'
			);
	}// End method 'testQuitacaoIrregularidadesSemServicoAberto'

	/**
	* Testa acesso a API do código de Quitação de Irregularidades
	*/
	public function testQuitacaoIrregularidadesCompleto() {
		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variavel que recebe o número de irregularidades pendentes
		$qtdeTicketsIrregularesPendentes = 0;
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
		// Variavel que vai receber os números dos tickets
		$ticketsIrregularidade = '';
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
				'equipamento_id_pagamento' 	=> NULL,
				'entidade_id_pagamento'		=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;

			$numTickets = $this->Ticket->find('all', array('conditions' => array('placa' => $this->data['placa'], 'situacao' => 'AGUARDANDO')));
			$ticketsIrregularidade .= $numTickets[$i]['Ticket']['id'] . ";";
		}

		// Popula código de pagamento para o código da Quitação de Irregularidades : 2 
		$this->data['valor_centavos'] 		= $valorTotalTickets * 100; // Multiplica para ter o valor em centavos
		$this->data['produto_id'] 			= $this->dataGenerator->produtoId;
		$this->data['ticket_id'] 			= $ticketsIrregularidade; 
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Adiciona campo a quantidade de tickets Irregulares que é o retorno esperado
		$this->data['qtde_tickets_irregulares'] = $qtdeTicketsIrregulares;
		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($this->vars['data']['pagamento']['forma']	, $this->data['forma_pagamento'] 	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->vars['data']['pagamento']['valor_centavos']	, $this->data['valor_centavos']	 	, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] . ' / Retornado: '. $this->vars['data']['pagamento']['valor_centavos']);
		$this->assertEquals($this->vars['data']['pagamento']['codigo']	, $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['codigo']);
		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->data['cpf_cnpj_pagamento'] , 'O campo \'cpf_cnpj\' enviado é diferente do retornado. Enviado: '		 	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['cpf_cnpj_pagamento']);
		$this->assertEquals($this->vars['data']['pagamento']['rps']				, $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] . ' / Retornado: '. $this->vars['data']['pagamento']['rps']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['placa']				, $this->data['placa']	 			, 'O campo \'placa\' enviado é diferente do retornado. Enviado: '    			. $this->data['placa'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['placa']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['produto_id']		, $this->data['produto_id']	 		, 'O campo \'produto_id\' enviado é diferente do retornado. Enviado: '	 		. $this->data['produto_id'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['produto_id']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['park_cobranca_id']	, $this->dataGenerator->cobrancaId, 'O campo \'park_cobranca_id\' enviado é diferente do retornado. Enviado: '	. $this->dataGenerator->cobrancaId . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['park_cobranca_id']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pagas']		, $qtdeTicketsIrregulares	 	, 'O campo \'qtd_irregularidades_pagas\' enviado é diferente do retornado. Enviado: '	. $qtdeTicketsIrregulares . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pagas']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pendentes']		, $qtdeTicketsIrregularesPendentes	 	, 'O campo \'qtd_irregularidades_pendentes\' enviado é diferente do retornado. Enviado: '	. $qtdeTicketsIrregularesPendentes . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pendentes']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['id_tickets_pagos']		, $ticketsIrregularidade	 	, 'O campo \'id_tickets_pagos\' enviado é diferente do retornado. Enviado: '	. $ticketsIrregularidade . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['id_tickets_pagos']);
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['nsu'], 'Campo nsu vazio!');
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['pago_em'], 'Campo pago_em vazio!');



		// Busca ticket para validar se o campo da autorização_id no ticket foi preenchido corretamente.
		$this->Ticket->recursive = -1;
		$listTickets = $this->Ticket->find('all');

		// Valida se encontrou alguma autorização
		$this->assertNotNull($listTickets);

		// Variável que recebe os valores do tickets concatenados
		$totValor = 0;
		// Variável que recebe o id da autorização
		$autorizacaoId = NULL;

		// Varre lista de tickts comparando se os valores somandos do ticket equivalem ao valor da autorização
		foreach($listTickets as $parkTicket){
			// Concatena valor
			$totValor += $parkTicket['Ticket']['valor'];
			// Guarda a autorização id
			if(null == $autorizacaoId){
				$autorizacaoId = $parkTicket['Ticket']['autorizacao_id'];
			}
		}

		// Busca a autorização id dos tickets
		$autorizacao = $this->Autorizacao->find('first');
		// Valida se encontrou alguma autorização
		$this->assertNotNull($autorizacao);
		// Valida o valor dos tickets e da autorização
		$this->assertEquals($autorizacao['Autorizacao']['valor'], $totValor);
		// Valida se a placa da autorização com o dos tickets
		$this->assertEquals($this->data['placa'], $autorizacao['Autorizacao']['compl']);

	}// End method 'testQuitacaoIrregularidadesCompleto'

	/**
	* Testa acesso a API do código de Quitação de Irregularidades Individual
	*/
	public function testQuitacaoIrregularidadesIndividual() {
		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variavel que recebe o número de irregularidades que será pago
		$qtdeTicketsIrregularesPagas = 0;
		// Variavel que recebe o número de irregularidades pendentes
		$qtdeTicketsIrregularesPendentes = 0;
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
		// Variavel que vai receber os números dos tickets
		$ticketsIrregularidade = '';
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
				'equipamento_id_pagamento' 	=> NULL,
				'entidade_id_pagamento'		=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets = $valorRandom;

			$numTickets = $this->Ticket->find('all', array('conditions' => array('placa' => $this->data['placa'], 'situacao' => 'AGUARDANDO')));
			$ticketsIrregularidade = $numTickets[$i]['Ticket']['id'] . ";";
		}

		// Popula código de pagamento para o código da Quitação de Irregularidades : 2 
		$this->data['valor_centavos'] 		= $valorTotalTickets * 100; // Multiplica para ter o valor em centavos
		$this->data['produto_id'] 			= $this->dataGenerator->produtoId;
		$this->data['ticket_id'] 			= $ticketsIrregularidade; 
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		$qtdeTicketsIrregularesPendentes = $this->Ticket->find('count', array('conditions' => array('placa' => $this->data['placa'], 'situacao' => 'AGUARDANDO')));
		$qtdeTicketsIrregularesPagas = $this->Ticket->find('count', array('conditions' => array('placa' => $this->data['placa'], 'situacao' => 'PAGO')));
		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($this->vars['data']['pagamento']['forma']	, $this->data['forma_pagamento'] 	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['forma']);
		$this->assertEquals($this->vars['data']['pagamento']['valor_centavos']	, $this->data['valor_centavos']	 	, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] . ' / Retornado: '. $this->vars['data']['pagamento']['valor_centavos']);
		$this->assertEquals($this->vars['data']['pagamento']['codigo']	, $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['codigo']);
		$this->assertEquals($this->vars['data']['pagamento']['cpf_cnpj_pagamento'], $this->data['cpf_cnpj_pagamento'] , 'O campo \'cpf_cnpj\' enviado é diferente do retornado. Enviado: '		 	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $this->vars['data']['pagamento']['cpf_cnpj_pagamento']);
		$this->assertEquals($this->vars['data']['pagamento']['rps']				, $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] . ' / Retornado: '. $this->vars['data']['pagamento']['rps']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['placa']				, $this->data['placa']	 			, 'O campo \'placa\' enviado é diferente do retornado. Enviado: '    			. $this->data['placa'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['placa']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['produto_id']		, $this->data['produto_id']	 		, 'O campo \'produto_id\' enviado é diferente do retornado. Enviado: '	 		. $this->data['produto_id'] . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['produto_id']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['park_cobranca_id']	, $this->dataGenerator->cobrancaId, 'O campo \'park_cobranca_id\' enviado é diferente do retornado. Enviado: '	. $this->dataGenerator->cobrancaId . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['park_cobranca_id']);

		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pagas']		, $qtdeTicketsIrregularesPagas	 	, 'O campo \'qtd_irregularidades_pagas\' enviado é diferente do retornado. Enviado: '	. $qtdeTicketsIrregularesPagas . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pagas']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pendentes']		, $qtdeTicketsIrregularesPendentes	 	, 'O campo \'qtd_irregularidades_pendentes\' enviado é diferente do retornado. Enviado: '	. $qtdeTicketsIrregularesPendentes . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['qtd_irregularidades_pendentes']);
		$this->assertEquals($this->vars['data']['quitacao_irregularidades']['id_tickets_pagos']		, $ticketsIrregularidade	 	, 'O campo \'id_tickets_pagos\' enviado é diferente do retornado. Enviado: '	. $ticketsIrregularidade . ' / Retornado: '. $this->vars['data']['quitacao_irregularidades']['id_tickets_pagos']);
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['nsu'], 'Campo nsu vazio!');
		$this->assertNotEmpty($this->vars['data']['quitacao_irregularidades']['pago_em'], 'Campo pago_em vazio!');
	}// End method 'testQuitacaoIrregularidadesIndividual'

	/**
	 * Testa se uma placa com irregularidades do tipo notificação em uma 
	 */
	public function testQuitacaoIrregularidadesPlacaComNotificacaoEAviso(){

		// Popula os campos padrões para a quitação de irregularidade
		$this->getIrregularitiesFields();

		$placa = $this->data['placa'];
		$valorIrregularidade = 3.33;

		// Cria um preço com notificação 
		$precoNotificacao = $this->dataGenerator->getPreco();
		$precoNotificacao['Preco']['nome'] = 'Preco Not.';
		$precoNotificacao['Preco']['irregularidade'] = 'NOTIFICACAO';
		$precoNotificacao['Preco']['valor_irregularidade'] = $valorIrregularidade;
		$this->dataGenerator->savePreco($precoNotificacao);
		$precoSemTicketId = $this->dataGenerator->precoId;

		// 	Cria um preço com AVISO
		$precoAviso = $this->dataGenerator->getPreco();
		$precoAviso['Preco']['nome'] = 'Preco Aviso';
		$precoAviso['Preco']['irregularidade'] = 'AVISO';
		$precoAviso['Preco']['valor_irregularidade'] = 0.00;
		$this->dataGenerator->savePreco($precoAviso);
		$precoForaVagaId = $this->dataGenerator->precoId;

		// Busca cobranca para atualizar os preços das irregularidades
		$cobranca = $this->Cobranca->findById($this->dataGenerator->cobrancaId);
		$cobranca['Cobranca']['nome'] = 'Cob. Aviso';
		$cobranca['Cobranca']['preco_id_irregularidade_sem_ticket'] = $precoSemTicketId;
		$cobranca['Cobranca']['preco_id_irregularidade_fora_vaga'] = $precoForaVagaId;

		$this->dataGenerator->saveCobranca($cobranca);

		// Emite uma NOTIFICACAO
		$ticketNotificacao = $this->dataGenerator->emiteIrregularidade($placa, 0, 'SEM_TICKET');

		// Emite um AVISO
		$ticketAviso = $this->dataGenerator->emiteIrregularidade($placa, 0, 'FORA_DA_VAGA');

		// Popula código de pagamento para o código da Quitação de Irregularidades
		$this->data['valor_centavos'] 		= $valorIrregularidade * 100;
		$this->data['produto_id'] 			= $this->dataGenerator->produtoId;		

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Busca os ticket
		$newTicketNotificacao = $this->Ticket->findById($ticketNotificacao[0]['id']);
		$newTicketAviso = $this->Ticket->findById($ticketAviso[0]['id']);

		// Valida se a notificação foi e paga e se o aviso permaneceu aguardando
		$this->assertEquals('PAGO', $newTicketNotificacao['Ticket']['situacao']);
		$this->assertEquals('AGUARDANDO', $newTicketAviso['Ticket']['situacao']);
	}// End Method 'testQuitacaoIrregularidadesPlacaComNotificacaoEAviso'

	/**
	 * Testa a compra de período com cartão.
	 */
	public function testCompraPeriodoCartao() {
		// Popula os campos padrões para a compra de período
		$this->getPeriodPurchaseFields();

		$placa = $this->data['placa'];
		$nsu = intval($this->data['nsu']);
		$this->data['forma_pagamento'] = 'CARTAO';
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		$recebidoPagamentoForma            = $this->vars['data']['pagamento']['forma'];
		$recebidoPagamentoValorCentavos    = $this->vars['data']['pagamento']['valor_centavos'];
		$recebidoPagamentoCodigo           = $this->vars['data']['pagamento']['codigo'];
		$recebidoPagamentoCpfCnpjPagamento = $this->vars['data']['pagamento']['cpf_cnpj_pagamento'];
		$recebidoPagamentoCpfCnpjCliente   = $this->vars['data']['pagamento']['cpf_cnpj_cliente'];
		$recebidoPagamentoRps              = $this->vars['data']['pagamento']['rps'];
		$recebidoPagamentoNsu              = $this->vars['data']['pagamento']['nsu'];

		// Valida se os dados enviados são os mesmos dados retornados
		$this->assertEquals($recebidoPagamentoForma				, $this->data['forma_pagamento']	, 'O campo \'forma_pagamento\' enviado é diferente do retornado. Enviado: ' 	. $this->data['forma_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoForma);
		$this->assertEquals($recebidoPagamentoValorCentavos		, $this->data['valor_centavos']		, 'O campo \'valor_centavos\' enviado é diferente do retornado. Enviado: '  	. $this->data['valor_centavos'] 	. ' / Retornado: '. $recebidoPagamentoValorCentavos);
		$this->assertEquals($recebidoPagamentoCodigo			, $this->data['codigo_pagamento']	, 'O campo \'codigo_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['codigo_pagamento'] 	. ' / Retornado: '. $recebidoPagamentoCodigo);
		$this->assertEquals($recebidoPagamentoCpfCnpjPagamento	, $this->data['cpf_cnpj_pagamento']	, 'O campo \'cpf_cnpj_pagamento\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_pagamento'] . ' / Retornado: '. $recebidoPagamentoCpfCnpjPagamento);
		$this->assertEquals($recebidoPagamentoCpfCnpjCliente	, $this->data['cpf_cnpj_cliente']	, 'O campo \'cpf_cnpj_cliente\' enviado é diferente do retornado. Enviado: '	. $this->data['cpf_cnpj_cliente'] 	. ' / Retornado: '. $recebidoPagamentoCpfCnpjCliente);
		$this->assertEquals($recebidoPagamentoRps				, $this->data['rps']			 	, 'O campo \'rps\' enviado é diferente do retornado. Enviado: '			 		. $this->data['rps'] 				. ' / Retornado: '. $recebidoPagamentoRps);
		$this->assertEquals($recebidoPagamentoNsu				, $this->data['nsu']			 	, 'O campo \'nsu\' enviado é diferente do retornado. Enviado: '			 		. $this->data['nsu'] 				. ' / Retornado: '. $recebidoPagamentoNsu);
		
		$this->assertEquals($this->vars['data']['compra_periodos']['placa']				, $this->data['placa']			 ,   'O campo \'placa\' enviado é diferente do retornado. Enviado: '    		 . $this->data['placa'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['placa']);
		$this->assertEquals($this->vars['data']['compra_periodos']['qtde_periodos']		, $this->data['qtde_periodos']	 ,   'O campo \'qtde_periodos\' enviado é diferente do retornado. Enviado: '	 . $this->data['qtde_periodos'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['qtde_periodos']);
		$this->assertEquals($this->vars['data']['compra_periodos']['tipo_veiculo']		, $this->data['tipo_veiculo']	 ,   'O campo \'tipo_veiculo\' enviado é diferente do retornado. Enviado: '	 . $this->data['tipo_veiculo'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['tipo_veiculo']);
		$this->assertEquals($this->vars['data']['compra_periodos']['vaga']				, $this->data['vaga']	 		 ,   'O campo \'vaga\' enviado é diferente do retornado. Enviado: '	 		 . $this->data['vaga'] . ' / Retornado: '. $this->vars['data']['compra_periodos']['vaga']);
		
		// Faz uma busca no ticket e verifica se os campos 'servico_id_origem' e 'operador_id_origem' estão preenchidos corretamente
		$parkTicket = $this->Ticket->find('first', array('conditions' => array('placa' => $this->data['placa'])));
		// Valida se o ticket não é nulo
		$this->assertNotNull($parkTicket, 'Ticket não foi inserido corretamente no pagamento da compra de períodos.');
		// Valida se o ticket possui dados válidos
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_origem']		, 'Serviço de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['servico_id_pagamento']	, 'Serviço de pagamento do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_origem']	, 'Operador de origem do ticket não deve ser nulo.');
		$this->assertNotNUll($parkTicket['Ticket']['operador_id_pagamento']	, 'Operador de pagamento do ticket não deve ser nulo.');

		// Busca registro antigo do veículo
		$oldParkHistorico = $this->Historico->find('first', array('conditions' => array('placa' => $this->data['placa'], 'area_id' => $this->dataGenerator->areaId, 'situacao' => 'LANCADO')));
		// Valida se encontrou registro na park_historico
		$this->assertNotNull($oldParkHistorico, 'ParkHistorico após compra de ticket não encontrado');

		// Guarda id da área anterior
		$oldAreaId = $this->dataGenerator->areaId;

		// Salva novos registros necessários para o segundo serviço
		$newParkAreaSave = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($newParkAreaSave);
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveAreaPonto();
		
		// Busca registro da área ponto para buscar o número da vaga inserido
		$parkAreaPonto = $this->AreaPonto->findById($this->dataGenerator->areapontoId);
		// Validação se o registro da área ponto não é nulo
		$this->assertNotNull($parkAreaPonto, 'Registro da Área Ponto é NULL!');
		// Cria nova área
		$newParkArea = $this->Area->find('first', array('conditions' => array('Area.nome' => $newParkAreaSave['Area']['nome'])));
		$this->assertNotNull($newParkArea);

		// Setá os valores para os campos padrões
		$this->data = null;
		$this->data = $this->getApiDefaultParams();

		// salva id do equipamento antigo 
		$oldEquipamentoId = $this->dataGenerator->equipamentoId;

		// Seta dados para o novo equipamento
		$newSerial = '1111111111';
		$newEquipamentSave = $this->dataGenerator->getEquipamento();
		$newEquipamentSave['Equipamento']['no_serie'] = $newSerial;
		$newEquipamentSave['Equipamento']['api_key'] = md5(API_KEY.$this->data['nsu'].$newSerial);
		$newEquipamentSave['Equipamento']['tipo'] = 'ANDROID';
		$newEquipamentSave['Equipamento']['modelo'] = 'ANDROID';
		$this->dataGenerator->saveEquipamento($newEquipamentSave);
		// Busca novo equipamento, afim de buscar seu id
		$newEquipament = $this->Equipamento->findByNoSerie($newSerial);
		// Valida se o mesmo foi salvo com sucesso
		$this->assertNotNull($newEquipament);
		// Salva novo operador
		$newParkOperadorSave = $this->dataGenerator->getOperador();
		$this->dataGenerator->saveOperador($newParkOperadorSave);
		// Valida novo operador
		$newParkOperador = $this->Operador->find('first', array('order' => array('id' => 'desc')));
		$this->assertNotNull($newParkOperador);

		// Guarda id do serviço antigo
		$oldServicoId = $this->dataGenerator->servicoId;

		// Salva novo serviço
		$newParkServico = $this->dataGenerator->getServico();
		$newParkServico['Servico']['equipamento_id'] = $newEquipament['Equipamento']['id'];
		$newParkServico['Servico']['operador_id'] = $newParkOperador['Operador']['id'];
		$newParkServico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveServico($newParkServico);

		// Salva uma nova tarifa 
		$this->ParkTarifa->clear();
		$newParkTarifa = $this->dataGenerator->getParkTarifa();
		$newParkTarifa['ParkTarifa']['codigo'] = 2;
		$newParkTarifa['ParkTarifa']['preco_id'] = $this->dataGenerator->precoId;
		$newParkTarifa['ParkTarifa']['valor'] = 2.00;
		$newParkTarifa['ParkTarifa']['minutos'] = 60;
		$newParkTarifa['ParkTarifa']['vender_associado'] = 1;
		$newParkTarifa['ParkTarifa']['vender_posto'] = 1;
		$newParkTarifa['ParkTarifa']['vender_internet'] = 1;
		$this->ParkTarifa->save($newParkTarifa);

		// Calcula valor em reais a serem comprados
		$valorTicket = ($this->getValueByAmoutPurchasePeriod($newParkTarifa['ParkTarifa']['codigo'] , $this->dataGenerator->precoId) / 100);

		// Compra ticket
		$this->dataGenerator->venderTicketEstacionamentoDinheiro($valorTicket, $placa, $parkAreaPonto['AreaPonto']['codigo'], $newParkTarifa['ParkTarifa']['codigo']);

		// Busca informações da park_historico
		$listHistorico = $this->Historico->find('all', array(
			'conditions' => array(
				'placa' => $placa),
			'order' => array('Historico.id')
		));

		// Valida quantidade de registros encontrados.
		$this->assertEquals(2, count($listHistorico) , 'Quantidade de registros da park_historico está errado');
		// Separa historicos
		$parkHistorico1 = $listHistorico[0]['Historico'];
		$parkHistorico2 = $listHistorico[1]['Historico'];

		// Valida primeiro registro
		$this->assertNotNull($parkHistorico1['removido_em'], 'Registro antigo não está removido');
		$this->assertEquals('REMOVIDO', $parkHistorico1['situacao'],'Registro antigo não está removido');
		$this->assertEquals($oldEquipamentoId, $parkHistorico1['equipamento_id'], 'Equipamento do registro antigo não é o esperado.');
		$this->assertEquals($oldAreaId, $parkHistorico1['area_id'], 'Area esperada diferente da recebida');

		// Valida segundo registro
		$this->assertNull($parkHistorico2['removido_em'], 'Registro novo está removido');
		$this->assertEquals('LANCADO', $parkHistorico2['situacao'],'Registro novo não está removido');
		$this->assertEquals($newEquipament['Equipamento']['id'], $parkHistorico2['equipamento_id'], 'Equipamento do registro novo não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkHistorico2['area_id'], 'Area esperada diferente da recebida');


		// BUsca informações dos tickets criados
		$listTickets = $this->Ticket->find('all', array(
			'conditions'=> array('placa' => $placa),
			'order' => array('Ticket.id')
			));

		// Valida existência de tickets
		$this->assertNotNUll($listTickets);

		// Valida quantidade de tickets criados
		$this->assertEquals(3, count($listTickets), 'Quantidade de tickets esperado diferente do recebido');
		// Extrai os tickets
		$parkTicket1 = $listTickets[0]['Ticket'];
		$parkTicket2 = $listTickets[1]['Ticket'];
		$parkTicket3 = $listTickets[2]['Ticket'];


		// Valida placa
		$this->assertEquals($placa, $parkTicket1['placa'], 'Placa do ticket1 diferente do esperado');
		$this->assertEquals($placa, $parkTicket2['placa'], 'Placa do ticket2 diferente do esperado');
		$this->assertEquals($placa, $parkTicket3['placa'], 'Placa do ticket3 diferente do esperado');

		// Valida valor
		$this->assertEquals('0.00', $parkTicket1['valor'], 'Valor do ticket1 diferente do esperado');
		$this->assertEquals('1.00', $parkTicket2['valor'], 'Valor do ticket2 diferente do esperado');
		$this->assertEquals('2.00', $parkTicket3['valor'], 'Valor do ticket3 diferente do esperado');

		// Valida situação
		$this->assertEquals('PAGO', $parkTicket1['situacao'], 'Situação do ticket1 diferente de PAGO');
		$this->assertEquals('PAGO', $parkTicket2['situacao'], 'Situação do ticket2 diferente de PAGO');
		$this->assertEquals('PAGO', $parkTicket3['situacao'], 'Situação do ticket3 diferente de PAGO');

		// Valida tipo
		$this->assertEquals('UTILIZACAO', $parkTicket1['tipo'], 'Tipo do ticket1 diferente de UTILIZACAO');
		$this->assertEquals('UTILIZACAO', $parkTicket2['tipo'], 'Tipo do ticket2 diferente de UTILIZACAO');
		$this->assertEquals('UTILIZACAO', $parkTicket3['tipo'], 'Tipo do ticket3 diferente de UTILIZACAO');

		// Valida área id
		$this->assertEquals($oldAreaId, $parkTicket1['area_id'], 'AreaId do ticket1 não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkTicket2['area_id'], 'AreaId do ticket2 não é o esperado');
		$this->assertEquals($this->dataGenerator->areaId, $parkTicket3['area_id'], 'AreaId do ticket3 não é o esperado');

		// Valida serviçoId
		$this->assertEquals($oldServicoId, $parkTicket1['servico_id_origem'], 'ServicoId do ticket1 diferente do esperado.');
		$this->assertEquals($oldServicoId, $parkTicket2['servico_id_origem'], 'ServicoId do ticket2 diferente do esperado.');
		$this->assertEquals($this->dataGenerator->servicoId, $parkTicket3['servico_id_origem'], 'ServicoId do ticket3 diferente do esperado.');

		// Validação da autorização
		$this->assertNotNull($parkTicket1['autorizacao_id'], 'Autorização do ticket1 está null');
		$this->assertNotNull($parkTicket2['autorizacao_id'], 'Autorização do ticket2 não está null');
		$this->assertNotNull($parkTicket3['autorizacao_id'], 'Autorização do ticket3 não está null');
		$this->assertNotEquals($parkTicket1['autorizacao_id'], $parkTicket3['autorizacao_id'], 'Id das autorizações dos tickets 1 e 3 não são diferentes.');

		// Validação do historico Id
		$this->assertEquals($parkHistorico1['id'], $parkTicket1['historico_id'], 'HistoricoId do ticket1, diferente do esperado');
		$this->assertEquals($parkHistorico2['id'], $parkTicket2['historico_id'], 'HistoricoId do ticket2, diferente do esperado');
		$this->assertEquals($parkHistorico2['id'], $parkTicket3['historico_id'], 'HistoricoId do ticket3, diferente do esperado');

		// Validação do valor original
		$this->assertEquals('1.00', $parkTicket1['valor_original'], 'Valor Original do ticket1 diferente do esperado');
		$this->assertEquals('1.00', $parkTicket2['valor_original'], 'Valor Original do ticket2 diferente do esperado');
		$this->assertEquals('2.00', $parkTicket3['valor_original'], 'Valor Original do ticket3 diferente do esperado');

		// Validação do id da cobrança
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket1['cobranca_id'], 'CobrancaId do ticket1, diferente do esperado');
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket2['cobranca_id'], 'CobrancaId do ticket2, diferente do esperado');
		$this->assertEquals($this->dataGenerator->cobrancaId, $parkTicket3['cobranca_id'], 'CobrancaId do ticket3, diferente do esperado');
	}
	

	// /**
	//  * Testa quitar várias irregularidades em área diferente.
	//  */
	// public function testQuitacaoIrregularidadesAreasDiferentes_PagamentoDinheiro() {

	// 	// Popula os campos padrões para a quitação de irregularidade
	// 	$this->getIrregularitiesFields();
	// 	// Cria uma quantidade de tickets a serem gerados randômicamente.
	// 	$qtdeTicketsIrregulares = rand(5,10);
	// 	// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
	// 	$valorTotalTickets = 0;

	// 	// Gera tickets irregulares de acordo com a quantidade criada randomicamente
	// 	for($i = 0; $i < $qtdeTicketsIrregulares; $i++) {

	// 		// Salva produto diferente para cada cobranca
	// 		$this->dataGenerator->saveProduto();

	// 		// Salva preco diferente para cada cobranca
	// 		$this->dataGenerator->savePreco();

	// 		// Salva tarifa
	// 		$this->dataGenerator->saveParkTarifa();

	// 		// Salva cobranca diferente para cada área
	// 		$this->dataGenerator->saveCobranca(array(
	// 			'Cobranca' => array(
	// 				'preco_id' => $this->dataGenerator->precoId, 
	// 				'produto_id' => $this->dataGenerator->produtoId)));

	// 		// Salva uma área diferente para cada ticket
	// 		$this->dataGenerator->saveArea(array('Area'=> array('cobranca_id' => $this->dataGenerator->cobrancaId)));

	// 		// Cria um valor randômicamente para o ticket
	// 		$valorRandom = rand(1,99999) / 100;
	// 		// Insere o ticket com os dados gerados
	// 		$this->dataGenerator->saveTicket(array('Ticket' => array(
	// 			'placa'                      => $this->data['placa'],
	// 			'situacao'                   => 'AGUARDANDO',
	// 			'valor'                      => $valorRandom,
	// 			'tipo'                       =>'IRREGULARIDADE',
	// 			'valor_original'             => $valorRandom,
	// 			'equipamento_id_pagamento'   => NULL,
	// 			'entidade_id_pagamento'      => NULL,
	// 			'servico_id_pagamento'       => NULL,
	// 			'operador_id_pagamento'      => NULL,
	// 			'numero_autuacao'            => 0,
	// 			'notificacao_transmitida_em' => NULL,
	// 			'entidade_id_origem'         => ADMIN_PARKING_ID,
	// 			'entidade_id_pagamento'      => NULL,
	// 			'area_id'                    => $this->dataGenerator->areaId
	// 		)));
	// 		// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
	// 		$valorTotalTickets += $valorRandom;
	// 	}

	// 	$this->dataGenerator->saveEquipamento();

	// 	$firstParkServico = $this->dataGenerator->servicoId;

	// 	$this->dataGenerator->saveServico();

	// 	// Popula código de pagamento para o código da Quitação de Irregularidades : 2
	// 	$this->data['valor_centavos'] 		= $valorTotalTickets * 100; // Multiplica para ter o valor em centavos

	// 	// Acessa o link da API
	// 	$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

	// 	// Valida se houve retorno da classe testada
	// 	$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

	// 	// TODO: Validar dados de retorno

	// 	// Busca na base os tickets verificando se os mesmos estão com situação paga e se foi gerado uma autorização para os mesmos
	// 	$listParkTickets = $this->Ticket->find('all');

	// 	// Valida se encontrou tickets
	// 	$this->assertNotEmpty($listParkTickets);

	// 	// Varre lista de tickets
	// 	foreach ($listParkTickets as $key => $ticket) {

	// 		$this->assertEquals('PAGO', $ticket['Ticket']['situacao']);
	// 		$this->assertNotNull($ticket['Ticket']['pago_em']);
	// 		$this->assertNotNull($ticket['Ticket']['equipamento_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['operador_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['servico_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['entidade_id_pagamento']);
	// 	}

	// 	// Busca autorização do pagamento dos tickets
	// 	$autorizacao = $this->Autorizacao->find('all');

	// 	// Valida se encontrou autorização
	// 	$this->assertNotNull($autorizacao);

	// 	// Valida quantidade de autorizações. Deverá trazer apenas um
	// 	$this->assertEquals(count($autorizacao), 1);

	// 	$autorizacao = $autorizacao[0];	

	// 	// Valida valor do pagamento
	// 	$this->assertEquals($valorTotalTickets, $autorizacao['Autorizacao']['valor_original']);
	// 	$this->assertEquals('DINHEIRO', $autorizacao['Autorizacao']['pagamento']);
	// 	$this->assertEquals('CONSUMO', $autorizacao['Autorizacao']['tipo']);
	// 	$this->assertEquals('APROVADO', $autorizacao['Autorizacao']['situacao']);
	// 	$this->assertEquals(ADMIN_PARKING_ID, $autorizacao['Autorizacao']['administrador_id']);

	// 	// Validação da park_autorizacao
	// 	$parkAutorizacao = $this->ParkAutorizacao->find('all');

	// 	// Valida se encontrou park_autorização
	// 	$this->assertNotNull($parkAutorizacao);

	// 	// Valida quantidade de park autorizações. Deverá trazer apenas um
	// 	$this->assertEquals(count($parkAutorizacao), 1);

	// 	$parkAutorizacao = $parkAutorizacao[0];

	// 	// Validações do registro da park_autorizacao
	// 	$this->assertEquals($autorizacao['Autorizacao']['id'], $parkAutorizacao['ParkAutorizacao']['autorizacao_id']);
	// 	$this->assertEquals($firstParkServico, $parkAutorizacao['ParkAutorizacao']['servico_id']);
	// 	$this->assertEquals($this->data['placa'], $parkAutorizacao['ParkAutorizacao']['placa']);
	// 	$this->assertEquals('IRREGULARIDADE', $parkAutorizacao['ParkAutorizacao']['tipo']);
	// 	$this->assertEquals(1, $parkAutorizacao['ParkAutorizacao']['atualizar']);
	// 	$this->assertEquals(ADMIN_PARKING_ID, $parkAutorizacao['ParkAutorizacao']['administrador_id']);
	// 	$this->assertEquals($valorTotalTickets, $parkAutorizacao['ParkAutorizacao']['valor']);
	// 	$this->assertEquals('DINHEIRO', $parkAutorizacao['ParkAutorizacao']['forma_pagamento']);
	// }// End Method 'testQuitacaoIrregularidadesAreasDiferentes_PagamentoDinheiro'

	// /**
	//  * Testa quitar várias irregularidades em área diferente.
	//  */
	// public function testQuitacaoIrregularidadesAreasDiferentes_PagamentoCpfCnpj() {

	// 	// Busca dados do cliente
	// 	$cliente = $this->Cliente->findById($this->dataGenerator->clienteId);

	// 	// Popula os campos padrões para a quitação de irregularidade
	// 	$this->getIrregularitiesFields();
	// 	// Cria uma quantidade de tickets a serem gerados randômicamente.
	// 	$qtdeTicketsIrregulares = rand(5,10);
	// 	// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
	// 	$valorTotalTickets = 0;

	// 	// Gera tickets irregulares de acordo com a quantidade criada randomicamente
	// 	for($i = 0; $i < $qtdeTicketsIrregulares; $i++) {

	// 		// Salva produto diferente para cada cobranca
	// 		$this->dataGenerator->saveProduto();

	// 		// Salva preco diferente para cada cobranca
	// 		$this->dataGenerator->savePreco();

	// 		// Salva tarifa
	// 		$this->dataGenerator->saveParkTarifa();

	// 		// Salva cobranca diferente para cada área
	// 		$this->dataGenerator->saveCobranca(array(
	// 			'Cobranca' => array(
	// 				'preco_id' => $this->dataGenerator->precoId, 
	// 				'produto_id' => $this->dataGenerator->produtoId)));

	// 		// Salva uma área diferente para cada ticket
	// 		$this->dataGenerator->saveArea(array('Area'=> array('cobranca_id' => $this->dataGenerator->cobrancaId)));

	// 		// Cria um valor randômicamente para o ticket
	// 		$valorRandom = rand(1,99999) / 100;
	// 		// Insere o ticket com os dados gerados
	// 		$this->dataGenerator->saveTicket(array('Ticket' => array(
	// 			'placa'                      => $this->data['placa'],
	// 			'situacao'                   => 'AGUARDANDO',
	// 			'valor'                      => $valorRandom,
	// 			'tipo'                       =>'IRREGULARIDADE',
	// 			'valor_original'             => $valorRandom,
	// 			'equipamento_id_pagamento'   => NULL,
	// 			'entidade_id_pagamento'      => NULL,
	// 			'servico_id_pagamento'       => NULL,
	// 			'operador_id_pagamento'      => NULL,
	// 			'numero_autuacao'            => 0,
	// 			'notificacao_transmitida_em' => NULL,
	// 			'entidade_id_origem'         => ADMIN_PARKING_ID,
	// 			'entidade_id_pagamento'      => NULL,
	// 			'area_id'                    => $this->dataGenerator->areaId
	// 		)));
	// 		// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
	// 		$valorTotalTickets += $valorRandom;
	// 	}

	// 	$this->dataGenerator->saveEquipamento();

	// 	$firstParkServico = $this->dataGenerator->servicoId;

	// 	$this->dataGenerator->saveServico();

	// 	// Popula código de pagamento para o código da Quitação de Irregularidades :
	// 	$this->data['valor_centavos'] 		= $valorTotalTickets * 100; // Multiplica para ter o valor em centavos
	// 	$this->data['cpf_cnpj_pagamento'] 	= $cliente['Cliente']['cpf_cnpj'];
	// 	$this->data['senha']              	= $cliente['Cliente']['senha_site'];
	// 	$this->data['forma_pagamento'] 		= 'PRE';

	// 	// Acessa o link da API
	// 	$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

	// 	// Valida se houve retorno da classe testada
	// 	$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

	// 	// TODO: Validar dados de retorno

	// 	// Busca na base os tickets verificando se os mesmos estão com situação paga e se foi gerado uma autorização para os mesmos
	// 	$listParkTickets = $this->Ticket->find('all');

	// 	// Valida se encontrou tickets
	// 	$this->assertNotEmpty($listParkTickets);

	// 	// Varre lista de tickets
	// 	foreach ($listParkTickets as $key => $ticket) {

	// 		$this->assertEquals('PAGO', $ticket['Ticket']['situacao']);
	// 		$this->assertNotNull($ticket['Ticket']['pago_em']);
	// 		$this->assertNotNull($ticket['Ticket']['equipamento_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['operador_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['servico_id_pagamento']);
	// 		$this->assertNotNull($ticket['Ticket']['entidade_id_pagamento']);
	// 	}

	// 	// Busca autorização do pagamento dos tickets
	// 	$autorizacao = $this->Autorizacao->find('all');

	// 	// Valida se encontrou autorização
	// 	$this->assertNotNull($autorizacao);

	// 	// Valida quantidade de autorizações. Deverá trazer apenas um
	// 	$this->assertEquals(count($autorizacao), 1);

	// 	$autorizacao = $autorizacao[0];	

	// 	// Valida valor do pagamento
	// 	$this->assertEquals($valorTotalTickets, $autorizacao['Autorizacao']['valor_original']);
	// 	$this->assertEquals('PRE', $autorizacao['Autorizacao']['pagamento']);
	// 	$this->assertEquals('CONSUMO', $autorizacao['Autorizacao']['tipo']);
	// 	$this->assertEquals('APROVADO', $autorizacao['Autorizacao']['situacao']);
	// 	$this->assertEquals(ADMIN_PARKING_ID, $autorizacao['Autorizacao']['administrador_id']);
	// 	$this->assertEquals($this->limiteCliente['Limite']['id'], $autorizacao['Autorizacao']['limite_id_cliente']);

	// 	// Validação da park_autorizacao
	// 	$parkAutorizacao = $this->ParkAutorizacao->find('all');

	// 	// Valida se encontrou park_autorização
	// 	$this->assertNotNull($parkAutorizacao);

	// 	// Valida quantidade de park autorizações. Deverá trazer apenas um
	// 	$this->assertEquals(count($parkAutorizacao), 1);

	// 	$parkAutorizacao = $parkAutorizacao[0];

	// 	// Validações do registro da park_autorizacao
	// 	$this->assertEquals($autorizacao['Autorizacao']['id'], $parkAutorizacao['ParkAutorizacao']['autorizacao_id']);
	// 	$this->assertEquals($firstParkServico, $parkAutorizacao['ParkAutorizacao']['servico_id']);
	// 	$this->assertEquals($this->data['placa'], $parkAutorizacao['ParkAutorizacao']['placa']);
	// 	$this->assertEquals('IRREGULARIDADE', $parkAutorizacao['ParkAutorizacao']['tipo']);
	// 	$this->assertEquals(1, $parkAutorizacao['ParkAutorizacao']['atualizar']);
	// 	$this->assertEquals(ADMIN_PARKING_ID, $parkAutorizacao['ParkAutorizacao']['administrador_id']);
	// 	$this->assertEquals($valorTotalTickets, $parkAutorizacao['ParkAutorizacao']['valor']);
	// 	$this->assertEquals('CPF_CNPJ', $parkAutorizacao['ParkAutorizacao']['forma_pagamento']);
	// }// End Method 'testQuitacaoIrregularidadesAreasDiferentes_PagamentoCpfCnpj'


	/**
	 * Testa a recarga feita para um cliente recém cadastrado para validar se deverá contar como se fosse a recarga inicial
	 * para o operador que o cadastrou. Validações feitas para o ranking de operadores
	 * OBS: teste apenas com um operador no mesmo dia
	 */
	public function testInitialRechargeOperador() {
		// Cria array de dados da tabela 'park_operador_cliente'
		$parkOperadorCliente = array('OperadorCliente' => array(
			'cliente_id' => $this->dataGenerator->clienteId,
			'operador_id' => $this->dataGenerator->operadorId
		));
		// Insere um registro na tabela 'park_operador_cliente' na mao
		$this->OperadorCliente->save($parkOperadorCliente);
		// Zera limite do cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0.00);
		// Cria dados para efetuar a recarga		
		$valorRecargaCentavos = 3333.0;
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= $this->cliente['Cliente']['cpf_cnpj'];
		$this->data['forma_pagamento']		= 'DINHEIRO';
		// Envia request para efetuar a recarga		
		$this->sendRequest($this->url('PaymentParking', 'add'), 'POST', $this->data); // Try placing the order
		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();
		// Busca novo registro na park_operador_cliente 
		$newParkOperadorCliente = $this->OperadorCliente->findByClienteId($this->dataGenerator->clienteId);
		// Valida se existe
		$this->assertNotNull($newParkOperadorCliente);
		$this->assertNotEmpty($newParkOperadorCliente);
		// Valida se o valor do campo 'recarga_inicial' está populada com o valor da recarga do cliente
		$this->assertEquals($valorRecargaCentavos /100, $newParkOperadorCliente['OperadorCliente']['recarga_inicial']);
	} // End Method 'testInitialRechargeOperador'

	public function testInitialRechargeCardOperador() {
		$valorRecargaCentavos = 3333.0;
		// Cria pedido + item de recarga a ser aprovado
		$pedido = array(
			'entidade_id' => $this->dataGenerator->clienteId,
			'pagamento_id' => 3,
			'tipo' => 'RECARGA',
			'associado_id' => ADMIN_PARKING_ID,
			'equipamento_id' => $this->dataGenerator->equipamentoId,
			'nsu' => 100
		);

		$this->dataGenerator->savePedido(array('Pedido' => $pedido));

		$item = array(
			'limite_id' => $this->limiteCliente['Limite']['id'],
			'pedido_id' => $this->dataGenerator->pedidoId,
			'valor' => $valorRecargaCentavos / 100
		);

		$this->dataGenerator->saveItem(array('Item' => $item));
		
		$transacao = $this->dataGenerator->getTransacao();

		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['pedido_id']	 		= $this->dataGenerator->pedidoId;
		$this->data['cpf_cnpj_cliente'] 	= '811.271.030-91';
		$this->data['forma_pagamento']		= 'CARTAO';

		$transacao['Transacao']['valor'] = $valorRecargaCentavos / 100;

		$this->data['transaction'] = json_encode($transacao['Transacao']);

		// Cria array de dados da tabela 'park_operador_cliente'
		$parkOperadorCliente = array('OperadorCliente' => array(
			'cliente_id' => $this->dataGenerator->clienteId,
			'operador_id' => $this->dataGenerator->operadorId
		));
		// Insere um registro na tabela 'park_operador_cliente' na mao
		$this->OperadorCliente->save($parkOperadorCliente);
		// Zera limite do cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0.00);
		// Envia request para efetuar a recarga		
		$this->sendRequest($this->url('PaymentParking', 'edit'), 'POST', $this->data); // Try placing the order
		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();
		// Busca novo registro na park_operador_cliente 
		$newParkOperadorCliente = $this->OperadorCliente->findByClienteId($this->dataGenerator->clienteId);
		// Valida se existe
		$this->assertNotNull($newParkOperadorCliente);
		$this->assertNotEmpty($newParkOperadorCliente);
		// Valida se o valor do campo 'recarga_inicial' está populada com o valor da recarga do cliente
		$this->assertEquals($valorRecargaCentavos /100, $newParkOperadorCliente['OperadorCliente']['recarga_inicial']);
	} // End Method 'testInitialRechargeOperador'


	/**
	* Testa a recarga inicial de um cliente que seja cadastrado pelo operador em um equipamento e tenha a primeira recarga 
	* feito pelo mesmo operador em outro serviço/equipamento
	*/
	public function testInitialRechargeTwoServicesOperador(){

		// Cria array de dados da tabela 'park_operador_cliente'
		$parkOperadorCliente = array('OperadorCliente' => array(
			'cliente_id' => $this->dataGenerator->clienteId,
			'operador_id' => $this->dataGenerator->operadorId
		));
		// Insere um registro na tabela 'park_operador_cliente' na mao
		$this->OperadorCliente->save($parkOperadorCliente);
		// Zera limite do cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0.00);
		// Encerra o serviço atual
		$this->dataGenerator->callParkCashClosing();
		// Cria um novo serviço com o mesmo operador
		$this->dataGenerator->saveServico();
		// Cria dados para efetuar a recarga		
		$valorRecargaCentavos = 3333.0;
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= $this->cliente['Cliente']['cpf_cnpj'];
		$this->data['forma_pagamento']		= 'DINHEIRO';
		// Envia request para efetuar a recarga		
		$this->sendRequest($this->url('PaymentParking', 'add'), 'POST', $this->data); // Try placing the order
		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();
		// Busca novo registro na park_operador_cliente 
		$newParkOperadorCliente = $this->OperadorCliente->findByClienteId($this->dataGenerator->clienteId);
		// Valida se existe
		$this->assertNotNull($newParkOperadorCliente);
		$this->assertNotEmpty($newParkOperadorCliente);
		// Valida se o valor do campo 'recarga_inicial' está populada com o valor da recarga do cliente
		$this->assertEquals($valorRecargaCentavos /100, $newParkOperadorCliente['OperadorCliente']['recarga_inicial']);
	}// End Method 'testInitialRechargeTwoServicesOperador'

	/**
	 * Testa a recarga inicial de um cliente com o mesmo operador que o cadastrou porém um dia após seu cadastro, ou seja, 
	 * não deverá atualizar o registro da tabela 'park_operador_cliente'
	 */
	public function testInitialRechargeOperadorPlusOneDay(){
		// Atualiza a data de criação do cliente para um dia atrás
		$newCriadoEm = $this->dataGenerator->getDateTime('-1 day');
		$clienteId = $this->dataGenerator->clienteId;

		$this->Entidade->updateAll(
		    array('Entidade.criado_em' => "'$newCriadoEm'"),
		    array('Entidade.id' => $clienteId)
		);

		// Cria array de dados da tabela 'park_operador_cliente'
		$parkOperadorCliente = array('OperadorCliente' => array(
			'cliente_id' => $this->dataGenerator->clienteId,
			'operador_id' => $this->dataGenerator->operadorId
		));
		// Insere um registro na tabela 'park_operador_cliente' na mao
		$this->OperadorCliente->save($parkOperadorCliente);
		// Zera limite do cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0.00);
		// Cria dados para efetuar a recarga		
		$valorRecargaCentavos = 3333.0;
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= $this->cliente['Cliente']['cpf_cnpj'];
		$this->data['forma_pagamento']		= 'DINHEIRO';
		// Envia request para efetuar a recarga		
		$this->sendRequest($this->url('PaymentParking', 'add'), 'POST', $this->data); // Try placing the order
		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();
		// Busca novo registro na park_operador_cliente 
		$newParkOperadorCliente = $this->OperadorCliente->findByClienteId($this->dataGenerator->clienteId);
		// Valida se existe
		$this->assertNotNull($newParkOperadorCliente);
		$this->assertNotEmpty($newParkOperadorCliente);
		// Valida se o valor do campo 'recarga_inicial' está populada com o valor da recarga do cliente
		$this->assertEquals(0.00, $newParkOperadorCliente['OperadorCliente']['recarga_inicial']);
	}// End Method 'testInitialRechargeOperadorPlusOneDay'


	/**
	* Testa a recarga inicial de um cliente cadastrado por um operador e tenta fazer a primeira recarga em outro operador.
	* O valor do campo 'recarga_inicial' da tabela 'park_operador_cliente' deverá ficar vazio
	*/
	public function testInitialRechargeTwoServicesTwoOperador(){

		// Cria array de dados da tabela 'park_operador_cliente'
		$parkOperadorCliente = array('OperadorCliente' => array(
			'cliente_id' => $this->dataGenerator->clienteId,
			'operador_id' => $this->dataGenerator->operadorId
		));
		// Insere um registro na tabela 'park_operador_cliente' na mao
		$this->OperadorCliente->save($parkOperadorCliente);
		// Zera limite do cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0.00);
		// Salva um novo equipamento
		$newEquipament = $this->dataGenerator->getEquipamento();
		$this->dataGenerator->saveEquipamento($newEquipament);
		// Altera os dados da requisição
		$this->data['serial'] = $newEquipament['Equipamento']['no_serie'];
		$this->data['nsu'] = $newEquipament['Equipamento']['nsu'] + 1;
		// Cria um novo operador
		$this->dataGenerator->saveOperador();
		// Cria um novo servico
		$this->dataGenerator->saveServico();
		// Cria dados para efetuar a recarga		
		$valorRecargaCentavos = 3333.0;
		$this->data['codigo_pagamento'] 	= CODPAG_RECARGA_PREPAGO;
		$this->data['valor_centavos'] 		= $valorRecargaCentavos;
		$this->data['cpf_cnpj_cliente'] 	= $this->cliente['Cliente']['cpf_cnpj'];
		$this->data['forma_pagamento']		= 'DINHEIRO';
		// Envia request para efetuar a recarga		
		$this->sendRequest($this->url('PaymentParking', 'add'), 'POST', $this->data); // Try placing the order
		// Libera processamento da recarga
		$this->dataGenerator->clearPendente();
		// Busca novo registro na park_operador_cliente 
		$newParkOperadorCliente = $this->OperadorCliente->findByClienteId($this->dataGenerator->clienteId);
		// Valida se existe
		$this->assertNotNull($newParkOperadorCliente);
		$this->assertNotEmpty($newParkOperadorCliente);
		// Valida se o valor do campo 'recarga_inicial' está populada com o valor da recarga do cliente
		$this->assertEquals(0.00, $newParkOperadorCliente['OperadorCliente']['recarga_inicial']);
	}// End Method 'testInitialRechargeTwoServicesTwoOperador'

	/**
	 ********************************************************************
	 ********************************************************************
	 *******                                                      *******
	 ******* CAMPOS DEFAULT PARA CADA TIPO DE CODIGO DE PAGAMENTO *******
	 *******                                                      *******
	 ********************************************************************
	 ********************************************************************
	 */

	
	/**
	 * Método auxiliar para popular os campos da compra de periodo em todos os testes deste tipo de codigo de pagamento.
	 */
	private function getPeriodPurchaseFields(){
		// Busca registro da área ponto para buscar o número da vaga inserido
		$parkAreaPonto = $this->AreaPonto->findById($this->dataGenerator->areapontoId);
		// Validação se o registro da área ponto não é nulo
		$this->assertNotNull($parkAreaPonto, 'Registro da Área Ponto é NULL!');

		// Popula os campos necessários para compra de período
		$this->data['codigo_pagamento'] = CODPAG_COMPRA_PERIODO_PARKING;
		$this->data['placa']            = $this->getRandomPlace();
		$this->data['qtde_periodos']    = 1;
		// $this->data['tipo_veiculo']     = $this->getRandomTypeVehicle();
		$this->data['tipo_veiculo']     = 'CARRO';		
		$this->data['vaga']             = $parkAreaPonto['AreaPonto']['codigo'];
		$this->data['valor_centavos']	= $this->getValueByAmoutPurchasePeriod($this->data['qtde_periodos'], $this->dataGenerator->precoId);

		//TODO: POR ENQUANTO MANDA SEMPRE DINHEIRO
		$this->data['forma_pagamento'] = 'DINHEIRO';
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