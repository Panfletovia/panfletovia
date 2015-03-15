<?php

App::uses('ApiBaseControllerTestCase','Api.Lib');

class TicketsMobileControllerTest extends ApiBaseControllerTestCase {
	
	public $mockUser = false;
	public $uses = array('Parking.Ticket', 'Equipamento', 'Cliente', 'Limite', 'Autorizacao', 'Parking.Historico', 'Autorizacao', 'ContraPartida', 'Pendente', 'Parking.Preco', 'Parking.Area');
	private $URL = '/api/tickets_mobile';
	private $cliente = '';
	private $extension = '.json';

	/**
	 * Rotina executada antes de cada teste
	 */
	public function setUp () {
		parent::setUp();
		// Cria valores padrões para utilização nos testes
		$this->dataGenerator->savePosto();
		$this->dataGenerator->saveTarifa(array('Tarifa' => array('posto_id' => null)));
		$this->dataGenerator->savePreco(array('Preco' => array(
			'duracao_periodo'           => 60,
			'excedente_periodo_minutos' => 1,
			'excedente_periodo_valor'   => 1.00,
			'tolerancia_cancelamento'   => 59,
			'cobranca_turnos'           => 0,
			'cobranca_periodos'         => 1,
			'faixa_1_minutos'           => 0,
			'faixa_1_valor'             => 0,
			'faixa_1_tolerancia'        => 0

			)));
		$this->dataGenerator->saveProduto();
		$this->dataGenerator->saveCobranca();
		$this->dataGenerator->saveArea(array('Area' => array('devolucao_periodo' => 1, 'uteis_fim' => '23:59:59')));
		$this->dataGenerator->saveSetor();
		$this->areaPonto = $this->dataGenerator->getAreaPonto();
		$this->dataGenerator->saveAreaPonto($this->areaPonto);
		$this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
		$this->dataGenerator->saveOperador(array('Operador' =>	 array('usuario' => '1234567890','senha' => '1234567890')));
		$servico = $this->dataGenerator->getServico();
		$servico['Servico']['data_fechamento'] = null;
		$this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
		$this->dataGenerator->saveServico($servico);
		$this->dataGenerator->saveCliente();
		$this->parkTarifa = $this->dataGenerator->getParkTarifa();
		$this->parkTarifa['ParkTarifa']['id'] = $this->dataGenerator->saveParkTarifa($this->parkTarifa);
		$this->parkPlaca = $this->dataGenerator->getPlaca();
		$this->dataGenerator->savePlaca($this->parkPlaca);


		//adiciona limite de 1000 reais
		$this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 100000);

		// Popula os campos default
		$this->data = $this->getApiDefaultParams();
	}// End Method 'setUp'


	/**
	 * Testa a validação do tipo de requisição
	 * @return [type] [description]
	 */
	public function testRequestGet() {
		$this->validateTestException(
			$this->URL . '/active_tickets/' . $this->extension,
			'GET',
			$this->data,
			'NotImplementedException',
			''
		);
	}// End Method 'testRequestGet'

	/**
	 * Testa sem enviar o campo de client_id
	 * @return [type] [description]
	 */
	public function testInvalidClientId() {
		$this->validateTestException(
			$this->URL . '/active_tickets/' . $this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Id do cliente não recebido'
		);
	}// End Method 'testRequestGet'

	/**
	 * Testa a validação do tipo de requisição
	 * @return [type] [description]
	 */
	public function testClientNotFound() {
		// Compra primeiro ticket para placa 
		$this->dataGenerator->venderTicketEstacionamentoCpfCnpj(
			$this->parkTarifa['ParkTarifa']['valor'], 
			$this->parkPlaca['Placa']['placa'], 
			$this->areaPonto['AreaPonto']['codigo'], 
			$this->parkTarifa['ParkTarifa']['codigo']
		);
		// Envia um id de cliente que não existe
		$this->data['client_id'] = 999;
		// Envia requisição esperando erro de cliente não encontrado
		$this->validateTestException(
			$this->URL . '/active_tickets/' . $this->extension,
			'POST',
			$this->data,
			'ApiException',
			'Cliente não encontrado'
		);
	}// End Method 'testClientNotFound'

	/**
	 * Testa a listagem de mais de um ticket ativo para mais de uma placa
	 */
	public function testThreeDifferentsPlatesWithActivePlate(){
		// Salva primeira placa
		$placa1 = $this->dataGenerator->getPlaca();
		$this->dataGenerator->savePlaca($placa1);
		// Salva segunda placa
		$placa2 = $this->dataGenerator->getPlaca();
		$this->dataGenerator->savePlaca($placa2);
		// Salva terceira placa
		$placa3 = $this->dataGenerator->getPlaca();
		$this->dataGenerator->savePlaca($placa3);
		// Extrai os dados da compra de periodo
		$valor    = $this->parkTarifa['ParkTarifa']['valor'];
		$vaga     = $this->areaPonto['AreaPonto']['codigo'];
		$periodos = $this->parkTarifa['ParkTarifa']['codigo'];
		// Compra um ticket para cada placa
		$autorizacao1 = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($valor, $placa1['Placa']['placa'], $vaga, $periodos);
		$autorizacao2 = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($valor, $placa2['Placa']['placa'], $vaga, $periodos);
		$autorizacao3 = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($valor, $placa3['Placa']['placa'], $vaga, $periodos);
		// Busca os tickets correspondentes a cada autorizacao
		$ticket1 = $this->Ticket->findByAutorizacaoId($autorizacao1[0]['id']);
		$ticket2 = $this->Ticket->findByAutorizacaoId($autorizacao2[0]['id']);
		$ticket3 = $this->Ticket->findByAutorizacaoId($autorizacao3[0]['id']);
		// Envia um id de cliente que não existe
		$this->data['client_id'] = $this->dataGenerator->clienteId;
		// Acessa o link da API
		$this->sendRequest($this->URL . '/active_tickets' . $this->extension, 'POST', $this->data);
		// Valida se as variáveis de retorno foram retornadas
		$this->assertNotNull($this->vars['data']['tickets']);
		$this->assertTrue(isset($this->vars['data']['tickets']));
		// Valida os tickets retornados
		$tickets = $this->vars['data']['tickets'];
		foreach ($tickets as $key => $value) {
			switch($key){
				case 0:
					$this->assertEquals($value['Ticket']['id']			, $ticket1['Ticket']['id']);
					$this->assertEquals($value['Ticket']['placa']		, $ticket1['Ticket']['placa']);
					$this->assertEquals($value['Ticket']['data_inicio']	, $ticket1['Ticket']['data_inicio']);
					$this->assertEquals($value['Ticket']['data_fim']	, $ticket1['Ticket']['data_fim']);
					break;
				case 1:
					$this->assertEquals($value['Ticket']['id']			, $ticket2['Ticket']['id']);
					$this->assertEquals($value['Ticket']['placa']		, $ticket2['Ticket']['placa']);
					$this->assertEquals($value['Ticket']['data_inicio']	, $ticket2['Ticket']['data_inicio']);
					$this->assertEquals($value['Ticket']['data_fim']	, $ticket2['Ticket']['data_fim']);
					break;
				case 2:
					$this->assertEquals($value['Ticket']['id']			, $ticket3['Ticket']['id']);
					$this->assertEquals($value['Ticket']['placa']		, $ticket3['Ticket']['placa']);
					$this->assertEquals($value['Ticket']['data_inicio']	, $ticket3['Ticket']['data_inicio']);
					$this->assertEquals($value['Ticket']['data_fim']	, $ticket3['Ticket']['data_fim']);
					break;
			}
		}
	}// End Method 'testThreeDifferentsPlatesWithActivePlate'
}// End Class