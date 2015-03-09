<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

/**
 * Classe que efetua os testes do Cancelamento de Compra de Períodos
 */
class CancelPurchaseControllerTest extends ApiBaseControllerTestCase {

	public $mockUser = false;

	public $uses = array(
		'Parking.Area',
		'Parking.AreaPonto',
		'Parking.Ticket',
		'Parking.Servico',
		'Equipamento',
		'Comunicacao',
		'Parking.ParkTarifa'
	);

	// Variável que recebe os campos default das transações
	var $data = NULL;
	// Variável que recebe a extensão a ser retornada pelo WebService
	var $extension = '.json';
	// Variável que recebe a url para requisição do teste
	var $URL = '/api/cancel_purchase';

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
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => NULL)));
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveParkTarifa(array('ParkTarifa' => array(
			'minutos' => 30,
			'valor' => 2.00,
			'codigo' => 1
		)));
		$this->dataGenerator->saveTicket(array('Ticket' => array(
			'tipo' => 'UTILIZACAO', 
			'data_inicio' => $this->dataGenerator->getDateTime('-10 minutes'),
			'data_fim' => $this->dataGenerator->getDateTime('+50 minutes')
		)));

		// Seta os valores para os campos padrões
		$this->data = $this->getApiDefaultParams();
	}

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action INDEX, pois na classe só deverá tratar o DELETE
	*/
	public function testIndexError() {
		$this->validateTestException(
			$this->URL.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testIndexError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action VIEW, pois na classe só deverá tratar o DELETE
	*/
	public function testViewError(){
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testViewError'
	
	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action EDIT, pois na classe só deverá tratar o DELETE
	*/
	public function testEditError(){
		$this->validateTestException(
			$this->URL.'/1'.$this->extension,
			'PUT',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testEditError'

	/**
	* Método que efetua o teste esperando erro de Operação Inválida na action ADD, pois na classe só deverá tratar o DELETE
	*/
	public function testAddError(){
		$this->validateTestException(
			$this->URL,
			'POST',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End 'testAddError'

	/**
	 * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro ParkTicketId não foi recebido
	 */
	public function testSemParkTicketId() {
		$this->validateTestException(
			$this->URL . '/0'.$this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Ticket id inválido'
		);
	}// End 'testSemParkTicketId'

	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de o ticket para cancelamento não foi encontrado
	 */
	public function testParkTicketNotFound() {
		// Passa um ID de ticket que não existe par que receba a exceção esperada
		$this->validateTestException(
			$this->URL . '/99999'. $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Ticket não encontrado'
		);
		
		
	}//End 'testParkTicketNotFound'

	/**
	 * Testa acesso a API, esperando exceção de "Forbidden" e a mensagem de o equipamento que gerou 
	 * o ticket não é o mesmo que está sendo cancelado.
	 */
	public function testParkTicketEquipamentoCriadoDiferente() {
		// Cria um novo equipamento para alterar o ID do equipamento origem do ticket
		$newEquipamento = $this->dataGenerator->getEquipamento();
		$newEquipamento['Equipamento']['tipo'] 		= EQUIPAMENTO_TIPO_SMARTPHONE;
		$newEquipamento['Equipamento']['modelo'] 	= 'ANDROID';
		$newEquipamento['Equipamento']['no_serie'] 	= '123456';
		$this->dataGenerator->saveEquipamento($newEquipamento);
		// Faz select na base de dados para saber o ID deste novo equipamento
		$newEquip = $this->Equipamento->findByNoSerie('123456');
		// Caso não encontre, lança exceção
		if(empty($newEquip)){
			throw new InternalErrorException('Equipamento não encontrado');
		}

		// Atualiza registro do ticket para setar o campo do equipamento de origem para NULL
		$this->Ticket->id = $this->dataGenerator->ticketId;
		$this->Ticket->saveField('equipamento_id_origem', $newEquip['Equipamento']['id']);
		
		$this->validateTestException(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Equipamento de origem diferente do atual'
		);
	}// End 'testParkTicketEquipamentoCriadoDiferente'

	/**
	 * Testa acesso a API, esperando exceção de "Forbidden" e a mensagem de o tipo do tipo
	 * é diferente de utilização.
	 */
	public function testParkTicketDiferenteUtilizacao() {
		// Atualiza o registro da 'park_ticket' alterando o campo 'tipo'
		$this->Ticket->id = $this->dataGenerator->ticketId;
		$this->Ticket->saveField('tipo', 'IRREGULARIDADE');
		
		$this->validateTestException(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Ticket não é de utilização'
		);
	}// End 'testParkTicketDiferenteUtilizacao'

	/**
	 * Testa acesso a API, esperando exceção de "Forbidden" e a mensagem de o ticket já está cancelado
	 * é diferente de utilização.
	 */
	public function testParkTicketSituacaoJaCancelado() {
		// Atualiza o registro da 'park_ticket' alterando o campo 'tipo'
		$this->Ticket->id = $this->dataGenerator->ticketId;
		$this->Ticket->saveField('situacao', 'CANCELADO');
		
		$this->validateTestException(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Ticket já está cancelado'
		);
	}// End 'testParkTicketDiferenteUtilizacao'


	/**
	 * Testa acesso a API, esperando exceção de "NotFound" e a mensagem de o serviço atual não foi encontrado.
	 */
	public function testSemServicoAberto() {
		// Encerra o serviço atual do equipamento para forçar exceção
		$this->Servico->id = $this->dataGenerator->servicoId;
		$this->Servico->saveField('data_fechamento', '2030-01-01 00:00:00');
		
		$this->validateTestException(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Comando não processado: o caixa foi encerrado manualmente. Por favor verifique com a sua operação.'
		);
	}// End 'testSemServicoAberto'

	/**
	 * Testa acesso a API, esperando exceção de "Forbidden" e a mensagem de o serviço de 
	 * origem do ticket é diferente do ID do serviço atual
	 */
	public function testParkServicoOrigemDiferenteAtual() {
		// Encerra o serviço atual do equipamento para forçar exceção
		$this->Servico->id = $this->dataGenerator->servicoId;
		$this->Servico->saveField('data_fechamento', '2030-01-01 00:00:00');
		$this->Servico->saveField('fechamento_forcado', 1);
		// Cria um novo serviço para ser o atual
		$this->dataGenerator->saveServico(array('Servico' => array('data_fechamento' => NULL)));
		
		$this->validateTestException(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension,
			'DELETE',
			$this->data,
			'ApiException',
			'Ticket já contabilizado em outro serviço'
		);
	}// End 'testParkServicoOrigemDiferenteAtual'

	/**
	* Método que efetua o teste completo, sem esperar exceção.
	*/
	public function testValidarRetorno() {
		// Acessa o link da API
		$this->sendRequest(
			$this->URL . '/'. $this->dataGenerator->ticketId . $this->extension, 
			'DELETE',
			$this->data
		);

		// Valida se houve retorno da classe testada
		$this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
		// Valida todos se todos os campos do retorno estão preenchidos
		$this->assertNotNull($this->vars['data']['park_ticket_id']	, 'Campo park_ticket_id retorno da função está null');
		$this->assertNotNull($this->vars['data']['valor']			, 'Campo valor de retorno da função está null');
		$this->assertNotNull($this->vars['data']['vaga']				, 'Campo vaga de retorno da função está null');
		$this->assertNotNull($this->vars['data']['data_hora_entrada'], 'Campo data_hora_entrada de retorno da função está null');
		$this->assertNotNull($this->vars['data']['data_hora_saida']	, 'Campo data_hora_saida retorno da função está null');
		$this->assertNotNull($this->vars['data']['placa']			, 'Campo placa de retorno da função está null');
		$this->assertNotNull($this->vars['data']['quantidade']		, 'Campo quantidade de retorno da função está null');
		$this->assertNotNull($this->vars['data']['tempo_decorrido']		, 'Campo tempo de retorno da função está null');

		// Busca informações do ticket após o cancelamento
		$parkTicket = $this->Ticket->findById($this->dataGenerator->ticketId);
		// Valida se encontrou o ticket corretamente
		$this->assertNotNull($parkTicket, 'Ticket não pode ser nulo!');
		// Valida a situação do ticket para cancelado
		$this->assertEquals($parkTicket['Ticket']['situacao'], 'CANCELADO', 'O ticket não foi cancelado!');
	}// End Method 'testValidarRetorno'
}// End Class