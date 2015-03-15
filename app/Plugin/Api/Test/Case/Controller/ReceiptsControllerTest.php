<?php
/**
 * Arquivo AuthorizationControllerTest.php
 */

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes do comando de autorização da API
 */
class ReceiptsControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Operador',
		'Parking.Area',
		'Parking.Preco',
		'Produto',
		'Parking.Cobranca',
		'Parking.Setor',
		'Parking.Servico',
		'Equipamento',
		'Recibo',
		'Posto'
	);

	// Variável que recebe os campos default das transações
	private $data      = NULL;
	// Variável que recebe a extensão que espera retornar as informações
	private $extension = '.json';
	// Variável que recebe a URL completa
	private $fullURL;
	// Variável que recebe a URL apenas com o controller para testar VIEW, que necessita passar o ID na URL
	private $URL;

	// Método para inicializar as variáveis de URL para serem utilizadas nos testes.
	function __construct(){
		parent::__construct();
		// Cria URL 
		$this->URL = '/api/receipts';
		// Cria a url completa.
		$this->fullURL   = $this->URL . $this->extension;
	}

	/**
	 * Método que é executado antes de cada testes
	 */
	public function setUp() {
		parent::setUp();

		// Salva registros necessários para efetuar testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890')));
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => null)));

		$reciboEntrada = array('Recibo' => 
								array('leiaute_id' => 2,
									'alvo' => 'DATECS')
								);
		$reciboSaida = $this->dataGenerator->getRecibo();
		$this->dataGenerator->saveRecibo($reciboEntrada);
		$this->dataGenerator->saveRecibo($reciboSaida);



		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
		$this->data['target'] = DATECS;
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action ADD, 
	* pois na classe só deverá tratar o INDEX
	*/
	public function test_AddError() {
		$this->validateTestException(
			$this->fullURL,
			'POST',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End Method 'test_AddError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, 
	* pois na classe só deverá tratar o INDEX
	*/
	public function test_ViewError(){
		$this->validateTestException(
			$this->URL . '/1' . $this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End Method 'test_ViewError'
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, 
	* pois na classe só deverá tratar o INDEX
	*/
	public function test_EditError(){
		$this->validateTestException(
			$this->fullURL,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End Method 'test_EditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, 
	* pois na classe só deverá tratar o INDEX
	*/
	public function test_DeleteError(){
		$this->validateTestException(
			$this->fullURL,
			'DELETE',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End Method 'test_DeleteError'

	/**
	 * Testa acesso a API via GET, esperando exceção de "Forbidden" e 
	 * a mensagem que o modelo de impressora não foi recebido
	 */
	public function test_SemTarget() {
		// Remove campo do array de envio
		unset($this->data['target']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->fullURL,
			'GET',
			$this->data,
			'ApiException',
			'Modelo de impressora não recebido'
		);
	}// End Method 'test_SemTarget'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, 
	* pois na classe só deverá tratar o INDEX
	*/
	public function test_Full(){
		// Acessa o link da API
		$this->sendRequest($this->fullURL, 'GET', $this->data);
		// Testa se todos os campos esperados foram recebidos
		$this->assertNotNull($this->vars['data']);
		$this->assertNotNull($this->vars['data']['recibos']);
		$this->assertEquals(2, count($this->vars['data']['recibos']));
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_ENTRADA']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_ENTRADA']['leiaute_id']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_ENTRADA']['modelo']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_ENTRADA']['codigo_barras']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_SAIDA']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_SAIDA']['leiaute_id']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_SAIDA']['modelo']);
		$this->assertNotNull($this->vars['data']['recibos']['PARKING_SAIDA']['codigo_barras']);
	}// End Method 'test_Full'

}// End Class