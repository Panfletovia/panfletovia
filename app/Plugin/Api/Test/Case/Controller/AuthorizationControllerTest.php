<?php
/**
 * Arquivo AuthorizationControllerTest.php
 */

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes do comando de autorização da API
 */
class AuthorizationControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Operador',
		'Parking.Area',
		'Parking.Preco',
		'Produto',
		'Parking.Cobranca',
		'Parking.Setor',
		'Parking.Servico',
		'Equipamento',
		'Recibo',
		'Posto',
		'Associado',
		'Configuracao',
		'Parking.ParkConfiguracao',
		'Pagamento'
	);

	// Variável que recebe os campos default das transações
	private $data      = NULL;
	// Variável que recebe a extensão que espera retornar as informações
	private $extension = '.json';
	// Variável que recebe a URL completa
	private $fullURL;
	// Variável que recebe a URL com ID para os testes de Operação Inválida
	private $idURL;	
	// Variavel que armazena o associado criado para os testes
	private $associado;
	// Variavel que armazena o operador criado para os testes
	private $operador;
	// Variavel que armazena a area criada para os testes
	private $area;
	// Variavel que armazena o setor criado para os testes
	private $setor;
	// Variavel que armazena a configuracao da rede para os testes
	private $configuracao;

	// Método para inicializar as variáveis de URL para serem utilizadas nos testes.
	function __construct(){
		parent::__construct();
		// Cria a url completa.
		$this->fullURL   = '/api/authorization' . $this->extension;
		// Cria a url com ID fictício.
		$this->idURL     = '/api/authorization/1' . $this->extension;	
	}

	/**
	 * Método que é executado antes de cada testes
	 */
	public function setUp() {
		parent::setUp();

		// Salva registros necessários para efetuar testes
		$this->associado = $this->dataGenerator->getAssociado();
		$this->dataGenerator->saveAssociado($this->associado);
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveParkConfiguracao();
		$this->area = $this->dataGenerator->getArea();
		$this->dataGenerator->saveArea($this->area);
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));		
		$this->setor = $this->dataGenerator->getSetor();
		$this->dataGenerator->saveSetor($this->setor);
		$this->operador = $this->dataGenerator->getOperador();
		$this->operador['Operador']['usuario'] = '1234567890';
		$this->operador['Operador']['senha'] = '1234567890';
		$this->dataGenerator->saveOperador($this->operador);

		// Pega a configuração da Rede 
		$this->configUtilizaSMS = $this->dataGenerator->getConfiguracao();
		$this->dataGenerator->saveConfiguracao($this->configUtilizaSMS);

		// Seta os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
		$this->data['target'] = DATECS;
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX, 
	* pois na classe só deverá tratar o ADD
	*/
	public function testIndexError() {
		try {
			$this->testAction($this->fullURL, array('method' => 'GET','data' => $this->data));
		} catch (Exception $e) {
			$this->assertEqual(get_class($e), 'NotImplementedException');
		}
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, 
	* pois na classe só deverá tratar o ADD
	*/
	public function testViewError(){
		$this->validateTestException(
			$this->idURL,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, 
	* pois na classe só deverá tratar o ADD
	*/
	public function testEditError(){
		$this->validateTestException(
			$this->idURL,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, 
	* pois na classe só deverá tratar o ADD
	*/
	public function testDeleteError(){
		$this->validateTestException(
			$this->idURL,
			'DELETE',
			$this->data,
			'NotImplementedException',
			''
		);
	}

	/**
	 * Testa acesso a API via POST, esperando exceção de "Forbidden" e 
	 * a mensagem que usuário ou password não foram recebidos
	 */
	public function testSemUser() {
		// Remove campo do array de envio
		unset($this->data['username']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'ApiException',
			'Usuário/Senha não recebidos'
		);
	}

	/**
	 * Testa acesso a API via POST, esperando exceção de "Forbidden" e 
	 * a mensagem que usuário ou password não foram recebidos
	 */
	public function testSemPassWord() {
		// Remove campo do array de envio
		unset($this->data['password']);

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'ApiException',
			'Usuário/Senha não recebidos'
		);
	}

	/**
	 * Testa acesso a API a partir de um equipamento sem área configurada
	 * 
	 * @deprecated Equipamento não precisa mais ser vinculado a uma área
	 */
	public function testEquipamentoSemArea() {
		
		$this->testAction($this->fullURL, array('method' => 'POST', 'data' => $this->data));
		
		$this->assertEqual(200, $this->controller->response->statusCode());

	}

	/**
	 * Testa acesso a API a partir de um equipamento sem associado
	 */
	public function testEquipamentoSemAssociado() {
		// Altera o associado do equipamento
		$this->alteraEquipamento(array('administrador_id' => NULL));

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'ApiException',
			'Associado não encontrado'
		);
	}

	/**
	 * Testa acesso a API a partir de um equipamento que não está ativo
	 */
	public function testEquipamentoSituacaoDiferenteAtivo() {
		// Cria array para setar randômicamente a situação do veículo
		$situacoes = array(0 => 'BLOQUEADO',1 => 'INATIVO',2 => 'DESCARTADO');
		// Atualiza registro do equipamento na base de dados
		$this->alteraEquipamento(array('situacao' => $situacoes[rand(0, (count($situacoes) - 1))]));

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'ApiException',
			'Equipamento inativo'
		);
	}

	/**
	 * Testa acesso a API sem um operador cadastrado na base de dados
	 */
	public function testSemOperador() {
		// Altera username do operador
		$this->Operador->clear();
		$this->Operador->id = $this->dataGenerator->operadorId;
		$this->Operador->set(array('usuario' => 'foo'));
		$this->Operador->save();
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'ApiException',
			'Operador não encontrado'
		);
	}

	/**
	 * Testa acesso a API validando caso já exista um serviço aberto, se o mesmo será encerrado.
	 * Este teste força a primeira exceção após a atualização do registro para serviço fechado.
	 * Após receber a exceção efetua SELECT na base pelo ID do registro do serviço inserido e 
	 * verifica o campo 'data_fechamento' para indicar que realmente fechou o serviço.
	 */
	public function testFecharServicosAbertos() {
		// Cria um serviço antes de enviar a requisição, para validar se o mesmo  estará fechado
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => NULL)));
		
		$this->testAction($this->fullURL, array('method' => 'POST', 'data' => $this->data));

		// Busca informações do serviço anterior após a chamada do comando
		$parkServico = $this->Servico->findById($this->dataGenerator->servicoId);
		// Valida se encontrou o serviço anterior
		$this->assertNotEmpty($parkServico, 'Não encontrou serviço inserido anteriormente.');
		// Valida se o serviço está com o campo 'data_fechamento' preenchido, indicando que está fechado.
		$this->assertNotNull($parkServico['Servico']['data_fechamento'], 'Serviço anterior não foi fechado');
		// Valida se o campo 'fechamento_forcado' está marcado, indicando que o mesmo foi fechado forçadamente.
		$this->assertEquals($parkServico['Servico']['fechamento_forcado'], 1 ,'Serviço anterior não foi fechado forçadamente');
	}// End 'testFecharServicosAbertos'
	 
	

	/**
	 * Testa o acesso a API verificando se o serviço novo foi criado corretamente.
	 * 
	 */
	public function testCriarServicoNovo() {
		// Acessa o link da API
		$this->sendRequest($this->fullURL, 'POST', $this->data);

		// Busca informações do serviço anterior após a chamada do comando
		$parkServico = $this->Servico->find('first', array('conditions' => array(
			'equipamento_id' => $this->dataGenerator->equipamentoId
		)));

		// Validações referente ao serviço criado.
		$this->assertNotEmpty($parkServico, 'Serviço não foi inserido!');
		$this->assertNull    ($parkServico['Servico']['data_fechamento'], 'Serviço inserido está fechado');
		$this->assertEquals  ($parkServico['Servico']['sequencia'], 1, 'Sequência do serviço diferente de UM!');
		$this->assertEquals  ($parkServico['Servico']['fechamento_forcado'], 0 , 'Serviço foi fechado forçadamente');
		$this->assertEquals  ($parkServico['Servico']['equipamento_id'], $this->dataGenerator->equipamentoId, 'Equipamento do serviço diferente do equipamento criado');
		$this->assertEquals  ($parkServico['Servico']['operador_id'], $this->dataGenerator->operadorId, 'Operador do serviço é diferente do operador gerado');
		$this->assertEquals  ($parkServico['Servico']['administrador_id'], ADMIN_PARKING_ID, 'Associado do serviço diferente do associado DEFAULT.' );
		$this->assertNull    ($parkServico['Servico']['data_acerto'], 'Data Acerto é diferente de NULL.');
		$this->assertEquals  ($parkServico['Servico']['valor_recebido'], '0.00', 'Valor recebido diferente do DEFAULT.');
		$this->assertTrue	 (isset($this->vars['data']['confirmar_placa']));
	}// End 'testCriarServicoNovo'

	/**
	 * Testa o acesso a API verificando se existem cobranças vinculadas a área do equipamento.
	 * 
	 * @deprecated Authorization não precisa mais validar área e nem cobrança
	 */
	public function testCobrancaVinculada() {
		$this->testAction($this->fullURL, array('method' => 'POST', 'data' => $this->data));
		
		$this->assertEqual(200, $this->controller->response->statusCode());
	}// End 'testCobrancaVinculada'

	/**
	 * Testa o acesso a API verificando se existe pelo menos um setor vinculado a área
	 * 
	 * @deprecated 
	 */
	public function testSemParkSetor() {
		// Deleta a cobrança vinculada a área para forçar exceção
		$this->Setor->delete($this->dataGenerator->setorId);
		
		$this->testAction($this->fullURL, array('method' => 'POST', 'data' => $this->data));
		
		$this->assertEqual(200, $this->controller->response->statusCode());
	}// End 'testCobrancaVinculada'


	/**
	 * Método para efetuar processamento do controller sem esperar erro
	 * @return [type] [description]
	 */
	public function testRetornoEsperado() {

		$valorMinimoRecargaCartao = 15;
		$this->Configuracao->updateAll(array('valor' => $valorMinimoRecargaCartao), array('chave' => 'VALOR_MINIMO_RECARGA_CARTAO'));

		$pagamentoCielo = $this->Pagamento->findByForma('CIELO');

		// Acessa o link da API
		$this->sendRequest($this->fullURL, 'POST', $this->data);
		
		// Valida se houve retorno da classe testada
		$this->assertNotNull(	$this->vars['data'], 'Nenhum dado foi retornado');

		// Valida se todos os campos do retorno estão preenchidos
		$this->assertNotNull(	$this->vars['data']['operador'], 											'Campo operador	de retorno da função está null');
		$this->assertNotEmpty(	$this->vars['data']['operador']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_4'],								(bool)$this->operador['Operador']['direito_4']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_5'],								(bool)$this->operador['Operador']['direito_5']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_6'],								(bool)$this->operador['Operador']['direito_6']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_7'],								(bool)$this->operador['Operador']['direito_7']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_8'],								(bool)$this->operador['Operador']['direito_8']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_11'],								(bool)$this->operador['Operador']['direito_11']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_12'],								(bool)$this->operador['Operador']['direito_12']);
		$this->assertEquals(	$this->vars['data']['operador']['direito_13'],								(bool)$this->operador['Operador']['direito_13']);
		
		$this->assertNotNull(	$this->vars['data']['areas'], 												'Campo area	de retorno da função está null');
		$this->assertNotEmpty(	$this->vars['data']['areas']);
		$this->assertNotEmpty(	$this->vars['data']['areas'][0]);
		$this->assertEqual(		$this->vars['data']['areas'][0]['id'], 										$this->dataGenerator->areaId);
		$this->assertEqual(		$this->vars['data']['areas'][0]['nome'], 									$this->area['Area']['nome']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['informar_marca_modelo_irregularidade'],	(bool)$this->area['Area']['informar_marca_modelo_irregularidade']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['informar_cor_irregularidade'], 			(bool)$this->area['Area']['informar_cor_irregularidade']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['informar_tipo_veiculo'], 					(bool)$this->area['Area']['informar_tipo_veiculo']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['aceitar_tickets_terceiros'], 				(bool)$this->area['Area']['aceitar_tickets_terceiros']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['foto_irregularidade'], 					(bool)$this->area['Area']['foto_irregularidade']);
		$this->assertEqual(		$this->vars['data']['areas'][0]['lancar_veiculo_ocr'], 						(bool)$this->area['Area']['lancar_veiculo_ocr']);
		
		$this->assertNotEmpty(	$this->vars['data']['areas'][0]['setores']);
		$this->assertNotEmpty(	$this->vars['data']['areas'][0]['setores'][0]);
		$this->assertEqual(		$this->vars['data']['areas'][0]['setores'][0]['id'], 						$this->dataGenerator->setorId);
		$this->assertEqual(		$this->vars['data']['areas'][0]['setores'][0]['nome'], 						$this->setor['Setor']['nome']);
		$this->assertTrue(array_key_exists('total_vagas', $this->vars['data']['areas'][0]['setores'][0]));
		
		$this->assertNotNull(	$this->vars['data']['associado'], 											'Campo associado de retorno da função está nulo');
		$this->assertNotEmpty(	$this->vars['data']['associado'],											'Campo associado de retorno da função está vazio');
		$this->assertEqual(		$this->vars['data']['associado']['vende_em_dinheiro'],						(bool)$this->associado['Associado']['vende_em_dinheiro']);
		$this->assertEqual(		$this->vars['data']['associado']['aceita_pre'],			 					(bool)$this->associado['Associado']['aceita_pre']);
		$this->assertEqual(		$this->vars['data']['associado']['aceita_cartao'],					(bool)$this->associado['Associado']['aceita_cartao_debito']);
		$this->assertEqual(		$this->vars['data']['rede']['utilizar_envio_sms'],			 				(bool)$this->configUtilizaSMS['Configuracao']['valor']);
		$this->assertEqual(		$this->vars['data']['rede']['valor_minimo_recarga_cartao'],	 				(int)($valorMinimoRecargaCartao * 100));
		
		$this->assertEqual(		$this->vars['data']['rede']['codigo_estabelecimento_cielo'],				$pagamentoCielo['Pagamento']['token']);
		$this->assertEqual(		$this->vars['data']['rede']['nome_aplicacao'],								$pagamentoCielo['Pagamento']['mensagem1']);
		$this->assertEqual(		$this->vars['data']['rede']['referencia'],									$pagamentoCielo['Pagamento']['mensagem2']);
	}
	
	/**
	 * Método para efetuar processamento do controller sem esperar erro
	 * @return [type] [description]
	 */
	public function testAtualizaRecibos() {
		// Salva um novo recibo
		$this->dataGenerator->saveRecibo();
		$this->dataGenerator->saveRecibo(array('Recibo' => array('leiaute_id' => 2)));
		$this->dataGenerator->saveRecibo(array('Recibo' => array('alvo' => 'POS')));
		// Cria um novo equipamento e atualiza o parâmetro no request
		$equipamento = $this->dataGenerator->getEquipamento();
		$equipamento['Equipamento']['atualizar_recibos'] = 1;
		$equipamento['Equipamento']['tipo'] = 'ANDROID';
		$equipamento['Equipamento']['no_serie'] = 'TesteUrrul';
		$this->dataGenerator->saveEquipamento($equipamento);

		$this->data['serial'] = $equipamento['Equipamento']['no_serie'];
		$this->data['target'] = DATECS;
		
		// Acessa o link da API
		$this->sendRequest($this->fullURL, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull($this->vars['data']['recibos'], 	'Nenhum recibo foi retornado');
		// Testa se somente os recibos da DATECS foram retornados
		$this->assertTrue(count($this->vars['data']['recibos']) == 2, 'Número incorreto de recibos foi retornado.');

		// Valida se o campo atualizar_recibos do equipamento foi zerado
		$newEquipamento = $this->Equipamento->findByNoSerie($equipamento['Equipamento']['no_serie']);
		$this->assertFalse($newEquipamento['Equipamento']['atualizar_recibos']);
	}



	/**
	 * Testa se os leiautes dos recibos na abertura do caixa estão sendo enviados corretamente
	 * @return [type] [description]
	 */
	public function testRetornoLeiautes() {
		// Salva um novo recibo
		$this->dataGenerator->saveRecibo();
		$this->dataGenerator->saveRecibo(array('Recibo' => array('leiaute_id' => 2)));
		$this->dataGenerator->saveRecibo(array('Recibo' => array('alvo' => 'POS')));
		// Cria um novo equipamento e atualiza o parâmetro no request
		$equipamento = $this->dataGenerator->getEquipamento();
		$this->dataGenerator->saveEquipamento($equipamento);

		$this->data['serial'] = $equipamento['Equipamento']['no_serie'];
		$this->data['target'] = DATECS;
		
		// Acessa o link da API
		$this->sendRequest($this->fullURL, 'POST', $this->data);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida se recebeu os id's dos leiautes
		$this->assertNotNull($this->vars['data']['leiautes']);
		// Valida quantidade de leiautes recebidos na abertura do caixa
		$this->assertEquals(2, count($this->vars['data']['leiautes']));
	}// End Method ''
	
	
	/**
	 * Testa a configuração automática de um parquímetro
	 * ainda não cadastrado na base
	 */
	public function testRegisterParquimetro() {
		// Seta os valores para os campos padrões
		$data = $this->getApiDefaultParams();
		
		$equipamento = $this->dataGenerator->getEquipamento();
		$equipamento['Equipamento']['no_serie'] = time();
		$data['serial'] = $equipamento['Equipamento']['no_serie'];
		$data['type'] = EQUIPAMENTO_TIPO_PARQUIMETRO;
		$data['model'] = EQUIPAMENTO_TIPO_PARQUIMETRO;
			
		// Testa se já existe este equipamento cadastrado na base
		$equipamento = $this->Equipamento->findByNoSerie($data['serial']);
		$this->assertTrue(empty($equipamento), 'Já existe um equipamento cadastrado na base.');
		try {
			// Executa uma action sem equipamento cadastrado, esperando erro
			$this->testAction($this->fullURL, array('method' => 'POST','data' => $data));
		} catch (Exception $e) {
	
			// Testa se o erro acionado é o esperado
			$this->assertEqual($e->getMessage(), 'Modelo de impressora não recebido', 'Mensagem incorreta exibida: ' . $e->getMessage());
		}
		// Testa se o equipamento foi criado na base
		$equipamento = $this->Equipamento->findByNoSerie($data['serial']);
			
		$posto = $this->Posto->findById($equipamento['Equipamento']['posto_id']);
			
		// Valida se equipamento foi encontrado
		$this->assertTrue(!empty($equipamento), 'Equipamento não foi cadastrado.');
		// Valida se o número serial do equipamento é o mesmo do que o cadastrado
		$this->assertEqual($equipamento['Equipamento']['no_serie'], $data['serial'], 'Serial divergente.');
			
		$this->assertNotEmpty($posto);
	}// End method 'testRegister'

	/**
	 * Modifica equipamento para
	 */
	private function alteraEquipamento($dataEquipamento) {
		// Altera o equipamento na base para que o equipamento não seja encontrado
		$this->Equipamento->clear();
		$this->Equipamento->id = $this->dataGenerator->equipamentoId;
		$this->Equipamento->set($dataEquipamento);
		$this->Equipamento->save();
	}

}