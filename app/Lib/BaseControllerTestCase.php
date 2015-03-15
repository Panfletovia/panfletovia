<?php

App::uses('ControllerTestCase', 'TestSuite');
App::uses('ClassRegistry', 'Utility');
// App::uses('DataGenerator', 'Lib');

class BaseControllerTestCase extends ControllerTestCase {

    public $dataGenerator = null;
    public $entidadeId = null;
    public $session = array();
    public $mockUser = true;
    
    public function setUp() {
        parent::setUp();
        @session_destroy();

        // $this->dataGenerator->clearDatabase();
        // $this->dataGenerator->insertDefaultData();

        // $this->setupMocks($this->mockUser);
    }

    public function tearDown() {
        parent::tearDown();
        @session_destroy();
    }
    
    public function __construct() {
        //Identifica qual é o plugin através do diretório da classe pai
        $rc = new ReflectionClass(get_class($this));
        $filename = dirname($rc->getFileName());
        $pathParts = explode(DS, strstr($filename, 'app'));
        if ($pathParts[1] === 'Plugin') {
            $this->pluginName = $pathParts[2];
        } else {
            $this->pluginName = null;
        }

        //Verifica o nome do controller sendo testado
        $controller = strstr(get_class($this), 'ControllerTest', true);
        if (!empty($this->pluginName)) {
           $this->controllerName = "{$this->pluginName}.". $controller;
           $this->controller = "{$this->pluginName}.". $controller;
        } else {
            $this->controllerName = $controller;
            $this->controller = $controller;
        }

        //Armazena o caminho para a url da action
        $this->actionPath = $this->controller;

        //Atalho para singleton DataGenerator
        // $this->dataGenerator = DataGenerator::get();
        Configure::write('test', true);
        
        if (!isset($this->uses)) {
            $this->uses = array();
        }
        foreach ($this->uses as $fullModel) {
            list($plugin, $model) = pluginSplit($fullModel);
            $this->$model = ClassRegistry::init($fullModel);
        }
    }

    private function auth() {
        //Cria um registro na tabela de sessão
        $this->Sessao = ClassRegistry::init('Sessao');
        $ret = $this->Sessao->save(array('Sessao'=>array(
            'ip' => '0.0.0.0',
            'entidade_id' => $this->entidadeId,
            'tipo' => 'SUCESSO'
        )));
        $this->session['Sessao.id'] = $this->Sessao->id;
    }

    protected function authParking() {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = ADMIN_PARKING_ID;
        $this->auth();
    }
    
    protected function authAdmin() {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = ADMIN_ID;
        $this->auth();
    }
    
    protected function authTicket() {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = ADMIN_TICKET_ID;
        $this->auth();
    }

    protected function authCliente() {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = CLIENTE_ID;
        $this->auth();
    }

    protected function authTransparencia() {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = TRANSPARENCIA_ID;
        $this->auth();
    }

    protected function authThis($id) {
        $this->Entidade = ClassRegistry::init('Entidade');
        $this->entidadeId = $id;
        $this->Auth();
    }

    /**
     * Configura os mocks 
     */
    private function setupMocks($mockUser) {
        $components = array(
            'Auth' => array('user', 'isAllowed', 'login'),
            'Acl' => array('check'),
            'Session' => array('read', 'write')
        );
        $mock = $this->generate($this->controllerName, array(
            'components' => $components)
        );
        $that = $this;

        $mock->Acl->expects(
            $this->any())->method('check')->will(
            $this->returnValue(true));
        $mock->Auth->expects(
            $this->any())->method('isAllowed')->will(
            $this->returnValue(true));
        if ($mockUser) {
            $mock->Auth->staticExpects(
                $this->any())->method('user')->will(
                $this->returnCallback(function() use ($that) {
                    $args = func_get_args();
                    $key = @$args[0];
                    $entidade = ClassRegistry::init('Entidade');
                    $authUser = $entidade->findById($that->entidadeId);
                    if (!$authUser) {
                        throw new Exception("Entidade de id " . $that->entidadeId . " não encontrada!");
                    }
                    
                    $authUser = $authUser['Entidade'];
                    if ($key === null) {
                        return $authUser;
                    } else {
                        return $authUser[$key];
                    }
                }
                )
            );
        }

        $mock->Auth->expects(
            $this->any())->method('login')->will(
            $this->returnCallback(function() use ($that)  {
                //Salva o id do cliente que autenticou
                $user = func_get_arg(0);
                $that->entidadeId = $user['id'];
                //Reseta a sessão
                $that->session = array();
                return true;
        }));
        $mock->Session->expects(
            $this->any())->method('read')->will(
            $this->returnCallback(function($key = null) use ($that)  {
                return isset($that->session[$key])? $that->session[$key] : null;
        }));
        $mock->Session->expects(
            $this->any())->method('write')->will(
            $this->returnCallback(function($key, $value) use ($that)  {
                $that->session[$key] = $value;
                return true;
        })); 
    }

    /*
     * Testa se o controller setou as variáveis necessárias 
     * para a index.
     * @param ([array $vars]) - Variáveis a serem testadas
    */
    public function indexAsserts($vars, $action = 'index') {
        $this->testAction("$this->actionPath/$action", array('method' => 'get'));
        if (!is_array($vars)) {
            $this->assertTrue(isset($this->vars[$vars]), "Erro: variável $vars não setada.");
            return;
        }        
        foreach ($vars as $varName => $models) {            
            $this->assertTrue(isset($this->vars[$varName]), "Erro: variável $varName não setada.");
            foreach ($models as $modelName => $fields) {
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $rows = $this->vars[$varName];
                        $this->assertTrue(!empty($rows), 'Erro: campos não setados - ' . implode(', ', $fields));
                        foreach ($rows as $row) {
                            $this->assertTrue(isset($row[$modelName][$field]), "Erro: variável $modelName->$field não setada.");
                        }
                    }
                }
            }
        }
        return $this->vars;
    }

    /*
     * Teste da view
     * Se nenhum model for informado, usa o model padrão do controller.
     * @param (array $vars) - Nome da(s) variável(is) setada(s) pelo model
     * @param (string $model) - Nome do model
    */
    public function viewAsserts($vars, $data = null, $model = null) {
        // Se o model não houver sido informado, usa o definido no mock do controller
        if (empty($model)) {
            $model = $this->controller->modelClass;
        }
        if (!empty($data)) {
            // Cria o registro e armazena o id
            $modelId = $this->createRecord($model, $data);
        } else {
            // Busca no banco o último registro
            $method = 'save' . $model;
            $modelId = $this->dataGenerator->$method();
        }

        $this->testAction("$this->actionPath/view/" . $modelId);
        
        foreach ($vars as $varName => $models) {
            $this->assertTrue(isset($this->vars[$varName]), "Erro: variável $varName não setada.");
            foreach ($models as $modelName => $fields) {
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $this->assertTrue(isset($this->vars[$varName][$modelName][$field]), "Erro: variável $modelName->$field não setada.");
                    }
                }
            }
        }
        return $this->vars;
    }
    
    /*
     * Testa se o controller setou as variáveis necessárias para
     * a view add ou edit, utilizando o método get. É possível informar 
     * parâmetros adicionais, que serão apostos na url. Os parâmetros
     * devem ser concatenados por '/', por exemplo: 1354/10. Se a view 
     * possui request->data setado pelo controller, as variáveis podem
     * ser testadas informando o(s) model(s) em $requestDataModel
     *
     * @param ([mixed $vars]) - Variáveis setadas para a view
     * @param ([mixed $requestDataModel]) - Nome do(s) model(s) setado(s) para a view
     * @param ([string $parameters]) - Parâmetros adicionais
     * @param ([bool $edit]) - Se a action é add ou edit
    */
    public function getAsserts($vars = null, $requestDataModel = false, $parameters = null, $edit = false) {
        // Usa o model definido no mock do controller
        $model = $this->controller->modelClass;
        // Se for add
        if (!$edit) {
            $this->testAction("$this->actionPath/add/" . $parameters, array('method' => 'get'));
        } else {
            // Busca no banco o último registro
            $modelId = $this->controller->$model->find('first', array('order' => "$model.id DESC"));
            $this->testAction("$this->actionPath/edit/" . $modelId[$model]['id'] . '/' . $parameters, array('method' => 'get'));
        }
        if (!empty($vars)) {
            // Testa se as variáveis da view foram setadas pelo controller
            if (!is_array($vars)) {// Se apenas um model foi informado
              $this->assertTrue(!empty($this->vars[$vars]), "Variável $vars vazia.");
            } else {
                foreach ($vars as $var) {
                    $this->assertTrue(!empty($this->vars[$var]), "Variável $var vazia.");
                }
            }
        }
        if (!empty($requestDataModel)) {
                // Testa se as variáveis do model foram setadas pelo controller
                if (!is_array($requestDataModel)) {// Se apenas um model foi informado
                    $this->assertTrue(!empty($this->controller->data[$requestDataModel]), "Variável $requestDataModel vazia.");
                } else {
                    foreach ($requestDataModel as $model) {
                        $this->assertTrue(!empty($this->controller->data[$model]), "Variável $model vazia.");
                }
            }
        }        
        return $this->vars;
    }
    
    /*
     * Teste add
     * @param (array $addData) - Array com os valores a serem enviados por post
     * @param (array $testFields) - Array com os campos a serem comparados
     * @param ([bool $edit]) - Se a action é add ou edit
     * @param ([mixed $model]) - Nome do model
    */
    public function addPostAsserts($addData, $testFields, $model = null, $action = 'add', $params = null) {
        // Se o model não houver sido informado, usa o definido no mock do controller
        if (empty($model)) {
            $model = $this->controller->modelClass;
        }
        $this->testAction("$this->actionPath/$action/" . "$params", array('data' => $addData, 'method' => 'post'));
        // Testa se o flashSuccess foi exibido
        $erro = isset($this->controller->flashError) ? $this->validationErrors($model) : 'Erro de validação não encontrado.';
        $this->assertTrue(isset($this->controller->flashSuccess), 'Ocorreu um erro: ' . $erro);
        // Busca no banco o último registro
        $findData = $this->controller->$model->find('first', array('order' => "$model.id DESC"));
        // Confere se os dados recuperados são iguais aos fornecidos
        foreach ($testFields as $field) {
            $this->assertEqual($findData[$model][$field], $addData[$model][$field], "Campo $field divergente.");
        }
    }

    /*
     * Teste do add
     * @param (array $addData) - Array com o registro a ser criado
     * @param (array $editData) - Array com os valores a serem enviados por post
     * @param (array $testFields) - Array com os campos a serem comparados
     * @param ([mixed $model]) - Nome do model
    */
    public function editPostAsserts($addData, $editData, $testFields, $model = null, $params = null, $action = 'edit') {
        // Se o model não houver sido informado, usa o definido no mock do controller
        if (empty($model)) {
            $model = $this->controller->modelClass;
        }
        $lastId = $this->createRecord($model, $addData);
        $editData[$model]['id'] = $lastId;
        // Busca no banco o último registro
        $this->testAction("$this->actionPath/$action/$lastId/" . $params, array('data' => $editData, 'method' => 'post'));
        // Testa se o flashSuccess foi exibido
        $erro = isset($this->controller->flashError) ? $this->validationErrors($model) : $this->controller->flashError;
        $this->assertTrue(isset($this->controller->flashSuccess), 'Ocorreu um erro: ' . $erro);
        // Busca no banco o último registro
        $findData = $this->controller->$model->find('first', array('order' => "$model.id DESC"));
        // Confere se os dados recuperados são iguais aos fornecidos
        foreach ($testFields as $field) {
            $this->assertEqual($findData[$model][$field], $editData[$model][$field], "Campo $field divergente.");
        }
        return $this->vars;
    }

    /**
     * Deleta o registro inserido.
     */
    public function deleteAsserts($model = null){
        if (empty($model)) {
            $model = $this->controller->modelClass;
        }
      $modelId = $this->controller->$model->find('first', array('order' => "$model.id DESC"));
      $this->testAction("$this->actionPath/delete/".$modelId[$model]['id']);

      $erro = isset($this->controller->flashError) ? $this->validationErrors($model) : 'Erro de validação não encontrado.';
      $this->assertTrue(isset($this->controller->flashSuccess), 'Mensagem de sucesso não foi exibida. Erro: ' . $erro);

      $newModelId = $this->controller->$model->find('first', array('order' => "$model.id DESC"));
      $this->assertNotEqual($modelId, $newModelId, 'Deleção falhou.');
    }

    public function createRecord($model, $data) {
        try {
            $this->controller->$model->save($data);
        } catch (Exception $e) {
            $this->assertTrue(false, "Erro ao inserir registro: $e");
        }
        return $this->controller->$model->id;
    }

    public function validationErrors($model) {
      $errors = array();
      foreach ($this->controller->$model->validationErrors as $key => $val) {
        foreach ($val as $v) {
          $data = !empty($this->controller->request->data[$model][$key]) ? $this->controller->request->data[$model][$key] : '';
          $errors[] = $v . '(' . $data . ')';
        }
      }
      array_unshift($errors, $this->controller->flashError);
      return implode(PHP_EOL, $errors);
    }

    protected function getFirstId($model, $order = 'DESC'){
      return $this->controller->$model->find('first', array('order' => "$model.id $order"));
    }
    
    /**
     * Realiza asserções na exceção
     *
     * @param Exception $excecao Exceção
     * @param string $tipoExcecao
     * @param string $mensagemErro
     * @return boolean
     */
    protected function comparaException(Exception $excecao, $tipoExcecao = '', $mensagemErroEsperada = '', $testContains = false){
    	
        // Testa se exceção recebida é a esperada
        // $this->assertTrue(is_a($excecao, $tipoExcecao));
        $this->assertEqual(get_class($excecao), $tipoExcecao, 'ClassExpected : ' . $tipoExcecao . ' ClassOcurried: ' . get_class($excecao) . ' (' . $excecao->getMessage() . ')');

        // Variável que testa se deverá validar a excessão com contains ou true
        if($testContains){
            // Valida variável de exceção verificando se a mensagem passagem está dentro da excessão gerada
            $this->assertContains($mensagemErroEsperada, $excecao->getMessage(), 'Expected : ' . $mensagemErroEsperada .' Ocurried : ' . $excecao->getMessage());
        }else{
            // Valida se a mensagem de erro é igual a mensagem esperada
            $this->assertEqual($excecao->getMessage(), $mensagemErroEsperada, 'Expected : ' . $mensagemErroEsperada .' Ocurried : ' . $excecao->getMessage());
        }
    
        // Seta variável indicando que recebeu a excessão esperada
        return true;
    }

        /**
    * Método para enviar a requisição de acordo com os parâmetros passados SEM ESPERAR EXCEÇÃO
    * @param $urlFull           - URL completa para ser testada na Action
    * @param $method            - Forma de entrada de dados. Ex: POST, GET, PUT or DELETE
    * @param $data              - Campos enviados
    */ 
    protected function sendRequest($urlFull = NULL, $method = NULL, $data = NULL){
        // Método para validar os parâmetros
        if(empty($urlFull) || empty($method) ||  empty($data)){
            throw new BadRequestException('Invalid Parameters for Test');
        }   
        // Acessa o link da API
        $this->testAction($urlFull , array('method' => $method,'data' => $data, 'return' => 'vars'));
        // $this->testAction($urlFull , array('method' => $method,'data' => $data));
    }
}// End Class