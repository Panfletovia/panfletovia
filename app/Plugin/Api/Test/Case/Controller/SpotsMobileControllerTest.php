<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe responsável por efetuar testes da classe que controla as vagas do aplicativo cliente
 */
class SpotsMobileControllerTest extends ApiBaseControllerTestCase {

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
	private $URL       = '/api/spots_mobile';
	
	private $vaga;

	private $nVagas;

	/**
	 * Metódo que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria Registros necessários para teste
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveTarifa();
		$this->dataGenerator->savePreco();
		$this->parkTarifa = $this->dataGenerator->getParkTarifa();
		$this->dataGenerator->saveParkTarifa($this->parkTarifa);

		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveSetor();
		
		//cria vagas
		$this->nVagas = rand(10, 20);
		for ($i = 0; $i < $this->nVagas; $i++) {

			$this->vaga = $this->dataGenerator->getAreaPonto();
			$this->vaga['AreaPonto']['codigo'] = $i;
			$this->dataGenerator->saveAreaPonto($this->vaga);
		}
		
		$equipamento = $this->dataGenerator->getEquipamento();
		$equipamento['tipo']   = 'ANDROID';
		$equipamento['modelo'] = 'ANDROID';
		$this->dataGenerator->saveEquipamento($equipamento);
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
		// Indica que é um app de cliente
		$this->data['client'] = 1;
		// Altera o serial default do método anterior
		$this->data['serial'] = $equipamento['Equipamento']['no_serie'];
	}

	/**
	 * Testa o acesso a API, esperando erro de id da área inválido
	 */
	public function testListaVagasSemAreaId() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Area id inválida'
		);
	}// End 'testListaVagasSemAreaId'

	/**
	 * Verifica se retorna a lista de vagas esperando que todas estão disponíveis
	 */
	public function testListaTodasAsVagas() {
		// Adiciona o id da area como parâmetro da requisição
		$this->data['park_area_id'] = $this->dataGenerator->areaId;
		// Efetua a requisição
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida se recebeu a resposta
		$this->assertNotNull($this->vars['data']['vagas']);
		$this->assertNotEmpty($this->vars['data']['vagas']);
		// Valida a quantidade de vagas disponíveis.
		$this->assertEquals($this->nVagas, count($this->vars['data']['vagas']));
	}// End Method 'testListaTodasAsVagas'

	/**
	 * Verifica se retorna a lista de vagas disponíveis esperando que apenas uma esteja ocupada
	 */
	public function testListaVagasComVeiculo() {
		// Estaciona um carro pago na ultima vaga cadastrada
		$this->dataGenerator->venderTicketEstacionamentoDinheiro(
			$this->parkTarifa['ParkTarifa']['valor'], 
			'TST-6969', 
			$this->vaga['AreaPonto']['codigo']
		);
		// Adiciona o id da area como parâmetro da requisição
		$this->data['park_area_id'] = $this->dataGenerator->areaId;
		// Efetua a requisição
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida se recebeu a resposta
		$this->assertNotNull($this->vars['data']['vagas']);
		$this->assertNotEmpty($this->vars['data']['vagas']);
		// Valida a quantidade de vagas disponíveis.
		$this->assertEquals(($this->nVagas - 1), count($this->vars['data']['vagas']));
	}// End Method 'testListaVagasComVeiculo'
} // End Class