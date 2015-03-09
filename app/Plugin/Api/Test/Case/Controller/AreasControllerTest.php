<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

class AreasControllerTest extends ApiBaseControllerTestCase {
	
	/**
	 *
	 * @var unknown
	 */
	public $mockUser = false;
	
	public $uses = array('Parking.Area');

	/**
	 * Testa o request de áreas e seu retorno
	 */
	public function testIndex() {
		// Cria duas áreas
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveArea();
		
		// Faz o request
		$this->testAction('api/areas.json', array('method' => 'GET', 'return' => 'vars'));

		// Testa se a api retornou áreas
		$this->assertNotNull($this->vars['data']['areas'], 'Dados de área não retornados!');
		// Testa se a api retornou o número correto de áreas
		$this->assertEquals(count($this->vars['data']['areas']), 2, 'Número incorreto de áreas retornado');
		// Testa se os campos necessários estão presentes
		$this->assertNotNull($this->vars['data']['areas'][0]['id'], 'Id não foi retornado');
		$this->assertNotNull($this->vars['data']['areas'][0]['nome'], 'Id não foi retornado');
	}

	/**
	 * Testa o request de detalhes de uma área específica
	 */
	public function testView() {
		// Cria duas áreas
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveParkTarifa();
		$this->dataGenerator->saveArea();
		
		// Faz o request
		$this->testAction("api/areas/{$this->dataGenerator->areaId}.json", array('method' => 'GET', 'return' => 'vars'));

		// Testa se a api retornou os dados da área
		$this->assertNotNull($this->vars['data']['area'], 'Dados da área não retornados!');
		// Testa os dados dos preços
		$this->assertNotNull($this->vars['data']['precos'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['PrecoCarro'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['PrecoMoto'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['VagaFarmacia'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['VagaIdoso'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['IrregularidadeVencido'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['IrregularidadeSemticket'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['IrregularidadeForaVaga'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['IrregularidadeTicketIncompativel'], 'Dados de preços não retornados!');
		$this->assertNotNull($this->vars['data']['precos']['PrecoCarro']['tarifas'], 'Dados de tarifa não retornados!');
	}

	/**
	 * Testa o request de uma área inexistente
	 */
	public function testViewAreaInexistente() {
		$this->expectException('ApiException', 'Área não encontrada').
		// Faz o request
		$this->testAction("api/areas/1.json", array('method' => 'GET', 'return' => 'vars'));
	}
}