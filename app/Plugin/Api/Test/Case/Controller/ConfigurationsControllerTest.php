
<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Esta classe testa a chamada de dados de configuração de parking
 * @author André Meirelles
 */
class ConfigurationsControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array('Parking.Marca', 'Parking.Modelo', 'Parking.Cor', 'Equipamento');

	// Variável que recebe os campos default das transações
	private $data      = NULL;
	// Variável que recebe a extensão que espera retornar as informações
	private $extension = '.json';
	// Variável que recebe a URL completa
	private $url = '/api/configurations';
	// Tipo de configuração solicitada
	private $configName;

	/**
	 * Método que é executado antes de cada teste
	 */
	public function setUp() {
		parent::setUp();

		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveOperador();
		$this->dataGenerator->saveServico();

		// Seta os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
	}

	/**
	 * Testa a solicitação de marcas
	 */
	public function testBrands() {
		$this->Marca->unbindModel(array('hasMany' => array('Modelo')));
		$this->goTest('Marca', 'brands');
	}

	/**
	 * Testa a solicitação de modelos
	 */
	public function testModels() {
		$data = $this->Modelo->findOrderedByMarcaId();
		$this->sendRequest($this->url . '/models' . $this->extension, 'GET', $this->data);
		$this->assertEqual($this->vars['data']['models'], $data, "Modelos retornados não equivalem aos modelos da base.");
	}

	/**
	 * Testa a solicitação de cores
	 */
public function testColors() {
		$this->goTest('Cor', 'colors');
	}

	/**
	 * Testa a exception lançada quando não encontrar 
	 * os dados solicitados
	 */
	public function testBrandNotFoundException() {
		$this->Marca->deleteAll(array('id <> 0'));
		$this->validateTestException($this->url . '/brands' . $this->extension, 'GET', $this->data, 'ApiException', 'Arquivo de marcas não encontrado');
	}

	public function testModelNotFoundException() {
		$this->Modelo->deleteAll(array('id <> 0'));
		$this->validateTestException($this->url . '/models' . $this->extension, 'GET', $this->data, 'ApiException', 'Arquivo de modelos não encontrado');
	}

	public function testColorNotFoundException() {
		$this->Cor->deleteAll(array('id <> 0'));
		$this->validateTestException($this->url . '/colors' . $this->extension, 'GET', $this->data, 'ApiException', 'Arquivo de cores não encontrado');
	}

	/**
	 * Testa a tentativa de acessar alguma das 
	 * actions usando método que não seja GET
	 */
	public function testInvalidAccessMethod() {
		$action = array('brands', 'models', 'colors');
		$url = $this->url . '/' . $action[rand(0,count($action)-1)] . $this->extension;
		$method = array('put', 'post', 'delete');
		$this->validateTestException($url, $method[rand(0,count($method)-1)], $this->data, 'NotImplementedException', '');
	}

	/**
	 * Generaliza o teste de requisição de informações
	 */
	private function goTest($model, $action) {
		$data = $this->$model->find('all');
		$this->sendRequest($this->url . "/$action" . $this->extension, 'GET', $this->data);
		$this->assertEqual($this->vars['data'][$action], $data, "{$model}s retornados não equivalem aos modelos da base.");	
	}
}