<?php
App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe TaxiControllerTest
 */
class TaxiProviderControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Acompanhamento',
						 'Taxi.Chamada',
		                 'Equipamento',		                 
		                 'Taxi.Regra',
		                 'Entidade',
		                 'Monitor');

	public function setUp() 
	{
		parent::setUp();

		$cliente = array();

		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('no_serie' => 'PHPUNIT',
																		   'situacao' => 'ATIVO',
																		    'servico' => 'TAXI',
		                                                                     'modelo' => 'VX520',
		                                                                       'tipo' => 'POS'))); // Add an equipament
		$this->dataGenerator->saveCliente(array('Cliente' => array('cpf_cnpj' => '811.271.030-91', 'versao_contrato' => 1))); // Add a customer
		$this->dataGenerator->saveRegra(); // Add a rule	

		$cliente = $this->Entidade->find('first'); /// Get the customer
		$this->dataGenerator->chamada($cliente['Entidade']['id'], 'TAXI'); // Add an order
	}

	public function url($controller, $action)
	{
		return '/api/' . $controller . '/' . $action . '.json';
	}

	public function test_Provider_order()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		


		$url = $this->url('taxiprovider', 'order');

        $equipament = $this->Equipamento->find('first', array('conditions' => array('Equipamento.no_serie' => 'PHPUNIT', 
                                                                                            'Equipamento.situacao' => 'ATIVO')));

		$monitor = $this->Monitor->find('first', array('conditions' => array('equipamento_id' => $equipament['Equipamento']['id']), 'recursive' => -1));

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['situacao'] = 'LIVRE';
		           $data['id'] = $monitor['Monitor']['id'];
		
		$this->Monitor->save($data);

		unset($data['situacao']);
		unset($data['id']);

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try geting the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order'][0]['id'], 'Must return the id of order');    	
	}
	
	public function _test_Provider_take()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$url = $this->url('taxiprovider', 'order');

        $equipament = $this->Equipamento->find('first', array('conditions' => array('Equipamento.no_serie' => 'PHPUNIT', 
                                                                                            'Equipamento.situacao' => 'ATIVO')));

		$monitor = $this->Monitor->find('first', array('conditions' => array('equipamento_id' => $equipament['Equipamento']['id']), 'recursive' => -1));

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['situacao'] = 'LIVRE';
		           $data['id'] = $monitor['Monitor']['id'];
		
		$this->Monitor->save($data);

		unset($data['situacao']);
		unset($data['id']);

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try taking the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order'][0]['id'], 'Must return the id of order');    	

		$orderId = $this->vars['data']['order'][0]['id'];

		unset($data);

		$url = $this->url('taxiprovider', 'take');

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['order_id'] = $orderId;

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try taking the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}

    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['take']['order_id'], 'Must return the id of order');    	
	}

	public function _test_Provider_status()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

        $equipament = $this->Equipamento->find('first', array('conditions' => array('Equipamento.no_serie' => 'PHPUNIT', 
                                                                                            'Equipamento.situacao' => 'ATIVO')));

		$monitor = $this->Monitor->find('first', array('conditions' => array('equipamento_id' => $equipament['Equipamento']['id']), 'recursive' => -1));

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['situacao'] = 'LIVRE';
		           $data['id'] = $monitor['Monitor']['id'];
		
		$this->Monitor->save($data);

		unset($data['situacao']);
		unset($data['id']);
    	
		$url = $this->url('taxiprovider', 'order');

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // list the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order'][0]['id'], 'Must return the id of order');    	

		$orderId = $this->vars['data']['order'][0]['id'];

		$url = $this->url('taxiprovider', 'take');

		$data['order_id'] = $orderId;

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try getting status the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['take']['order_id'], 'Must return the id of order'); 

		$url = $this->url('taxiprovider', 'status');
    	
    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try getting status the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['status']['order_id'], 'Must return the id of order'); 
	}

	public function _test_Provider_cancel()
	{
		$errorMessage = NULL;
    	 $finalTester = false;    	
		        $data = array();		

		$url = $this->url('taxiprovider', 'order');

        $equipament = $this->Equipamento->find('first', array('conditions' => array('Equipamento.no_serie' => 'PHPUNIT', 
                                                                                            'Equipamento.situacao' => 'ATIVO')));

		$monitor = $this->Monitor->find('first', array('conditions' => array('equipamento_id' => $equipament['Equipamento']['id']), 'recursive' => -1));

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['situacao'] = 'LIVRE';
		           $data['id'] = $monitor['Monitor']['id'];
		
		$this->Monitor->save($data);

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try taking the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}
    	
    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['order'][0]['id'], 'Must return the id of order');    	

		$orderId = $this->vars['data']['order'][0]['id'];

		unset($data);

		$url = $this->url('taxiprovider', 'take');

		$data['serial_device'] = 'PHPUNIT';
		    $data['longitude'] = customer_longitude;		
		     $data['latitude'] = customer_latitude;
		     $data['order_id'] = $orderId;

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try taking the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}

    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['take']['order_id'], 'Must return the id of order');    	

		$url = $this->url('taxiprovider', 'cancel');

    	try 
    	{
			$this->sendRequest($url, 'POST', $data); // Try canceling the order
    	} catch(Exception $e) 
    	{
    		$finalTester = true;
    		$errorMessage = $e->getMessage();
    	}

    	$this->assertFalse($finalTester, 'Unexpected exeption: ' . $errorMessage);
		$this->assertNotEmpty($this->vars['data']['cancel']['order_id'], 'Must return the id of order');  
	}
}	

?>