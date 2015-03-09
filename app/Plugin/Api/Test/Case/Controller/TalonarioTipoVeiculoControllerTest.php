<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe TaxiControllerTest
 */
class TalonarioTipoVeiculoControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Equipamento',
						 'Talonario.TipoVeiculo');

	public function setUp() 
	{
		parent::setUp();

		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('no_serie' => 'PHPUNIT',
																		   'situacao' => 'ATIVO',
																		    'servico' => 'TAXI',
		                                                                     'modelo' => 'VX520',
		                                                                       'tipo' => 'POS'))); // Add an equipament
		$this->dataGenerator->saveTipoVeiculo(); // Add a vehicule type
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

		$url = $this->url('talonariotipoveiculo', 'index');
    	
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); 
    	} catch(Exception $e) 
    	{    		   		
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data'][0], 'Must return a list of vehicule types');		
		$this->assertEqual($this->vars['data'][0][0]['TipoVeiculo']['descricao'], 'PHPUNIT', 'Must return this data');
	}
}
?>