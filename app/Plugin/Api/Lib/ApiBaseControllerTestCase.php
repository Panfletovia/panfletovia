<?php

App::uses('BaseControllerTestCase', 'Lib');

class ApiBaseControllerTestCase extends BaseControllerTestCase {
	
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Retorna um array com os parametros default
	 *
	 * @return array
	 */
	protected function getApiDefaultParams()
	{
		// Popula parâmetro DateTime
		$data['datetime'] = date('Y-m-d H:i:s');
		// Popula parâmetro Serial
		$data['serial'] = '1234567890';
		// Popula parâmetro Version Commands
		$data['version'] = API_VERSION;
		// Popula parâmetro NSU
		$data['nsu'] = '2';
		// Popula parâmetro tipo
		$data['type'] = EQUIPAMENTO_TIPO_SMARTPHONE;
		// Popula parâmetro Model
		$data['model'] = 'ANDROID';
		// Popula parâmetro Username
		$data['username'] = '1234567890';
		// Popula parâmetro PassWord
		$data['password'] = md5('1234567890');
		// Popula parâmetro api_key
		$data['api_key'] = md5(API_KEY.$data['nsu'].$data['serial']);
	
		return $data;
	}

	/**
	* Método para efetuar os testes que são esperados exceções
	* @param $urlFull 			- URL completa para ser testada na Action
	* @param $method  			- Forma de entrada de dados. Ex: POST, GET, PUT or DELETE
	* @param $data    			- Campos enviados
 	* @param $exceptionExpected - Classe da exception a ser esperada após envio de requisição
	* @param $messageException  - Mensagem esperada de exceção
	* @param $testContains		- Caso TRUE, a validação da mensagem da exceção é feita através do assertContains e não assertEquals
	*/ 
	protected function validateTestException(
		$urlFull = NULL,
		$method = NULL,
		$data = NULL,
		$exceptionExpected = NULL,
		$messageExpected = NULL,
		$testContains = false){
			
		$message = '';

		// Método para validar os parâmetros
		if(empty($urlFull) || empty($method) ||  empty($data) || empty($exceptionExpected)){
			throw new BadRequestException('Invalid Parameters for Test');
		}

		// Variável que verificará se ocorreu ou não a exceção esperada
		$finalTester = false;
		try {
			// Acessa o link da API
			$this->testAction($urlFull, array('method' => $method,'data' => $data));
		} catch (Exception $e) {
			// Caso ocorra exceção, valida se a classe  e a mensagem são as esperadas.
			$finalTester = $this->comparaException($e, $exceptionExpected, $messageExpected, $testContains);
			$message = $e->getMessage();
		}

		// Valida variável de exceção
		$this->assertTrue($finalTester, "Não ocorreu exceção esperada: $exceptionExpected => $message");
	}

	/**
	* Método para enviar a requisição de acordo com os parâmetros passados SEM ESPERAR EXCEÇÃO
	* @param $urlFull 			- URL completa para ser testada na Action
	* @param $method  			- Forma de entrada de dados. Ex: POST, GET, PUT or DELETE
	* @param $data    			- Campos enviados
	*/ 
	protected function sendRequest($urlFull = NULL, $method = NULL, $data = NULL){
		// Método para validar os parâmetros
		if(empty($urlFull) || empty($method) ||  empty($data)){
			throw new BadRequestException('Invalid Parameters for Test');
		}	
		// Acessa o link da API
		$this->testAction($urlFull , array('method' => $method,'data' => $data, 'return' => 'vars'));
		// $this->testAction($urlFull , array('method' => $method,'data' => $data));
	}

	/**
	 * Método para criar uma placa randômica.
	 * @return Placa padrão Brasil
	 */
	protected function getRandomPlace(){
		return 'AND'.rand(1000,9999);
	}// End method 'getRandomPlace'

	/**
	 * Método para criar uma tipo de veículo randômicamente
	 * @return 'CARRO' ou 'MOTO'
	 */
	protected function getRandomTypeVehicle(){
		$listType = array('CARRO', 'MOTO');
		return $listType[rand(0,1)];
	}// End method 'getRandomTypeVehicle'


	/**
	 * Método para calcular o valor a ser pago de acordo com a quantidade de períodos
	 * OBS: ESTE CALCULO É FEITO DESSA MANEIRA SIMPLES, MAS DEPENDE DAS CONFIGURAÇÕES DO PREÇO.
	 * @param  Integer $qtdePeriod 	Quantidade de períodos a serem adquiridos
	 * @return Double $value 		Valor referente a quantidade de períodos em centavos
	 */
	protected function getValueByAmoutPurchasePeriod($qtdePeriodo, $precoId = null){
		$tarifa = $this->ParkTarifa->findByPrecoIdAndCodigo($precoId, $qtdePeriodo);
		$this->assertTrue(!!$tarifa, 'Tarifa não encontrada');
		// Retorna o valor em centavos
		return $tarifa['ParkTarifa']['valor'] * 100;
	}// End method 'getValueByAmoutPurchasePeriod'
}// End class