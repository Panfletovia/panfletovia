<?php

App::uses('ApiBaseControllerTestCase','Api.Lib');

class TicketsControllerTest extends ApiBaseControllerTestCase {
	
	public $mockUser = false;
	public $uses = array('Parking.Ticket', 'Equipamento', 'Cliente', 'Limite', 'Autorizacao', 'Parking.Historico', 'Autorizacao', 'ContraPartida', 'Pendente', 'Parking.Preco', 'Parking.Area');
	private $URL = '/api/tickets';
	private $cliente = '';
	private $extension = '.json';

	/**
	 * Rotina executada antes de cada teste
	 */
	public function setUp () {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveTarifa(array('Tarifa' => array('posto_id' => null)));
		$this->dataGenerator->savePreco(array('Preco' => array(
			'duracao_periodo'           => 60,
			'excedente_periodo_minutos' => 1,
			'excedente_periodo_valor'   => 1.00,
			'tolerancia_cancelamento'   => 59,
			'cobranca_turnos'           => 0,
			'cobranca_periodos'         => 1,
			'faixa_1_minutos'           => 0,
			'faixa_1_valor'             => 0,
			'faixa_1_tolerancia'        => 0

			)));
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea(array('Area' => array('devolucao_periodo' => 1, 'uteis_fim' => '23:59:59')));
		$this->dataGenerator->saveSetor();
		$this->areaPonto = $this->dataGenerator->getAreaPonto();
		$this->dataGenerator->saveAreaPonto($this->areaPonto);
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveOperador(array('Operador' =>	 array('usuario' => '1234567890','senha' => '1234567890')));
		$servico = $this->dataGenerator->getServico();
		$servico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveServico($servico);
		$this->dataGenerator->saveCliente();
		$this->parkTarifa = $this->dataGenerator->getParkTarifa();
		$this->parkTarifa['ParkTarifa']['id'] = $this->dataGenerator->saveParkTarifa($this->parkTarifa);

		//adiciona limite de 1000 reais
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 100000);

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}// End Method 'setUp'

	/**
	 * Testa a desativação do ticket, sem esperar uma exceção
	 * @param park_ticket_id
	 * @todo  Alterar criação do ticket para forma manual
	 */
	public function testDeactivateTicket() {
		// Busca limite do associado
		$limiteAssociado = $this->Limite->findByEntidadeId($this->dataGenerator->postoId);
		// Valida se encontrou limite
		$this->assertNotNull($limiteAssociado);
		// Busca limite do cliente
		$limiteCliente = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		// Valida se encontrou limite
		$this->assertNotNull($limiteCliente);
		// Cria variável que armazena o valor a ser utilizado na park_ticket e na autorizacao
		$valor = 10.33;
		// Insere registro na autorizacao
		$this->dataGenerator->saveAutorizacao(array('Autorizacao' => array(
			'equipamento_id' => $this->dataGenerator->equipamentoId,
			'valor_original' => $valor,
			'limite_id' => $limiteAssociado['Limite']['id'],
			'limite_id_cliente' => $limiteCliente['Limite']['id'],
			'forma_pagamento' => 'CPF_CNPJ',
			)));

		// Cria ticket a ser desativado, setando tipo , pois senão poderia gerar um ticket de irregularidade, não gerando autorizacao
		$this->dataGenerator->saveTicket(array('Ticket' => array(
			'tipo' => 'UTILIZACAO',
			'valor' => $valor,
			'autorizacao_id' => $this->dataGenerator->autorizacaoId,
			'data_inicio' => $this->dataGenerator->getDateTime('-5 seconds'),
			'data_fim' => $this->dataGenerator->getDateTime('+1 hour'),
			'forma_pagamento' => 'CPF_CNPJ',
		)));

		// Busca ticket inserido
		$parkTicket = $this->Ticket->find('first');

		// Valida se encontrou o registro
		$this->assertNotNull($parkTicket);

		// Adiciona o id do ticket nos parâmetros da requisição
		$this->data['park_ticket_id'] = $parkTicket['Ticket']['id'];

		// Acessa o link da API
		$this->testAction($this->URL . '/deactivate/' . $this->extension , array('method' => 'POST','data' => $this->data));

		// Valida se criou o registro de devolução na tabela 'autorizacao'
		$autorizacao = $this->Autorizacao->findByTipo('DEVOLUCAO');
		// Valida se encontrou autorizacao
		$this->assertNotNull($autorizacao);
		// Busca registro na contra_partida
		$contraPartida = $this->ContraPartida->find('first');
		// Valida se encontrou o registro
		$this->assertNotNull($contraPartida);
		// Valida se a contra partida é da autorizacao original
		$this->assertEquals($autorizacao['Autorizacao']['id'], $contraPartida['ContraPartida']['autorizacao_id_vinculo']);
		// Busca algum registro pendente para processamento
		$pendente = $this->Pendente->find('first');
		// Não deverá ter nenhum registro pendente
		$this->assertEmpty($pendente);
		// Busca novamente o ticket
		$parkTicketAfter = $this->Ticket->findById($parkTicket['Ticket']['id']);
		// Não deverá ser nulo
		$this->assertNotNull($parkTicketAfter);
		// O campo desconto da ticket deverá estar populado com o valor devolvido
		$this->assertNotNull($parkTicketAfter['Ticket']['desconto']);
	}// End Method 'testDeactivateTicket'

	public function testDeactivateRequestPut() {
		$this->validateTestException(
			$this->URL . '/deactivate/' . $this->extension,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}

	/**
	 * Testa a inclusão do código de autuação no ticket de irregularidade
	 */
	public function testCodigoAutuacao() {
		// Cria placa e código de autuação randômicos
		$this->createPlateAndAssessmentCode();
		// Gera um número aleatório de irregularidades
		$numTickets = rand(1,7);
		for ($i = 0; $i < $numTickets; $i++) {
			$this->dataGenerator->saveTicket(array('Ticket' => array(
				'placa' 					=> $this->data['placa'],
				'situacao' 					=> 'AGUARDANDO',
				'pago_em'  					=> NULL,
				'tipo'     					=> 'IRREGULARIDADE',
				'equipamento_id_pagamento' 	=> NULL,
				'nsu_pagamento' 			=> 0,
				'entidade_id_pagamento' 		=> NULL, 
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento' 	=> NULL, 
				'motivo_irregularidade' 	=> 'FORA_DA_VAGA',
				'numero_autuacao'			=> 0
				)));
		}
		// Acessa o link da API
		$this->sendRequest($this->URL ."/{$this->dataGenerator->ticketId}{$this->extension}", 'PUT', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertEqual($this->vars['data']['message'], 'OK', 'Mensagem de retorno incorreta ou inexistente');

		// Lê o ticket alterado e recupera o código de autuação
		$this->Ticket->read(null, $this->dataGenerator->ticketId);
		$numAutuacao = $this->Ticket->data['Ticket']['numero_autuacao'];

		// Testa se o código foi inserido
		$this->assertTrue(!empty($numAutuacao), 'Código de autuação não inserido');

		// Testa se o código inserido é o correto
		$this->assertEquals($numAutuacao, $this->data['fine_code'], 'Código de autuação não inserido');
	}// End Method 'testCodigoAutuacao'

	/**
	 * Testa a inclusão do código de autuação no ticket de irregularidade Vencida
	 */
	public function testCodigoAutuacaoVencida() {
		// Alterar a Configuração do Preço para não ter tempo livre
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tempo_livre', 0);

		// Chama o método que cria as placas e o codigo de autuação
		$this->createPlateAndAssessmentCode();

		// Lança a Irregularidade para o Veiculo
		$this->dataGenerator->emiteIrregularidade($this->data['placa'], $this->areaPonto['AreaPonto']['codigo'], 'VENCIDO', true);

		// Pega o Ticket de irregularidade da placa
		$this->ticket = $this->Ticket->find('first', array(
			'recursive' => -1,
			'conditions'=> array('tipo' => 'IRREGULARIDADE', 'placa' => $this->data['placa']),
			'order' =>array('id' => 'desc')
			));
		// Acessa o link da API
		$this->sendRequest($this->URL ."/{$this->ticket['Ticket']['id']}{$this->extension}", 'PUT', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertEqual($this->vars['data']['message'], 'OK', 'Mensagem de retorno incorreta ou inexistente');

		// Lê o ticket alterado e recupera o código de autuação
		$this->Ticket->read(null, $this->ticket['Ticket']['id']);
		$numAutuacao = $this->Ticket->data['Ticket']['numero_autuacao'];

		// Testa se o código foi inserido
		$this->assertTrue(!empty($numAutuacao), 'Código de autuação não inserido');

		// Testa se o código inserido é o correto
		$this->assertEquals($numAutuacao, $this->data['fine_code'], 'Código de autuação não inserido');
	}// End Method 'testCodigoAutuacao'

	/**
	 * Testa a inclusão do código de autuação no ticket de irregularidade Sem Ticket
	 */
	public function testCodigoAutuacaoSemTicket() {

		// Alterar a Configuração do Preço para não ter tempo livre
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tempo_livre', 0);

		// Chama o método que cria as placas e o codigo de autuação
		$this->createPlateAndAssessmentCode();
		// Lança a Irregularidade para o Veiculo
		$this->dataGenerator->emiteIrregularidade($this->data['placa'], $this->areaPonto['AreaPonto']['codigo'], 'SEM_TICKET');

		// Pega o Ticket de irregularidade da placa
		$this->ticket = $this->Ticket->find('first', array(
			'recursive' => -1,
			'conditions'=> array('tipo' => 'IRREGULARIDADE', 'placa' => $this->data['placa']),
			'order' =>array('id' => 'desc')
			));
		// Acessa o link da API
		$this->sendRequest($this->URL ."/{$this->ticket['Ticket']['id']}{$this->extension}", 'PUT', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertEqual($this->vars['data']['message'], 'OK', 'Mensagem de retorno incorreta ou inexistente');

		// Lê o ticket alterado e recupera o código de autuação
		$this->Ticket->read(null, $this->ticket['Ticket']['id']);
		$numAutuacao = $this->Ticket->data['Ticket']['numero_autuacao'];

		// Testa se o código foi inserido
		$this->assertTrue(!empty($numAutuacao), 'Código de autuação não inserido');

		// Testa se o código inserido é o correto
		$this->assertEquals($numAutuacao, $this->data['fine_code'], 'Código de autuação não inserido');
	}// End Method 'testCodigoAutuacao'

	/**
	 * Testa a inclusão do código de autuação de uma notificação vencida
	 */
	public function testCodigoAutuacaoNotificacaoVencida() {
		// Cria placa e código de autuação randômicos
		$this->createPlateAndAssessmentCode();
		// Gera uma irregularidade
		$this->dataGenerator->saveTicket(array('Ticket' => array(
			'placa' 					=> $this->data['placa'],
			'situacao' 					=> 'AGUARDANDO',
			'pago_em'  					=> NULL,
			'tipo'     					=> 'IRREGULARIDADE',
			'equipamento_id_pagamento' 	=> NULL,
			'nsu_pagamento' 			=> 0,
			'data_inicio'				=> $this->dataGenerator->getDateTime('-31 minutes'),
			'data_fim'					=> $this->dataGenerator->getDateTime('-1 minutes'),
			'entidade_id_pagamento' 	=> NULL, 
			'servico_id_pagamento'		=> NULL,
			'operador_id_pagamento' 	=> NULL, 
			'motivo_irregularidade' 	=> 'FORA_DA_VAGA',
			'numero_autuacao'			=> 0
			)));

		$this->validateTestException(
			$this->URL."/{$this->dataGenerator->ticketId}".$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'A situação deste veículo foi atualizada: o veículo encontra-se regular. A autuação não foi registrada.'
		);

		
	}// End Method 'testCodigoAutuacaoNotificacaoVencida'

	/**
	 * Testa a inclusão do código de uma autuação no aviso vencido
	 */
	public function testCodigoAutuacaoAvisoVencido() {
		// Cria placa e código de autuação randômicos
		$this->createPlateAndAssessmentCode();
		// Gera uma irregularidade
		$this->dataGenerator->saveTicket(array('Ticket' => array(
			'placa' 					=> $this->data['placa'],
			'situacao' 					=> 'AGUARDANDO',
			'pago_em'  					=> NULL,
			'tipo'     					=> 'IRREGULARIDADE',
			'equipamento_id_pagamento' 	=> NULL,
			'nsu_pagamento' 			=> 0,
			'data_inicio'				=> $this->dataGenerator->getDateTime('-31 minutes'),
			'data_fim'					=> $this->dataGenerator->getDateTime('-1 minutes'),
			'valor' 					=> 0,
			'entidade_id_pagamento' 	=> NULL, 
			'servico_id_pagamento'		=> NULL,
			'operador_id_pagamento' 	=> NULL, 
			'motivo_irregularidade' 	=> 'VENCIDO',
			'numero_autuacao'			=> 0
			)));

		$this->validateTestException(
			$this->URL."/{$this->dataGenerator->ticketId}".$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'A situação deste veículo foi atualizada: o veículo encontra-se regular. A autuação não foi registrada.'
		);

		
	}// End Method 'testCodigoAutuacaoAvisoVencido'

	// Testa se o aviso foi interrompido de acordo com a troca de vaga.
	public function testCodigoAutuacaoAvisoInterrompido() {

		//Traz o Preço
		$this->Preco->read(null,$this->dataGenerator->precoId);

		//Configura o Preço para utilizar o Aviso.
		$this->Preco->data['Preco']['valor_irregularidade'] = 0;
		$this->Preco->data['Preco']['irregularidade'] = 'AVISO';

		$this->Preco->save();

		// Cria placa e código de autuação randômicos
		$this->createPlateAndAssessmentCode();

		// Estaciona o Veiculo na vaga.
		$this->dataGenerator->verificaVeiculo($this->data['placa'],$this->areaPonto['AreaPonto']['codigo']);

		//Emitir Irregulariedade.
		$this->dataGenerator->emiteIrregularidade($this->data['placa'],$this->areaPonto['AreaPonto']['codigo']);

		$novaVaga = $this->dataGenerator->getAreaPonto();
		$this->dataGenerator->saveAreaPonto($novaVaga);

		// Estaciona o Veiculo em uma vaga diferente.
		$this->dataGenerator->verificaVeiculo($this->data['placa'],$novaVaga['AreaPonto']['codigo']);

		$avisoInterrompido = $this->Ticket->find('first');

		// Verificar se o Ticket foi realmente interrompido
		$this->assertTrue(!empty($avisoInterrompido['Ticket']['data_fim_original']), 'O Aviso não foi Interrompido');

		//Lançar uma autuação para um Aviso interrompido
		$this->validateTestException(
			$this->URL."/{$avisoInterrompido['Ticket']['id']}".$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'A situação deste veículo foi atualizada: o veículo encontra-se regular. A autuação não foi registrada.'
		);
	}

	// Testar se o API irá bloquear o codigo de autuação se a irregulariedade for 'SEM_TICKET' ou 'VENCIDO' 
	// e puder ser feito o Débito Automático
	public function testCodigoAutuacaoDebitoAutomatico() {
		
		// Cria placa e código de autuação randômicos
		$this->createPlateAndAssessmentCode();

		//Configura o Preço para utilizar o Aviso.
		$this->Preco->read(null,$this->dataGenerator->precoId);
		$this->Preco->data['Preco']['valor_irregularidade'] = 0;
		$this->Preco->data['Preco']['irregularidade'] = 'AVISO';
		$this->Preco->save();

		// Configura a área para ignorar a tolerância e executar o DA no momento da verificação
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('debito_automatico_apos_tolerancia', 0);
		$this->Area->saveField('bloquear_compra_apos_irregularidade', 0);
		$this->Area->saveField('consumir_eticket', 0);

		// Estaciona o Veiculo na vaga.
		$this->dataGenerator->verificaVeiculo($this->data['placa'], $this->areaPonto['AreaPonto']['codigo']);

		//Emitir Irregularidade.
		$this->dataGenerator->emiteIrregularidade($this->data['placa'], $this->areaPonto['AreaPonto']['codigo']);
	
		//Associa a placa ao cliente com o saldo.
		$this->dataGenerator->savePlaca(array('Placa'=>array('placa'=> $this->data['placa'])));

		// Busca ticket do aviso
		$aviso = $this->Ticket->find('first');

		//Lançar uma autuação para um Aviso interrompido
		$this->validateTestException(
			$this->URL."/{$aviso['Ticket']['id']}".$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'A situação deste veículo foi atualizada: o veículo encontra-se regular. A autuação não foi registrada.'
		);

		// Confirma que um consumo de DA foi efetuado
		$ticketDA = $this->Ticket->find('all', array(
			'conditions' => array(
				'Ticket.situacao' => 'PAGO', 
				'Ticket.tipo' => 'UTILIZACAO', 
				'Ticket.debito_automatico' => 1
			)
		));
		// Valida se o ticket buscado é referente ao débito automático
		$this->assertTrue(!empty($ticketDA));
		$this->assertEquals(count($ticketDA), 1, 'Número incorreto de tickets');

	}// End Method 'testCodigoAutuacaoDebitoAutomatico'

	public function test_InvalidTicketId() {
		$this->createPlateAndAssessmentCode();

		$this->validateTestException(
			$this->URL.'/999999999'.$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'Ticket não encontrado'
		);
	}// End Method 'testCodigoAutuacaoInvalidTicketId'

	public function testCodigoAutuacaoFineCodeInvalid() {
		// Cria placa e código de autuação randômicos
		$this->data['placa'] = 'AND'. rand(1000,9999);

		$this->validateTestException(
			$this->URL.'/999999999'.$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'Código de autuação não recebido'
		);
	}// End Method 'testCodigoAutuacaoFineCodeInvalid'

	public function testCodigoAutuacaoIrregularityNotFound() {
		$this->createPlateAndAssessmentCode();

		$this->dataGenerator->saveTicket(array('Ticket' => array(
				'placa' 					=> $this->data['placa'],
				'situacao' 					=> 'AGUARDANDO',
				'pago_em'  					=> NULL,
				'tipo'     					=> 'UTILIZACAO',
				'equipamento_id_pagamento' 	=> NULL,
				'nsu_pagamento' 			=> 0,
				'entidade_id_pagamento' 		=> NULL, 
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento' 	=> NULL, 
				'motivo_irregularidade' 	=> 'FORA_DA_VAGA',
				'numero_autuacao'			=> 0
				)));

		$this->validateTestException(
			$this->URL."/{$this->dataGenerator->ticketId}".$this->extension,
			'PUT',
			$this->data,
			'ApiException',
			'Irregularidade não encontrada'
		);
	}// End Method 'testCodigoAutuacaoIrregularityNotFound'
	
	public function testDesativarPorParquimetro(){
		
		$codCartao = '12345';
		// Converte para hexa o codigo do cartão
		$codigoCartao = dechex(intval($codCartao));
		
		// Seta tamanho de caracteres
		while (strlen($codigoCartao) < 8) {
			$codigoCartao =  '0' . $codigoCartao;
		}
		
		//criar equipamento parquímetro
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_PARQUIMETRO,'no_serie' => '4564358676454','modelo' => 'PARQUIMETRO')));
		
		// Busca limite do associado
		$limiteAssociado = $this->Limite->findByEntidadeId($this->dataGenerator->postoId);
		// Valida se encontrou limite
		$this->assertNotNull($limiteAssociado);
		// Busca limite do cliente
		$limiteCliente = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		// Valida se encontrou limite
		$this->assertNotNull($limiteCliente);
		// Cria variável que armazena o valor a ser utilizado na park_ticket e na autorizacao
		$valor = 10.33;
		// Insere registro na autorizacao
		$this->dataGenerator->saveAutorizacao(array('Autorizacao' => array(
				'equipamento_id' => $this->dataGenerator->equipamentoId,
				'valor_original' => $valor,
				'limite_id' => $limiteAssociado['Limite']['id'],
				'limite_id_cliente' => $limiteCliente['Limite']['id'],
				'forma_pagamento' => 'CPF_CNPJ',
		)));
		
		// Cria ticket a ser desativado, setando tipo , pois senão poderia gerar um ticket de irregularidade, não gerando autorizacao
		$this->dataGenerator->saveTicket(array('Ticket' => array(
				'tipo' => 'UTILIZACAO',
				'valor' => $valor,
				'autorizacao_id' => $this->dataGenerator->autorizacaoId,
				'data_inicio' => $this->dataGenerator->getDateTime('-5 seconds'),
				'data_fim' => $this->dataGenerator->getDateTime('+1 hour'),
				'forma_pagamento' => 'CPF_CNPJ',
				'codigo_cartao' => $codigoCartao
		)));
		
		// Busca ticket inserido
		$parkTicket = $this->Ticket->find('first');
		
		// Valida se encontrou o registro
		$this->assertNotNull($parkTicket);
		
		// Adiciona o id do ticket nos parâmetros da requisição
		$this->data['park_ticket_id'] = $parkTicket['Ticket']['id'];
		
		//dados enviados pelo parquímetro
		unset($this->data['park_ticket_id']);
		$this->data['valor_devolucao_centavos'] = '0';
		$this->data['codigo_cartao'] = $codCartao;
		
		// Acessa o link da API
		$this->testAction($this->URL . '/deactivate' . $this->extension , array('method' => 'POST','data' => $this->data));
		
		// Valida se criou o registro de devolução na tabela 'autorizacao'
		$autorizacao = $this->Autorizacao->findByTipo('DEVOLUCAO');
		// Valida se encontrou autorizacao
		$this->assertNotNull($autorizacao);
		// Busca registro na contra_partida
		$contraPartida = $this->ContraPartida->find('first');
		// Valida se encontrou o registro
		$this->assertNotNull($contraPartida);
		// Valida se a contra partida é da autorizacao original
		$this->assertEquals($autorizacao['Autorizacao']['id'], $contraPartida['ContraPartida']['autorizacao_id_vinculo']);
		// Busca algum registro pendente para processamento
		$pendente = $this->Pendente->find('first');
		// Não deverá ter nenhum registro pendente
		$this->assertEmpty($pendente);
		// Busca novamente o ticket
		$parkTicketAfter = $this->Ticket->findById($parkTicket['Ticket']['id']);
		// Não deverá ser nulo
		$this->assertNotNull($parkTicketAfter);
		// O campo desconto da ticket deverá estar populado com o valor devolvido
		$this->assertNotNull($parkTicketAfter['Ticket']['desconto']);
	}// End Method 'testDesativarPorParquimetro'

	public function testDesativarPorParquimetroSemCodigo(){
		//dados enviados pelo parquímetro
		unset($this->data['park_ticket_id']);
		$this->data['valor_devolucao_centavos'] = '0';
		
		$this->validateTestException(
			$this->URL . '/deactivate' . $this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Parâmetros para devolução incorretos'
		);
	}// End Method 'testDesativarPorParquimetro'

	public function createPlateAndAssessmentCode() {
		// Cria placa e código de autuação randômicos
		$this->data['placa'] = 'AND'. rand(1000,9999);
		$this->data['fine_code'] = rand(100,999);		
	}

	/**
	 * Testa se na emissao da AIT de um veículo com irregularidades e cliente com débito automático, o mesmo lança o erro de cliente sem saldo pré,
	 * porém trata o erro e emite AIT normalmente
	 */
	public function testEmissaoAITSemSaldoClienteComDebitoAutomatico(){
		// Variáveis separados para futuras comparações
		$placa = $this->dataGenerator->randomPlaca();
		$vaga = $this->areaPonto['AreaPonto']['codigo'];
		$numeroAutuacao = rand(100,999);
		// Vincula um cliente para a placa gerada
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['placa'] = $placa;
		$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		$this->dataGenerator->savePlaca($parkPlaca);
		// Salva remove tempo_livre para veículo não ganhar tolerância
		$this->Preco->read(null, $this->dataGenerator->precoId);
		$this->Preco->set('tempo_livre', 0);
		$this->Preco->save();
		// Remove limite usuário para o teste
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 0);
		// Espera a exception do banco 
		$this->expectException('PDOException', 'SQLSTATE[42000]: Syntax error or access violation: 1305 PROCEDURE selenium._erro_saldo_insuficiente_pre does not exist');
		// Verifica o veículo para criar o seu registro na park_historico
		$this->dataGenerator->verificaVeiculo($placa, $vaga);
		// Emite irregularidade para poder lançar AIT
		$this->dataGenerator->emiteIrregularidade($placa, $vaga, 'VENCIDO');
		// Busca registro na park_historico
		$parkHistorico = $this->Historico->findByPlaca($placa);
		// Valida se encontrou histórico do veículo
		$this->assertNotNull($parkHistorico);
		// Seta o número de atuação que o veículo irá receber
		$this->data['fine_code'] = $numeroAutuacao;
		// Busca ticket da irregularidade
		$this->Ticket->recursive = -1;
		$parkTicket = $this->Ticket->find('first', array('conditions' => array('placa' => $placa, 'tipo' => 'irregularidade')));
		// Valida se encontrou
		$this->assertNotNull($parkTicket);
		$this->assertNotEmpty($parkTicket);
		// Acessa o link da API
		$this->testAction($this->URL.'/'.$parkTicket['Ticket']['id'] .$this->extension , array('method' => 'PUT','data' => $this->data));
		// Busca ticket para validar o código autuação
		$parkTicket = $this->Ticket->findById($parkTicket['Ticket']['id']);
		// Valida se o número passado no parâmetro da requisição é o mesmo que o ticket foi atualizado
		$this->assertEquals($numeroAutuacao, $parkTicket['Ticket']['numero_autuacao']);
	}// End Method 'testEmissaoAITSemSaldoClienteComDebitoAutomatico'


	/**
	 * Testa a desativação de dois tickets para a mesma placa na URA. Neste caso deverá interromper todos os tickets desta placa
	 */
	public function testDeactivateTwoTicketsSamePlateURA () {
		// Salva um equipamento tipo URA
		$equipamentoURA = $this->dataGenerator->getEquipamentoURA();
		$this->dataGenerator->saveEquipamento($equipamentoURA);
		// Atualiza o tempo para devolução do ticket
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tolerancia_cancelamento', 5);

		// Atualiza tempo minimo de devolução para zero
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('tempo_minimo_devolucao', 0);
		// Variável que armazena a placa a ser utilizado no teste
		$placa = 'TST-1234';
		// Salva segunda tarifa
		$parkTarifa2 = $this->dataGenerator->getParkTarifa();
		$parkTarifa2['ParkTarifa']['minutos'] = 20;
		$parkTarifa2['ParkTarifa']['valor']   = 4.00;
		$parkTarifa2['ParkTarifa']['codigo']  = 2;
		$this->dataGenerator->saveParkTarifa($parkTarifa2);
		
		// Compra primeiro ticket para placa 
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$this->parkTarifa['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$this->parkTarifa['ParkTarifa']['codigo']
		);
		// Compra segundo ticket para a placa
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$parkTarifa2['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$parkTarifa2['ParkTarifa']['codigo']
		);
		// Adiciona o id do ticket nos parâmetros da requisição
		$dataUra = $this->getApiDefaultParams();

		$dataUra['placa']  = $placa;
		$dataUra['serial'] =  $equipamentoURA['Equipamento']['no_serie'];
		$dataUra['model']  =  $equipamentoURA['Equipamento']['tipo'];
		$dataUra['type']   =  $equipamentoURA['Equipamento']['tipo'];

		// Acessa o link da API
		$this->sendRequest($this->URL . '/deactivate_ura' . $this->extension, 'POST', $dataUra);
		// Valida se as variáveis de retorno foram retornadas
		$this->assertNotNull($this->vars['data']['devolucao']['valor']);
		$this->assertNotNull($this->vars['data']['devolucao']['qtde']);
		$this->assertNotNull($this->vars['data']['devolucao']['qtde_dinheiro']);
		$this->assertFalse(isset($this->vars['data']['devolucao']['autorizacao_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['cliente_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['nsu']));
		// Valida o conteúdo do retorno
		$valorTickets = $this->parkTarifa['ParkTarifa']['valor'] + $parkTarifa2['ParkTarifa']['valor'];
		$this->assertEquals($valorTickets * 100  ,$this->vars['data']['devolucao']['valor']);
		$this->assertEquals(2, $this->vars['data']['devolucao']['qtde']);
		$this->assertEquals(0, $this->vars['data']['devolucao']['qtde_dinheiro']);
		// Busca tickets na base 
		$this->Ticket->recursive = -1;
		$parkTickets = $this->Ticket->find('all', array('conditions' => array('placa' => $placa)));
		// Validoes dos tickets encontrados
		$this->assertNotEmpty($parkTickets);
		$this->assertEquals( 2, sizeof($parkTickets));

		foreach ($parkTickets as $key => $value) {

			$this->assertEquals('PAGO', $value['Ticket']['situacao']);
			$this->assertEquals(0.00, $value['Ticket']['valor']);
			$periodos = $value['Ticket']['periodos'];

			switch ($periodos){
				case 1:
					$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $value['Ticket']['valor_original']);
					$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $value['Ticket']['desconto']);
					$this->assertEquals($this->parkTarifa['ParkTarifa']['minutos'] , $value['Ticket']['tempo_tarifa']);
					break;
				case 2:
					$this->assertEquals($parkTarifa2['ParkTarifa']['valor']   , $value['Ticket']['valor_original']);
					$this->assertEquals($parkTarifa2['ParkTarifa']['valor']   , $value['Ticket']['desconto']);
					$this->assertEquals($parkTarifa2['ParkTarifa']['minutos'] , $value['Ticket']['tempo_tarifa']);
					break;
			}
		}
	}// End Method 'testDeactivateTwoTicketsSamePlateURA'

	/**
	 * Teste para validar a requisição com um equipamento diferente da URA
	 */
	public function testDeactivateTicketURAEquipamentoDiferente(){
		$this->validateTestException(
			$this->URL.'/deactivate_ura'.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Tipo de equipamento diferente do esperado'
		);
	}// End Method 'testDeactivateTicketURAEquipamentoDiferente'

	/**
	 * Teste para validar a requisição sem enviar a placa do ticket a ser cancelado
	 */
	public function testDeactivateTicketURASemPlaca(){
		// Salva um equipamento tipo URA
		$equipamentoURA = $this->dataGenerator->getEquipamentoURA();
		$this->dataGenerator->saveEquipamento($equipamentoURA);
		// Adiciona o id do ticket nos parâmetros da requisição
		$dataUra = $this->getApiDefaultParams();
		$dataUra['serial'] =  $equipamentoURA['Equipamento']['no_serie'];
		$dataUra['model']  =  $equipamentoURA['Equipamento']['tipo'];
		$dataUra['type']   =  $equipamentoURA['Equipamento']['tipo'];
		// Envia requisição esperando exception
		$this->validateTestException(
			$this->URL.'/deactivate_ura'.$this->extension,
			'POST',
			$dataUra,
			'ApiException',
			'Placa inválida'
		);
	}// End Method 'testDeactivateTicketURASemPlaca'

	/**
	 * Testa a desativação de um ticket na URA
	 */
	public function testDeactivateOneTicketsSamePlateURA () {
		// Salva um equipamento tipo URA
		$equipamentoURA = $this->dataGenerator->getEquipamentoURA();
		$this->dataGenerator->saveEquipamento($equipamentoURA);
		// Atualiza o tempo para devolução do ticket
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tolerancia_cancelamento', 5);

		// Atualiza tempo minimo de devolução para zero
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('tempo_minimo_devolucao', 0);
		// Variável que armazena a placa a ser utilizado no teste
		$placa = 'TST-1234';
		// Compra primeiro ticket para placa 
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$this->parkTarifa['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$this->parkTarifa['ParkTarifa']['codigo']
		);

		// Adiciona o id do ticket nos parâmetros da requisição
		$dataUra = $this->getApiDefaultParams();

		$dataUra['placa']  = $placa;
		$dataUra['serial'] =  $equipamentoURA['Equipamento']['no_serie'];
		$dataUra['model']  =  $equipamentoURA['Equipamento']['tipo'];
		$dataUra['type']   =  $equipamentoURA['Equipamento']['tipo'];

		// Acessa o link da API
		$this->sendRequest($this->URL . '/deactivate_ura' . $this->extension, 'POST', $dataUra);
		// Valida se as variáveis de retorno foram retornadas
		$this->assertNotNull($this->vars['data']['devolucao']['valor']);
		$this->assertNotNull($this->vars['data']['devolucao']['qtde']);
		$this->assertFalse(isset($this->vars['data']['devolucao']['autorizacao_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['cliente_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['nsu']));
		// Valida o conteúdo do retorno
		$valorTicket = $this->parkTarifa['ParkTarifa']['valor'];
		$this->assertEquals($valorTicket * 100 ,$this->vars['data']['devolucao']['valor']);
		$this->assertEquals(1, $this->vars['data']['devolucao']['qtde']);
		$this->assertEquals(0, $this->vars['data']['devolucao']['qtde_dinheiro']);
		// Busca tickets na base 
		$this->Ticket->recursive = -1;
		$parkTicket = $this->Ticket->find('first', array('conditions' => array('placa' => $placa)));
		// Validoes dos tickets encontrados
		$this->assertNotEmpty($parkTicket);
		$this->assertEquals(1                                          , sizeof($parkTicket));
		$this->assertEquals('PAGO'                                     , $parkTicket['Ticket']['situacao']);
		$this->assertEquals(0.00                                       , $parkTicket['Ticket']['valor']);
		$this->assertEquals($this->parkTarifa['ParkTarifa']['codigo']  , $parkTicket['Ticket']['periodos']);
		$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $parkTicket['Ticket']['valor_original']);
		$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $parkTicket['Ticket']['desconto']);
		$this->assertEquals($this->parkTarifa['ParkTarifa']['minutos'] , $parkTicket['Ticket']['tempo_tarifa']);
	}// End Method 'testDeactivateOneTicketsSamePlateURA'

	/**
	 * Testa validação do cancelamento de todos os tickets comprados com a conta do usuário, mantendo o ticket em dinheiro.
	 * Ordem de compra: pre, dinheiro, pre.
	 */
	public function testDeactivateTicketURATwoPreOneMoney(){
		// Salva um equipamento tipo URA
		$equipamentoURA = $this->dataGenerator->getEquipamentoURA();
		$this->dataGenerator->saveEquipamento($equipamentoURA);
		// Atualiza o tempo para devolução do ticket
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tolerancia_cancelamento', 5);

		// Atualiza tempo minimo de devolução para zero
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('tempo_minimo_devolucao', 0);
		// Variável que armazena a placa a ser utilizado no teste
		$placa = 'TST-1234';
		// Salva segunda tarifa
		$parkTarifa2 = $this->dataGenerator->getParkTarifa();
		$parkTarifa2['ParkTarifa']['minutos'] = 20;
		$parkTarifa2['ParkTarifa']['valor']   = 4.00;
		$parkTarifa2['ParkTarifa']['codigo']  = 2;
		$this->dataGenerator->saveParkTarifa($parkTarifa2);
		// Salva terceira tarifa
		$parkTarifa3 = $this->dataGenerator->getParkTarifa();
		$parkTarifa3['ParkTarifa']['minutos'] = 30;
		$parkTarifa3['ParkTarifa']['valor']   = 6.00;
		$parkTarifa3['ParkTarifa']['codigo']  = 3;
		$this->dataGenerator->saveParkTarifa($parkTarifa3);
		
		// Compra primeiro ticket PRE para placa 
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$this->parkTarifa['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$this->parkTarifa['ParkTarifa']['codigo']
		);
		// Compra primeiro ticket DINHEIRO para placa 
		$this->dataGenerator->venderTicketEstacionamentoDinheiro(
			$parkTarifa2['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$parkTarifa2['ParkTarifa']['codigo']
		);
		// Compra terceira ticket PRE para a placa
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$parkTarifa3['ParkTarifa']['valor'], 
			$placa, 
			$this->areaPonto['AreaPonto']['codigo'], 
			$parkTarifa3['ParkTarifa']['codigo']
		);

		// Adiciona o id do ticket nos parâmetros da requisição
		$dataUra = $this->getApiDefaultParams();

		$dataUra['placa']  = $placa;
		$dataUra['serial'] = $equipamentoURA['Equipamento']['no_serie'];
		$dataUra['model']  = $equipamentoURA['Equipamento']['tipo'];
		$dataUra['type']   = $equipamentoURA['Equipamento']['tipo'];

		// Acessa o link da API
		$this->sendRequest($this->URL . '/deactivate_ura' . $this->extension, 'POST', $dataUra);
		// Valida se as variáveis de retorno foram retornadas
		$this->assertNotNull($this->vars['data']['devolucao']['valor']);
		$this->assertNotNull($this->vars['data']['devolucao']['qtde']);
		$this->assertNotNull($this->vars['data']['devolucao']['qtde_dinheiro']);
		$this->assertFalse(isset($this->vars['data']['devolucao']['autorizacao_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['cliente_id']));
		$this->assertFalse(isset($this->vars['data']['devolucao']['nsu']));
		// Valida o conteúdo do retorno
		$valorTicketsPre = $this->parkTarifa['ParkTarifa']['valor'] + $parkTarifa3['ParkTarifa']['valor'];
		$this->assertEquals($valorTicketsPre * 100  ,$this->vars['data']['devolucao']['valor']);
		$this->assertEquals(2, $this->vars['data']['devolucao']['qtde']);
		$this->assertEquals(1, $this->vars['data']['devolucao']['qtde_dinheiro']);
		// Busca tickets na base 
		$this->Ticket->recursive = -1;
		$parkTickets = $this->Ticket->find('all', array('conditions' => array('placa' => $placa)));
		// Validoes dos tickets encontrados
		$this->assertNotEmpty($parkTickets);
		$this->assertEquals( 3, sizeof($parkTickets));

		foreach ($parkTickets as $key => $value) {

			$this->assertEquals('PAGO', $value['Ticket']['situacao']);
			$periodos = $value['Ticket']['periodos'];

			switch ($periodos){
				case 1:
					$this->assertEquals(0.00, $value['Ticket']['valor']);
					$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $value['Ticket']['valor_original']);
					$this->assertEquals($this->parkTarifa['ParkTarifa']['valor']   , $value['Ticket']['desconto']);
					$this->assertEquals($this->parkTarifa['ParkTarifa']['minutos'] , $value['Ticket']['tempo_tarifa']);
					break;
				case 2:
					$this->assertEquals($parkTarifa2['ParkTarifa']['valor']   , $value['Ticket']['valor']);
					$this->assertEquals($parkTarifa2['ParkTarifa']['valor']   , $value['Ticket']['valor_original']);
					$this->assertEquals(0.00                                  , $value['Ticket']['desconto']);
					$this->assertEquals($parkTarifa2['ParkTarifa']['minutos'] , $value['Ticket']['tempo_tarifa']);
					break;
				case 3:
					$this->assertEquals(0.00, $value['Ticket']['valor']);
					$this->assertEquals($parkTarifa3['ParkTarifa']['valor']   , $value['Ticket']['valor_original']);
					$this->assertEquals($parkTarifa3['ParkTarifa']['valor']   , $value['Ticket']['desconto']);
					$this->assertEquals($parkTarifa3['ParkTarifa']['minutos'] , $value['Ticket']['tempo_tarifa']);
					break;
			}
		}
	}// End Method 'testDeactivateTicketURATwoPreOneMoney'

	// @Todo: criar testes para outras situações na emissão da AIT. TicketsController -> action : edit

}// End Class
