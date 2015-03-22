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
		$cliente = $this->dataGenerator->getCliente();
		$this->data['username'] = $cliente['Cliente']['login'];
		$this->setExpectedException('ApiException', "Usuário ou senha inválidos");
		$this->sendRequest($this->fullURL, 'POST', $this->data);
	}// End 'test_InvalidPassword'

	/**
	 * Espera erro ao não enviar o campo 'login'.
	 */
	public function test_InvalidUsername() {
		$cliente = $this->dataGenerator->getCliente();
		$this->data['password'] = $cliente['Cliente']['senha'];
		$this->setExpectedException('ApiException', "Usuário ou senha inválidos");
		$this->sendRequest($this->fullURL, 'POST', $this->data);
	}// End 'test_InvalidUsername'

	/**
	 * Espera erro ao enviar dados inválido.
	 */
	public function test_ClientNotFound(){
		$this->dataGenerator->saveCliente();
		$this->data['username'] = 'USERNAME_TEST';
		$this->data['password'] = 'PASSWORD_TEST';
		$this->setExpectedException('ApiException', 'Usuário ou senha inválidos');
		$this->sendRequest($this->fullURL, 'POST', $this->data);
	}// End Method 'test_ClientNotFound'


	/**
	 * Espera receber os dados do cliente salvo, sem nenhum erro.
	 */
	public function test_FindClientOK(){
		$cliente = $this->dataGenerator->getCliente();
		$this->dataGenerator->saveCliente($cliente);

		$this->data['username'] = $cliente['Cliente']['login'];
		$this->data['password'] = 'panfletovia';

		$responseClient = $this->sendRequest($this->fullURL, 'POST', $this->data);

		$this->assertNotNull($responseClient);
		$this->assertNotEmpty($responseClient);

		$this->assertEquals(1, $responseClient['Cliente']['id']);
      	$this->assertEquals($cliente['Cliente']['cpf_cnpj'], $responseClient['Cliente']['cpf_cnpj']);
      	$this->assertEquals($cliente['Cliente']['pessoa'], $responseClient['Cliente']['pessoa']);
      	$this->assertEquals($cliente['Cliente']['nome'], $responseClient['Cliente']['nome']);
      	$this->assertEquals($cliente['Cliente']['fantasia'], $responseClient['Cliente']['fantasia']);
      	$this->assertEquals($cliente['Cliente']['data_nascimento'], $responseClient['Cliente']['data_nascimento']);
      	$this->assertEquals($cliente['Cliente']['telefone'], $responseClient['Cliente']['telefone']);
      	$this->assertEquals($cliente['Cliente']['cep'], $responseClient['Cliente']['cep']);
      	$this->assertEquals($cliente['Cliente']['logradouro'], $responseClient['Cliente']['logradouro']);
      	$this->assertEquals($cliente['Cliente']['numero'], $responseClient['Cliente']['numero']);
      	$this->assertEquals($cliente['Cliente']['compl'], $responseClient['Cliente']['compl']);
      	$this->assertEquals($cliente['Cliente']['cidade'], $responseClient['Cliente']['cidade']);
      	$this->assertEquals($cliente['Cliente']['bairro'], $responseClient['Cliente']['bairro']);
      	$this->assertEquals($cliente['Cliente']['uf'], $responseClient['Cliente']['uf']);
      	$this->assertEquals($cliente['Cliente']['ativo'], $responseClient['Cliente']['ativo']);
      	$this->assertEquals($cliente['Cliente']['tipo'], $responseClient['Cliente']['tipo']);
      	$this->assertEquals($cliente['Cliente']['criado_em'], $responseClient['Cliente']['criado_em']);
      	$this->assertEquals($cliente['Cliente']['login'], $responseClient['Cliente']['login']);
      	$this->assertEquals($cliente['Cliente']['senha'], $responseClient['Cliente']['senha']);
      	$this->assertEquals($cliente['Cliente']['sexo'], $responseClient['Cliente']['sexo']);
	}// End Method 'test_FindClientOK'
}// End Class