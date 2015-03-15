<?php

App::uses('ApiBaseControllerTestCase','Api.Lib');

class ClientsControllerTest extends ApiBaseControllerTestCase {

    public $mockUser = false;
    public $uses = array('Limite', 'Cliente', 'ClienteEstrangeiro', 'Equipamento', 'Parking.OperadorCliente');

    private $data = null;
    
    /**
     * Rotina executada antes de cada teste
     */
    public function setUp () {
        parent::setUp();
        // Cria valores padrões para utilização nos testes
        $this->dataGenerator->savePosto();
        $this->dataGenerator->saveTarifa(array('Tarifa' => array('posto_id' => null)));
        $this->dataGenerator->savePreco();
        $this->dataGenerator->saveProduto();
        $this->dataGenerator->saveCobranca();
        // Salva áreas do tipo privado para testar a URA
        for ($i=0; $i < 5; $i++) { 
            $areaPrivado = $this->dataGenerator->getArea(false);
            $this->dataGenerator->saveArea($areaPrivado);
        }
        // Salva área Do tipo rotativo
        $this->dataGenerator->saveArea();
        $this->dataGenerator->saveSetor();
        $this->areaPonto = $this->dataGenerator->getAreaPonto();
        $this->dataGenerator->saveAreaPonto($this->areaPonto);
        $this->dataGenerator->saveEquipamento(array('Equipamento' => array('tipo' => EQUIPAMENTO_TIPO_SMARTPHONE,'no_serie' => '1234567890','modelo' => 'ANDROID')));
        $this->dataGenerator->saveOperador(array('Operador' => array('usuario' => '1234567890','senha' => '1234567890')));
        $servico = $this->dataGenerator->getServico();
        $servico['Servico']['data_fechamento'] = null;
        $this->dataGenerator->saveComissao(array('Comissao' => array('posto_id' => null)));
        $this->dataGenerator->saveServico($servico);
        
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);
        // Adiciona limite de 1000 reais
        $this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 100000);

        // Popula os campos default
        $this->data = $this->getApiDefaultParams();
    }

    /**
    * Testa o cadastro de um cliente via app
    */
    public function testAddClient() {
        // Popula os dados do request
        $this->populateData();
        // Acessa a API
        $this->testAction('/api/clients.json', array('method' => 'POST', 'data' => $this->data, 'return' => 'vars'));
        // Busca o cliente criado
        $cliente = $this->Cliente->find('first', array('conditions' => array('cpf_cnpj' => $this->data['cpf_cnpj'])));
        // Testa se o cliente foi encontrado
        $this->assertNotEmpty($cliente, 'Cliente não foi adicionado.');

        $this->assertTrue(isset($this->vars['data']['cliente']), 'Dados de retorno não foram setados');

        $this->assertEquals($this->vars['data']['cliente']['placa_1'], $this->data['placa1'], 'Placa 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_2'], $this->data['placa2'], 'Placa 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_3'], $this->data['placa3'], 'Placa 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_1'], $this->data['tipo1'], 'Tipo veículo 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_2'], $this->data['tipo2'], 'Tipo veículo 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_3'], $this->data['tipo3'], 'Tipo veículo 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['cpf_cnpj'], $this->data['cpf_cnpj'], 'Cpf não confere!');
        $this->assertEquals($this->vars['data']['cliente']['email_sac'], 'rede@rede.com.br', 'E-mail sac não confere!');
    }

    /**
    * Testa o cadastro de um cliente via app com envio automático de SMS
    */
    public function testAddClientReceberSms() {
        // Popula os dados do request
        $this->populateData();
        // seta o campo receber_sms para 0, para testar se ele muda para 1 na hora de salvar o cliente, 
        // pois tem telefone que é setado em seguida
        $this->data['receber_sms'] = false;
        $this->data['telefone'] = '(51)0000-0000';
        // Acessa a API
        $this->testAction('/api/clients.json', array('method' => 'POST', 'data' => $this->data, 'return' => 'vars'));
        // Busca o cliente criado
        $cliente = $this->Cliente->find('first', array('conditions' => array('cpf_cnpj' => $this->data['cpf_cnpj'])));
        // Testa se o cliente foi encontrado
        $this->assertNotEmpty($cliente, 'Cliente não foi adicionado.');
        $this->assertTrue(isset($this->vars['data']['cliente']), 'Dados de retorno não foram setados');
        $this->assertEquals($this->vars['data']['cliente']['placa_1'], $this->data['placa1'], 'Placa 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_2'], $this->data['placa2'], 'Placa 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_3'], $this->data['placa3'], 'Placa 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_1'], $this->data['tipo1'], 'Tipo veículo 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_2'], $this->data['tipo2'], 'Tipo veículo 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_3'], $this->data['tipo3'], 'Tipo veículo 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['cpf_cnpj'], $this->data['cpf_cnpj'], 'Cpf não confere!');
        $this->assertEquals($this->vars['data']['cliente']['email_sac'], 'rede@rede.com.br', 'E-mail sac não confere!');
        // Testa se o campo receber_sms foi setado para 1
        $this->assertEquals(1, $cliente['Cliente']['receber_sms'], 'Opção de receber SMS não setada!');
    }

    /**
    * Testa o cadastro de um cliente estrangeiro via app
    */
    public function testAddForeignClient() {
        // Popula parâmetros necessários para o teste
        $this->data['telefone'] = '9999549435966';        
        $senha = $this->dataGenerator->generatePassword(123456, $this->data['telefone']);
        $this->data['senha'] = $senha;
        $this->data['confirmacao_senha'] = $senha;
        $this->data['placa1'] = 'BBB9999';
        $this->data['placa2'] = 'BBB8888';
        $this->data['placa3'] = 'BBB7777';
        $this->data['tipo1'] = 'CARRO';
        $this->data['tipo2'] = 'MOTO';
        $this->data['tipo3'] = 'CARRO';
        $this->data['estrangeiro'] = true;
        // Acessa a API
        $this->testAction('/api/clients.json', array('method' => 'POST', 'data' => $this->data, 'return' => 'vars'));

        // Busca o cliente criado
        $cliente = $this->ClienteEstrangeiro->find('first', array('conditions' => array('cpf_cnpj' => $this->data['telefone'])));
        
        // Testa se o cliente foi encontrado
        $this->assertNotEmpty($cliente, 'Cliente não foi adicionado.');

        $this->assertEquals(1, $cliente['ClienteEstrangeiro']['estrangeiro']);

        $this->assertTrue(isset($this->vars['data']['cliente']), 'Dados de retorno não foram setados');

        $this->assertEquals($this->vars['data']['cliente']['placa_1'], $this->data['placa1'], 'Placa 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_2'], $this->data['placa2'], 'Placa 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['placa_3'], $this->data['placa3'], 'Placa 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_1'], $this->data['tipo1'], 'Tipo veículo 1 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_2'], $this->data['tipo2'], 'Tipo veículo 2 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['tipo_3'], $this->data['tipo3'], 'Tipo veículo 3 não confere!');
        $this->assertEquals($this->vars['data']['cliente']['cpf_cnpj'], $this->data['telefone'], 'CPF não confere!');
        $this->assertEquals($this->vars['data']['cliente']['telefone'], $this->data['telefone'], 'Telefone não confere!');
    }

    /**
     * Faz o teste caso o CPF/CNPJ esteja Null, deverá lançar uma exceção
     */
    public function testCpfEmpty() {
        // Popula os dados do request
        $this->populateData();
        $this->data['cpf_cnpj'] = NULL;

        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Campo cpf/cnpj não pode ser vazio'
            );
    }

    /**
     * Faz o teste caso o CPF/CNPJ esteja inválido, deverá lançar uma exceção
     */
    public function testInvalidCpf() {
        // Popula os dados do request
        $this->populateData();
        $this->data['cpf_cnpj'] = 'huashuashuashuas';
        $this->data['estrangeiro'] = false;

        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Erro ao salvar Cliente: Documento inválido.'
            );
    }

    /**
     * Faz o teste caso o CPF/CNPJ já exista nos banco
     */
    public function testUniqueCpf() {
        $cpfCnpj = '505.831.747-87';
        $cliente = $this->dataGenerator->getCliente();
        $cliente['Cliente']['cpf_cnpj'] = $cpfCnpj;
        $this->dataGenerator->saveCliente($cliente);
        
        // Popula os dados do request       
        $this->populateData();
        $this->data['cpf_cnpj'] = $cpfCnpj;

        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Erro ao salvar Cliente: CPF/CNPJ já cadastrado.'
            );
    }

    /**
     * Faz o teste caso a senha esteja Null, deverá lançar uma exceção
     */
    public function testSenhaEmpty() {
        // Popula os dados do request
        $this->populateData();
        $this->data['senha'] = NULL;

        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Campo senha não pode ser vazio'
            );
    }

    /**
     * Faz o teste caso a senha esteja Null, deverá lançar uma exceção
     */
    public function testSenhaInvalida() {
        // Popula os dados do request
        $this->populateData();
        $this->data['senha'] = '123456';
        $this->data['confirmacao_senha'] = '12345';
        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Erro ao salvar Cliente: Por favor, repita a senha neste campo.'
            );
    }

    /*
     * Método que efetua o teste esperando encontrar um telefone já cadastrado nos clientes
     */
    public function testParameterPhoneClienteExists() {
        // Popula parâmetros necessários para o teste
        $phone = '(51)9986-9288';
        
        $entidadeCliente = $this->dataGenerator->getEntidade();
        $entidadeCliente['Entidade']['telefone'] = $phone;
        $this->dataGenerator->saveEntidade($entidadeCliente);
        
        $this->populateData();
        $this->data['telefone'] = $phone;

        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/add.json',
            'POST',
            $this->data,
            'ApiException',
            'Telefone já cadastrado'
            );
    }
    
    private function populateData() {
        // Popula os dados do request
        $this->data['cpf_cnpj'] = $this->dataGenerator->randomCpf(true);
        $this->data['placa1'] = 'AAA-9999';
        $this->data['placa2'] = 'AAA-8888';
        $this->data['placa3'] = 'AAA-7777';
        $this->data['tipo1'] = 'CARRO';
        $this->data['tipo2'] = 'MOTO';
        $this->data['tipo3'] = 'CARRO';
        $this->data['autorizar_debito'] = 1;
        $senha = $this->dataGenerator->generatePassword(123456, $this->data['cpf_cnpj']);
        $this->data['senha'] = $senha;
        $this->data['confirmacao_senha'] = $senha;
    }

    /**
    * Método que efetua o teste sem enviar nenhum parâmetro
    */
    public function testParameterWrong() {
        // Zera parâmetros
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = '';
        $this->data['password'] = '';
        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Parâmetros do cliente não informados'
            );
    }

    /*
    * Método que efetua o teste esperando erro de senha incorreta
    */
    public function testParameterPasswordWrong() {
        // Popula parâmetros necessários para o teste
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = 'qualquerSenha';
        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Usuário/Senha incorretos'
            );
    }

    /**
    * Método que efetua o teste esperando erro de senha incorreta
    */
    public function testParameterCpfCnpjWrong() {
        // Popula parâmetros necessários para o teste
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = '123.654.798-99';
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];
        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Usuário/Senha incorretos'
            );
    }

    /**
    * Método que efetua o teste esperando erro limite não encontrado
    */
    public function testParameterLimitClientWrong() {
        // Popula parâmetros necessários para o teste
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];

        $this->Limite->deleteAll(array('Limite.id > 0'));

        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Erro interno'
        );
    }

    /**
     * Testa a consulta de saldo do cliente, passando o númeor de telefone como parâmetro.
     */
    public function testBalancaPhone() {
        // Popula apenas o parâmetro de telefone 
        $this->data['phone'] = $this->cliente['Cliente']['telefone'];       
        // Envia requisição para API
        $this->sendRequest('/api/clients/balance.json', 'GET', $this->data);
        // Busca o registro do limite do cliente
        $limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
        // Verifica existencia do campo a ser verificado
        $this->assertNotEmpty($limite['Limite']['pre_creditado']);
        // Verifica igualdade dos valores de saldo
        $this->assertEqual($limite['Limite']['pre_creditado'], '100000.00');
    }

    /**
     * Testa a consulta de saldo do cliente, passando o cpf_cnpj como parâmetro.
     */
    public function testBalanceCPFCNPJ() {
        // Popula os campos de cpf_cnpj e senha 
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];
        // Envia requisição para API
        $this->sendRequest('/api/clients/balance.json', 'GET', $this->data);
        // Busca registro do limite do cliente
        $limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
        // Verifica existência do campo a ser verificado 
        $this->assertNotEmpty($limite['Limite']['pre_creditado']);
        // Verifica igualdade dos valores de saldo
        $this->assertEqual($limite['Limite']['pre_creditado'], '100000.00');
    }
    /**
     * Testa a consulta de saldo do cliente, passando o cpf_cnpj como parâmetro.
     */
    public function testBalanceParquimetro() {
        // Popula os campos de cpf_cnpj e senha 
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];
        // Envia requisição para API
        $this->sendRequest('/api/clients/balance.json', 'GET', $this->data);
        // Busca registro do limite do cliente
        $limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
        // Verifica existência do campo a ser verificado 
        $this->assertNotEmpty($limite['Limite']['pre_creditado']);
        // Verifica igualdade dos valores de saldo
        $this->assertEqual($limite['Limite']['pre_creditado'], '100000.00');
    }

    /**
    * Método que efetua o teste esperando erro de cliente não encontrado
    */
    public function testClientNotFound() {
        // Popula parâmetros necessários para o teste
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = '999.999.666-66';
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];

        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Usuário/Senha incorretos'
        );
    }// End Method 'testClientNotFound'

    /**
    * Método que efetua o teste esperando erro de cliente não encontrado
    */
    public function testCpfCnpjInvalidLength() {
        // Popula parâmetros necessários para o teste
        $this->data['phone'] = '';
        $this->data['cpf_cnpj'] = '123456789';
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];

        // Envia requisição para API
        $this->validateTestException(
            '/api/clients/balance.json',
            'GET',
            $this->data,
            'ApiException',
            'Formato inválido de CPF / CNPJ'
        );
    }// End Method 'testCpfCnpjInvalidLength'

    /**
     * Testa a consulta de saldo a partir de um telefone de outro cliente
     */
    public function testOtherPhoneNumberCurrentBalance() {

        $cliente1Id = $this->cliente['Cliente']['id']; 

        $cliente2 = $this->dataGenerator->getCliente();     
        $this->dataGenerator->saveCliente($cliente2);

        //adiciona limite de 1000 reais
        $limite = $this->Limite->findByEntidadeId($this->dataGenerator->clienteId);
        $limite['Limite']['pre_creditado'] = 50000;
        $this->Limite->save($limite);

        // Popula os campos de cpf_cnpj e senha 
        $this->data['phone'] = $cliente2['Cliente']['telefone'];
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = $this->cliente['Cliente']['raw_password'];
        // Envia requisição para API
        $this->sendRequest('/api/clients/balance.json', 'GET', $this->data);

        // Valida se houve retorno da classe testada
        $this->assertNotNull($this->vars['data'], 'Nenhum dado foi retornado');
        // Valida todos se todos os campos do retorno estão preenchidos
        $this->assertNotNull($this->vars['data']['cliente']['id'], 'O Id da entidade não foi retornado');
        $this->assertNotNull($this->vars['data']['cliente']['telefone'],    'O campo de telefone não foi retornado');
        $this->assertNotNull($this->vars['data']['cliente']['saldo_pre'], 'O campo de saldo pre não foi retornado');
        $this->assertNotNull($this->vars['data']['cliente']['saldo_pos'], 'O campo de saldo pos não foi retornado');

        // Busca registro do limite do cliente
        $limite = $this->Limite->findByEntidadeId($cliente1Id);

        // Verifica existência do campo a ser verificado 
        $this->assertNotEmpty($limite['Limite']['pre_creditado']);
        // Verifica igualdade dos valores de saldo
        // Number format é necessário pois o saldo pre retornado está em centavos, enquanto o valor da base em em decimal
        $this->assertEqual($limite['Limite']['pre_creditado'], number_format($this->vars['data']['cliente']['saldo_pre'], 2, '.', '')/100);
    }// End Method 'testOtherPhoneNumberCurrentBalance'

    /**
     * Busca um cliente pelo seu CPF
     */
    public function testBuscaCpf() {
        $cpf = $this->dataGenerator->randomCpf(TRUE);
        
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['cpf_cnpj'] = $cpf;
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);
        
        $this->data['cpf_cnpj'] = $cpf;
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        $this->assertEqual($this->vars['data']['cliente']['id'], $this->cliente['Cliente']['id']);
    }


    /**
     * Testa busca por cliente que não existe no sistema
     */
    public function testBuscaCpfClienteNaoEncontrado() {

        $this->dataGenerator->saveCliente();
        
        $this->data['cpf_cnpj'] = $this->dataGenerator->randomCpf(TRUE);

        // Envia requisição a API
        $this->validateTestException(
            '/api/clients/search.json',
            'GET',
            $this->data,
            'ApiException',
            'Cliente não encontrado'
            );
    }
    
    /**
     * Testa action que valida a senha correta do usuário de identificação da URA
     */
    public function testValidaSenhaCorretaUsuarioURA(){
        // Cria dados default
        $phone = '(51)99887-7665';
        $phoneBusca = '51998877665';
        // Salva o cliente com o telefone esperado
        $cliente = $this->dataGenerator->getCliente();
        $cliente['Cliente']['telefone'] = $phone;
        $cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente);
        // Popula parâmetros da requisiçaõ
        $this->data['phone'] = $phone;
        $this->data['password'] = $cliente['Cliente']['senha_site'];
        $this->data['cpf_cnpj'] = null;
        // Efetua requisição
        $this->sendRequest('/api/clients/login.json', 'GET', $this->data);
        // Valida se o retorno da requisição
        $this->assertEqual(URA_LOGIN_OK, $this->vars['data']['code'], 'Login não efetuado');
        // Valida se resultado do campo de erro_senha_site
        $this->validaIncrementoErroSenhaSite($cliente['Cliente']['id'], 0);
    }// End Method 'testValidaSenhaCorretaUsuarioURA'

    /**
     * Testa action que valida a senha incorreta do usuário de identificação da URA
     */
    public function testValidaLoginSenhaIncorretaUsuarioURA(){
        // Cria dados default        
        $phone = '(51)99887-7665';
        $phoneBusca = '51998877665';
        // Salva o cliente com o telefone esperado
        $cliente = $this->dataGenerator->getCliente();
        $cliente['Cliente']['telefone'] = $phone;
        $cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente);
        // Popula parâmetros da requisição
        $this->data['phone'] = $phone;
        $this->data['password'] = '123456789123456789';
        $this->data['cpf_cnpj'] = null;
        // Efetua a requisição
        $this->sendRequest('/api/clients/login.json', 'GET', $this->data);
        // Valida se o retorno recebido é de senha incorreta
        $this->assertEqual(URA_LOGIN_INCORRECT_PASSWORD, $this->vars['data']['code'], 'Senha do usuário cadastro é diferente da senha enviada pelo usuário da URA');
        // Valida se resultado do campo de erro_senha_site
        $this->validaIncrementoErroSenhaSite($cliente['Cliente']['id'], 1);
    }// End Method 'testValidaSenhaIncorretaUsuarioURA'

    /**
     * Testa action que valida a ultima tentativa antes de bloquear
     */
    public function testValidaLoginBloqueandoContaUsuarioURA(){
        // Cria dados default
        $phone = '(51)99887-7665';
        $phoneBusca = '51998877665';
        // Salva o cliente com o telefone e a quantidade de erros esperados
        $cliente = $this->dataGenerator->getCliente();
        $cliente['Cliente']['telefone'] = $phone;
        $cliente['Cliente']['erros_senha_site'] = 2;
        $cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente);
        // Popula parâmetros da requisição
        $this->data['phone'] = $phone;
        $this->data['password'] = '123456789123456789';
        $this->data['cpf_cnpj'] = null;
        // Efetua a requisição
        $this->sendRequest('/api/clients/login.json', 'GET', $this->data);
        // Vlaida se o retorno recebido é de conta bloqueada
        $this->assertEqual(URA_LOGIN_ACCOUNT_BLOCKED, $this->vars['data']['code'], 'Não retornou a mensagem de conta bloqueada do usuário da URA');
        // Valida se resultado do campo de erro_senha_site
        $this->validaIncrementoErroSenhaSite($cliente['Cliente']['id'], 3);
    }// End Method 'testValidaBloqueandoContaUsuarioURA'

        /**
     * Testa action que valida a ultima tentativa antes de bloquear
     */
    public function testValidaLoginContaBloqueadaUsuarioURA(){
        // Cria dados default
        $phone = '(51)99887-7665';
        $phoneBusca = '51998877665';
        // Salva o cliente com o telefone e a quantidade de erros esperados
        $cliente = $this->dataGenerator->getCliente();
        $cliente['Cliente']['telefone'] = $phone;
        $cliente['Cliente']['erros_senha_site'] = 3;
        $cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente);
        // Popula parâmetros da requisição
        $this->data['phone'] = $phone;
        // $this->data['password'] = $cliente['Cliente']['senha_site'];
        $this->data['password'] = '65151661';
        $this->data['cpf_cnpj'] = null;
        // Efetua a requisição
        $this->sendRequest('/api/clients/login.json', 'GET', $this->data);
        // Vlaida se o retorno recebido é de conta bloqueada
        $this->assertEqual(URA_LOGIN_ACCOUNT_BLOCKED, $this->vars['data']['code'], 'Não retornou a mensagem de conta bloqueada do usuário da URA');
        // Valida se resultado do campo de erro_senha_site
        $this->validaIncrementoErroSenhaSite($cliente['Cliente']['id'], 3);
    }// End Method 'testValidaLoginContaBloqueadaUsuarioURA'

    /**
     *  Função que valida a quantidade de erros para os logins de uma usuário na URA
     */
    private function validaIncrementoErroSenhaSite($clienteId, $qtdeEsperado){
        // Validação do parâmetro
        if (empty($clienteId)){
            $this->fail('Cliente id para validação inválido');
        }
        // Busca cliente pelo id
        $cliente = $this->Cliente->findById($clienteId);
        // Valida se o campo de erro é igual ao esperado
        $this->assertEquals($qtdeEsperado, $cliente['Cliente']['erros_senha_site']);

    }// End Method 'validaIncrementoErroSenhaSite'
    
    /**
     * Busca um cliente pelo seu telefone.
     */
    public function testBuscaPorPhone() {
        $phone = '(51)99887-7665';
        $phoneBusca = '51998877665';
        
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['telefone'] = $phone;
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);

        $this->data['phone'] = $phone;
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        $this->assertEqual($this->vars['data']['cliente']['id'], $this->cliente['Cliente']['id']);
    }

    /**
     * Testa se a controller está retornando o saldo do cliente.
     */
    public function testExtradoSaldo() {
        // Gera o cliente, com os dados necessários.
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->clienteId;
        $this->cliente['Cliente']['senha_site'] = $this->dataGenerator->generatePassword('123456', $this->cliente['Cliente']['cpf_cnpj']);
        $this->dataGenerator->saveCliente($this->cliente);

        // seta os valores padrões para serem ultilizados no request.
        $this->data['only_balance'] = true;
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = '123456';

        // Envia requisição para API
        $this->sendRequest('/api/clients/extract.json', 'GET', $this->data);
        
        // verifica se retornou o saldo do request, de acordo com o valor.
        $this->assertEquals($this->vars['data']['cliente']['saldo_pre'], 10000000);
    }

    /**
     * Testa se a controller está retornando o saldo do cliente com os últimos 10 movimentos.
     */
    public function testExtratoSaldoComMovimentos() {
        // Gera o cliente com os dados necessários
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->clienteId;
        $this->cliente['Cliente']['senha_site'] = $this->dataGenerator->generatePassword('123456', $this->cliente['Cliente']['cpf_cnpj']);
        $this->dataGenerator->saveCliente($this->cliente);

        // Cria uma placa
        $placa = $this->dataGenerator->getPlaca();
        $this->assertEquals($this->cliente['Cliente']['id'], $placa['Placa']['entidade_id']);
        $this->dataGenerator->savePlaca($placa);

        //Faz um compra de um ticket com o cliente e a placa salvos anteriormente.
        $this->dataGenerator->saveTarifa();
        $this->dataGenerator->saveParkTarifa();
        $this->dataGenerator->saveArea();
        $this->dataGenerator->saveSetor();
        $areaPonto = $this->dataGenerator->getAreaPonto();
        $this->dataGenerator->saveAreaPonto($areaPonto);
        $this->dataGenerator->venderTicketEstacionamentoCpfCnpj(2.00, $placa['Placa']['placa'], $areaPonto['AreaPonto']['codigo']);

        // seta os valores padrões para serem ultizadoes no request.
        $this->data['only_balance'] = false;
        $this->data['cpf_cnpj'] = $this->cliente['Cliente']['cpf_cnpj'];
        $this->data['password'] = '123456';

        // Envia requisição para API
        $this->sendRequest('/api/clients/extract.json', 'GET', $this->data);
        // Validação se o saldo retorna corretaemente.
        $this->assertEquals($this->vars['data']['cliente']['saldo_pre'], 9999800);
        // Verifica se está retornando dados.
        $this->assertNotEmpty($this->vars['data']['extrato_cliente']);
        // verifica se ouve retorno em movimento.criado_em e se foi setada a variavel
        $this->assertTrue(isset($this->vars['data']['extrato_cliente'][0]['Movimento']['criado_em']));
        // verifica se ouve retorno em movimento.compĺ e se foi setada a variavel
        $this->assertTrue(isset($this->vars['data']['extrato_cliente'][0]['Movimento']['compl']));
        // Verifica se ouve o retorno de extrato mocimento.valor e se foi setada a variavel
        $this->assertTrue(isset($this->vars['data']['extrato_cliente'][0]['Movimento']['valor']));
        // Verifica se no extrato cliente com o texto correto.
        $this->assertEquals(trim($this->vars['data']['extrato_cliente'][0]['Movimento']['compl']), 'VENDA PRODUTO ' . $placa['Placa']['placa']);
    }


    /**
     * Testa busca do cliente simulando requisição da URA buscando por cpf_cnpj.
     * Deverá retornar array com as placas ativas, tickets ativos, saldo, áreas e a tarifa de débito automático de cada área
     */
    public function testBuscaClienteURA_CpfCnpj() {

        // Salva usuário com determinado cpf
        $cpf = $this->dataGenerator->randomCpf(TRUE);
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['cpf_cnpj'] = $cpf;
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);

       // Efetua requisição e comparação dos dados
        $this->buscaClienteURA(null , $cpf);
    }// End Method 'testBuscaClienteURA_CpfCnpj'

     /**
     * Testa busca do cliente simulando requisição da URA buscando por telefone.
     * Deverá retornar array com as placas ativas, tickets ativos, saldo, áreas e a tarifa de débito automático de cada área
     */
    public function testBuscaClienteURA_Telefone() {

        // Salva usuário com determinado cpf
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);
        // Efetua requisição e comparação dos dados
        $this->buscaClienteURA($this->cliente['Cliente']['telefone'], null);
    }// End Method 'testBuscaClienteURA_Telefone'

    /**
     * Testa busca do cliente simulando requisição da URA buscando por telefone, com mais de uma área, sendo as outras do tipo privado.
     * Deverá retornar array com as placas ativas, tickets ativos, saldo, áreas e a tarifa de débito automático de cada área
     */
    public function testBuscaClienteURA_Telefone_OutrasAreasPrivadas() {
        // Salva usuário com determinado cpf
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);
        // Efetua requisição e comparação dos dados
        $this->buscaClienteURA($this->cliente['Cliente']['telefone'], null);
    }// End Method 'testBuscaClienteURA_Telefone_OutrasAreasPrivadas'
    /**
     * Método auxiliar para testar a busca de clientes pela URA.
     */
    private function buscaClienteURA($phone, $cpfCnpj){
        // Popula array de parâmetros da requisição        
        if (!empty($phone)) {
            $this->data['phone'] = $phone;
        } else if (!empty($cpfCnpj)){
            $this->data['cpf_cnpj'] = $cpfCnpj;
        }else{
            // Não deverá ocorrer
            throw new Exception('Não foi passado nenhum argumento para teste');
        }

        // Vincula uma placa ao cliente
        $parkPlaca = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca);

        // Salva tarifas
        $parkTarifa = $this->dataGenerator->getParkTarifa();
        $this->dataGenerator->saveParkTarifa($parkTarifa);

        // Adiciona limite
        $this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 10000);

        // Compra primeiro ticket
        $autorizacaoPrimeiraCompra = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $parkPlaca['Placa']['placa']);

        // Alterado valor da tarifa para não gerar erro de compra para mesma entidade
        $parkTarifa['ParkTarifa']['id'] = $this->dataGenerator->parktarifaId;
        $parkTarifa['ParkTarifa']['valor'] = 5.00;
        $this->dataGenerator->saveParkTarifa($parkTarifa);

        // Compra segundo ticket com valor alterado
        $autorizacaoSegundaCompra = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $parkPlaca['Placa']['placa']);

        // Busca saldo do usuário
        $saldoUsuario = $this->dataGenerator->getSaldoPreUsuario();

        // Salva uma nova placa apenas para validar se a lista de placas deverá trazer uma placa que não possui ticket ativo.
        $parkPlaca2 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca2);

        // Salva um equipamento do tipo URA para enviar na requisição
        $this->dataGenerator->saveEquipamento(array(
            'Equipamento' => array(
                'tipo' => EQUIPAMENTO_TIPO_URA,
                'no_serie' => EQUIPAMENTO_TIPO_URA,
                'modelo' => 'LOGICO')));

        // Atribui nos parâmetros da requisição o equipamento
        $this->data['type']   = EQUIPAMENTO_TIPO_URA;
        $this->data['serial'] = EQUIPAMENTO_TIPO_URA;
        $this->data['model']  = 'LOGICO';

        // Envia requisição
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        // Extrai dados de retorno
        $retorno = $this->vars['data'];

        // Valida se é diferente de nulo
        $this->assertNotNull($retorno);
        // Valida array do cliente
        $this->assertNotNull($retorno['cliente'], 'Cliente não retornado');
        $this->assertEquals($this->cliente['Cliente']['id'], $retorno['cliente']['id']);
        // Valida array das placas
        $this->assertNotNull($retorno['placas'], 'Lista de placas retornou vazio');
        $this->assertEquals($parkPlaca2['Placa']['placa'], $retorno['placas'][0]['Placa']['placa']);
        $this->assertEquals($parkPlaca2['Placa']['tipo'], $retorno['placas'][0]['Placa']['tipo']);
        // Valida array do saldo
        $this->assertNotNull($retorno['saldo'], 'Saldo do usuário retornou vazio');
        $this->assertEquals($saldoUsuario, $retorno['saldo']);
        // Valida array dos tickets
        $this->assertNotNull($retorno['tickets'], 'Lista de tickets retornou vazio');
        $this->assertEquals(1, count($retorno['tickets']), 'Lista de tickets retornou mais de um ticket');
        $this->assertEquals($autorizacaoPrimeiraCompra[0]['id'], $retorno['tickets'][0]['Autorizacao']['id']);
        // Valida array das areas
        $this->assertNotNull($retorno['areas'], 'Lista de áreas retornou vazio');
        // Valida quantidade de areas retornadas
        $this->assertEquals(1, count($retorno['areas']));
        // Valida os campos retornados
        $arrayPrecos = $retorno['areas'][0]['Area']['precos'];
        // Varre lista de preços afim de verificar se os campos corretos estão setados
        foreach ($arrayPrecos as $key => $value) {
            $this->assertTrue(isset($retorno['areas'][0]['Area']['precos'][$key]['id']));    
            $this->assertTrue(isset($retorno['areas'][0]['Area']['precos'][$key]['nome']));    
            $this->assertTrue(isset($retorno['areas'][0]['Area']['precos'][$key]['tarifa_codigo_debito_automatico']));    
            $this->assertTrue(isset($retorno['areas'][0]['Area']['precos'][$key]['tarifas']));    
        }
    }// End Method 'buscaClienteURA'



    /**
     * Testa se as placas vinculadas ao usuário da URA, são apenas as que não possuem ticket ativo, pois uma nova compra
     * para um ticket ativo, é feito na etapa anterior da URA
     */
    public function testBuscaClienteURA_ListaPlacasSemTicketAtivo(){

        // Salva um equipamento do tipo URA para enviar na requisição
        $this->dataGenerator->saveEquipamento(array(
            'Equipamento' => array(
                'tipo' => EQUIPAMENTO_TIPO_URA,
                'no_serie' => EQUIPAMENTO_TIPO_URA,
                'modelo' => 'LOGICO')));

        // Salva usuário com determinado cpf
        $this->cliente = $this->dataGenerator->getCliente();
        $this->cliente['Cliente']['id'] = $this->dataGenerator->saveCliente($this->cliente);

        // Salva as placas
        $parkPlaca1 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca1);

        $parkPlaca2 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca2);

        // Salva tarifas
        $parkTarifa = $this->dataGenerator->getParkTarifa();
        $this->dataGenerator->saveParkTarifa($parkTarifa);

        // Adiciona limite
        $this->dataGenerator->concedeLimitePre($this->dataGenerator->clienteId, 10000);

        // Compra tickets
        $autorizacaoPrimeiraCompra = $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $parkPlaca1['Placa']['placa']);

        // Atribui nos parâmetros da requisição o equipamento
        $this->data['phone'] = $this->cliente['Cliente']['telefone'];
        $this->data['type']   = EQUIPAMENTO_TIPO_URA;
        $this->data['serial'] = EQUIPAMENTO_TIPO_URA;
        $this->data['model']  = 'LOGICO';

        // Envia requisição
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        // Extrai dados de retorno
        $retorno = $this->vars['data'];
        // Valida se é diferente de nulo
        $this->assertNotNull($retorno);

        // Valida array das placas
        $this->assertNotNull($retorno['placas'], 'Lista de placas retornou vazio');
        // Valida quantidade de placas retornadas.
        $this->assertEquals(1, count($retorno['placas']));
        $this->assertEquals($parkPlaca2['Placa']['placa'], $retorno['placas'][0]['Placa']['placa']);
        $this->assertEquals($parkPlaca2['Placa']['tipo'], $retorno['placas'][0]['Placa']['tipo']);

    }// End Method 'testBuscaClienteURA_ListaPlacasSemTicketAtivo'




    /**
    * Testa se após o cadastro feito por um operador de um cliente, seja nacional ou internacional, se o mesmo é populado na tabela
    * de vinculo 'park_operador_cliente'
    */
    public function testValidaVinculoCadastroOperadorClienteNacional(){
        // Popula os dados do request
        $this->populateData();
        // Acessa a API para salvar cliente nacional
        $this->testAction('/api/clients.json', array('method' => 'POST', 'data' => $this->data, 'return' => 'vars'));
        // Busca o cliente criado
        $cliente = $this->Cliente->find('first', array('conditions' => array('cpf_cnpj' => $this->data['cpf_cnpj'])));
        // Testa se o cliente foi encontrado
        $this->assertNotEmpty($cliente, 'Cliente não foi adicionado.');
        // Busca registros na tabela park_operador_cliente
        $parkOperadorCliente = $this->OperadorCliente->find('all');
        // Valida se encontrou registro
        $this->assertNotEmpty($parkOperadorCliente, 'Não criou registro na tabela park_operador_cliente');
        // Valida se o cliente salvo na tabela é o id do cliente esperado
        $this->assertEquals($cliente['Cliente']['id'], $parkOperadorCliente[0]['OperadorCliente']['cliente_id']);
        // Valida se o operador salvo na tabela é o id do operador esperado
        $this->assertEquals($this->dataGenerator->operadorId, $parkOperadorCliente[0]['OperadorCliente']['operador_id']);
    }// End Method 'testValidaVinculoCadastroOperadorClienteNacional'

    /**
    * Testa se após o cadastro feito por um operador de um cliente, seja nacional ou internacional, se o mesmo é populado na tabela
    * de vinculo 'park_operador_cliente'
    */
    public function testValidaVinculoCadastroOperadorClienteInternacional(){
        // Popula parâmetros necessários para o teste
        $this->data['telefone'] = '9999549435966';        
        $senha = $this->dataGenerator->generatePassword(123456, $this->data['telefone']);
        $this->data['senha'] = $senha;
        $this->data['confirmacao_senha'] = $senha;
        $this->data['placa1'] = 'BBB9999';
        $this->data['placa2'] = 'BBB8888';
        $this->data['placa3'] = 'BBB7777';
        $this->data['tipo1'] = 'CARRO';
        $this->data['tipo2'] = 'MOTO';
        $this->data['tipo3'] = 'CARRO';
        $this->data['estrangeiro'] = true;

        // Acessa a API para salvar cliente nacional
        $this->testAction('/api/clients.json', array('method' => 'POST', 'data' => $this->data, 'return' => 'vars'));
        // Busca o cliente criado
        $cliente = $this->Cliente->find('first', array('conditions' => array('telefone' => $this->data['telefone'])));
        // Testa se o cliente foi encontrado
        $this->assertNotEmpty($cliente, 'Cliente não foi adicionado.');
        // Busca registros na tabela park_operador_cliente
        $parkOperadorCliente = $this->OperadorCliente->find('all');
        // Valida se encontrou registro
        $this->assertNotEmpty($parkOperadorCliente, 'Não criou registro na tabela park_operador_cliente');
        // Valida se o cliente salvo na tabela é o id do cliente esperado
        $this->assertEquals($cliente['Cliente']['id'], $parkOperadorCliente[0]['OperadorCliente']['cliente_id']);
        // Valida se o operador salvo na tabela é o id do operador esperado
        $this->assertEquals($this->dataGenerator->operadorId, $parkOperadorCliente[0]['OperadorCliente']['operador_id']);
    }// End Method 'testValidaVinculoCadastroOperadorClienteInternacional'

    /**
     * Valida se o retorno para URA trouxe os tickets comprados em dinheiro a partir da placa vinculado ao cliente.
     */
    public function testBuscaClienteURA_TicketAtivoDinheiro(){
        // Salva equipamento do tipo URA
        $equipamentoURA = $this->dataGenerator->getEquipamentoURA();
        $this->dataGenerator->saveEquipamento($equipamentoURA);
        // Salva uma placa ativa que será utilizado para comprar o ticket
        $parkPlaca = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca);
        // Salva uma tarifa
        $parkTarifa = $this->dataGenerator->getParkTarifa();
        $this->dataGenerator->saveParkTarifa($parkTarifa);
        // Compra ticket em dinheiro para a placa vinculada ao cliente
        $autorizacaoPrimeiraCompra = $this->dataGenerator->venderTicketEstacionamentoDinheiro($parkTarifa['ParkTarifa']['valor'], $parkPlaca['Placa']['placa']);
        // Atribui nos parâmetros da requisição o equipamento
        $this->data['phone'] = $this->cliente['Cliente']['telefone'];
        $this->data['type']   = EQUIPAMENTO_TIPO_URA;
        $this->data['serial'] = EQUIPAMENTO_TIPO_URA;
        $this->data['model']  = 'LOGICO';
        // Envia requisição
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        // Valida se existe os campos da resposta da requisição
        $this->assertTrue(isset($this->vars['data']['cliente']));
        $this->assertTrue(isset($this->vars['data']['placas']));
        $this->assertTrue(isset($this->vars['data']['saldo']));
        $this->assertTrue(isset($this->vars['data']['areas']));
        $this->assertTrue(isset($this->vars['data']['tickets']));
        // Valida o conteúdo do campo de ticket (objetivo do teste)
        $tickets = $this->vars['data']['tickets'];
        $this->assertNotEmpty($tickets);
        $this->assertEquals(1 , count($tickets));
        $parkTicket = $tickets[0];
        $this->assertEquals($parkPlaca['Placa']['placa']         , $parkTicket['Ticket']['placa']);
        $this->assertEquals('PAGO'                               , $parkTicket['Ticket']['situacao']);
        $this->assertEquals($parkTarifa['ParkTarifa']['valor']   , $parkTicket['Ticket']['valor']);
        $this->assertEquals('UTILIZACAO'                         , $parkTicket['Ticket']['tipo']);
        $this->assertEquals($this->dataGenerator->areaId         ,  $parkTicket['Ticket']['area_id']);
        $this->assertEquals($parkTarifa['ParkTarifa']['codigo']  ,  $parkTicket['Ticket']['periodos']);
        $this->assertEquals($parkTarifa['ParkTarifa']['minutos'] ,  $parkTicket['Ticket']['tempo_tarifa']);
        $this->assertEquals('DINHEIRO'                           ,  $parkTicket['Ticket']['forma_pagamento']);
        $this->assertEquals($parkPlaca['Placa']['tipo']          ,  $parkTicket['Ticket']['veiculo']);
        $this->assertTrue(isset($parkTicket['Area']));
        $this->assertTrue(isset($parkTicket['Area']['nome']));
        $this->assertTrue(isset($parkTicket['Autorizacao']));
        $this->assertTrue(isset($parkTicket['Autorizacao']['id']));
    }// End Method 'testBuscaClienteURA_TicketAtivoDinheiro'


    /**
     * Valida se o retorno para URA trouxe os tickets comprados em dinheiro apenas das placas vinculadas ao usuário
     */
    public function testBuscaClienteURADiversosTicketsOutrasPlacas(){
        // Salva equipamento do tipo URA
        $equipamentoURA = $this->dataGenerator->getEquipamentoURA();
        $this->dataGenerator->saveEquipamento($equipamentoURA);
        // Salva uma placa ativa que será utilizado para comprar o ticket
        $parkPlaca = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($parkPlaca);
        // Salva uma tarifa
        $parkTarifa = $this->dataGenerator->getParkTarifa();
        $this->dataGenerator->saveParkTarifa($parkTarifa);
        // Compra um ticket em dinheiro
        $this->dataGenerator->venderTicketEstacionamentoDinheiro($parkTarifa['ParkTarifa']['valor'], $parkPlaca['Placa']['placa']);
        // Compra um ticket em PRE
        $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $parkPlaca['Placa']['placa']);

        // Cria primeiro usuário atrapalhador
        $cliente2 = $this->dataGenerator->getCliente();
        $cliente2['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente2);
        // Libera saldo para usuario atrapalhador 1
        $this->dataGenerator->concedeLimitePre($cliente2['Cliente']['id'], 100000);
        // Cria duas placas vinculadas ao usuário atrapalhador 1
        $placa1Atrapalhador1 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($placa1Atrapalhador1);
        $placa2Atrapalhador1 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($placa2Atrapalhador1);
        // Compra um ticket em dinheiro para o usuario atrapalhador 1
        $this->dataGenerator->venderTicketEstacionamentoDinheiro($parkTarifa['ParkTarifa']['valor'], $placa1Atrapalhador1['Placa']['placa']);
        // Compra um ticket em PRE  para o usuario atrapalhador 1
        $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $placa2Atrapalhador1['Placa']['placa']);
        // Cria segundo usuário atrapalhador
        $cliente3 = $this->dataGenerator->getCliente();
        $cliente3['Cliente']['id'] = $this->dataGenerator->saveCliente($cliente3);
        // Libera saldo para usuario atrapalhador 2
        $this->dataGenerator->concedeLimitePre($cliente3['Cliente']['id'], 100000);
        // Cria duas placas vinculadas ao usuário atrapalhador 1
        $placa1Atrapalhador2 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($placa1Atrapalhador2);
        $placa2Atrapalhador2 = $this->dataGenerator->getPlaca();
        $this->dataGenerator->savePlaca($placa2Atrapalhador2);
        // Compra um ticket em dinheiro para o usuario atrapalhador 1
        $this->dataGenerator->venderTicketEstacionamentoDinheiro($parkTarifa['ParkTarifa']['valor'], $placa1Atrapalhador2['Placa']['placa']);
        // Compra um ticket em PRE  para o usuario atrapalhador 1
        $this->dataGenerator->venderTicketEstacionamentoCpfCnpj($parkTarifa['ParkTarifa']['valor'], $placa2Atrapalhador2['Placa']['placa']);
        // Atribui nos parâmetros da requisição o equipamento
        $this->data['phone'] = $this->cliente['Cliente']['telefone'];
        $this->data['type']   = EQUIPAMENTO_TIPO_URA;
        $this->data['serial'] = EQUIPAMENTO_TIPO_URA;
        $this->data['model']  = 'LOGICO';
        // Envia requisição
        $this->sendRequest('/api/clients/search.json', 'GET', $this->data);
        // Valida se existe os campos da resposta da requisição
        $this->assertTrue(isset($this->vars['data']['cliente']));
        $this->assertTrue(isset($this->vars['data']['placas']));
        $this->assertTrue(isset($this->vars['data']['saldo']));
        $this->assertTrue(isset($this->vars['data']['areas']));
        $this->assertTrue(isset($this->vars['data']['tickets']));
        // Valida o conteúdo do campo de ticket (objetivo do teste)
        $tickets = $this->vars['data']['tickets'];
        $this->assertNotEmpty($tickets);
        // Deverá trazer apenas um ticket ativo para placa vinculado
        $this->assertEquals(1 , count($tickets));
        $parkTicket = $tickets[0];
        $this->assertEquals($parkPlaca['Placa']['placa']         , $parkTicket['Ticket']['placa']);
        $this->assertEquals('PAGO'                               , $parkTicket['Ticket']['situacao']);
        $this->assertEquals($parkTarifa['ParkTarifa']['valor']   , $parkTicket['Ticket']['valor']);
        $this->assertEquals('UTILIZACAO'                         , $parkTicket['Ticket']['tipo']);
        $this->assertEquals($this->dataGenerator->areaId         ,  $parkTicket['Ticket']['area_id']);
        $this->assertEquals($parkTarifa['ParkTarifa']['codigo']  ,  $parkTicket['Ticket']['periodos']);
        $this->assertEquals($parkTarifa['ParkTarifa']['minutos'] ,  $parkTicket['Ticket']['tempo_tarifa']);
        $this->assertEquals('DINHEIRO'                           ,  $parkTicket['Ticket']['forma_pagamento']);
        $this->assertEquals($parkPlaca['Placa']['tipo']          ,  $parkTicket['Ticket']['veiculo']);
        $this->assertTrue(isset($parkTicket['Area']));
        $this->assertTrue(isset($parkTicket['Area']['nome']));
        $this->assertTrue(isset($parkTicket['Autorizacao']));
        $this->assertTrue(isset($parkTicket['Autorizacao']['id']));

    }// End Method 'testBuscaClienteURA_TicketAtivoDinheiro'
}// End class