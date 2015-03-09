<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes da lista de irregularidades para sua quitação
 */
class IrregularitiesControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
			'Parking.Operador',
			'Parking.Area',
			'Parking.AreaPonto',
			'Parking.Preco',
			'Produto',
			'Parking.Cobranca',
			'Equipamento',
			'Parking.Marca',
			'Parking.Modelo',
			'Parking.Cor',
			'Parking.Historico',
			'Parking.Servico',
			'Parking.Ticket',
			'Parking.Eticket',
			'Limite',
			'Recibo'
	);

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a extensão a ser retornada  WebService
	private $extension = '.json';
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/irregularities';
	// Variável que recebe registro da tabela park_area_ponto
	private $parkAreaPonto = NULL;

	/**
	 * Método que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveSetor();

		$this->parkAreaPonto = $this->dataGenerator->getAreaPonto();
		$this->dataGenerator->saveAreaPonto($this->parkAreaPonto);

		$this->dataGenerator->saveEquipamento(
				array(
						'Equipamento' => array(
								'tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,
								'no_serie' => '1234567890',
								'modelo' => 'ANDROID'
						)
				)
		);
		$this->dataGenerator->saveOperador(
				array(
						'Operador' => array(
								'usuario' => '1234567890',
								'senha' => '1234567890'
						)
				)
		);
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveServico(
				array(
						'Servico' => array(
								'data_fechamento' => NULL
						)
				)
		);
		$this->dataGenerator->saveParkTarifa();

		// Seta os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();

		
		// Popula variável do comando
        $this->data['placa'] = 'AND'.rand(1000,9999);
        //$this->data['area_id'] = $this->dataGenerator->areaId;
        
        $this->dataGenerator->saveRecibo(
			array(
				'Recibo' => array(
					'leiaute_id' 		=> 5,
					'codigo_barras' 	=> 1
				)
			)
		);
	}


	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, 
	* pois na classe só deverá tratar a index
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
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, 
	* pois na classe só deverá tratar a index
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
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, 
	* pois na classe só deverá tratar a index
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
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro Placa está incorreto
	 */
	public function testSemPlaca() {
		// Remove campo do array de envio
		unset($this->data['placa']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Placa inválida'
		);
	}// End 'testSemPlaca'


	/**
	* Testa acesso a API, esperando uma lista vazia de tickets irregulares.
	*/
	public function testSemTicketsIrregulares() {
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull($this->vars['data']['placa'], 'Campo de valor de retorno da função está null');
		$this->assertNotNull($this->vars['data']['valor'], 'Campo de valor de retorno da função está null');
		$this->assertNotNull($this->vars['data']['areas'], 'Campo de valor de retorno da função está null');
	}// End 'testSemTicketsIrregulares'

	/**
	* Testa acesso a API, esperando uma lista preenchida de tickets irregulares para validação dos dados recebidos.
	*/
	public function testComTicketsIrregulares(){
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
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
				'GETo_id_pagamento'			=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;
		}

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
		
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recebe os valores de resposta do comando de pagamento
		$receivePlaca 						= $this->vars['data']['placa'];
		// Converte o valor em reais
		$receiveValor 						= ($this->vars['data']['valor'] / 100);
		$receiveIrregularidades_data_valor  = $this->vars['data']['areas'];

		// Valida se os campos do retorno estão preenchidos
		$this->assertNotNull($receivePlaca, 'Campo de valor de retorno da função está null');
		$this->assertNotNull($receiveValor, 'Campo de valor de retorno da função está null');
		$this->assertNotNull($receiveIrregularidades_data_valor, 'Campo de valor de retorno da função está null');
		// Valida integridade dos campos, ou seja, valida se a placa  e valor calculado é o esperado.
		$this->assertEquals($this->data['placa'], $receivePlaca, "Placa enviada diferente de placa recebida. Enviada: {$this->data['placa']} / Recebida: $receivePlaca");
		$this->assertEquals($valorTotalTickets, $receiveValor, "Valor calculado diferente do valor recebido. Calculado: $valorTotalTickets / Recebido: $receiveValor");
	}// End 'testComTicketsIrregulares'

	/**
	 *  Testa acesso a API, esperando uma lista preenchida de tickets irregulares agrupado por área para validação dos dados recebidos.
	 */	
	public function testComTicketsIrregularesAreasDiferentes() {
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
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
				'GETo_id_pagamento'			=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;
		}

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
		
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recebe os valores de resposta do comando de pagamento
		$receivePlaca 						= $this->vars['data']['placa'];
		// Converte o valor em reais
		$receiveValor 						= ($this->vars['data']['valor'] / 100);
		$receiveIrregularidades_data_valor  = $this->vars['data']['areas'];
		

		// Valida se os campos do retorno estão preenchidos
		$this->assertNotNull($receivePlaca, 'Campo de valor de retorno da função está null');
		$this->assertNotNull($receiveValor, 'Campo de valor de retorno da função está null');
		$this->assertNotNull($receiveIrregularidades_data_valor, 'Campo de valor de retorno da função está null');
		// Valida integridade dos campos, ou seja, valida se a placa  e valor calculado é o esperado.
		$this->assertEquals($this->data['placa'], $receivePlaca, "Placa enviada diferente de placa recebida. Enviada: {$this->data['placa']} / Recebida: $receivePlaca");
		$this->assertEquals($valorTotalTickets, $receiveValor, "Valor calculado diferente do valor recebido. Calculado: $valorTotalTickets / Recebido: $receiveValor");		
	}

	/**
	 * Testa se a lista de irregularidades está retornando agrupado por área.
	 */
	public function testListIrregularities() {
		// Cria uma quantidade de tickets a serem gerados randômicamente
		$qtdeTicketsIrregulares = rand(1,10);
		// Variável que receberá o valor total dos tickets gerados randômicamente para comparar com o retorno do webService
		$valorTotalTickets = 0;
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
				'GETo_id_pagamento'			=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;
		}

		// Cria uma nova área.
		$this->dataGenerator->saveArea();
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
				'equipamento_id_pagamentor'	=> NULL,
				'GETo_id_pagamento'			=> NULL,
				'servico_id_pagamento'		=> NULL,
				'operador_id_pagamento'		=> NULL,
				'area_id'				    => $this->dataGenerator->areaId
			)));
			// Incrementa o valor do total de tickets com o valor gerado para o ticket individualmente
			$valorTotalTickets += $valorRandom;
		}

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
		$this->assertNotNull($this->data);
	}
	




	
	/*
	 * Notificação
	 */
	
	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro Placa não foi recebido
	 */
	public function testAddSemPlaca() {
		
		$this->setupNotifyIrregularities();
		
		// Remove campo do array de envio
		unset($this->data['placa']);

		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Placa não recebida'
		);
	}// End 'testSemPlaca'
	
	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro MotivoIrregularidade está incorreto
	 */
	public function testAddSemMotivoIrregularidade() {
		
		$this->setupNotifyIrregularities();
		
		// Remove campo do array de envio
		unset($this->data['motivo_irregularidade']);
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Motivo da irregularidade inválido'
		);
	}// End 'testSemMotivoIrregularidade'
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que a marca enviada não foi encontrada
	 */
	public function testAddSemParkMarcaId() {
		
		$this->setupNotifyIrregularities();
		
		// Altera o ID da marca a ser enviado para lançar exceção de marca não encontrada
		$this->data['park_marca_id'] = 99999;
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Marca não encontrada'
		);
	}// End 'testSemParkMarcaId'
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que o modelo enviada não foi encontrada
	 */
	public function testAddSemParkModeloId() {
		
		$this->setupNotifyIrregularities();
		
		// Altera o ID da marca a ser enviado para lançar exceção de marca não encontrada
		$this->data['park_modelo_id'] = 99999;
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Modelo não encontrado'
		);
	}// End 'testSemParkModeloId'
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que a cor enviada não foi encontrada
	 */
	public function testAddSemParkCorId() {
		
		$this->setupNotifyIrregularities();
		
		// Altera o ID da marca a ser enviado para lançar exceção de marca não encontrada
		$this->data['park_cor_id'] = 99999;
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Cor não encontrada'
		);
	}// End 'testSemParkCorId'
	
	/**
	 * Testa emissão de irregularidade, esperando erro pois o histórico está removido
	 */
	public function testAddVeiculoRemovido() {
		
		$this->setupNotifyIrregularities();
		
		// Cria objeto da park_historico com campo situação sendo 'REMOVIDO'
		$newParkHistorico = array('Historico' => array('id'=> $this->dataGenerator->historicoId, 'situacao' => 'REMOVIDO'));
		// Atualiza registro na base de dados
		$this->Historico->save($newParkHistorico);
	
		$this->data['motivo_irregularidade'] = 'FOO';
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);
	}// End 'testAddVeiculoRemovido'
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que o registro de vaga não encontrada
	 */
	public function testAddVagaInvalida() {
		
		$this->setupNotifyIrregularities();
	
		$this->data['motivo_irregularidade'] = IRREGULARIDADE_MOTIVO_FORA_DA_VAGA;
		$this->data['vaga'] = '65000';
		$this->data['area_id'] = $this->dataGenerator->areaId;
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Vaga não encontrada'
		);
	}
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que o registro de área não fornecida
	 */
	public function testAddAreaNaoFornecida() {
		// Popula parâmetros default da requisição
		$this->setupNotifyIrregularities();
	
		// Sobrescreve parâmetros 
		$this->data['motivo_irregularidade'] = IRREGULARIDADE_MOTIVO_FORA_DA_VAGA;
		$this->data['vaga'] = $this->parkAreaPonto['AreaPonto']['codigo'];

		// Remove campo 'area_id' dos parâmetros da requisição
		unset($this->data['area_id']);
		
		// Envia requisição esperando erro
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Área inválida'
		);
	}// End Method 'testAddAreaNaoFornecida'
	
	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de que o registro de área não encontrada
	 */
	public function testAddAreaNaoEncontrada() {
		
		$this->setupNotifyIrregularities();
	
		$this->data['motivo_irregularidade'] = IRREGULARIDADE_MOTIVO_FORA_DA_VAGA;
		$this->data['vaga'] = '1';
		$this->data['area_id'] = 4378654;
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Área não encontrada'
		);
	}
	
	/**
	 * Testa acesso a API, esperando exceção de "InternalError" e a mensagem de que o 'Veículo é isento' devido que não foi encontrado um preço id na chamada da função que calcula qual preço id deverá ser utilizado
	 */
	public function testAddVeiculoIsento() {
		
		$this->setupNotifyIrregularities();
	
		// Atualiza os preços para NULL do registro das cobranças para que o teste lançe a exceção de veículo isento.
		$newParkCobranca = array('Cobranca' => array(
				'id' 											=> $this->dataGenerator->cobrancaId,
				'preco_id_carro' 								=> NULL,
				'preco_id_vaga_farmacia' 						=> NULL,
				'preco_id_vaga_idoso' 							=> NULL,
				'preco_id_irregularidade_vencido' 				=> NULL,
				'preco_id_irregularidade_sem_ticket'		 	=> NULL,
				'preco_id_irregularidade_fora_vaga' 			=> NULL,
				'preco_id_irregularidade_ticket_incompativel' 	=> NULL
		));
	
		$this->dataGenerator->saveCobranca($newParkCobranca);
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Irregularidade bloqueada: veículo isento'
		);
	}// End Method 'testAddVeiculoIsento'
	
	/**
	 * Testa acesso a API, esperando exceção de "InternalError" e a mensagem de que não foi possível inserir irregularidade
	 */
	public function testAddMotivoVencidoInvalido() {
		
		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo fique em tolerância
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('+ 5 Minute')
		));
		$this->data['motivo_irregularidade'] = 'VENCIDO';

		$this->Historico->save($newParkHistorico);
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);
		
	}// End Method 'testAddMotivoVencidoInvalido'

	/**
	 * Método que efetua o teste completo, sem esperar exceção.
	 */
	public function testAddSemTipoVeiculo() {
		
		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo não fique em tolerância, nem pago
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('- 1 Minute')
		));
		$this->Historico->save($newParkHistorico);
		// Atualiza registro da park_serviço para que o mesmo seja aberto
		$newParkServico = array('Servico' => array('id' => $this->dataGenerator->servicoId,'data_fechamento' => NULL));
		$this->Servico->save($newParkServico);
	
		unset($this->data['tipo_veiculo']);
	
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
	
		$this->assertEquals($this->vars['data']['ticket']['tipo_veiculo'], 'CARRO');
	
	}
	
	/**
	 * Método que efetua o teste completo, sem esperar exceção.
	 */
	public function testAddValidarRetorno() 
	{

		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo não fique em tolerância, nem pago
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('- 1 Minute')
		));
		$this->Historico->save($newParkHistorico);
		// Atualiza registro da park_serviço para que o mesmo seja aberto
		$newParkServico = array('Servico' => array('id' => $this->dataGenerator->servicoId,'data_fechamento' => NULL));
		$this->Servico->save($newParkServico);
	
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull($this->vars['data']['operador']['usuario']             , 'Campo park_operador_usuario	de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']                          , 'Campo park_ticket 			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['placa']                 , 'Campo placa 					de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['motivo_irregularidade'] , 'Campo motivo_irregularidade 	de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['tipo_veiculo']          , 'Campo tipo_veiculo 			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['nome_marca']            , 'Campo nome_marca 			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['nome_modelo']           , 'Campo nome_modelo 			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['nome_cor']              , 'Campo nome_cor 				de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['criado_em']             , 'Campo criado_em 				de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['data_inicio']           , 'Campo data_inicio 			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['data_fim']              , 'Campo data_fim 				de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['valor_centavos']        , 'Campo valor_centavos			de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['vaga']                  , 'Campo vaga					de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['barcode'], 'Código de barras de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['linha_digitavel'], 'Linha digitável de retorno da função está null');
		$this->assertNotNull($this->vars['data']['ticket']['vencimento'], 'Vencimento está vazio');
		$this->assertNotNull($this->vars['data']['ticket']['mensagem'], 'Mensagem não foi retornada');
	}// End Method 'testValidarRetorno'


	public function testSemBoleto() {

		$recibo = $this->Recibo->findByLeiauteId(5);
		$recibo['Recibo']['codigo_barras'] = 0;
		$this->Recibo->save($recibo);

		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo não fique em tolerância, nem pago
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('- 1 Minute')
		));
		$this->Historico->save($newParkHistorico);
		// Atualiza registro da park_serviço para que o mesmo seja aberto
		$newParkServico = array('Servico' => array('id' => $this->dataGenerator->servicoId,'data_fechamento' => NULL));
		$this->Servico->save($newParkServico);
	
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		$this->assertNull($this->vars['data']['ticket']['barcode'], 'Código de barras de retorno da função está null');
		$this->assertNull($this->vars['data']['ticket']['linha_digitavel'], 'Linha digitável de retorno da função está null');
		$this->assertNull($this->vars['data']['ticket']['mensagem'], 'Mensagem não foi retornada');
	}// End Method 'testValidarRetorno'
	
	/**
	 * Codigo de autuação em texto
	 */
	public function testAddCodigoAutuacaoTexto() {
		
		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo não fique em tolerância, nem pago
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('- 1 Minute')
		));
		$this->Historico->save($newParkHistorico);
		// Atualiza registro da park_serviço para que o mesmo seja aberto
		$newParkServico = array('Servico' => array('id' => $this->dataGenerator->servicoId,'data_fechamento' => NULL));
		$this->Servico->save($newParkServico);
	
		$this->data['codigo_autuacao'] = 'fewpiovjçlvjçlvjflçvjflkvjerpigjroifh';
	
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Código de autuação inválido'
		);
	
	}
	
	/**
	 * Testa se o método add está salvando corretamente os dados no banco
	 */
	public function testAddValidarDadosBanco() {
		
		$this->setupNotifyIrregularities();
		
		// Atualiza registro da park_historico para que o veículo não fique em tolerância, nem pago
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'pago_ate' 			=> NULL,
				'tolerancia_ate' 	=> $this->dataGenerator->getDateTime('- 1 Minute')
		));
		$this->Historico->save($newParkHistorico);
		// Atualiza registro da park_serviço para que o mesmo seja aberto
		$newParkServico = array('Servico' => array('id' => $this->dataGenerator->servicoId,'data_fechamento' => NULL));
		$this->Servico->save($newParkServico);
	
		// Acessa o link da API
		$this->testAction($this->URL . $this->extension , array('method' => 'POST','data' => $this->data));
	
		// Recupera o ticket inserido
		$ticket = $this->Ticket->find('first');
		$this->assertEqual($ticket['Ticket']['placa'], $this->data['placa'], 'Placa não foi salva corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['numero_autuacao'], $this->data['codigo_autuacao'], 'Código autuação não foi salvo corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['motivo_irregularidade'], $this->data['motivo_irregularidade'], 'Motivo irregularidade não foi salva corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['veiculo'], $this->data['tipo_veiculo'], 'Tipo veículo não foi salvo corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['marca_id'], $this->data['park_marca_id'], 'Id da marca não foi salvo corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['modelo_id'], $this->data['park_modelo_id'], 'Id do modelo não foi salvo corretamente no banco.');
		$this->assertEqual($ticket['Ticket']['cor_id'], $this->data['park_cor_id'], 'Id da cor não foi salvo corretamente no banco.');
	}// End 'testValidarDadosBanco'


	//================================================================================================================================
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, SEM débito automático e COM tipo irregularidade 
	 * por fora de vaga. NESTE CASO DEVERÁ: emitir a irregularidade normalmente (Deverá criar registro na park_historico?)
	 */
	public function testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoForaDaVaga(){
	
		$this->setupNotifyIrregularities(0);
		
		// ALtera o motivo da irregularidade para FORA_DA_VAGA
		$this->data['motivo_irregularidade'] = 'FORA_DA_VAGA';
		// Faz a requisição
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
	
		$this->assertEquals($this->data['placa'], $this->vars['data']['ticket']['placa']);

	}// End 'testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoForaDaVaga'

	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por ticket incompátivel. NESTE CASO DEVERÁ: retornar erro pois não encontrará o histórico para validar o status do veículo
	 */
	public function testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoTicketIncompativel(){

		$this->setupNotifyIrregularities(0);
		
		// ALtera o motivo da irregularidade para TICKET_INCOMPATIVEL
		$this->data['motivo_irregularidade'] = 'TICKET_INCOMPATIVEL';
		// Testa a exceção
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);
	}// End 'testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoTicketIncompativel'
	
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por sem ticket. NESTE CASO DEVERÁ: retornar erro pois não encontrará o histórico
	 */
	public function testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoSemTicket(){

		$this->setupNotifyIrregularities(0);
		
		// ALtera o motivo da irregularidade para SEM_TICKET
		$this->data['motivo_irregularidade'] = 'SEM_TICKET';
		// Testa a exceção
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);

	}// End 'testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoSemTicket'

	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por ticket vencido. NESTE CASO DEVERÁ: retornar erro pois não encontrará o histórico para validar o status do veículo
	 */
	public function testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoVencido(){

		$this->setupNotifyIrregularities(0);
		
		// ALtera o motivo da irregularidade para VENCIDO
		$this->data['motivo_irregularidade'] = 'VENCIDO';
		// Testa a exceção
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);

	}// End 'testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoVencido'

	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por ticket vencido. NESTE CASO DEVERÁ: retornar erro pois não encontrará o histórico para validar o status do veículo
	 */
	public function testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoPermanenciaExcedida(){

		$this->setupNotifyIrregularities(0);
		
		// ALtera o motivo da irregularidade para PERMANENCIA_EXCEDIDA
		$this->data['motivo_irregularidade'] = 'PERMANENCIA_EXCEDIDA';
		// Testa a exceção
		$this->validateTestException(
				$this->URL,
				'POST',
				$this->data,
				'ApiException',
				'Não foi possível emitir a irregularidade: a situação deste veículo foi atualizada.'
		);

	}// End 'testEmitirIrregularidadeSemHistoricoSemDebitoAutomaticoPermanenciaExcedida'

	//================================================================================================================================
	
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por fora da vaga. NESTE CASO DEVERÁ: efetuar o débito automático e EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_SemHistorico_ComDebitoAutomatico_ForaDaVaga(){}
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por ticket incompatível. NESTE CASO DEVERÁ: efetuar o débito automático e NÃO EMITIR a irregularidade.
	 */
	public function test_EmitirIrregularidade_SemHistorico_ComDebitoAutomatico_TicketIncompativel(){}
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por sem ticket. NESTE CASO DEVERÁ: efetuar o débito automático e EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_SemHistorico_ComDebitoAutomatico_SemTicket(){}
	/**
	 * Testa emissão de irregularidade SEM possuir um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por vencido. NESTE CASO DEVERÁ: efetuar o débito automático e NÃO EMITIR a irregularidade.
	 */
	public function test_EmitirIrregularidade_SemHistorico_ComDebitoAutomatico_Vencido(){}
	//================================================================================================================================
	/**
	 * Testa emissão de irregularidade COM um histórico anterior lançado, SEM débito automático e COM tipo irregularidade
	 * por fora da vaga. NESTE CASO DEVERÁ: EMITIR a irregularidade normalmente.
	 */
	public function testEmitirIrregularidadeComHistoricoLancadoSemDebitoAutomaticoForaDaVaga(){
		$this->setupNotifyIrregularities();
		// ALtera o motivo da irregularidade para FORA_DA_VAGA
		$this->data['motivo_irregularidade'] = 'FORA_DA_VAGA';
		// Faz a requisição
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Verifica se a irregularidade foi emitida para a placa informada
		$this->assertEquals($this->data['placa'], $this->vars['data']['ticket']['placa']);

	}// End Method 'testEmitirIrregularidadeComHistoricoLancadoSemDebitoAutomaticoForaDaVaga'

	/**
	 * Testa emissão de irregularidade COM um histórico anterior removido, SEM débito automático e COM tipo irregularidade
	 * por fora da vaga. NESTE CASO DEVERÁ: EMITIR a irregularidade normalmente.
	 */
	public function testEmitirIrregularidadeComHistoricoRemovidoSemDebitoAutomaticoForaDaVaga() {
		
		$this->setupNotifyIrregularities();		
		// Atualiza registro da park_historico para que o veículo seja removido
		$newParkHistorico = array('Historico' => array(
				'id' 				=> $this->parkHistorico['Historico']['id'],
				'situacao' 			=> 'REMOVIDO'
		));
		$this->Historico->save($newParkHistorico);

		// ALtera o motivo da irregularidade para FORA_DA_VAGA
		$this->data['motivo_irregularidade'] = 'FORA_DA_VAGA';	
	
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
	
		$this->assertEquals($this->data['placa'], $this->vars['data']['ticket']['placa']);
		
	}// End Method 'testEmitirIrregularidadeComHistoricoRemovidoSemDebitoAutomaticoForaDaVaga'

	/**
	 * Testa emissão de irregularidade COM um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por ticket incompatível. NESTE CASO DEVERÁ: EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_SemDebitoAutomatico_TicketIncompativel(){}
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por sem ticket. NESTE CASO DEVERÁ: EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_SemDebitoAutomatico_SemTicket(){}
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, SEM débito automático e COM tipo irregularidade
	 * por vencido. NESTE CASO DEVERÁ: EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_SemDebitoAutomatico_Vencido(){}
	//================================================================================================================================
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por fora da vaga. NESTE CASO DEVERÁ: fazer o débito automático e EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_ComDebitoAutomatico_ForaDaVaga(){}
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por ticket incompatível. NESTE CASO DEVERÁ: fazer o débito automático e NÃO EMITIR a irregularidade.
	 */
	public function test_EmitirIrregularidade_ComHistorico_ComDebitoAutomatico_TicketIncompativel(){}
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por sem ticket. NESTE CASO DEVERÁ: fazer o débito automático e EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_ComDebitoAutomatico_SemTicket(){}
	/**
	 * Testa emissão de irregularidade COM um histórico anterior, COM débito automático e COM tipo irregularidade
	 * por vencido. NESTE CASO DEVERÁ: fazer o débito automático e NÃO EMITIR a irregularidade normalmente.
	 */
	public function test_EmitirIrregularidade_ComHistorico_ComDebitoAutomatico_Vencido(){}
	//================================================================================================================================
	/**
	 * @todo O que irá acontecer com um operador da área branca, emite uma irregularidade para uma placa paga da área azul ???
	 * @todo Fazer o teste com Sem Historico, Com Débito Automático, Motivo Fora da Vaga, E sem saldo?
	 *       // Neste caso deverá apenas não fazer o débito automatico e emitir a irregularidade normalmente.
	 */
	//================================================================================================================================
	
	/**
	 * Método para retornar um random entre os tipos de veículos  existentes
	 */
	public function getRandomVehicleType(){
		$type = array('CARRO','MOTO');
		return $type[rand(0,1)];
	}// End 'getRandomVehicleType'
	
	/**
	 * Método para buscar uma marca randômica
	 */
	public function getRandomParkMarca(){
		$listParkMarca = $this->Marca->find('all', array());
		return $listParkMarca[rand(0,(count($listParkMarca) - 1))];
	}// End 'getRandomParkMarca'
	
	/**
	 * Método para buscar um modelo randômico
	 */
	public function getRandomParkModelo(){
		$listParkModelo = $this->Modelo->find('all', array());
		return $listParkModelo[rand(0,(count($listParkModelo) - 1))];
	}// End 'getRandomParkModelo'
	
	/**
	 * Método para buscar uma cor randômica
	 */
	public function getRandomParkCor(){
		$listParkCor = $this->Cor->find('all', array());
		return $listParkCor[rand(0,(count($listParkCor) - 1))];
	}// End 'getRandomParkCor'
	
	private function setupNotifyIrregularities($temHistorico = 1) {
		// Seta placa para gerar contrato e historico
		$placaTest = $this->data['placa'];
		
		// Busca informações da vaga
		$areaPonto =  $this->AreaPonto->findById($this->dataGenerator->areapontoId);

		if ($temHistorico) {		
			// Antes de salvar o registro na park_historico deverá inserir uma vaga válida.
			$this->parkHistorico = $this->dataGenerator->getHistorico();
			$this->parkHistorico['Historico']['placa'] 		= $placaTest;
			$this->parkHistorico['Historico']['vaga'] 		= $areaPonto['AreaPonto']['codigo'];
			$this->parkHistorico['Historico']['removido_em'] = NULL;
			$this->parkHistorico['Historico']['veiculo'] = 'CARRO';
			$this->dataGenerator->saveHistorico($this->parkHistorico);
			
			// Adiciona o id no objeto
			$this->parkHistorico['Historico']['id'] = $this->dataGenerator->historicoId;
		}	
		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
		
		// Busca parâmetros randômicos
		$parkMarca 	= $this->getRandomParkMarca();
		$parkModelo = $this->getRandomParkModelo();
		$parkCor 	= $this->getRandomParkCor();
		
		// Seta os valores para os parâmetros da classe
		$this->data['placa'] 					= $placaTest;
		$this->data['codigo_autuacao']  		= rand(1,9999);
		$this->data['motivo_irregularidade']  	= 'FORA_DA_VAGA';
		$this->data['tipo_veiculo'] 			= 'CARRO';
		$this->data['park_marca_id'] 			= $parkMarca['Marca']['id'];
		$this->data['park_modelo_id'] 			= $parkModelo['Modelo']['id'];
		$this->data['park_cor_id'] 				= $parkCor['Cor']['id'];
		$this->data['codigo_autuacao']			= 0;
		$this->data['area_id']					= $this->dataGenerator->areaId;
		$this->data['vaga']						= $areaPonto['AreaPonto']['codigo'];
	}

	/**
	 * Testa o débito automático na tentativa de emitir uma irregularidade por motivo 'VENCIDO'
	 * para uma placa com débito automático ativado e saldo suficiente validando o eticket-token
	 */
	public function testETicketIrregularidadeVencidoClienteComDebitoAutomaticoRespostaToken() {
		$this->setupNotifyIrregularities();

		// Atualiza a área para que consuma etickets
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('consumir_eticket', 1);

		// Gera etickets para o teste
		$this->dataGenerator->geraEtickets(10, true);

		// Placa do cliente
		$placa = 'AAA-1111';
		// Vaga que o cliente irá estacionar
		$vaga  = $this->parkAreaPonto['AreaPonto']['codigo'];

		// Salva uma tarifa
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		/*$parkTarifa = $this->dataGenerator->getParkTarifa();		
		$this->dataGenerator->saveParkTarifa($parkTarifa);*/

		// Cria cliente com débito automático ativado
		$cliente = $this->dataGenerator->getCliente();
		$cliente['Cliente']['autorizar_debito'] = 1;
		$this->dataGenerator->saveCliente($cliente);

		// Cria registro na park_placa
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['placa']       = $placa;
		$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		$parkPlaca['Placa']['tipo']        = 'CARRO';
		// Salva registro
		$this->dataGenerator->savePlaca($parkPlaca);
		// Lança o veículo vencido já para poder emitir irregularidade
		$this->dataGenerator->lancaVeiculo($placa, $vaga, true);
		// Adiciona limite 
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 1000);		
		// Popula dados da requisição da irregularidade		
		$this->data['placa'] 					= $placa;
        $this->data['codigo_autuacao']  		= 0;
        $this->data['motivo_irregularidade']  	= 'VENCIDO';
        $this->data['tipo_veiculo'] 			= $this->parkHistorico['Historico']['veiculo'];
        $this->data['park_marca_id'] 			= $this->data['park_marca_id'];
        $this->data['park_modelo_id'] 			= $this->data['park_modelo_id'];
        $this->data['park_cor_id'] 				= $this->data['park_cor_id'];
        $this->data['vaga'] 					= $vaga;
        $this->data['area_id'] 					= $this->dataGenerator->areaId;

		// Tenta emitir uma irregularidade por VENCIDO
		$this->testAction($this->URL . $this->extension , array('method' => 'POST','data' => $this->data, 'return' => 'vars'));

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recupera o código do e-ticket
		$eTicketToken = $this->vars['data']['ticket']['e_ticket'];

		// Testa se o e-ticket foi retornado
		$this->assertNotNull($eTicketToken, 'E-ticket não foi retornado.');

		// Por padrão, o lote é gerado para ser utilizado o hash como código do e-ticket
		// Busca um e-ticket com o token igual ao código retornado pelo WS
		$eTicket = $this->Eticket->findByToken($eTicketToken);

		// Testa se o código é realmente o token
		$this->assertTrue(!empty($eTicket), 'Valor retornado não é o token do e-ticket.');

	}// End Method 'testETicketIrregularidadeVencidoClienteComDebitoAutomaticoRespostaToken'

	/**
	 * Testa o débito automático na tentativa de emitir uma irregularidade por motivo 'VENCIDO'
	 * para uma placa com débito automático ativado e saldo suficiente validando o eticket-sequencia
	 */
	public function testETicketIrregularidadeVencidoClienteComDebitoAutomaticoRespostaSequencia() {
		$this->setupNotifyIrregularities();

		// Atualiza a área para que consuma etickets
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('consumir_eticket', 1);

		// Gera etickets para o teste
		$this->dataGenerator->geraEtickets(10, true);

		// Placa do cliente
		$placa = 'AAA-1111';
		// Vaga que o cliente irá estacionar
		$vaga  = $this->parkAreaPonto['AreaPonto']['codigo'];

		// Salva uma tarifa
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		/*$parkTarifa = $this->dataGenerator->getParkTarifa();		
		$this->dataGenerator->saveParkTarifa($parkTarifa);*/

		// Cria cliente com débito automático ativado
		$cliente = $this->dataGenerator->getCliente();
		$cliente['Cliente']['autorizar_debito'] = 1;
		$this->dataGenerator->saveCliente($cliente);

		// Cria registro na park_placa
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['placa']       = $placa;
		$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		$parkPlaca['Placa']['tipo']        = 'CARRO';
		// Salva registro
		$this->dataGenerator->savePlaca($parkPlaca);
		// Lança o veículo vencido já para poder emitir irregularidade
		$this->dataGenerator->lancaVeiculo($placa, $vaga, true);
		// Adiciona limite 
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 1000);		
		// Popula dados da requisição da irregularidade		
		$this->data['placa'] 					= $placa;
        $this->data['codigo_autuacao']  		= 0;
        $this->data['motivo_irregularidade']  	= 'VENCIDO';
        $this->data['tipo_veiculo'] 			= $this->parkHistorico['Historico']['veiculo'];
        $this->data['park_marca_id'] 			= $this->data['park_marca_id'];
        $this->data['park_modelo_id'] 			= $this->data['park_modelo_id'];
        $this->data['park_cor_id'] 				= $this->data['park_cor_id'];
        $this->data['vaga'] 					= $vaga;
        $this->data['area_id'] 					= $this->dataGenerator->areaId;


		// Altera a configuração do lote para usar o número sequencial em lugar do hash
		$this->Eticket->Lote->id = $this->dataGenerator->loteId;
		$this->Eticket->Lote->saveField('usar_numeracao', 1);

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');

		// Recupera o código do e-ticket
		$eTicketSequencia = $this->vars['data']['ticket']['e_ticket'];

		// Testa se o e-ticket foi retornado
		$this->assertNotNull($eTicketSequencia, 'E-ticket não foi retornado.');

		// Busca um e-ticket com o token igual ao código retornado pelo WS
		$eTicket = $this->Eticket->findBySequencia($eTicketSequencia);

		// Testa se o código é realmente o token
		$this->assertTrue(!empty($eTicket), 'Valor retornado não é o token do e-ticket.');
	}// End Method 'testETicketIrregularidadeVencidoClienteComDebitoAutomaticoRespostaSequencia'
	

	/**
	* Método as situações: cliente não tem saldo, tem débito automático, foi lançado sem saldo, fez uma recarga e 
	* operador lança irregularidade. Neste caso, deverá cancelar a irregularidade e efetuar o débito automárico.
	*/
	public function testVerificacaoComSaldoEDaAtivo() {
		$this->setupNotifyIrregularities();
		// Placa do cliente
		$placa = 'AAA-1111';
		// Vaga que o cliente irá estacionar
		$vaga  = $this->parkAreaPonto['AreaPonto']['codigo'];

		// Salva uma tarifa
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		$parkTarifa = $this->dataGenerator->getParkTarifa();
		
		// Cria cliente com débito automático ativado
		$cliente = $this->dataGenerator->getCliente();
		$cliente['Cliente']['autorizar_debito'] = 1;
		$this->dataGenerator->saveCliente($cliente);

		// Cria registro na park_placa
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['placa']       = $placa;
		$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		$parkPlaca['Placa']['tipo']        = 'CARRO';
		// Salva registro
		$this->dataGenerator->savePlaca($parkPlaca);
		// Lança o veículo vencido já para poder emitir irregularidade
		$this->dataGenerator->lancaVeiculo($placa, $vaga, true);
		// Adiciona limite 
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 1000);
		// Popula dados da requisição da irregularidade
		$this->data['placa'] 					= $placa;
        $this->data['codigo_autuacao']  		= 0;
        $this->data['motivo_irregularidade']  	= 'VENCIDO';
        $this->data['vaga'] 					= $vaga;
        $this->data['area_id'] 					= $this->dataGenerator->areaId;

        // Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		
		$this->assertNotNull($this->vars['data']['ticket']);
		$this->assertEquals('DEBITO_AUTOMATICO', $this->vars['data']['ticket']['tipo_ticket']);
		// Testa se existe o saldo o cliente
		$this->assertTrue(!empty($this->vars['data']['cliente']), 'A api não retornou o cliente.');
		// Testa se existe o saldo atual do cliente na resposta
		$this->assertTrue(!empty($this->vars['data']['cliente']['saldo_atual']), 'A api não retornou o cliente.');
		// Testa se existe o saldo anterior do cliente na resposta
		$this->assertTrue(!empty($this->vars['data']['cliente']['saldo_anterior']), 'A api não retornou o cliente.');
		// Testa o nome do cliente
		$this->assertTrue(!empty($this->vars['data']['cliente']['nome']), 'A api não retornou o nome.');
		// E daí testa o cpf/cnpj
		$this->assertTrue(!empty($this->vars['data']['cliente']['cpf_cnpj']), 'A api não retornou o cpf_cnpj.');

		// Valida dados na park_historico
		$newParkHistorico = $this->Historico->findByPlaca($placa);
		// Valida se encontrou historico
		$this->assertNotNull($newParkHistorico);
		// Valida se o campo pago_ate foi preenchido
		$this->assertNotNull($newParkHistorico['Historico']['pago_ate']);
		// Valida se o equipamento continua como lançado
		$this->assertEquals('LANCADO', $newParkHistorico['Historico']['situacao']);
		// Valida se o tipo do veiculo não foi alterado
		$this->assertEquals('CARRO', $newParkHistorico['Historico']['veiculo']);

		// Valida dados na park_ticket
		$parkTicket = $this->Ticket->findByPlaca($placa);
		// Valida se encontrou registro
		$this->assertNotNull($parkTicket);
		// Valida situacao do ticket
		$this->assertEquals('PAGO', $parkTicket['Ticket']['situacao']);
		// Valida o valor do ticket
		$this->assertEquals($parkTarifa['ParkTarifa']['valor'], $parkTicket['Ticket']['valor']);
		// Valida o tipo do ticket que deve ser UTILIZACAO ao inves de IRREGULARIDADE
		$this->assertEquals('UTILIZACAO', $parkTicket['Ticket']['tipo']);
		// Valida forma de pagamento do ticket
		$this->assertEquals('DEBITO_AUTOMATICO', $parkTicket['Ticket']['forma_pagamento']);
		// Valida campo que indica se o ticket foi gerado por débito automatico
		$this->assertEquals(1, $parkTicket['Ticket']['debito_automatico']);
		// Cálcula diferençal entre data inicio e data fim
		$dataInicio = new DateTime($parkTicket['Ticket']['data_inicio']);
		$dataFim = new DateTime($parkTicket['Ticket']['data_fim']);
		$intervaloMinutos = $dataInicio->diff($dataFim);
		$intervaloMinutos = $intervaloMinutos->i;
		// Valida se o intervalo é o mesmo que a da tarifa utilizada
		$this->assertEquals($parkTarifa['ParkTarifa']['minutos'], $intervaloMinutos);
		// Valida se o intervalo calculado é o mesmo que o preenchido no próprio campo da park_ticket
		$this->assertEquals($intervaloMinutos, $parkTicket['Ticket']['tempo_tarifa']);

		// Valida dados do limite
		$limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		// Valida se encontrou registro
		$this->assertNotNull($limite);
		// Valida se o campo de utilização do saldo pré está preenchido com o valor da tarifa utilizada no débito automático
		$this->assertEquals($parkTarifa['ParkTarifa']['valor'] * -1, $limite['Limite']['pre_utilizado']);
	}// End Method 'testVerificacaoComSaldoEDaAtivo'


	/**
	 * Testa lançamento de irregularidade com DA ativo e com uma irregularidade já vencida.
	 */
	public function testDAComIrregularidadeVencida(){
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		// 
		$placa = $this->dataGenerator->randomPlaca();
		// Atualiza o preço para não ter tolerancia
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('tempo_livre', 0);
		// Cria cliente com DA
		$cliente = $this->dataGenerator->getCliente();
		$cliente['Cliente']['autorizar_debito'] = 1;
		$this->dataGenerator->saveCliente($cliente);
		// Concede limite pré ao cliente
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 10000);
		// Vincula placa ao cliente
		$this->dataGenerator->savePlaca(array('Placa' => array('placa' => $placa)));
		// Ativa configuração na área
		$this->Area->id = $this->dataGenerator->areaId;
		$this->Area->saveField('bloquear_compra_irregularidade_pendente', 1);
		$this->Area->saveField('numero_irregularidade_veiculo_advertido', 1);
		// Cria uma irregularidade não paga
		$ticketIrregularidade = $this->dataGenerator->getTicket();
		$ticketIrregularidade['Ticket']['placa']                 = $placa;
		$ticketIrregularidade['Ticket']['situacao']              = 'AGUARDANDO';
		$ticketIrregularidade['Ticket']['tipo']                  =  'IRREGULARIDADE';
		$ticketIrregularidade['Ticket']['motivo_irregularidade'] =  'TICKET_INCOMPATIVEL';
		$this->dataGenerator->saveTicket($ticketIrregularidade);
		// Estaciona o veículo
		$this->dataGenerator->lancaVeiculo($placa, 0, true);
		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
		// Seta os valores para os parâmetros da classe
		$this->data['placa'] 					= $placa;
		$this->data['codigo_autuacao']  		= 0;
		$this->data['motivo_irregularidade']  	= 'VENCIDO';
		$this->data['tipo_veiculo'] 			= 'CARRO';
		$this->data['park_marca_id'] 			= null;
		$this->data['park_modelo_id'] 			= null;
		$this->data['park_cor_id'] 				= null;
		$this->data['area_id']					= $this->dataGenerator->areaId;
		$this->data['vaga']						= 0;
		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'POST', $this->data);
		// Busca se a nova irregularidade foi criada
		$tickets = $this->Ticket->findByMotivoIrregularidade('VENCIDO');
		// Valida se encontrou a irregularidade
		$this->assertNotEmpty($tickets);
		// Valida o saldo do cliente
		$limiteCliente = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
		$this->assertNotNull($limiteCliente);
		$this->assertEquals(10000, ($limiteCliente['Limite']['pre_creditado'] + $limiteCliente['Limite']['pre_utilizado']));
	}// End Method 'testDAComIrregularidadeVencida'
}// End Class