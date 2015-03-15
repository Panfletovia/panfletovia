<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

class ApiTarifasControllerTest extends ApiBaseControllerTestCase {
	
	/**
	 *
	 * @var unknown
	 */
	public $mockUser = false;
	
	public $uses = array('Tarifa');

	/**
	 * Testa o request de tarifa e seu retorno
	 */
	public function testIndex() {
		// Cria uma tarifa genérica
		$tarifaGenerica = $this->dataGenerator->getTarifa();
		$this->dataGenerator->saveTarifa($tarifaGenerica);
		
		// Cria um posto para a tarifa específica
		$this->dataGenerator->savePosto();
		// Cria a tarifa específica
		$tarifaEspecifica = $this->dataGenerator->getTarifa();
		$tarifaEspecifica['Tarifa']['posto_id'] = $this->dataGenerator->postoId;
		$this->dataGenerator->saveTarifa($tarifaEspecifica);
		
		// Faz o request
		$this->testAction('api/apitarifas.json', array('method' => 'GET', 'return' => 'vars'));

		$this->assertEquals($this->vars['data']['valores_recarga']['valor_minimo'], $tarifaGenerica['Tarifa']['valor_recarga_minima']);
		$this->assertEquals($this->vars['data']['valores_recarga']['valor_maximo'], $tarifaGenerica['Tarifa']['valor_recarga_maxima']);
	}

	/**
	 * Testa se o controller vai retornar uma tarifa específica (só deve retornar tarifa genérica)
	 */
	public function testIndexEmptyValues() {
		// Cria um posto para a tarifa específica
		$this->dataGenerator->savePosto();
		// Cria a tarifa específica
		$tarifaEspecifica = $this->dataGenerator->getTarifa();
		$tarifaEspecifica['Tarifa']['posto_id'] = $this->dataGenerator->postoId;
		$this->dataGenerator->saveTarifa($tarifaEspecifica);
		
		// Faz o request
		$this->testAction('api/apitarifas.json', array('method' => 'GET', 'return' => 'vars'));

		$this->assertEmpty($this->vars['data']['valores_recarga'], 'Valores foram indevidamente retornados.');
	}

}