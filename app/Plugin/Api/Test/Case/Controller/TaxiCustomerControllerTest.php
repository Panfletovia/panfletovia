<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe TaxiControllerTest
 */
class TaxiCustomerControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Acompanhamento',
		                 'Equipamento',		                 
		                 'Taxi.Regra',
		                 'Entidade',
		                 'Monitor');

	public function setUp() 
	{
		parent::setUp();

		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('no_serie' => 'PHPUNIT',
																		   'situacao' => 'ATIVO',
																		    'servico' => 'TAXI',
		                                                                     'modelo' => 'VX520',
		                                                                       'tipo' => 'POS'))); // Add an equipament
		$this->dataGenerator->saveCliente(); // Add a customer
		$this->dataGenerator->saveRegra(); // Add a rule
	}

	public function url($controller, $action)
	{
		return '/api/' . $controller . '/' . $action . '.json';
	}

	public function test_Customer_Order()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$cliente = $this->Entidade->find('first', array('recursive' => -1));

		$url = $this->url('taxicustomer', 'order');

		$data['passenger_id'] = $cliente['Entidade']['id'];
		 $data['customer_id'] = $cliente['Entidade']['id'];
		  $data['round_trip'] = 1;
		   $data['big_trunk'] = 1;
		   $data['longitude'] = customer_longitude;		
		    $data['latitude'] = customer_latitude;
		     $data['service'] = 'TAXI';
		     $data['address'] = 'Rua Marcílio Dias';
		      $data['number'] = 1659;		

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try placing the order
    	} catch(Exception $e) 
    	{    		   		
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order']['id'], 'Must return the id of order');
	}

	public function _test_Customer_MoreThanOne()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$cliente = $this->Entidade->find('first', array('recursive' => -1));

		$url = $this->url('taxicustomer', 'order');

		$data['passenger_id'] = $cliente['Entidade']['id'];
		 $data['customer_id'] = $cliente['Entidade']['id'];
		  $data['round_trip'] = 1;
		   $data['big_trunk'] = 1;
		   $data['longitude'] = customer_longitude;		
		    $data['latitude'] = customer_latitude;
		     $data['service'] = 'TAXI';
		     $data['address'] = 'Rua Marcílio Dias';
		      $data['number'] = 1659;		
		      
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try placing the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order']['id'], 'Must return the id of order');

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try placing the order again
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertTrue($finalTester, 'Cant place 2 orders at same time');
	}

	public function _test_Customer_Cancel()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$cliente = $this->Entidade->find('first', array('recursive' => -1));

		$url = $this->url('taxicustomer', 'order');

		$data['passenger_id'] = $cliente['Entidade']['id'];
		 $data['customer_id'] = $cliente['Entidade']['id'];
		  $data['round_trip'] = 1;
		   $data['big_trunk'] = 1;
		   $data['longitude'] = customer_longitude;		
		    $data['latitude'] = customer_latitude;
		     $data['service'] = 'TAXI';
		     $data['address'] = 'Rua Marcílio Dias';
		      $data['number'] = 1659;		
		      
    	try {
			$this->sendRequest($url, 'POST', $data); // Try placing the order
    	} catch(Exception $e) {
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order']['id'], 'Must return the id of order');

		$order_id = $this->vars['data']['order']['id'];

		$url = $this->url('taxicustomer', 'cancel');

		unset($data);

		$data['customer_id'] = $cliente['Entidade']['id'];
		   $data['order_id'] = $order_id;

    	try {
			$this->sendRequest($url, 'POST', $data); // Try canceling the order
    	} catch(Exception $e) {
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}

    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertEquals($order_id, $this->vars['data']['cancel']['order_id'], 'Cancel the order sent');
	}

	public function _test_Customer_Board() {
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$cliente = $this->Entidade->find('first', array('recursive' => -1));

		$url = $this->url('taxicustomer', 'order');

		$data['passenger_id'] = $cliente['Entidade']['id'];
		 $data['customer_id'] = $cliente['Entidade']['id'];
		  $data['round_trip'] = 1;
		   $data['big_trunk'] = 1;
		   $data['longitude'] = customer_longitude;		
		    $data['latitude'] = customer_latitude;
		     $data['service'] = 'TAXI';
		     $data['address'] = 'Rua Marcílio Dias';
		      $data['number'] = 1659;		
		      
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try placing the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order']['id'], 'Must return the id of order');

		$order_id = $this->vars['data']['order']['id'];

		$url = $this->url('taxicustomer', 'board');

		unset($data);

		$data['customer_id'] = $cliente['Entidade']['id'];
		   $data['order_id'] = $order_id;
	}
}

?>