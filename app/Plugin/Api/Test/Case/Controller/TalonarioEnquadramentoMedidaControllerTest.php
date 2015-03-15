<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe TaxiControllerTest
 */
class TalonarioEnquadramentoMedidaControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Equipamento',
						 'Talonario.EnquadramentoMedida',
						 'Talonario.Medida',
						 'Talonario.Enquadramento');

	public function setUp() 
	{
		parent::setUp();

		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('no_serie' => 'PHPUNIT',
																		   'situacao' => 'ATIVO',
																		    'servico' => 'TAXI',
		                                                                     'modelo' => 'VX520',
		                                                                       'tipo' => 'POS'))); // Add an equipament
		$this->dataGenerator->saveEnquadramento(); // Add a taxe
		$this->dataGenerator->saveMedida(); // Add an action
		$this->dataGenerator->saveEnquadramentoMedida(); // Add link
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

		$url = $this->url('talonarioenquadramentomedida', 'index');
    	
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); 
    	} catch(Exception $e) 
    	{    		   		
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data'][0], 'Must return a list of linked tables');		
		$this->assertEqual($this->vars['data'][0][0]['EnquadramentoMedida']['enquadramento_id'], 1, 'Must return this data');
	}
}
?>