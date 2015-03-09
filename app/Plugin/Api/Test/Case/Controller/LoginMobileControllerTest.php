<?php

App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe de teste do controller LoginMobileController
 */
class LoginMobileControllerTest extends ApiBaseControllerTestCase {

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
		'Equipamento',
		'ClienteEstrangeiro',
		);

	// Variável que recebe os campos defautl
	private $data      = NULL;
	// Variável que recebe o formato de dados da requisição
	private $extension = '.json';
	// Variável que recebe a URL para requisição
	private $URL       = '/api/login_mobile';

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
		$this->dataGenerator->saveArea();
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
		// Indica que é um aplicativo de cliente para não controlar NSU
		$this->data['client'] = 1;
	}// End Method 'Setup'

	/**
	 * Testa a validação do envio da informação que indica se é um cliente
	 */
	public function testClientFalse() {
		// Remove o campo dos parâmetros da requisição
		unset($this->data['client']);
		// Variável que cria o cliente (por padrão é brasileiro. Apenas no mask_phone é sobrescrito com o estrangeiro)
		$cliente = $this->dataGenerator->getCliente(false);
		$this->dataGenerator->saveCliente($cliente);
		// Informa dados da requisição
		$this->data['login']    = $cliente['Cliente']['cpf_cnpj']; 
		$this->data['password'] = $cliente['Cliente']['raw_password']; 
		$this->data['nsu']      = 1;
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'invalid_nsu_2'
		);
	}// End Method 'testEmptyLogin'


	/**
	 * Testa a validação do envio vazio do login
	 */
	public function testEmptyLogin() {
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Login ou senha inválidos'
		);
	}// End Method 'testEmptyLogin'

	/**
	 * Testa a validação do envio vazio da senha
	 */
	public function testEmptyPassword() {
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Login ou senha inválidos'
		);
	}// End Method 'testEmptyPassword'


	/**
	 * 	Testa a validação do cliente não encontrado
	 */
	public function testAccessFailClientNotFound(){
		// Cria um cliente
		$cliente = $this->dataGenerator->getCliente();
		$this->dataGenerator->saveCliente($cliente);

		// Informa um cpf inexistente na requisição
		$this->data['login'] = '99999999999';
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Usuário não encontrado'
		);
	}// End Method 'testAccessFailClientNotFound'

	/**
	 * Testa a validação da conta bloqueada do usuário quando essa é a ultima tentativa
	 */
	public function testAccessFailAccountBlockedNow(){
		// Cria um cliente
		$clienteBlocked = $this->dataGenerator->getCliente(false);
		$clienteBlocked['Cliente']['erros_senha_site'] = 2;
		$this->dataGenerator->saveCliente($clienteBlocked);
		// Informa o cpf_valido
		$this->data['login'] = $clienteBlocked['Cliente']['cpf_cnpj'];
		// Informa uma senha inválida
		$this->data['password'] = '123'; 
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Conta bloqueada'
		);
	}// End Method '_testAccessFailAccountBlockedNow'

	/**
	 * Testa a validação da conta bloqueada do usuário quando a conta já está bloqueada
	 */
	public function testAccessFailBeforeAccountBlocked(){
		// Cria um cliente
		$clienteBlocked = $this->dataGenerator->getCliente(false);
		$clienteBlocked['Cliente']['erros_senha_site'] = 3;
		$this->dataGenerator->saveCliente($clienteBlocked);
		// Informa o cpf_valido
		$this->data['login'] = $clienteBlocked['Cliente']['cpf_cnpj'];
		// Informa uma senha inválida
		$this->data['password'] = $clienteBlocked['Cliente']['raw_password']; 
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Conta bloqueada'
		);
	}// End Method 'testAccessFailBeforeAccountBlocked'

	/**
	 * Testa a validação da senha incorreta
	 */
	public function testAccessFailIncorrectPassword(){
		// Cria um cliente
		$clienteBlocked = $this->dataGenerator->getCliente(false);
		$this->dataGenerator->saveCliente($clienteBlocked);
		// Informa o cpf_valido
		$this->data['login'] = $clienteBlocked['Cliente']['cpf_cnpj'];
		// Informa uma senha inválida
		$this->data['password'] = '123'; 
		// Acessa o link da API esperando o erro
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Senha incorreta'
		);
	}// End Method 'testAccessFailIncorrectPassword'

	/**
	 * Testa o login do usuário pelo email
	 */
	public function testAccessWithEmail(){
		$this->accessTest('EMAIL');
	} // End Method 'testAccessWithEmail'

	/**
	 * Testa o login do usuário pelo telefone com 10 dígitos
	 */
	public function testAccessWithPhone10Digits(){
		$this->accessTest('PHONE');
	} // End Method 'testAccessWithPhone10Digits'

	/**
	 * Testa o acesso do usuário pelo cpf_cnpj
	 */
	public function testAccessWithCpfCnpj(){
		$this->accessTest('CPF_CNPJ');
	} // End Method 'testAccessWithCpfCnpj'

	/**
	 * Testa o acesso de um usuário estrangeiro pelo telefone
	 */
	public function testAccessWithMaskLessPhone(){
		$this->accessTest('MASK_PHONE');
	} // End Method 'testAccessWithMaskLessPhone'

	/**
	 * Método para efetuar os testes de acesso sem esperar erro
	 */
	private function accessTest($typeAccess){
		// Variável que cria o cliente (por padrão é brasileiro. Apenas no mask_phone é sobrescrito com o estrangeiro)
		$cliente = $this->dataGenerator->getCliente(true);
		$this->dataGenerator->saveCliente($cliente);
		// Informa uma senha válido (Por padrão é o cliente brasileiro)
		$this->data['password'] = $cliente['Cliente']['raw_password']; 
		// Vincula uma placa ao usuário
		$parkPlaca = $this->dataGenerator->getPlaca();
		$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteId;
		// Cria o cliente de acordo com o tipo esperado
		switch($typeAccess){
			case 'EMAIL':
				// Informa o email
				$this->data['login'] = $cliente['Cliente']['email'];
				break;
			case 'PHONE':
				// Informa o telefone
				$this->data['login'] = $cliente['Cliente']['telefone'];
				break;
			case 'CPF_CNPJ':
				// Informa o cpf_cnpj
				$this->data['login'] = $cliente['Cliente']['cpf_cnpj'];
				break;
			case 'MASK_PHONE': // Estrangeiro
				$cliente = $this->dataGenerator->getClienteEstrangeiro();
				$this->dataGenerator->saveClienteEstrangeiro($cliente);
				// Vincula a placa
				$parkPlaca['Placa']['entidade_id'] = $this->dataGenerator->clienteestrangeiroId;
				// Informa o telefone
				$this->data['login'] = $cliente['ClienteEstrangeiro']['telefone'];
				// Informa o password
				$this->data['password'] = $cliente['ClienteEstrangeiro']['raw_password']; 
				break;
			default:
				$this->fail('Tipo Inválido');
		}
		// Salva placa
		$this->dataGenerator->savePlaca($parkPlaca);
		// Salva o id da área do rotativo antes de salvar a área privada
		$areaRotativoId = $this->dataGenerator->areaId;
		// Cria uma área privada apenas para validar se o retorno é apenas das áreas do rotativo
		$areaPrivado = $this->dataGenerator->getArea(false);
		$this->dataGenerator->saveArea($areaPrivado);
		// Envia requisição
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se recebeu uma resposta válida
		$this->assertNotEmpty($this->vars['data']);
		// Valida se os campos retornados são os campos esperados
		$this->assertTrue(isset($this->vars['data']['client_id']));
		$this->assertTrue(isset($this->vars['data']['areas']));
		$this->assertTrue(isset($this->vars['data']['plates']));
		// Valida se o cliente id retornado é o esperado
		if ($typeAccess == 'MASK_PHONE'){
			$this->assertEquals($this->dataGenerator->clienteestrangeiroId, $this->vars['data']['client_id']);
		}else{
			$this->assertEquals(intval($this->dataGenerator->clienteId), $this->vars['data']['client_id']);
		}
		// Valida as áreas retornadas
		$this->assertEquals(1, count($this->vars['data']['areas']));
		$this->assertTrue(isset($this->vars['data']['areas'][0]['Area']['id']));
		$this->assertTrue(isset($this->vars['data']['areas'][0]['Area']['nome']));
		$this->assertTrue(isset($this->vars['data']['areas'][0]['Area']['cobranca_id']));
		$this->assertTrue(isset($this->vars['data']['areas'][0]['Area']['devolucao_periodo']));
		$this->assertEquals($areaRotativoId, $this->vars['data']['areas'][0]['Area']['id']);
		// Valida as placas retornadas
		$this->assertEquals(1, count($this->vars['data']['plates']));
		$this->assertEquals($parkPlaca['Placa']['placa'], $this->vars['data']['plates'][0]['Placa']['placa']);
		$this->assertEquals($parkPlaca['Placa']['tipo'], $this->vars['data']['plates'][0]['Placa']['tipo']);
	}// End Method 'accessTest'
}// End Class