<?php
/**
 */

App::uses ('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe de teste para o comando de fechamento de caixa
 */
class CashClosingControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Produto',
		'Equipamento',
		'Parking.Operador',
		'Parking.Area',
		'Parking.Preco',
		'Parking.Cobranca',
		'Parking.Servico',
		'Parking.ParkTarifa'
	);

	// Variável que recebe os campos default das transações
	private $data = NULL;
	// Variável que recebe a url para requisição do teste
	private $URL = '/api/cash_closing';
	// Variável que recebe a extensão para teste
	private $extension = '.json';

	/**
	 * Rotina executada antes de cada teste
	 */
	public function setUp () {
		parent::setUp ();
		// Salva registros necessários para efetuar os testes
		$this->dataGenerator->savePreco ();
		$this->dataGenerator->saveProduto ();
		$this->dataGenerator->saveCobranca ();
		$this->dataGenerator->saveArea ();
		$this->dataGenerator->saveEquipamento (array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID' ) ));
		$this->dataGenerator->saveOperador (array('Operador' => array('usuario' => '1234567890','senha' => '1234567890' ) ));
		$this->dataGenerator->saveServico (array('Servico' => array('data_fechamento' => NULL ) ));
		$this->dataGenerator->saveParkTarifa();
		
		// Popula os campos default
		$this->data = $this->getApiDefaultParams ();
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX, pois na classe só deverá tratar a add
	*/
	public function testIndexError() {
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testIndexError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, pois na classe só deverá tratar a add
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
	}// End 'testViewError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, pois na classe só deverá tratar a add
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
	}// End 'testEditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action DELETE, pois na classe só deverá tratar a add
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
	}// End 'testDeleteError'

	/**
	 * Testa acesso a API via POST, esperando exceção de "NotFound" e a mensagem que o equipamento não possui associado
	 */
	public function testEquipamentoSemAssociado () {
		// Altera o número de série do equipamento na base para que o equipamento não seja encontrado
		$this->Equipamento->id = $this->dataGenerator->equipamentoId;
		$this->Equipamento->set (array('no_serie' => '123' ));
		$this->Equipamento->save ();
		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Equipamento não vinculado'
		);
	}// End 'testEquipamentoSemAssociado'
	
	/**
	 * Testa acesso a API via POST, esperando exceção de "NotFound" e a mensagem que não encontrou nenhum serviço aberto
	 */
	public function testSemServicoAberto () {

		// Encerra o serviço aberto para o mesmo não ser encontrado e lançar a exceção
		$this->Servico->id = $this->dataGenerator->servicoId;
		$this->Servico->set (array('data_fechamento' => $this->dataGenerator->getDateTime () ));
		$this->Servico->save ();

		// Envia a requisição e valida a exceção recebida
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'ApiException',
			'Equipamento sem serviço aberto'
		);
	}// End 'testSemServicoAberto'
	

    /**
    * Testa acesso a API via POST, esperando receber o mesmo valor referente aos tickets criados para teste
    */
    public function testValorTotalTicketsDia () {
		// Cria posto
		$this->dataGenerator->savePosto();
		// Variável que recebe o valor total dos tickets criados
		$valorTotalTickets = 0;
		// Gera tickets e valores randômicos para o teste de valor total
		for ($x = 0; $x < 10; $x ++) {
			// Cria um valor randômico para o ticket
			$valorTicket = rand(1, 999);
			// Insere tickets para comparar com o valor retornado da classe
			$this->dataGenerator->saveTicket (array('Ticket' => array('valor' => $valorTicket/100,'valor_original' => $valorTicket/100)));
			// Incrementa valor total para comparar ao valor recebido da requisição
			$valorTotalTickets += $valorTicket;
		}
		
		// Acessa o link da API
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Armazena o valor total do serviço
		$responseValorTotal = $this->vars['data']['servico']['valor_total'];

		// Valida se o valor retornado é igual ao valor gerado randomicamente
		$this->assertEquals(
			$valorTotalTickets, 
			$responseValorTotal, 
			'Valor total de tickets difere do valor total do caixa. ' . $valorTotalTickets . ' / ' . $responseValorTotal . 
			' Diferença: ' . $valorTotalTickets - $responseValorTotal);
	}// End 'testValorTotalTicketsDia'
	
	/**
	 * Testa acesso a API via POST, esperando receber a mesma quantidade se serviços criados
	 */
	public function testNumeroServicosDia () {
		// Variável que recebe a quantidade de serviços a serem criados
		$qtdeServicos = rand (1, 10);
		// Gera a quantidade de serviços de acordo com o número randômico
		for ($x = 0; $x < $qtdeServicos; $x ++) {
			// Insere o serviço
			$this->dataGenerator->saveServico ();
		}
		
		// Acessa o link da API
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull ($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida se o valor retornado é igual ao valor gerado randômicamente
		// OBS: é adicionado um na quantidade de serviços gerados devido ao serviço criado no set-up
		$this->assertEqual($qtdeServicos + 1, $this->vars['data']['servico']['qtde_servicos']);
	}// End 'testNumeroServicosDia'
	
	/**
	 * Testa acesso a API via POST, esperando validar se o serviço foi encerrado corretamente
	 */
	public function testEncerrouServico () {
		// Acessa o link da API
		$this->testAction ($this->URL, array('method' => 'POST','data' => $this->data ));
		// Busca se existe algum serviço aberto para o equipamento
		$parkServico = $this->Servico->find ('first', array('conditions' => array('equipamento_id' => $this->dataGenerator->equipamentoId,'administrador_id' => ADMIN_PARKING_ID,'data_fechamento' => NULL ) ));
		// Valida se a variável que recebe o serviço é vazio, indicando que não encontrou nenhum serviço aberto
		$this->assertEmpty ($parkServico, 'O serviço atual do equipamento continua aberto!');
	}// End 'testEncerrouServico'
	
	/**
	 * Testa acesso a API via POST, esperando validar se todos os campos de retorno estão preenchidos
	 */
	public function testValidarRetorno () {
		// Acessa o link da API
		$this->sendRequest($this->URL.$this->extension, 'POST', $this->data);
		// Valida se houve retorno da classe testada
		$this->assertNotNull ($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull ($this->vars['data']['operador']['nome'], 				'Campo operador nome está null');
		$this->assertNotNull ($this->vars['data']['operador']['usuario'], 			'Campo operador usuario está null');
		$this->assertNotNull ($this->vars['data']['servico']['data_abertura'], 		'Campo data_abertura está null');
		$this->assertNotNull ($this->vars['data']['servico']['data_fechamento'], 	'Campo data_fechamento está null');
		$this->assertNotNull ($this->vars['data']['servico']['qtde_servicos'], 		'Campo operador está null');
		$this->assertNotNull ($this->vars['data']['servico']['total_recargas'], 	'Campo operador está null');
		$this->assertNotNull ($this->vars['data']['servico']['total_tickets'], 		'Campo operador está null');
		$this->assertNotNull ($this->vars['data']['servico']['valor_total'], 		'Campo valor_total está null');
	}// End 'testValidarRetorno'
}// End Class