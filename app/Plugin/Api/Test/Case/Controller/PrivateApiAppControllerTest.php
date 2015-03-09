<?php

/**
  * @author André Meirelles, Cristian Dietrich
  */

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

	/**
	 * Classe PrivateApiAppControllerTest
	 */
	Class PrivateApiAppControllerTest extends ApiBaseControllerTestCase {

		public $mockUser = false;
		public $uses = array(
			'Equipamento', 
			'Comunicacao', 
			'Associado', 
			'Comissao', 
			'Entidade',
			'Parking.Servico'
		);

		// Variável que recebe o endereço da API para requisição
		private $URL 	= '/api/authorization.json';
		// Variável que recebe os dados a serem enviados na requisição
		private $data 	= NULL;

		/**
		 * Método que é executado a cada teste
		 */
		public function setUp() {
			parent::setUp();
			// Popula array dos dados default
			$this->data = $this->getApiDefaultParams();
			// Salva dados default
			$this->dataGenerator->savePosto();
			$this->dataGenerator->saveAssociado();
			$this->dataGenerator->saveProduto();
			$this->dataGenerator->saveComissao();
			$this->Comissao->id = $this->dataGenerator->comissaoId;
			$this->Comissao->saveField('posto_id', null);
			$this->dataGenerator->saveTarifa();
	        $this->dataGenerator->savePreco();
	        $this->parkTarifa = $this->dataGenerator->getParkTarifa();
	        $this->dataGenerator->saveParkTarifa($this->parkTarifa);
	        $this->dataGenerator->saveCobranca();
	        $this->dataGenerator->saveArea(array('Area' => array('nome' => 'Área Versul', 'debito_automatico_apos_tolerancia' => '0',)));
	        $this->dataGenerator->saveSetor();
	        $this->dataGenerator->saveOperador();
	        $this->dataGenerator->saveCliente(array('Cliente' => array('autorizar_debito' => 1)));
	        $this->dataGenerator->saveAreaPonto(array('AreaPonto' => array('codigo' => 1)));
		}

		/**
		 * Testa a configuração automática de um smartphone 
		 * ainda não cadastrado na base
		 */
		public function testRegister() {

			// Testa se já existe este equipamento cadastrado na base
			$equipamento = $this->Equipamento->findByNoSerie($this->data['serial']);
			$this->assertTrue(empty($equipamento), 'Já existe um equipamento cadastrado na base.');
			//Cria serviço para o equipamento cadastrado
			try {
				// Executa uma action sem equipamento cadastrado, esperando erro
				$this->testAction($this->URL, array('method' => 'POST','data' => $this->data));
			} catch (Exception $e) {
				// Testa se o erro acionado é o esperado
				$this->assertEqual($e->getMessage(), 'Equipamento não vinculado', 'Mensagem incorreta exibida: ' . $e->getMessage());
			}
			// Testa se o equipamento foi criado na base
			$equipamento = $this->Equipamento->findByNoSerie($this->data['serial']);
			// Valida se equipamento foi encontrado
			$this->assertTrue(!empty($equipamento), 'Equipamento não foi cadastrado.');
			// Valida se o número serial do equipamento é o mesmo do que o cadastrado
			$this->assertEqual($equipamento['Equipamento']['no_serie'], $this->data['serial'], 'Serial divergente.');
		}// End method 'testRegister'
		
		/**
		 * Testa o retorno de associado não encontrado
		 */
		public function testAccessEquipamentoSemAssociado() {
			// Salva um equipamento sem vínculo com associado
			$this->dataGenerator->saveEquipamento(array('Equipamento' => array('administrador_id' => null, 'no_serie' => '111')));
			// Seta os valores para os campos padrões
			$data = $this->getApiDefaultParams();
			$data['serial'] = '111';
			try {
				// Executa uma action sem equipamento cadastrado, esperando erro
				$this->testAction($this->URL, array('method' => 'POST','data' => $data));
			} catch (Exception $e) {
				// Testa se o erro acionado é o esperado
				$this->assertEqual($e->getMessage(), 'Associado não encontrado', 'Mensagem incorreta exibida: ' . $e->getMessage());
			}
		}// End method 'testAccessEquipamentoSemAssociado'

		/**
		 * Testa a configuração com dados inválidos
		 */
		public function testRegisterInvalidData() {
			// Seta os valores para os campos padrões
			$data = $this->getApiDefaultParams();
			// Altera o pacote de dados para gerar um cadastro inválido
			$data['type'] = 'INVALIDO';
			try {
				// Executa uma action sem equipamento cadastrado, esperando erro
				$this->testAction($this->URL, array('method' => 'POST','data' => $data));
			} catch (Exception $e) {
				// Testa se o erro acionado é o esperado
				$this->assertEqual($e->getMessage(), 'Não foi possível salvar o equipamento', 'Mensagem incorreta exibida: ' . $e->getMessage());
			}
		}// End method 'testRegisterInvalidData'


	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de parâmetro DateTime está incorreto
	*/
	public function testSemDateTime() {
		// Retira o campo de SetorId
		unset($this->data['datetime']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Data inválida'
		);
	}// End method 'testSemDateTime'

	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de parâmetro Serial está incorreto
	 */
	public function testSemSerial() {
		// Retira o campo de SetorId
		unset($this->data['serial']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Serial inválido'
		);
	}// End method 'testSemSerial'

	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de parâmetro Version Commands está incorreto
	 */
	public function testSemVersion() {
		// Retira o campo de SetorId
		unset($this->data['version']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Versão inválida'
		);
	}// End method 'testSemVersionCommands'

	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de parâmetro NSU está incorreto
	 */
	public function testSemNSU() {
		// Retira o campo de SetorId
		unset($this->data['nsu']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'O campo NSU é obrigatório'
		);
	}// End method 'testSemNSU'

	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de o parâmetro Model está incorreto
	 */
	public function testSemModel() {
		// Retira o campo de SetorId
		unset($this->data['model']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Modelo inválido'
		);
	}// End method 'testSemModel'

	/**
	 * Testa acesso a API via POST, esperando exceção de "BadRequest" e a mensagem de parâmetro Type está incorreto
	 */
	public function testSemType() {
		// Retira o campo de type
		unset($this->data['type']);
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Tipo de equipamento inválido'
		);
	}// End method 'testSemType'

	/**
	 * Testa acesso a API, com uma versão antiga de aplicação, pois deverá lançar um erro indicando que as versões de API
	 * e aplicativo não são compatíveis.
	 */
	public function testOldVersionInRequest(){
		// Altera versão do request esperando erro
		$this->data['version'] = 99999;

		$equipamento = $this->dataGenerator->getEquipamento();
		$this->dataGenerator->saveEquipamento($equipamento);

		// Altera versão do request esperando erro
		$this->data['serial'] = $equipamento['Equipamento']['no_serie'];

		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveAreaEquipamento();

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Comando não processado. Por favor atualize seu aplicativo!'
		);
	}// End Method 'testOldVersionInRequest'

	/**
	 * Método para forçar o erro de bloqueio de comando por ter encerrado o serviço manualmente
	 */
	public function test_BloqueioComandoFechamentoForcado(){
		// Salva o equipamento
		$equipamento = $this->dataGenerator->getEquipamento();
		$this->dataGenerator->saveEquipamento($equipamento);

		// Altera o equipamento
		$this->data['serial'] = $equipamento['Equipamento']['no_serie'];

		// Salva area, areaEquipamento e o serviço
		$this->dataGenerator->saveArea();
		$this->dataGenerator->saveAreaEquipamento();
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => $this->dataGenerator->getDateTime('-30 minutes'))));

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Comando não processado: o caixa foi encerrado manualmente. Por favor verifique com a sua operação.'
		);
	} // End Method 'test_BloqueioComandoFechamentoForcado'
}// End Class