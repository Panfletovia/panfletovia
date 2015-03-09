<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

class OcrControllerTest extends ApiBaseControllerTestCase {

    public $mockUser = false;

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/ocr';
	// Variável que recebe a extensão para teste
	private $extension = '.json';

	/**
	 * Rotina executada antes de cada teste
	 */
	public function setUp () {
		parent::setUp ();

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
		
		$this->data = $this->getApiDefaultParams ();
	}

    /**
	* Testa se a API retorna uma exceção ao efetuar-se um request para a action INDEX
	*/
	public function testIndexError() {
		$this->validateTestException($this->URL . '/index' . $this->extension, 'GET', $this->data, 'NotImplementedException', '');
	}// End method 'testIndexError'

	/**
	* Testa se a API retorna uma exceção ao efetuar-se um request para a action EDIT
	*/
	public function testEditError() {
		$this->validateTestException($this->URL . '/edit' . $this->extension, 'PUT', $this->data, 'NotImplementedException', '');
	}// End method 'testEditError'

	/**
	* Testa se a API retorna uma exceção ao efetuar-se um request para a action ADD cujo Content-Type não seja multipart/form-data
	*/
	public function testAddError() {
		$this->validateTestException($this->URL . '/add' . $this->extension, 'POST', $this->data, 'ApiException', 'Foto não recebida');
	}// End method 'testAddError'

	// /**
	// * Testa se a API retorna uma exceção ao efetuar-se um request para a action ADD cujo Content-Type não seja multipart/form-data
	// */
	// public function testAdd() {
	// 	$stub = $this->getMock('OcrController', array('is_uploaded_file', 'move_uploaded_file'));

	// 	$stub->expects($this->any())
	// 	->method('is_uploaded_file')
	// 	->will($this->returnValue(TRUE));

	// 	$stub->expects($this->any())
	// 	->method('move_uploaded_file')
	// 	->will($this->returnCallback('copy'));

	// 	$_FILES['file']['name'] = 'IOX-6056_1410441347.jpg';
	// 	$_FILES['file']['type'] = 'image/jpeg';
	// 	$_FILES['file']['tmp_name'] = '/opt/lampp/temp/IOX-6056_1410441347.jpg';
	// 	$_FILES['file']['error'] = 0;
	// 	$_FILES['file']['size'] = 192601;

	// 	$this->testAction($this->URL . '/add' . $this->extension, array('method' => 'POST', 'data' => $this->data));		
	// }// End method 'testAddError'

	/**
	* Testa se a API retorna uma exceção ao efetuar-se um request para a action VIEW
	*/
	public function testViewError() {
		$this->validateTestException($this->URL . '/view' . $this->extension, 'GET', $this->data, 'NotImplementedException', '');
	}// End method 'testViewError'		

	/**
	* Testa se a API retorna uma exceção ao efetuar-se um request para a action DELETE
	*/
	public function testDeleteError() {
		$this->validateTestException($this->URL . '/delete' . $this->extension, 'DELETE', $this->data, 'NotImplementedException', '');
	}// End method 'testDeleteError'		
}
