<?php

/**
 * Classe que efetua os testes do comando de autorização da API
 */
App::uses('BaseControllerTestCase', 'Lib');

class AuthorizationControllerTest extends BaseControllerTestCase {

	// Models necessários para os testes
	public $uses = array(
		'Cliente'
	);

	// Variável que recebe os campos default das transações
	private $data      = NULL;
	// Variável que recebe a extensão dos dados a serem recebidos
	private $extension = '.json';
	// Variável que recebe o controller na url
	private $url = '/api/authorization/';
	// Variável que recebe a url completa
	private $fullURL = '/api/authorization.json';
	
	/**
	 * Método que é executado antes de cada testes
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Esperando erro ao acessar a index 
	 */
	public function test_IndexError(){
		$this->validateTestException(
			$this->fullURL,
			'GET',
			$this->data,
			'NotImplementedException',
			'index'
		);
	}// End Method 'test_IndexError'

	/**
	 * Esperando erro ao acessar a index 
	 */
	public function test_EditError(){
		$this->validateTestException(
			$this->url.'1'.$this->extension,
			'POST',
			$this->data,
			'NotImplementedException',
			'edit'
		);
	}// End Method 'test_EditError'

	/**
	 * Esperando erro ao acessar o delete
	 */
	public function test_DeleteError(){
		$this->validateTestException(
			$this->url.'1'.$this->extension,
			'DELETE',
			$this->data,
			'NotImplementedException',
			'delete'
		);
	}// End Method 'test_DeleteError'

	/**
	 * Esperando erro ao acessar a view
	 */
	public function test_ViewError(){
		$this->validateTestException(
			$this->url.'1'.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			'view'
		);
	}// End Method 'test_DeleteError'

	/**
	 * Espera erro ao não enviar o campo 'password'.
	 */
	public function test_InvalidPassword() {
		// Popula variável com os dados do cliente
		$cliente = $this->dataGenerator->getCliente();
		// Não popula o campo 'password' para requisição
		$this->populateData($cliente['Cliente']['login'], null);
		try{
			// Envia a requisição
			$this->sendRequest($this->fullURL, 'POST', $this->data);
			// Caso não ocorra exception, deverá lançar mensagem de erro
			$this->fail('Não ocorreu exception esperada');
		} catch (Exception $e){
			// Verifica se a classe da exception é a esperada
			$this->assertEquals('ApiException', get_class($e));
			// Verifica se a mensagem da exception é a esperada
			$this->assertEquals('Usuário ou senha inválidos', $e->getMessage());
		}
	}// End 'test_InvalidPassword'

	/**
	 * Espera erro ao não enviar o campo 'login'.
	 */
	public function test_InvalidUsername() {
		//  Popula variável com os dados do cliente
		$cliente = $this->dataGenerator->getCliente();
		// Não popula o campo 'username' para requisição
		$this->populateData(null, $cliente['Cliente']['senha']);
		try{
			// Envia a requisição
			$this->sendRequest($this->fullURL, 'POST', $this->data);
			// Caso não ocorra exception, deverá lançar mensagem de erro
			$this->fail('Não ocorreu exception esperada');
		} catch (Exception $e){
			// Verifica se a classe da exception é a esperada
			$this->assertEquals('ApiException', get_class($e));
			// Verifica se a mensagem da exception é a esperada
			$this->assertEquals('Usuário ou senha inválidos', $e->getMessage());
		}
	}// End 'test_InvalidUsername'

	/**
	 * Espera erro ao enviar dados inválido.
	 */
	public function test_ClientNotFound(){
		// Salva um cliente válido
		$this->dataGenerator->saveCliente();
		// Popula dados para requisição
		$this->populateData('USERNAME_TEST', 'PASSWORD_TEST');
		try{
			// Envia a requisição
			$this->sendRequest($this->fullURL, 'POST', $this->data);
			// Caso não ocorra exception, deverá lançar mensagem de erro
			$this->fail('Não ocorreu exception esperada');
		} catch (Exception $e){
			// Verifica se a classe da exception é a esperada
			$this->assertEquals('ApiException', get_class($e));
			// Verifica se a mensagem da exception é a esperada
			$this->assertEquals('Usuário ou senha inválidos', $e->getMessage());
		}
	}// End Method 'test_ClientNotFound'

	/**
	 * Espera receber os dados do cliente salvo, sem nenhum erro.
	 */
	public function test_FindClientOK(){
		// Popula variável com os dados do cliente
		$cliente = $this->dataGenerator->getCliente();
		// Salva cliente
		$this->dataGenerator->saveCliente($cliente);
		// Popula dados da requisição
		$this->populateData($cliente['Cliente']['login'], $cliente['Cliente']['raw_password']);
		// Envia requisição e armazena resposta
		$response = $this->sendRequest($this->fullURL, 'POST', $this->data);
		// Valida se os campos de resposta são válidos
		$this->assertNotNull($response);
		$this->assertNotEmpty($response);
		$this->assertNotNull($response['client']);
		$this->assertNotEmpty($response['client']);
		$this->assertNotNull($response['profiles']);
		$this->assertNotEmpty($response['profiles']);
		// Extrai os dados do cliente retornados
		$responseClient = $response['client'];
		// Extrai os campos de perfils encontrados
		$profiles = $response['profiles'];
		// Valida se o cliente retornado é o cliente cadastrado
		$this->assertEquals(1, $responseClient['id']);
		$this->assertEquals($cliente['Cliente']['cpf_cnpj']        , $responseClient['cpf_cnpj']);
		$this->assertEquals($cliente['Cliente']['pessoa']          , $responseClient['pessoa']);
		$this->assertEquals($cliente['Cliente']['nome']            , $responseClient['nome']);
		$this->assertEquals($cliente['Cliente']['fantasia']        , $responseClient['fantasia']);
		$this->assertEquals($cliente['Cliente']['data_nascimento'] , $responseClient['data_nascimento']);
		$this->assertEquals($cliente['Cliente']['telefone']        , $responseClient['telefone']);
		$this->assertEquals($cliente['Cliente']['cep']             , $responseClient['cep']);
		$this->assertEquals($cliente['Cliente']['logradouro']      , $responseClient['logradouro']);
		$this->assertEquals($cliente['Cliente']['numero']          , $responseClient['numero']);
		$this->assertEquals($cliente['Cliente']['compl']           , $responseClient['compl']);
		$this->assertEquals($cliente['Cliente']['cidade']          , $responseClient['cidade']);
		$this->assertEquals($cliente['Cliente']['bairro']          , $responseClient['bairro']);
		$this->assertEquals($cliente['Cliente']['uf']              , $responseClient['uf']);
		$this->assertEquals($cliente['Cliente']['ativo']           , $responseClient['ativo']);
		$this->assertEquals($cliente['Cliente']['tipo']            , $responseClient['tipo']);
		$this->assertEquals($cliente['Cliente']['criado_em']       , $responseClient['criado_em']);
		$this->assertEquals($cliente['Cliente']['login']           , $responseClient['login']);
		$this->assertEquals($cliente['Cliente']['senha']           , $responseClient['senha']);
		$this->assertEquals($cliente['Cliente']['sexo']            , $responseClient['sexo']);
		// Valida apenas a quantidade de perfils da base encontrados
		$this->assertEquals(29, count($profiles));
	}// End Method 'test_FindClientOK'

	/**
	 * Espera receber os dados do cliente que possua perfil com sub-perfil vinculados
	 */
	public function test_FindClientWithPerfil(){

		// Salva cliente
		$cliente = $this->dataGenerator->getCliente();
		$this->dataGenerator->saveCliente($cliente);
		// Salva vinculo cliente X perfil
		$clientePerfil1 = $this->dataGenerator->getClientePerfil(1);
		$this->dataGenerator->saveClientePerfil($clientePerfil1);
		$clientePerfil2 = $this->dataGenerator->getClientePerfil(2);
		$this->dataGenerator->saveClientePerfil($clientePerfil2);
		$clientePerfil3 = $this->dataGenerator->getClientePerfil(3);
		$this->dataGenerator->saveClientePerfil($clientePerfil3);
		// Popula dados da requisição
		$this->populateData($cliente['Cliente']['login'], $cliente['Cliente']['raw_password']);
		// Envia requisição e armazena resposta
		$response = $this->sendRequest($this->fullURL, 'POST', $this->data);
		// Valida a resposta 
		$this->assertNotNull($response);
		$this->assertNotEmpty($response);
		$this->assertNotNull($response['client']);
		$this->assertNotEmpty($response['client']);
		$this->assertNotNull($response['profiles']);
		$this->assertNotEmpty($response['profiles']);
		$this->assertNotNull($response['client_profiles']);
		$this->assertNotEmpty($response['client_profiles']);
		$this->assertEquals(3, count($response['client_profiles']));

		$this->assertEquals(1, $response['client_profiles'][0]['Perfil']['id']);
		$this->assertEquals('alimentação', $response['client_profiles'][0]['Perfil']['codigo']);

		$this->assertEquals(2, $response['client_profiles'][1]['Perfil']['id']);
		$this->assertEquals('supermercados', $response['client_profiles'][1]['Perfil']['codigo']);

		$this->assertEquals(3, $response['client_profiles'][2]['Perfil']['id']);
		$this->assertEquals('padarias / bistros / cafés', $response['client_profiles'][2]['Perfil']['codigo']);
	}// End Method 'test_FindClientWithPerfil'

	/**
	 * Método para popular dados do request
	 */
	private function populateData($username, $password){
		if(!empty($username)){
			$this->data['username'] = $username;
		}

		if(!empty($password)){
			$this->data['password'] = $password;
		}
	}// End Method 'populateData'
}// End Class