<?php

App::uses('ApiBaseControllerTestCase', 'Api.Lib');

class ServicesControllerTest extends ApiBaseControllerTestCase {


        public $mockUser = false;

    public $uses = array('Aplicativo');

    /**
     * Testa action index
     */
    public function testIndex() {

        // Faz o request
        $this->testAction("api/services.json", array('method' => 'GET', 'return' => 'vars'));
        // Valida se recebeu dados 
        $this->assertNotNull($this->vars['data']['aplicativo']);
        // Extrai dados da resposta da requisição
        $vars = $this->vars['data']['aplicativo'];
        // Valida se o resultado encontrado é igual a dois : EC, PARKING
        $this->assertEquals(2, count($vars));
        // Variável para controlar as validações
        $cont = 0;
        // Varre lista de aplicativos recebidos
        foreach ($vars as $key => $value) {
            // Efetua os asserts de acordo com o contador
            switch($cont){
                case 0:
                    $this->assertEquals(1, $value['id']);
                    $this->assertEquals('EC', $value['descricao']);
                    break;
                case 1:
                    $this->assertEquals(2, $value['id']);
                    $this->assertEquals('PARKING', $value['descricao']);
                    break;
            }
            // Incrementa contador
            $cont++;
        }
    }// End Method 'testIndex'

}// End Class