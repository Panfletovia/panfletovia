<?php

App::uses('BaseCakeTestCase', 'Lib');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('DataGenerator', 'Lib');
App::uses('ClassRegistry', 'Utility');
App::uses('ComponentCollection', 'Controller');
App::uses('PeriodPurchaseComponent', 'Api.Controller/Component');
App::uses('PeriodPurchaseController', 'Api.Controller');

/**
 * Teste do componente para compra de período
 */
class PeriodPurchaseComponentTest extends BaseCakeTestCase {

	var $component; 
    var $placa;
    var $parkAreaId;
    var $parkCobrancaId;
    var $qtdePeriodos;
    var $vaga;
    var $tipoVeiculo;

	   public $uses = array(
        'Parking.Area',
        'Parking.AreaPonto',
        'Parking.Cobranca',
        'Parking.Contrato',
        'Parking.ContratoPlaca',
        'Parking.Operador',
        'Parking.Preco',
        'Parking.Placa',
        'Produto',
        'Plugin',
        'Equipamento' 
    );

    public function setUp() {
        parent::setUp();

    	$Collection = new ComponentCollection();
    	$this->component = new PeriodPurchaseComponent($Collection);

    	$test = new PeriodPurchaseController(new CakeRequest(), new CakeResponse());
    	$this->component->startup($test);

        // Cria valores padrões para utilização nos testes
        $this->dataGenerator->savePosto();
        $this->dataGenerator->saveTarifa(array('Tarifa' => array('posto_id' => null)));
        $this->dataGenerator->savePreco();
        $this->dataGenerator->saveProduto();
        $this->dataGenerator->saveCobranca();
        $this->dataGenerator->saveArea();
        $this->dataGenerator->saveSetor();
        $this->areaPonto = $this->dataGenerator->getAreaPonto();
        $this->dataGenerator->saveAreaPonto($this->areaPonto);
        $this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => 'ANDROID','no_serie' => '1234567890','modelo' => 'ANDROID')));
        $this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890')));
        $servico = $this->dataGenerator->getServico();
        $servico['Servico']['data_fechamento'] = null;
        $this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
        $this->dataGenerator->saveServico($servico);
        $this->dataGenerator->saveParkTarifa();
        
        $this->entidade = $this->dataGenerator->getEntidade();
        $this->entidade['Entidade']['tipo'] = 'CLIENTE';
        $this->entidade['Entidade']['negocio_id'] = 1;
        $idEntidade = $this->dataGenerator->saveEntidade($this->entidade);      
        $this->entidade['Entidade']['id'] = $idEntidade;

        $this->dataGenerator->savePlaca();

        $parkPlaca = $this->Placa->findById($this->dataGenerator->placaId);
        $this->placa          = $parkPlaca['Placa']['placa'];
        $this->parkAreaId     = $this->dataGenerator->areaId;
        $this->parkCobrancaId = $this->dataGenerator->cobrancaId;
        $this->qtdePeriodos   = 1;
        $this->vaga           = 0;
        $this->tipoVeiculo    = 'CARRO';
    }

    /**
     * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro ParkCobrancaId está incorreto
     */
    public function testSemParkCobrancaId() {
        $this->expectError('ApiException', 'Cobrança não recebida');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $finalValue = $this->component->getValue(
            $this->qtdePeriodos,
            null,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testSemParkCobrancaId'

    /**
     * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro Placa está incorreto
     */
    public function testSemPlaca() {
        $this->expectError('ApiException', 'Placa não recebida');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $finalValue = $this->component->getValue(
            $this->qtdePeriodos,
            $this->parkCobrancaId,
            null, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testSemPlaca'

    /**
     * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro Tipo de veículo está incorreto
     */
    public function testSemTipoVeiculo() {
        $this->expectError('ApiException', 'Tipo de veículo não recebido');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $finalValue = $this->component->getValue(
            $this->qtdePeriodos,
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            null,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testSemTipoVeiculo'


    /**
     * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro ParkAreaId está incorreto
     */
    public function testSemParkAreaId() {
        $this->expectError('ApiException', 'Área inválida');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $finalValue = $this->component->getValue(
            $this->qtdePeriodos,
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            null,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testSemTipoVeiculo'

     /**
     * Testa acesso a API, esperando exceção de "BadRequest" e a mensagem de parâmetro qtdePeriodos está incorreto
     */
    public function testSemQtdePeriodos() {
        $this->expectError('ApiException', 'Quantidade de períodos não recebida');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $this->component->getValue(
            null,
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testSemQtdePeriodos'


    /**
     * Método que testa o acesso a API, esperando exceção de veículo isento
     */ 
    public function testGetFreeVehicle() {

        // Salva contrato de isenção para lançar exceção por este motivo
        $contrato = $this->dataGenerator->getContrato();
        $this->dataGenerator->saveContrato($contrato);
        $parkContratoPlaca = $this->dataGenerator->getContratoPlaca($this->placa);
        $parkContratoPlaca['Contrato'] = $contrato['Contrato'];
        $this->ContratoPlaca->save($parkContratoPlaca);

        // Ao efetuar requisição GET aguarda a exceção abaixo
        $this->expectError('ApiException', 'Compra bloqueada: veículo isento');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $this->component->getValue(
            $this->qtdePeriodos,
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testGetFreeVehicle'


    /**
     * Método que testa o acesso a API, esperando exceção que a procedure possa lançar 
     */ 
    public function testInternalErrorProcedure() {
        
        // Ao efetuar requisição GET aguarda a exceção abaixo
        $this->expectError('ApiException', 'Tarifa não encontrada');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $this->component->getValue(
            array(0),
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testGetFreeVehicle'

     /**
     * Método que testa o acesso a API, esperando exceção que a vaga não foi encontrada, pois ultrapassa o limite do
     * campo na base de dados (SMALLINT 5 UNSIGNED)
     */ 
    public function testIVagaNotFound() {
        
        // Ao efetuar requisição GET aguarda a exceção abaixo
        $this->expectError('ApiException', 'Vaga não encontrada');
         // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $this->component->getValue(
            1,
            $this->parkCobrancaId,
            $this->placa, 
            99999,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
    }// End method 'testIVagaNotFound'


    /**
     * Método que testa o acesso a API, esperando o que o valor de retorno seja o mesmo que o calculado manualmente
     */ 
    public function testGetValue() {
    	 // Chama método que retorna o valor a ser pago de acordo com a quantidade de períodos
        $finalValue = $this->component->getValue(
            $this->qtdePeriodos,
            $this->parkCobrancaId,
            $this->placa, 
            $this->vaga,
            $this->tipoVeiculo,
            $this->parkAreaId,
            $this->Equipamento->getDataSource()
        );
        // Valida retorno da procedure
        $this->assertNotEmpty($finalValue, 'Valor de retorno da procedure é inválido');

        // Busca informações do preço
        $parkPreco = $this->Preco->findById($this->dataGenerator->precoId);
        // Valida se o preço é valido
        $this->assertNotEmpty($parkPreco);
        // Calcula valor cobrado 
        $valorCalculado = 2.00;
        //Converte para centavos 
        $valorCalculado = $valorCalculado * 100;
        // Verifica se os valores são iguais
        $this->assertEqual($finalValue, $valorCalculado, 'Valor do retorno da função e calculado são diferentes.');
    }// End method 'testGetValue'

}// End Class