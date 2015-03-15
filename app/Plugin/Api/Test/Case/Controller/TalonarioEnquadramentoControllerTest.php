<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe TaxiControllerTest
 */
class TalonarioEnquadramentoControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Equipamento',
						 'Talonario.Enquadramento');

	public function setUp() 
	{
		parent::setUp();

		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('no_serie' => 'PHPUNIT',
																		   'situacao' => 'ATIVO',
																		    'servico' => 'TAXI',
		                                                                     'modelo' => 'VX520',
		                                                                       'tipo' => 'POS'))); // Add an equipament
		$this->dataGenerator->saveEnquadramento(); // Add an taxe
	}

	public function url($controller, $action)
	{
		return '/api/' . $controller . '/' . $action . '.json';
	}

	public function test_index()
	{
    	$data['serial_device'] = 'PHPUNIT';
				
		$errorMessage = NULL;
		 $finalTester = false;

		$url = $this->url('talonarioenquadramento', 'index');
    	
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); 
    	} catch(Exception $e) 
    	{    		   		
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data'][0], 'Must return a list of taxes');		
		$this->assertEqual($this->vars['data'][0][0]['Enquadramento']['descricao'], 'PHPUNIT', 'Must return this data');
	}
}
?>