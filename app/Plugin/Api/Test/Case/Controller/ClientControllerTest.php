<?php

/**
 * Classe que efetua os testes do comando de autorização da API
 */
App::uses('BaseControllerTestCase', 'Lib');

class ClientControllerTest extends BaseControllerTestCase {

	// Models necessários para os testes
	public $uses = array(
		'Cliente',
	);

	// Variável que recebe os campos default das transações
	private $data      = NULL;
	// Variável que recebe a extensão dos dados a serem recebidos
	private $extension = '.json';
	// Variável que recebe o controller na url
	private $url = '/api/client/';
	// Variável que recebe a url completa
	private $fullURL = '/api/client.json';
	
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
		// Não popula o campo 'password' para requisição
		$this->populateData('teste@panfletovia.com.br', null);
		try{
			// Envia a requisição
			$this->sendRequest($this->fullURL, 'POST', $this->data);
			// Caso não ocorra exception, deverá lançar mensagem de erro
			$this->fail('Não ocorreu exception esperada');
		} catch (Exception $e){
			// Verifica se a classe da exception é a esperada
			$this->assertEquals('ApiException', get_class($e));
			// Verifica se a mensagem da exception é a esperada
			$this->assertEquals('Por favor, informe corretamente os dados', $e->getMessage());
			// Verifica o status code da requisição
			$this->assertEquals(400, $e->getCode());
		}
	}// End 'test_InvalidPassword'

	/**
	 * Espera erro ao não enviar o campo 'login'.
	 */
	public function test_InvalidUsername() {
		// Não popula o campo 'username' para requisição
		$this->populateData(null, '12456');
		try{
			// Envia a requisição
			$this->sendRequest($this->fullURL, 'POST', $this->data);
			// Caso não ocorra exception, deverá lançar mensagem de erro
			$this->fail('Não ocorreu exception esperada');
		} catch (Exception $e){
			// Verifica se a classe da exception é a esperada
			$this->assertEquals('ApiException', get_class($e));
			// Verifica se a mensagem da exception é a esperada
			$this->assertEquals('Por favor, informe corretamente os dados', $e->getMessage());
			// Verifica o status code da requisição
			$this->assertEquals(400, $e->getCode());
		}
	}// End 'test_InvalidUsername'

	/**
	 * Espera receber o id do cliente, indicando que foi salvo com sucesso
	 */
	public function test_SaveClientOK(){
		// Popula variável com os dados do cliente
		$cliente = $this->dataGenerator->getCliente();
		// Popula dados da requisição
		$this->populateData($cliente['Cliente']['login'], $cliente['Cliente']['raw_password']);
		// Envia requisição e armazena resposta
		$response = $this->sendRequest($this->fullURL, 'POST', $this->data);
		// Valida se os campos de resposta são válidos
		$this->assertNotNull($response);
		$this->assertNotEmpty($response);
		$this->assertNotNull($response['client_id']);
		$this->assertNotEmpty($response['client_id']);
		// Extrai os dados do cliente retornados
		$clientId = $response['client_id'];
		// Busca na base esse cliente, para compara se a senha foi gerada corretamente
		$dataBaseClient = $this->Cliente->findById($clientId);
		// Valida se encontrou o cliente com o id retornado
		$this->assertNotNull($dataBaseClient);
		$this->assertNotEmpty($dataBaseClient);
		// Valida os dados da base com o objeto do cliente 
		$this->assertEquals($cliente['Cliente']['tipo'], $dataBaseClient['Cliente']['tipo']);
		$this->assertEquals($cliente['Cliente']['login'], $dataBaseClient['Cliente']['login']);
		$this->assertEquals($cliente['Cliente']['senha'], $dataBaseClient['Cliente']['senha']);
		$this->assertEquals($cliente['Cliente']['ativo'], $dataBaseClient['Cliente']['ativo']);
		// die(var_dump($dataBaseClient));
	}// End Method 'test_FindClientOK'

	/**
	 * Método para popular dados do request
	 */
	private function populateData($username, $password){
		if(!empty($username)){
			$this->data['login'] = $username;
		}

		if(!empty($password)){
			$this->data['password'] = $password;
		}
	}// End Method 'populateData'
}// End Class