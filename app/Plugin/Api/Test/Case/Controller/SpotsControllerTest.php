<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe responsável por efetuar testes da classe de Vagas
 */
class SpotsControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Area',
		'Parking.AreaPonto',
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
	private $URL       = '/api/spots';
	
	private $vaga;

	private $nVagas;

	/**
	 * Metódo que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria Registros necessários para teste
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveParkTarifa();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea(array(
			'Area' => array(
				'lista_fiscalizacao' => 'COM_IRREGULARIDADE_NAO_PAGA', 
				'bloquear_compra_apos_irregularidade' => 0,
				'irregularidade_vigencia_imediata' => 1
		)));
		$this->dataGenerator->saveSetor();
		
		//cria vagas
		$this->nVagas = rand(10, 20);
		for ($i = 1; $i < $this->nVagas; $i++) {

			$sensor = $this->dataGenerator->getSensor();
			$sensor['Sensor']['vaga_ocupada'] = 1;
			$this->dataGenerator->saveSensor($sensor);

			$this->vaga = $this->dataGenerator->getAreaPonto();
			$this->vaga['AreaPonto']['codigo'] = $i;
			$this->vaga['AreaPonto']['sensor_id'] = $i;
			$this->dataGenerator->saveAreaPonto($this->vaga);
		}
		
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
		$this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890' ) ));
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => NULL)));

		//cria historico
		$nHistorico = 10 * $this->nVagas;
		for ($i = 0; $i < $nHistorico; $i++) {
			$historico = $this->dataGenerator->getHistorico();
			$historico['Historico']['placa'] = $this->dataGenerator->randomPlaca();
			$historico['Historico']['vaga'] = rand(1, $this->nVagas -1);
			$historico['Historico']['pago_ate'] = date('Y-m-d H:i:s', (time() + (60 * 10)));
			$historico['Historico']['removido_em'] = NULL;
			$historico['Historico']['tolerancia_ate'] = date('Y-m-d H:i:s');
			$this->dataGenerator->saveHistorico($historico);
		}

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}

	/**
	 * Verifica se depois que o veiculo estiver como excedido e for lançada uma irregularidade ele retorna como irregular.
	 */
	public function testExcedidoIrregular() {		
		// Placa utilizada nos testes
		$placa = 'GOM-3535';
		// Deleta todas as vagas 
		$vaga = 1;
		$this->AreaPonto->deleteAll(array('AreaPonto.id >= ' => $vaga), false);
		// Deleta todos os park_historicos
		$this->Historico->deleteAll(array('Historico.id >= ' => $vaga), false);
		// Vaga a ser utilizado no teste
		$this->dataGenerator->saveAreaPonto(array('AreaPonto' => array('codigo' => $vaga)));

		// Adiciona setor id no request data
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;

		// Salva dados
		$preco = $this->dataGenerator->getPreco();
		$preco['Preco']['tempo_livre'] = 0;
		$preco['Preco']['tempo_max_periodos'] = 2;
		$preco['Preco']['ignorar_tempo_max_periodo_compra'] = 1;
		$preco['Preco']['inserido_veiculo_excedido'] = 1;
		$this->dataGenerator->savePreco($preco);

		$precoId = $this->dataGenerator->precoId;
		$this->Cobranca->updateAll(
			array('Cobranca.preco_id_carro' => $precoId, 'Cobranca.preco_id_moto' => $precoId, 'preco_id_vaga_farmacia' => $precoId , 'preco_id_vaga_idoso' => $precoId, 'preco_id_irregularidade_vencido' => $precoId, 'preco_id_irregularidade_sem_ticket' => $precoId, 'preco_id_irregularidade_fora_vaga' => $precoId, 'preco_id_irregularidade_ticket_incompativel' => $precoId, 'preco_id_irregularidade_permanencia_excedida' => $precoId),
			array('Cobranca.id >=' => 1)
		);

		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();

		$parkTarifa = $this->dataGenerator->getParkTarifa();
		$parkTarifa['ParkTarifa']['valor'] = 2.00;
		$parkTarifa['ParkTarifa']['minutos'] = 3;
		$this->dataGenerator->saveParkTarifa($parkTarifa);

		// Vende periodo Dinheiro
		$this->dataGenerator->venderTicketEstacionamentoDinheiro('2.00', $placa);
		//Verifica o veículo
		$this->dataGenerator->verificaVeiculo($placa, $vaga = 1);
		
		// Lança uma irregularidade pro veiculo
		$this->dataGenerator->emiteIrregularidade($placa, 1, 'PERMANENCIA_EXCEDIDA');
		$ticketIrregularidade = $this->Ticket->findByMotivoIrregularidade('PERMANENCIA_EXCEDIDA');
		$this->assertNotEmpty($ticketIrregularidade);

		//Faz o testAction
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		$this->assertNotNull($this->vars['data']['vagas']);								
		$this->assertEquals(VEICULO_STATUS_IRREGULAR, $this->vars['data']['vagas'][1]['veiculos'][1]['status']);
	}
	
	/**
	 * Testa configuração do veículo excedido a partir do momento da sua verificação ao invés da compra.
	 * Configuração 'inserido_veiculo_excedido' na 'park_preco'
	 */
	public function testVeiculoExcedidoPelaVerificacao(){
		// Deleta todas as vagas
		$vaga = 1;
		$this->AreaPonto->deleteAll(array('AreaPonto.id >= ' => $vaga), false);
		// Deleta todos os park_historicos
		$this->Historico->deleteAll(array('Historico.id >= ' => $vaga), false);
		// Vaga a ser utilizado no teste
		$this->dataGenerator->saveAreaPonto(array('AreaPonto' => array('codigo' => $vaga)));
		
		// Variável que recebe a placa do veículo de teste
		$placa = 'TES-6969';
		// Atualiza configuração da tabela park_preco
		$parkPreco = $this->Preco->find('first');
		$parkPreco['Preco']['inserido_veiculo_excedido']        = 1;
		$parkPreco['Preco']['ignorar_tempo_max_periodo_compra'] = 1;
		$parkPreco['Preco']['tempo_max_periodos']               = 1;
		$parkPreco['Preco']['tempo_livre']                      = 0;
		$this->Preco->save($parkPreco);
		// Lança um veículo
		$this->dataGenerator->verificaVeiculo($placa, 1);
		// Volta o tempo do inserido_ate para estar excedido
		$parkHistorico = $this->Historico->findByPlaca($placa);
		$parkHistorico['Historico']['inserido_em'] = $this->dataGenerator->getDateTime('-10 minutes');
		$this->Historico->save($parkHistorico);
		// Popula parâmetro setor
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Busca algum existência de um ticket com essa placa
		$listTickets = $this->Ticket->findByPlaca($placa);
		// Valida se o status do veículo é excedido mesmo não tendo ticket algum, indicando que foi pela verificação
		$this->assertEmpty($listTickets);
		// Extrai os veículos retornados
		$listVehicles = $this->vars['data']['vagas'][1]['veiculos'];
		// Valida quantidade de veículo
		$this->assertEquals(1, count($listVehicles));
		// Extrai o veículo encontrado
		$veiculo = $listVehicles[0];
		// Valida se é diferente de nulo
		$this->assertNotNull($veiculo);
		// Valida se o status é de excedido
		$this->assertEquals(5, $veiculo['status']);
	}// End Method 'testVeiculoExcedidoPelaVerificacao'


	
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action add, pois na classe só deverá tratar a index
	*/
	public function testaddError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End method 'test_addError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, pois na classe só deverá tratar a index
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
	}// End method 'test_ViewError'
	
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, pois na classe só deverá tratar a index
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
	}// End method 'test_EditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, pois na classe só deverá tratar a index
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
	}// End method 'test_DeleteError'

	/**
	 * Método que efetua o teste se a lista de vagas foi recebido corretamente.
	 */
	public function testListaVagas() {
		// Popula parâmetro setor
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;
		// Acessa o link da API
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotEmpty($this->vars['data']['vagas'], 'Vagas');
		
		
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']], 'Vaga 0');
		
		//campos de uma vaga
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['id'], 'Vaga 0 id');
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['tipo_vaga'], 'Vaga 0 tipo_vaga');
		$this->assertTrue(is_array($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['veiculos']), 'Vaga 0 veiculos');
		
	}// End test_ListaVagas

	/**
	 * Método que efetua o teste se foi passado o setor
	 */
	public function testEmptySetor() {
		// Acessa o link da API, sem o setor
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Setor inválido'
		);
	}

	/**
	 * Método que efetua o teste se a lista de vagas está com os sensores e foi recebido corretamente.
	 */
	public function testListaVagasSensores() {
		// Popula parâmetro setor.
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;

		// Acessa o link da API.
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);

		// Valida se houve retorno da classe testada.
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		
		// Valida todos se todos os campos do retorno estão preenchidos.
		$this->assertNotEmpty($this->vars['data']['vagas'], 'Vagas');
		
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']], 'Vaga 0');
		
		//campos de uma vaga.
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['id'], 'Vaga 0 id');
		$this->assertNotEmpty($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['tipo_vaga'], 'Vaga 0 tipo_vaga');
		$this->assertTrue(is_array($this->vars['data']['vagas'][$this->vaga['AreaPonto']['codigo']]['veiculos']), 'Vaga 0 veiculos');
		// Valida se os sensores estão ocupados.
		for($x = 1; $x < $this->nVagas; $x ++) {
			$this->assertNotNull($this->vars['data']['vagas'][$x]['sensor_vaga_ocupada'], 'Nenhum valor do sensor foi retornado');
		}
		
		
	}// End test_ListaVagas

	/**
	 * Testa a lista com veículos excedidos
	 */
	public function testStatusListaVagas() {
		// Remove todos os veículos da park_historico
		$this->dataGenerator->query('TRUNCATE park_historico');
		// Altera a configuração da área para permitir compra de períodos além do máximo
		$this->Preco->id = $this->dataGenerator->precoId;
		$this->Preco->saveField('ignorar_tempo_max_periodo_compra', true);

		// Recupera todas as vagas criadas
		$this->spots = $this->AreaPonto->find('all');

		// Array de placas indexadas pelo status
		$plates = array(
			VEICULO_STATUS_EM_TOLERANCIA        => 'AAA-1111', 
			VEICULO_STATUS_PAGO                 => 'AAA-2222', 
			VEICULO_STATUS_IRREGULAR            => 'AAA-3333', 
			VEICULO_STATUS_VENCIDO              => 'AAA-4444', 
			VEICULO_STATUS_PERMANENCIA_EXCEDIDA => 'AAA-5555'
		);

		// Veículo em tolerância
		$this->dataGenerator->saveHistorico(
			array(
    			'Historico' => array(
    					'placa' 				=> $plates[VEICULO_STATUS_EM_TOLERANCIA],
    					'inserido_em' 			=> $this->dataGenerator->getDateTime('-10 minutes'),
    					'pago_ate' 				=> null,
    					'tolerancia_ate' 		=> $this->dataGenerator->getDateTime('+5 minutes'),
    					'removido_em' 			=> null,
    					'ultima_verificacao' 	=> null,
    					'periodos' 				=> 1,
    					'situacao' 				=> 'LANCADO',
    					'vaga' 					=> $this->getSpotCode(),
    					'irregularidades' 		=> '0',
    				)
    			)
		);

		// Veículo pago
		$this->dataGenerator->saveHistorico(
			array(
    			'Historico' => array(
    					'placa' 				=> $plates[VEICULO_STATUS_PAGO],
    					'inserido_em' 			=> $this->dataGenerator->getDateTime('-10 minutes'),
    					'pago_ate' 				=> $this->dataGenerator->getDateTime('+1 minutes'),
    					'tolerancia_ate' 		=> $this->dataGenerator->getDateTime('-8 minutes'),
    					'removido_em' 			=> null,
    					'ultima_verificacao' 	=> null,
    					'periodos' 				=> 1,
    					'situacao' 				=> 'LANCADO',
    					'vaga' 					=> $this->getSpotCode(),
    					'irregularidades' 		=> '0',
    				)
    			)
		);

		// Veículo irregular
		$this->dataGenerator->emiteIrregularidade($plates[VEICULO_STATUS_IRREGULAR], $this->getSpotCode(), 'FORA_DA_VAGA');
		// Volta tempo do historico para condizer com o registro do ticket
		$parkHistoricoIrregular = $this->Historico->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'placa' => $plates[VEICULO_STATUS_IRREGULAR]
			)
		));
		// Atualiza o tempo criado_em
		$this->Historico->id = $parkHistoricoIrregular['Historico']['id'];
		$this->Historico->saveField('criado_em', $this->dataGenerator->getDateTime('-10 minutes'));

		// Volta o tempo da irregularidade para ficar vigente no teste
		$parkTicketIrregular = $this->Ticket->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'placa' => $plates[VEICULO_STATUS_IRREGULAR]
			)
		));
		// Atualiza o tempo criado_em
		$this->Ticket->id = $parkTicketIrregular['Ticket']['id'];
		$this->Ticket->saveField('data_inicio', $this->dataGenerator->getDateTime('-30 minutes'));

		// Veículo vencido
		$this->dataGenerator->saveHistorico(
			array(
    			'Historico' => array(
					'placa' 				=> $plates[VEICULO_STATUS_VENCIDO],
					'inserido_em' 			=> $this->dataGenerator->getDateTime('-10 minutes'),
					'pago_ate' 				=> null,
					'tolerancia_ate' 		=> $this->dataGenerator->getDateTime('-8 minutes'),
					'removido_em' 			=> null,
					'ultima_verificacao' 	=> null,
					'periodos' 				=> 1,
					'situacao' 				=> 'LANCADO',
					'vaga' 					=> $this->getSpotCode(),
					'irregularidades' 		=> '0',
				)
			)
		);

		// Veículo excedido
		$this->dataGenerator->saveHistorico(
			array(
    			'Historico' => array(
    					'placa' 				=> $plates[VEICULO_STATUS_PERMANENCIA_EXCEDIDA],
    					'inserido_em' 			=> $this->dataGenerator->getDateTime('-5 hours'), // Conf do preço 'tempo máximo períodos': 240min
    					'pago_ate' 				=> $this->dataGenerator->getDateTime('+1 hours'),
    					'tolerancia_ate' 		=> $this->dataGenerator->getDateTime('-5 hours'),
    					'removido_em' 			=> null,
    					'ultima_verificacao' 	=> null,
    					'periodos' 				=> 1,
    					'situacao' 				=> 'LANCADO',
    					'vaga' 					=> $this->getSpotCode(),
    					'irregularidades' 		=> '0',
    				)
    			)
		);

		$this->data['park_setor_id'] = $this->dataGenerator->setorId;
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);

		// Remove do retorno as vagas sem veículo estacionado
		$historicos = array_filter($this->vars['data']['vagas'], function($var){
			return !empty($var['veiculos']);
		});

		// Reindexa o array
		$historicos = array_values($historicos);

		$this->assertTrue(count($historicos) == 5, 'Número incorreto de veículos retornado.');

		// Agrupa os veículos pelo status
		$vehicles = array();
		for ($i = 0; $i < count($historicos); $i++) {
			$vehicles[$historicos[$i]['veiculos'][0]['status']][] = $historicos[$i]['veiculos'][0];
		}

		// Testa se as placas e status da lista retornada
		foreach ($plates as $key => $value) {
			// Testa se existe algum veículo com o status 
			$this->assertTrue(array_key_exists($key, $vehicles), "Nenhum veículo com o status $key.");
			// Testa se mais de um veículo recebeu o status (só pode haver um)
			$this->assertTrue(count($vehicles[$key]) == 1, "Mais de um veículo com status $key.");
			// Testa se é o veículo correto com o status em questão
			$this->assertEquals($vehicles[$key][0]['placa'], $plates[$key], "Veículo com status $key está incorreto.");
		}

	}

	/**
	 * Testa o retorno da lista com um veículo incompatível
	 */
	public function testListaVeiculoIncompativel() {
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;
		// Cria uma vaga do tipo moto
		$vagaMoto = $this->dataGenerator->getAreaPonto();
		$vagaMoto['AreaPonto']['tipo_vaga'] = 'MOTO';
		$this->dataGenerator->saveAreaPonto($vagaMoto);
		// Seta a placa da moto
		$placaMoto = 'MOT-0000';
		// Verifica um carro nessa vaga
		$this->dataGenerator->verificaVeiculo($placaMoto, $vagaMoto['AreaPonto']['codigo']);

		// Acessa a api
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
	
		$this->assertEquals($this->vars['data']['vagas'][$vagaMoto['AreaPonto']['codigo']]['veiculos'][0]['placa'], $placaMoto, 'Placa incompatível está incompatível (ieié)');
		$this->assertTrue($this->vars['data']['vagas'][$vagaMoto['AreaPonto']['codigo']]['veiculos'][0]['incompativel'], 'Veículo não está incompatível');
	}

	/**
	 * Retorna um código de vaga do array de vagas disponíveis
	 * e remove essa vaga do array, para evitar que seja utilizada
	 * novamente
	 */
	private function getSpotCode() {
		// Índice randômico
		$spotIndex = rand(0, count($this->spots) - 1);
		// Recupera o código dessa vaga
		$spot = $this->spots[$spotIndex]['AreaPonto']['codigo'];
		// Remove a vaga do array
		unset($this->spots[$spotIndex]);
		// Reindexa o array
		$this->spots = array_values($this->spots);
		return $spot;
	}

	/**
	 * Teste que verifica se o veículo está irregular a partir do horário da irregularidade do ticket e não pelo campo 'irregularidades'
	 * da tabela park_historico
	 */
	public function testVeiculoIrregular_Irregular(){
		// Placa a ser utilizado no teste
		$placa = 'AAA-1234';
		// Deleta todas as vagas 
		$vaga = 1;
		$this->AreaPonto->deleteAll(array('AreaPonto.id >= ' => $vaga), false);
		// Deleta todos os park_historicos
		$this->Historico->deleteAll(array('Historico.id >= ' => $vaga), false);
		// Vaga a ser utilizado no teste
		$this->dataGenerator->saveAreaPonto(array('AreaPonto' => array('codigo' => $vaga)));
		// Adiciona setor id no request data
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;	
		// Data Atual
		$dateNow = $this->dataGenerator->getDateTime();
		// Salva preço com configurações para teste
		$parkPreco = $this->dataGenerator->getPreco();
		$parkPreco['Preco']['cobranca_turnos']                  = 0;
		$parkPreco['Preco']['cobranca_periodos']                = 1; 
		$parkPreco['Preco']['turno_valor']                      = 0.00;
		$parkPreco['Preco']['excedente_periodo_minutos']        = 1;
		$parkPreco['Preco']['tempo_irregularidade']             = 1;
		$parkPreco['Preco']['valor_irregularidade']             = 0.00;
		$parkPreco['Preco']['irregularidade']                   = 'AVISO';
		$parkPreco['Preco']['ignorar_tempo_max_periodo_compra'] = 1;
		$parkPreco['Preco']['nome']                             = 'Vei.Irreg.';
		$this->dataGenerator->savePreco($parkPreco);
		// Cria comissao
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		// Cria tarifa
		$this->dataGenerator->saveTarifa();
		// Cria park_tarifa
		$this->dataGenerator->saveParkTarifa();
		// Lançar irregularidade
		$this->dataGenerator->emiteIrregularidade($placa, $vaga, 'FORA_DA_VAGA');
		// Atualiza data_inicio e data_fim do ticket para estar entre o NOW();
		$this->atualizaParkHistoricoEParkTicket($this->dataGenerator->getDateTime('-10 minutes'),$this->dataGenerator->getDateTime('+ 10 minutes'), $placa);
		// Atualiza o campo 'irregularidades' da park_historico para verificar 
		$this->Historico->updateAll(
			array('Historico.irregularidades' => 0),
			array('Historico.id > '           => 1)
		);

		// Acessa a api
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida retorno PARA SITUAÇÃO PAGO
		$this->assertNotNull($this->vars['data']['vagas']);
		$this->assertNotNull($this->vars['data']['vagas'][1]['veiculos']);
		$this->assertEquals(VEICULO_STATUS_IRREGULAR, $this->vars['data']['vagas'][1]['veiculos'][0]['status']);
	}// End Method 'testVeiculoIrregular_Irregular'

	/**
	 * Método auxiliar para atualizar registros das tabelas park_ticket e park_historico para buscar lista de vagas
	 * no momento a ser testado
	 */
	private function atualizaParkHistoricoEParkTicket($parkTicketDataInicio, $parkTicketDataFim, $placa){

		$this->Ticket->recursive = -1;
		$parkTicket = $this->Ticket->find('all', 
			array(
				'conditions' => array('Ticket.placa' => $placa),
				'order' => array('Ticket.id' => 'desc'),
				'limit' => 1
		));

		$this->Ticket->updateAll(
			array(
				'Ticket.data_inicio' => "'$parkTicketDataInicio'",
				'Ticket.data_fim' => "'$parkTicketDataFim'" 
			),
			array(
				'Ticket.id' => $parkTicket[0]['Ticket']['id']
			)
		);
	}// End Method 'atualizaParkHistoricoEParkTicket'

	/**
	 * Teste para validar novo tratamento do veículo excedido a partir de uma verificação saindo da vaga 0 para outra vaga
	 */
	public function testVeiculoExcedido() {
		// Placa utilizada nos testes
		$placa = 'AAA-2525';
		// Deleta todas as vagas 
		$vaga = 1;
		$this->AreaPonto->deleteAll(array('AreaPonto.id >= ' => $vaga), false);
		// Deleta todos os park_historicos
		$this->Historico->deleteAll(array('Historico.id >= ' => $vaga), false);
		// Vaga a ser utilizado no teste
		$this->dataGenerator->saveAreaPonto(array('AreaPonto' => array('codigo' => $vaga)));

		// Adiciona setor id no request data
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;	

		// Salva dados
		$preco = $this->dataGenerator->getPreco();
		$preco['Preco']['tempo_livre'] = 0;
		$preco['Preco']['tempo_max_periodos'] = 5;
		$preco['Preco']['ignorar_tempo_max_periodo_compra'] = 1;
		$this->dataGenerator->savePreco($preco);

		$precoId = $this->dataGenerator->precoId;
		$this->Cobranca->updateAll(
			array('Cobranca.preco_id_carro' => $precoId, 'Cobranca.preco_id_moto' => $precoId, 'preco_id_vaga_farmacia' => $precoId , 'preco_id_vaga_idoso' => $precoId, 'preco_id_irregularidade_vencido' => $precoId, 'preco_id_irregularidade_sem_ticket' => $precoId, 'preco_id_irregularidade_fora_vaga' => $precoId, 'preco_id_irregularidade_ticket_incompativel' => $precoId, 'preco_id_irregularidade_permanencia_excedida' => $precoId),
			array('Cobranca.id >=' => 1)
		);

		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();

		$parkTarifa = $this->dataGenerator->getParkTarifa();
		$parkTarifa['ParkTarifa']['valor'] = 2.00;
		$parkTarifa['ParkTarifa']['minutos'] = 20;
		$this->dataGenerator->saveParkTarifa($parkTarifa);

		//Faz a compra de período.
		$this->dataGenerator->saveCliente();
		// Dá saldo para usuário
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, '10000.00');
		// Vende periodo CPF_CNPJ
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj('2.00', $placa, 0);
		//Verifica o veículo
		$this->dataGenerator->verificaVeiculo($placa, $vaga = 1);		

		// Valida retorno da função		
		$this->Historico->updateAll(
			array(
				'Historico.inserido_em' => "'" . $this->dataGenerator->getDateTime('-30 minutes') . "'",
				'Historico.tolerancia_ate' => "Historico.inserido_em",
				'Historico.ultima_troca_em' => "'" . $this->dataGenerator->getDateTime('-18 minutes') . "'"),
			array('Historico.placa' => $placa)
		);

		//Faz o testAction
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		$this->assertNotNull($this->vars['data']['vagas']);
		$this->assertEquals(VEICULO_STATUS_PERMANENCIA_EXCEDIDA, $this->vars['data']['vagas'][1]['veiculos'][0]['status']);
	}// End Method 'testVeiculoExcedido'

	/**
	 * Valida se um veículo lançado em uma vaga isenta, vai ficar excedido com o preço do tipo do veículo
	 */
	public function testLancadoVagaIsentaExcedido() {
		$this->AreaPonto->deleteAll(array('AreaPonto.id <> ' => 0), false);
		// Deleta todos os park_historicos
		$this->Historico->deleteAll(array('Historico.id <> ' => 0), false);


		// Placa utilizada nos testes
		$placa = 'AAA-2525';
		// Cria vaga de deficiente
		$vagaDeficiente = $this->dataGenerator->createDeficientSpot();

		// Extrai codigo da vaga
		$vaga = $vagaDeficiente['AreaPonto']['codigo'];


		// Adiciona setor id no request data
		$this->data['park_setor_id'] = $this->dataGenerator->setorId;	

		//Variável que controla o tempo para o veiculo ficar excedido. Um minuto a mais que o tempo maximo de periodos.
		// Serve para ficar excedido
		$tempoMaxPeriodos = 8;

		// Salva dados
		$preco = $this->dataGenerator->getPreco();
		$preco['Preco']['tempo_livre'] = 0;
		$preco['Preco']['tempo_max_periodos'] = 5;
		$preco['Preco']['ignorar_tempo_max_periodo_compra'] = 1;
		$this->dataGenerator->savePreco($preco);

		$precoId = $this->dataGenerator->precoId;
		$this->Cobranca->updateAll(
			array('Cobranca.preco_id_carro' => $precoId, 'Cobranca.preco_id_moto' => $precoId, 'preco_id_vaga_farmacia' => $precoId , 'preco_id_vaga_idoso' => $precoId, 'preco_id_irregularidade_vencido' => $precoId, 'preco_id_irregularidade_sem_ticket' => $precoId, 'preco_id_irregularidade_fora_vaga' => $precoId, 'preco_id_irregularidade_ticket_incompativel' => $precoId, 'preco_id_irregularidade_permanencia_excedida' => $precoId),
			array('Cobranca.id >=' => 1)
		);

		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();

		// Cria as tarifas
		$parkTarifa = $this->dataGenerator->getParkTarifa();
		$parkTarifa['ParkTarifa']['valor'] = 2.00;
		$parkTarifa['ParkTarifa']['minutos'] = 200;
		$this->dataGenerator->saveParkTarifa($parkTarifa);

		//Verifica o veículo
		$this->dataGenerator->verificaVeiculo($placa, $vaga);		

		// Busca dados do historico
		$parkHistorico = $this->Historico->findByPlaca($placa);
		// Cria variáveis com o próprio campo menos a variável de tempo máximo de periodos.
		$newInseridoEm    = $this->dataGenerator->getDateTime("-$tempoMaxPeriodos minutes", new DateTime($parkHistorico['Historico']['inserido_em']));
		$newPagoAte       = $this->dataGenerator->getDateTime("-$tempoMaxPeriodos minutes", new DateTime($parkHistorico['Historico']['pago_ate']));
		$newToleranciaAte = $this->dataGenerator->getDateTime("-$tempoMaxPeriodos minutes", new DateTime($parkHistorico['Historico']['tolerancia_ate']));
		// Atualiza registro do pago até, para simular o excedido
		$this->Historico->id = $parkHistorico['Historico']['id'];
		$this->Historico->set('inserido_em', $newInseridoEm);
		$this->Historico->set('pago_ate', $newPagoAte);
		$this->Historico->set('tolerancia_ate', $newToleranciaAte);
		$this->Historico->save();
		//Faz o testAction
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida se recebeu as vagas normalmente
		$this->assertNotNull($this->vars['data']['vagas']);
		// Valida se o veículo verificado está com status excedido
		$this->assertEquals(VEICULO_STATUS_PERMANENCIA_EXCEDIDA, $this->vars['data']['vagas'][$vaga]['veiculos'][0]['status']);
	}// End Method 'testVeiculoExcedido'
	
}// End Class