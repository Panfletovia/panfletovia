<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe PeriodPurchaseControllerTest
 */
class PeriodPurchaseControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $components = array('PeriodPurchase', 'Db');

	public $uses = array(
		'Parking.Operador',
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.Preco',
		'Produto',
		'Parking.Cobranca',
		'Plugin',
		'Parking.Placa',
		'Parking.ContratoPlaca',
		'Equipamento',
		'Parking.Historico',
		'Parking.ParkTarifa'
		);

	// Variável que recebe os campos default das tidy_warning_count(object)ransações
	private $data = NULL;
	// Variável que recebe a extensão a ser retornada pelo WebService
	private $extension = '.json';
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/period_purchase';
	// Variável que recebe a quantidade de períodos a serem testados
	private $qtdePeriodos = 1;
	// Variável que recebe informações da park_tarifa a ser utilizada, gerada pelo random.
	private $parkTarifa;

	public function setUp() {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePreco();
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveSetor();
		$this->dataGenerator->saveAreaPonto();
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890')));
		$this->dataGenerator->saveServico();

		$initialMinutes = 5;
		$initialValue   = 5.00;

		for($x = 1; $x <= 5; $x++){
			$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
					'minutos' => $initialMinutes * $x,
					'valor'   => $initialValue * $x,
					'codigo'  => $x
			)));
		}

		// Setá os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
		// Seta os valores para os parâmetros da classe
		// Seta quantidade de periodos a serem comprados.
		$this->qtdePeriodos = 1;
		// Busca informações da vaga
		$this->areaPonto =  $this->AreaPonto->findById($this->dataGenerator->areapontoId);

		// Seta dados que irão ser enviado
        $this->data['qtde_periodos'] 	= $this->qtdePeriodos;
        $this->data['placa'] 			= 'AND'.rand(1000,9999);
        $this->data['vaga'] 			= $this->areaPonto['AreaPonto']['codigo'];
        $this->data['tipo_veiculo'] 	= 'CARRO';
        $this->data['area_id'] 	= $this->dataGenerator->areaId;
        $this->data['contrato'] 	= '1';
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action add, pois na classe só deverá tratar a index
	*/
	public function testAddError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End method 'testAddError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, pois na classe só deverá tratar a index
	*/
	public function testViewError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End method 'testViewError'
	
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, pois na classe só deverá tratar a index
	*/
	public function testEditError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End method 'testEditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, pois na classe só deverá tratar a index
	*/
	public function testDeleteError(){
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'DELETE',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End method 'testDeleteError'


	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro QtdePeriodo está incorreto
	 */
	public function testSemQtdePeriodos() {
		// Remove campo do array de envio
		unset($this->data['qtde_periodos']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Quantidade de períodos não recebida'
		);
	}// End method 'testSemQtdePeriodos'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro Placa está incorreto
	 */
	public function testSemPlaca() {
		// Remove campo do array de envio
		unset($this->data['placa']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Placa não recebida'
		);
	}// End method 'testSemPlaca'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro TipoVeiculo está incorreto
	 */
	public function testSemTipoVeiculo() {
		// Remove campo do array de envio
		unset($this->data['tipo_veiculo']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Tipo de veículo não recebido'
		);
	}// End method 'testSemTipoVeiculo'

	/**
	 * Testa acesso a API, esperando exceção de "InternalError" e a mensagem de 
	* que o veículo não necessita pagamento pois é isento
	 */
	public function testVeiculoIsento() {
		// Cria um contrato de isenção para a placa para gerar a exceção esperada
		/*$this->dataGenerator->saveContrato();
		$this->dataGenerator->saveContratoPlaca(array('ContratoPlaca' => array('placa' => $this->data['placa'])));*/
		// Salva contrato de isenção para lançar exceção por este motivo
        $contrato = $this->dataGenerator->getContrato();
        $this->dataGenerator->saveContrato($contrato);
        $parkContratoPlaca = $this->dataGenerator->getContratoPlaca($this->data['placa']);
        $parkContratoPlaca['Contrato'] = $contrato['Contrato'];
        $this->ContratoPlaca->save($parkContratoPlaca);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL . $this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Compra bloqueada: veículo isento'
		);
	}// End method 'testVeiculoIsento'

	/**
	 * Testa o comportamento da API para uma tentativa de compra de periodo para uma vaga do tipo ISENTO
	 *
	 * A API deve lancar a exceção uma vez que esse tipo de vaga e isenta de cobranca
	 */
	public function testVagaIsento() {
		
        $this->areaPonto['AreaPonto']['tipo_vaga'] = 'ISENTO';
        $this->AreaPonto->save($this->areaPonto);
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL . $this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Compra bloqueada: vaga isenta'
		);
	}// End method 'testVagaIsento'
	
	/**
	 * Testa o comportamento da API para uma tentativa de compra de periodo para uma vaga do tipo DEFICIENTE
	 *
	 * A API deve lancar a exceção uma vez que esse tipo de vaga e isenta de cobranca
	 */
	public function testVagaDeficiente() {
		
        $this->areaPonto['AreaPonto']['tipo_vaga'] = 'DEFICIENTE';
        $this->AreaPonto->save($this->areaPonto);
		
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL . $this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Compra bloqueada: vaga isenta'
		);
	}// End method 'testVagaDeficiente'

	/**
	 * Testa o comportamento da API para uma tentativa de compra de periodo para uma vaga cujo tipo nao possua preco, ou
	 * seja, a vaga e isenta mas nao e do tipo DEFICIENTE ou ISENTO
	 *
	 * A API deve lancar a exceção uma vez que esse tipo de vaga e isenta de cobranca
	 */
	public function testTipoVagaSemPreco() {
		// Atualiza a cobranca utilizada no teste para que a vaga do tipo IDOSO nao possua preco
		$this->Cobranca->id = $this->dataGenerator->cobrancaId;
		$this->Cobranca->set (array('preco_id_vaga_idoso' => NULL));
		$this->Cobranca->save ();
		
        $this->areaPonto['AreaPonto']['tipo_vaga'] = 'IDOSO';
        $this->AreaPonto->save($this->areaPonto);
		
		// Envia a requisição e valida a exceção recebida		
		$this->validateTestException(
			$this->URL . $this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Compra bloqueada: vaga isenta'
		);
	}// End method 'testTipoVagaSemPreco'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de 
	* que a vaga informada não existe, pois ultrapassa o limite do campo na base de dados (SMALLINT 5 UNSIGNED)
	 */
	public function testVagaNaoEncontrado() {

		$this->data['vaga'] = 99999;

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL . $this->extension,
			'GET',
			$this->data,
			'ApiException',
			'Vaga não encontrada'
		);
	}// End method 'testVeiculoIsento'

	/**
	* Método que efetua o teste completo, sem esperar exceção.
	*/
	public function testValidarRetorno() {

		// Acessa o link da API
		$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull($this->vars['data']['value'], 'Campo de valor de retorno da função está null');
		// Extrai o valor do retorno da função
		$valorRetorno = $this->vars['data']['value'];
		// Valida se o valor não é zero, pois não deverá ser feito neste teste.
		$this->assertNotEqual($valorRetorno, 0);
		// Busca informações da área
		$parkArea = $this->Area->findById($this->dataGenerator->areaId);
		// Valida se a área é válida
		$this->assertNotEmpty($parkArea);
		// Busca informações do preço
		$parkPreco = $this->Preco->findById($this->dataGenerator->precoId);
		// Valida se o preço é valido
		$this->assertNotEmpty($parkPreco);

		$valorTarifa = 5.00;

		// Verifica se os valores são iguais
		$this->assertEqual($valorRetorno, 100 * $valorTarifa, 'Valor do retorno da função e calculado são diferentes.');
	}// End Method 'testValidarRetorno'

	/*
	 * Faz o teste sem enviar a area.
	 */
	public function testSemArea() {
		// Remove campo do array de envio
		unset($this->data['area_id']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Área inválida'
		);
	}

	/*
	 * Faz o teste pegando uma Area que não existe.
	 */
	public function testSemAreaValida() {
		// Remove campo do array de envio
		$this->data['area_id'] = 10;
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Área não encontrada'
		);
	}

	/*
	 * Faz o teste sem a quantidade de tarifas.
	 */
	public function testSemQtdePeriodosTempo() {
		$this->ParkTarifa->deleteAll(array('minutos > 0'));

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'ApiException',
			'Quantidade de períodos/minutos inválida'
		);
	}

	// /**
	// * Método que efetua o teste completo, sem esperar exceção.
	// */
	// public function testValidarRetornoComTelefone() {
	// 	// Salva Cliente
	// 	$entidade = $this->dataGenerator->getEntidade();
 //        $entidade['Entidade']['tipo'] = 'CLIENTE';
 //        $entidade['Entidade']['negocio_id'] = 1;
 //        $this->dataGenerator->saveEntidade($entidade);      
 //        // Salva plugin
	// 	$this->dataGenerator->savePlugin();
	// 	// Salva registro na park_placa
	// 	$placa = $this->dataGenerator->getPlaca();
	// 	$this->dataGenerator->savePlaca($placa);
	// 	// Sobrescreve parâmetro de placa
	// 	$this->data['placa'] = $placa['Placa']['placa'];
 //        // Adiciona parâmetro de telefone na requisição
	// 	$this->data['phone'] = $entidade['Entidade']['telefone'];

	// 	// Acessa o link da API
	// 	$this->sendRequest($this->URL . $this->extension, 'GET', $this->data);
	// 	// Valida se houve retorno da classe testada
	// 	$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
	// 	// Valida todos se todos os campos do retorno estão preenchidos
	// 	$this->assertNotNull($this->vars['data']['value'], 'Campo de valor de retorno da função está null');
	// 	// Extrai o valor do retorno da função
	// 	$valorRetorno = $this->vars['data']['value'];
	// 	// Valida se o valor não é zero, pois não deverá ser feito neste teste.
	// 	$this->assertNotEqual($valorRetorno, 0);
	// 	// Busca informações da área
	// 	$parkArea = $this->Area->findById($this->dataGenerator->areaId);
	// 	// Valida se a área é válida
	// 	$this->assertNotEmpty($parkArea);
	// 	// Busca informações do preço
	// 	$parkPreco = $this->Preco->findById($this->dataGenerator->precoId);
	// 	// Valida se o preço é valido
	// 	$this->assertNotEmpty($parkPreco);
	// 	// Calcula valor cobrado 
	// 	$valorCalculado = 2.00;
	// 	//Converte para centavos 
	// 	$valorCalculado = $valorCalculado * 100;
	// 	// Verifica se os valores são iguais
	// 	$this->assertEqual($valorRetorno, $valorCalculado, 'Valor do retorno da função e calculado são diferentes.');
	// }// End Method 'testValidarRetorno'
}// End Class