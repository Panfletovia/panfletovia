<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes do comando de Pagamento
 */
class PaymentControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Area',
		'Equipamento' 
	);

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a extensão a ser retornada pelo WebService
	private $extension = '.json';
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/payment';

	/**
	 * Método que é executado antes de cada teste.
	 */
	public function setUp() {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array(
			'tipo' 		=> EQUIPAMENTO_TIPO_SMARTPHONE,
			'no_serie' 	=> '1234567890',
			'modelo' 	=> 'ANDROID')));
		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();

		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();

		// Cria array com as formas de pagamentos possíveis, para enviar randômicamente aos testes
		$formaPagamento = array('DINHEIRO', 'PRE', 'PRE_PARCELADO', 'POS', 'POS_PARCELADO', 'CPF_CNPJ', 'DEBITO_AUTOMATICO');

		// Popula variável do comando
        $this->data['forma_pagamento'] 		= $formaPagamento[rand(0,6)];
        $this->data['valor_centavos'] 		= rand(0,9999);
        $this->data['codigo_pagamento'] 	= rand(0,9);
        $this->data['informacoes_1'] 		= '';
        $this->data['informacoes_2'] 		= '';
        $this->data['informacoes_3'] 		= '';
        $this->data['informacoes_4'] 		= '';
        $this->data['cpf_cnpj_pagamento'] 	= '';
        $this->data['senha'] 				= '';
        $this->data['rps'] 					= '';
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX
	*/
	public function testIndexError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'ForbiddenException',
			'Payment Invalid Operation'
		);
	}// End method 'testIndexError'


	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action ADD
	*/
	public function testaddError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.$this->extension,
			'POST',
			$this->data,
			'ForbiddenException',
			'Payment Invalid Operation'
		);
	}// End method 'testaddError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW
	*/
	public function testViewError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'GET',
			$this->data,
			'ForbiddenException',
			'Payment Invalid Operation'
		);
	}// End method 'testViewError'
	
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT
	*/
	public function testEditError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'PUT',
			$this->data,
			'ForbiddenException',
			'Payment Invalid Operation'
		);
	}// End method 'testEditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE
	*/
	public function testDeleteError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'DELETE',
			$this->data,
			'ForbiddenException',
			'Payment Invalid Operation'
		);
	}// End method 'testDeleteError'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro FormaPagamento está incorreto
	 */
	public function testSemFormaPagamento() {
		// Remove campo do array de envio
		unset($this->data['forma_pagamento']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Forma de pagamento inválida'
		);
	}// End method 'testSemFormaPagamento'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro ValorCentavos está incorreto
	 */
	public function testSemValorCentavos() {
		// Remove campo do array de envio
		unset($this->data['valor_centavos']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Valor em centavos inválido'
		);
	}// End method 'testSemValorCentavos'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro CodigoPagamento está incorreto
	 */
	public function testSemCodigoPagamento() {
		// Remove campo do array de envio
		unset($this->data['codigo_pagamento']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Código de pagamento inválido'
		);
	}// End method 'testSemCodigoPagamento'
}// End Class