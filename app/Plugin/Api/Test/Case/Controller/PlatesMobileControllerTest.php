<?php

App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe de teste do controller PlatesMobileController
 */
class PlatesMobileControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Placa',
		'Equipamento'
		);

	// Variável que recebe os campos defautl
	private $data      = NULL;
	// Variável que recebe o formato de dados da requisição
	private $extension = '.json';
	// Variável que recebe a URL para requisição
	private $URL       = '/api/plates_mobile';

	/**
	 * Metódo que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();
		// Cria Registros necessários para teste
		$this->dataGenerator->saveCliente();
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

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
		// Indica que é um aplicativo de cliente para não controlar NSU
		$this->data['client'] = 1;
		// Adiciona campo default do cliente id
		$this->data['client_id'] = $this->dataGenerator->clienteId;
	}// End Method 'Setup'

	/**
	 * Testa validação sem o envio do campo 'client_id'
	 */
	public function testRemoveEmptyClientId() {
		// Remove o campo dos parâmetros da requisição
		unset($this->data['client_id']);
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.'/remove'.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Id do cliente não recebido'
		);
	}// End Method 'testRemoveEmptyClientId'

	/**
	 * Testa action de remover veículos enviando apenas um id para deleção
	 */
	public function testRemoveOnePlate(){
		// Salva duas placas
		$parkPlaca1 = $this->dataGenerator->getPlaca();
		$parkPlaca1['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca1);
		$parkPlaca2 = $this->dataGenerator->getPlaca();
		$parkPlaca2['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca2);
		// Adiciona placa nos parâmetros da requisição
		$this->data['plates'] = "{$parkPlaca2['Placa']['id']}";
		// Envia requisição
		$this->sendRequest($this->URL.'/remove'.$this->extension, 'POST', $this->data);
		// Valida se recebeu uma resposta válida
		$this->assertNotEmpty($this->vars['data']);
		// Valida se os campos retornados são os campos esperados
		$this->assertTrue(isset($this->vars['data']['plates']));
		// Testa dados da resposta das placas ativas
		$this->assertEquals(1, count($this->vars['data']['plates']));
		$this->assertEquals($parkPlaca1['Placa']['placa'], $this->vars['data']['plates'][0]['Placa']['placa']);
		// Busca informações das placas na base de dados verificando se a placa realmente foi inativada
		$plates = $this->Placa->find('all', array(
			'recursive' => -1,
			'conditions' => array(
				'entidade_id' => $this->dataGenerator->clienteId,
			)
		));
		// Valida se encontrou
		$this->assertNotNull($plates);
		// Valida a quantidade de registros
		$this->assertEquals(2, count($plates));
		// Varre a lista verificando as situações
		foreach ($plates as $key => $value) {
			// Validações da placa 1
			if($value['Placa']['placa'] === $parkPlaca1['Placa']['placa']){
				$this->assertEquals($parkPlaca1['Placa']['entidade_id'] , $value['Placa']['entidade_id']);
				$this->assertEquals($parkPlaca1['Placa']['tipo']        , $value['Placa']['tipo']);
				$this->assertEquals($parkPlaca1['Placa']['ativacoes']   , $value['Placa']['ativacoes']);
				$this->assertEquals($parkPlaca1['Placa']['inativo']     , $value['Placa']['inativo']);
			// Validações da placa 2
			}else if($value['Placa']['placa'] === $parkPlaca2['Placa']['placa']){
				$this->assertEquals($parkPlaca2['Placa']['entidade_id'] , $value['Placa']['entidade_id']);
				$this->assertEquals($parkPlaca2['Placa']['tipo']        , $value['Placa']['tipo']);
				$this->assertEquals($parkPlaca2['Placa']['ativacoes']   , $value['Placa']['ativacoes']);
				$this->assertEquals(1, $value['Placa']['inativo']);
			}
		}

	}// End Method 'testRemoveOnePlate'

	/**
	 * Testa action de remover veículos enviando dois id's para deleção
	 */
	public function testRemoveTwoPlate(){
		// Salva duas placas
		$parkPlaca1 = $this->dataGenerator->getPlaca();
		$parkPlaca1['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca1);
		$parkPlaca2 = $this->dataGenerator->getPlaca();
		$parkPlaca2['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca2);
		// Adiciona placa nos parâmetros da requisição
		$this->data['plates'] = "{$parkPlaca1['Placa']['id']};{$parkPlaca2['Placa']['id']}";
		// Envia requisição
		$this->sendRequest($this->URL.'/remove'.$this->extension, 'POST', $this->data);
		// Valida se recebeu uma resposta válida
		$this->assertNotEmpty($this->vars['data']);
		// Valida se os campos retornados são os campos esperados
		$this->assertTrue(isset($this->vars['data']['plates']));
		// Testa dados da resposta das placas ativas
		$this->assertEquals(0, count($this->vars['data']['plates']));
		// Busca informações das placas na base de dados verificando se a placa realmente foi inativada
		$plates = $this->Placa->find('all', array(
			'recursive' => -1,
			'conditions' => array(
				'entidade_id' => $this->dataGenerator->clienteId,
			)
		));
		// Valida se encontrou
		$this->assertNotNull($plates);
		// Valida a quantidade de registros
		$this->assertEquals(2, count($plates));
		// Varre a lista verificando as situações
		foreach ($plates as $key => $value) {
			// Validações da placa 1
			if($value['Placa']['placa'] === $parkPlaca1['Placa']['placa']){
				$this->assertEquals($parkPlaca1['Placa']['entidade_id'] , $value['Placa']['entidade_id']);
				$this->assertEquals($parkPlaca1['Placa']['tipo']        , $value['Placa']['tipo']);
				$this->assertEquals($parkPlaca1['Placa']['ativacoes']   , $value['Placa']['ativacoes']);
				$this->assertEquals(1                                   , $value['Placa']['inativo']);
			// Validações da placa 2
			}else if($value['Placa']['placa'] === $parkPlaca2['Placa']['placa']){
				$this->assertEquals($parkPlaca2['Placa']['entidade_id'] , $value['Placa']['entidade_id']);
				$this->assertEquals($parkPlaca2['Placa']['tipo']        , $value['Placa']['tipo']);
				$this->assertEquals($parkPlaca2['Placa']['ativacoes']   , $value['Placa']['ativacoes']);
				$this->assertEquals(1                                   , $value['Placa']['inativo']);
			}
		}

		// Testa dados da resposta das placas ativas
		$this->assertEquals(0, count($this->vars['data']['plates']));
	}// End Method 'testRemoveTwoPlate'

	/**
	 * Testa acesso a action 'add' via get
	 */
	public function testAddRequisicaoGet(){
		// Envia requisição
		$this->sendRequest($this->URL.'/add'.$this->extension, 'GET', $this->data);
		// Valida se recebeu uma resposta válida
		$this->assertNotEmpty($this->vars['data']);
		// Valida se os campos retornados são os campos esperados
		$this->assertEquals(null, $this->vars['data']['plates']);
	} // End Method 'testAddRequisicaoGet'

	/**
	 * Testa a validação do envio do campo 'plate'
	 */
	public function testAddEmptyPlate() {
		// Remove o campo dos parâmetros da requisição
		unset($this->data['plate']);
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Placa não recebida'
		);
	}// End Method 'testAddEmptyPlate'

	/**
	 * Testa a validação do envio do campo 'type'
	 */
	public function testAddEmptyType() {
		// Adiciona uma placa para adicionar
		$parkPlaca = $this->dataGenerator->getPlaca();
		$this->data['plate'] = $parkPlaca['Placa']['placa'];
		// Remove o campo dos parâmetros da requisição
		unset($this->data['plate_type']);
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Tipo de veículo não recebido'
		);
	}// End Method 'testAddEmptyType'

	/**
	 * Testa a validação do envio do campo 'cliente_id'
	 */
	public function testAddEmptyClientId() {
		// Adiciona uma placa para adicionar
		$parkPlaca = $this->dataGenerator->getPlaca();
		$this->data['plate']      = $parkPlaca['Placa']['placa'];
		$this->data['plate_type'] = $parkPlaca['Placa']['tipo'];
		// Remove o campo dos parâmetros da requisição
		unset($this->data['client_id']);
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Id do cliente não recebido'
		);
	}// End Method 'testAddEmptyClientId'

	/**
	 * Testa action de adicionar veículos esperando erro por adicionar a mesma placa para o mesmo cliente 
	 * e o processo de rollBack.
	 */
	public function testAddErrorModelUniqueKeyPlateAndEntidadeId(){
		// Salva duas placas
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca);
		// Adiciona os parâmetros de requisição
		$this->data['plate']      = $parkPlaca['Placa']['placa'];
		$this->data['plate_type'] = $parkPlaca['Placa']['tipo'];
		$this->data['client_id']  = $this->dataGenerator->clienteId;
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Erro ao salvar placa: Você já possui esta placa cadastrada.' , $this->vars['data']['message']
		);
	}// End Method 'testAddErrorModelUniqueKeyPlateAndEntidadeId'

	/**
	 * Testa action de adicionar veículos esperando exception por não existir a tabela 'park_placa'
	 */
	public function testAddExceptionAlterTableNameOnSave(){
		// Salva duas placas
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['id'] = $this->dataGenerator->savePlaca($parkPlaca);
		// Adiciona os parâmetros de requisição
		$this->data['plate']      = $parkPlaca['Placa']['placa'];
		$this->data['plate_type'] = $parkPlaca['Placa']['tipo'];
		$this->data['client_id']  = $this->dataGenerator->clienteId;
		// Renomeia a tabela para gerar exception
		$this->dataGenerator->query('RENAME TABLE park_placa TO park_placa_placa;');
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'PDOException',
			".park_placa' doesn't exist",
			true
		);
		// Renomeia de volta para o original para não estragar os testes abaixo
		$this->dataGenerator->query('RENAME TABLE park_placa_placa TO park_placa;');
	}// End Method 'testAddExceptionAlterTableNameOnSave'

	/**
	 * Testa a situação de adicionar uma placa, removê-la e adicioná-la novamente
	 */
	public function testAddPlateInactive(){
		// Salva uma placa na base vinculada ao usuário já porém inativa
		$parkPlacaInativa = $this->dataGenerator->getPlaca();
		$parkPlacaInativa['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		$parkPlacaInativa['Placa']['inativo'] = 1;
		$this->dataGenerator->savePlaca($parkPlacaInativa);
		// Popula parâmetros da requisição
		$this->data['plate']      = $parkPlacaInativa['Placa']['placa'];
		$this->data['plate_type'] = $parkPlacaInativa['Placa']['tipo'];
		$this->data['client_id']  = $this->dataGenerator->clienteId;
		// Envia requisição para adicionar a mesma placa novamente
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Busca a placa verificando se a mesma está ativa novamente
		$newPlaca = $this->Placa->findByPlaca($parkPlacaInativa['Placa']['placa']);
		$this->assertEquals(0, $newPlaca['Placa']['inativo']);
		$this->assertEquals($this->dataGenerator->clienteId, $newPlaca['Placa']['entidade_id']);
	}// End Method 'testAddPlateInactive'

	/**
	 * Testa validação de limite de caracteres permitido para a placa do cliente
	 */
	public function testAddPlateLimitCharacters(){
		// Popula parâmetros da requisição
		$this->data['plate']      = '1234567890123456';
		$this->data['plate_type'] = 'CARRO';
		$this->data['client_id']  = $this->dataGenerator->clienteId;

		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Erro ao salvar placa: Placa inválida.'
		);
	}// End Method 'testAddPlateLimitCharacters'

	/**
	 * Testa action de adicionar veículos sem esperar erro
	 */
	public function testAdd(){
		// Salva duas placas
		$parkPlaca = $this->dataGenerator->getPlaca();
		// Adiciona os parâmetros de requisição
		$this->data['plate']      = $parkPlaca['Placa']['placa'];
		$this->data['plate_type'] = $parkPlaca['Placa']['tipo'];
		$this->data['client_id']  = $this->dataGenerator->clienteId;
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se recebeu uma resposta válida
		$this->assertNotEmpty($this->vars['data']);
		$this->assertNotEmpty($this->vars['data']['plates']);
		$this->assertEquals(1, count($this->vars['data']['plates']));
		$this->assertEquals($parkPlaca['Placa']['placa'], $this->vars['data']['plates'][0]['Placa']['placa']);
		$this->assertEquals($parkPlaca['Placa']['tipo'], $this->vars['data']['plates'][0]['Placa']['tipo']);
		// Busca na base verificando se a placa realmente foi cadastrada
		$afterSaveParkPlaca = $this->Placa->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'placa'       => $parkPlaca['Placa']['placa'],
				'entidade_id' => $this->dataGenerator->clienteId
			)
		));
		// Valida se encontrou
		$this->assertNotEmpty($afterSaveParkPlaca);
		$this->assertNotNull($afterSaveParkPlaca);
		// Valida os dados
		$this->assertEquals($parkPlaca['Placa']['tipo'], $afterSaveParkPlaca['Placa']['tipo']);
		$this->assertEquals(0, $afterSaveParkPlaca['Placa']['ativacoes']);
		$this->assertEquals(0, $afterSaveParkPlaca['Placa']['inativo']);
	}// End Method 'testAdd'

	// Criar teste do index que retornar todas as placas ativas do'cliente

	/**
	 * Testa a action index que deverá retornar a lista de placas vinculadas ao usuáriou
	 */
	public function testIndexEmptyClientId(){
		// Remove o campo dos parâmetros da requisição
		unset($this->data['client_id']);
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Id do cliente não recebido'
		);
	} // End Method 'testIndexEmptyClientId'

	/**
	 * Testa a action index esperando receber a lista de vagas vinculadas ao cliente
	 */
	public function testIndexListPlates(){
		$arrayPlates = array();
		// Gera uma quantidade randômica de placas vinculadas
		$randCount = rand(1, 20);
		for ($i = 0; $i < $randCount; $i++){
			$parkPlaca = $this->dataGenerator->getPlaca();
			$this->dataGenerator->savePlaca($parkPlaca);
			// Adiciona no array para comparar futuramente
			$arrayPlates[] = array(
				'placa' => $parkPlaca['Placa']['placa'],
				'tipo' => $parkPlaca['Placa']['tipo']
			);
		}
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'GET', $this->data);
		// Valida se recebeu resposta
		$this->assertNotEmpty($this->vars['data']);
		$this->assertNotEmpty($this->vars['data']['plates']);
		// Extrai a lista de placas recebidas
		$plates = $this->vars['data']['plates'];
		// Varre a lista recebida para validar com o array criado anteriormente
		foreach ($plates as $key => $value) {
			// Extrai a placa
			$placa = $value['Placa']['placa'];

			// Varre a lista de placas criadas verificando se existe a placa recebida
			foreach ($arrayPlates as $originalKey => $originalValue) {

				if ($placa === $originalValue['placa']){
					// Apenas conta uma asserção
					$this->assertTrue(true);
					// Interrompe varredura
					break;
				}
			}
		}
	}// End Method 'testIndexListPlates'
}// End Class