<?php

App::uses('ControllerTestCase', 'TestSuite');
App::uses('ClassRegistry', 'Utility');
App::uses('DataGenerator', 'Lib');

class BaseControllerTestCase extends ControllerTestCase {

    public $dataGenerator = null;
    
    public function setUp() {
        parent::setUp();
        @session_destroy();

        $this->dataGenerator->clearDatabase();
        $this->dataGenerator->insertDefaultData();
    }

    public function tearDown() {
        parent::tearDown();
        @session_destroy();
    }

    protected function validateTestException(
        $urlFull = NULL,
        $method = NULL,
        $data = NULL,
        $exceptionExpected = NULL,
        $messageExpected = NULL,
        $testContains = false){
            
        $message = '';

        // Método para validar os parâmetros
        if(empty($urlFull) || empty($method) || empty($exceptionExpected)){
            throw new BadRequestException('Invalid Parameters for Test');
        }

        // Variável que verificará se ocorreu ou não a exceção esperada
        $finalTester = false;
        try {
            // Acessa o link da API
            $this->testAction($urlFull, array('method' => $method,'data' => $data));
        } catch (Exception $e) {
            // Caso ocorra exceção, valida se a classe  e a mensagem são as esperadas.
            $finalTester = $this->comparaException($e, $exceptionExpected, $messageExpected, $testContains);
            $message = $e->getMessage();
        }

        // Valida variável de exceção
        $this->assertTrue($finalTester, "Não ocorreu exceção esperada: $exceptionExpected => $message");
    }

    protected function comparaException(Exception $excecao, $tipoExcecao = '', $mensagemErroEsperada = '', $testContains = false){
        // Testa se exceção recebida é a esperada
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

    protected function sendRequest ($url, $method, $data) {
        $results = $this->testAction($url, array('method' => $method, 'data' => $data, 'return' => 'vars'));
        return $results['data'];
    }
    
    public function __construct() {
    //     //Identifica qual é o plugin através do diretório da classe pai
    //     $rc = new ReflectionClass(get_class($this));
    //     $filename = dirname($rc->getFileName());
    //     $pathParts = explode(DS, strstr($filename, 'app'));
    //     if ($pathParts[1] === 'Plugin') {
    //         $this->pluginName = $pathParts[2];
    //     } else {
    //         $this->pluginName = null;
    //     }

    //     //Verifica o nome do controller sendo testado
    //     $controller = strstr(get_class($this), 'ControllerTest', true);
    //     if (!empty($this->pluginName)) {
    //        $this->controllerName = "{$this->pluginName}.". $controller;
    //        $this->controller = "{$this->pluginName}.". $controller;
    //     } else {
    //         $this->controllerName = $controller;
    //         $this->controller = $controller;
    //     }

    //     //Armazena o caminho para a url da action
    //     $this->actionPath = $this->controller;

    //     //Atalho para singleton DataGenerator
            $this->dataGenerator = DataGenerator::get();
            Configure::write('test', true);
        
    //     if (!isset($this->uses)) {
    //         $this->uses = array();
    //     }
    //     foreach ($this->uses as $fullModel) {
    //         list($plugin, $model) = pluginSplit($fullModel);
    //         $this->$model = ClassRegistry::init($fullModel);
    //     }
    }

    // private function auth() {
    //     //Cria um registro na tabela de sessão
    //     $this->Sessao = ClassRegistry::init('Sessao');
    //     $ret = $this->Sessao->save(array('Sessao'=>array(
    //         'ip' => '0.0.0.0',
    //         'entidade_id' => $this->entidadeId,
    //         'tipo' => 'SUCESSO'
    //     )));
    //     $this->session['Sessao.id'] = $this->Sessao->id;
    // }

    // /**
    //  * Configura os mocks 
    //  */
    // private function setupMocks($mockUser) {
    //     $components = array(
    //         'Auth' => array('user', 'isAllowed', 'login'),
    //         'Acl' => array('check'),
    //         'Session' => array('read', 'write')
    //     );
    //     $mock = $this->generate($this->controllerName, array(
    //         'components' => $components)
    //     );
    //     $that = $this;

    //     $mock->Acl->expects(
    //         $this->any())->method('check')->will(
    //         $this->returnValue(true));
    //     $mock->Auth->expects(
    //         $this->any())->method('isAllowed')->will(
    //         $this->returnValue(true));
    //     if ($mockUser) {
    //         $mock->Auth->staticExpects(
    //             $this->any())->method('user')->will(
    //             $this->returnCallback(function() use ($that) {
    //                 $args = func_get_args();
    //                 $key = @$args[0];
    //                 $entidade = ClassRegistry::init('Entidade');
    //                 $authUser = $entidade->findById($that->entidadeId);
    //                 if (!$authUser) {
    //                     throw new Exception("Entidade de id " . $that->entidadeId . " não encontrada!");
    //                 }
                    
    //                 $authUser = $authUser['Entidade'];
    //                 if ($key === null) {
    //                     return $authUser;
    //                 } else {
    //                     return $authUser[$key];
    //                 }
    //             }
    //             )
    //         );
    //     }

    //     $mock->Auth->expects(
    //         $this->any())->method('login')->will(
    //         $this->returnCallback(function() use ($that)  {
    //             //Salva o id do cliente que autenticou
    //             $user = func_get_arg(0);
    //             $that->entidadeId = $user['id'];
    //             //Reseta a sessão
    //             $that->session = array();
    //             return true;
    //     }));
    //     $mock->Session->expects(
    //         $this->any())->method('read')->will(
    //         $this->returnCallback(function($key = null) use ($that)  {
    //             return isset($that->session[$key])? $that->session[$key] : null;
    //     }));
    //     $mock->Session->expects(
    //         $this->any())->method('write')->will(
    //         $this->returnCallback(function($key, $value) use ($that)  {
    //             $that->session[$key] = $value;
    //             return true;
    //     })); 
    // }
 

    // public function createRecord($model, $data) {
    //     try {
    //         $this->controller->$model->save($data);
    //     } catch (Exception $e) {
    //         $this->assertTrue(false, "Erro ao inserir registro: $e");
    //     }
    //     return $this->controller->$model->id;
    // }

    // public function validationErrors($model) {
    //   $errors = array();
    //   foreach ($this->controller->$model->validationErrors as $key => $val) {
    //     foreach ($val as $v) {
    //       $data = !empty($this->controller->request->data[$model][$key]) ? $this->controller->request->data[$model][$key] : '';
    //       $errors[] = $v . '(' . $data . ')';
    //     }
    //   }
    //   array_unshift($errors, $this->controller->flashError);
    //   return implode(PHP_EOL, $errors);
    // }

    // protected function getFirstId($model, $order = 'DESC'){
    //   return $this->controller->$model->find('first', array('order' => "$model.id $order"));
    // }
    

   
}// End Class