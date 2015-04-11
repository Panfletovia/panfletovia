<?php

App::uses('DatabaseUtils', 'Lib');

/**
 * Aqui ficam as rotinas responsáveis por popular o banco / inserir dados interessantes nos testes
 */
class DataGenerator extends DatabaseUtils {

    //Singleton 
    private static $instance = null;

    private function __construct() {
    }

    public static function get() {
        if (self::$instance === null) {
            self::$instance = new DataGenerator();
        }
        return self::$instance;
    }

    public $modelId = array();

    /*
     * Insere na base os dados do model informado. Insere $data, se informado, 
     * ou os dados padrão recuperados pelo método get.
     * @param ($function) - Nome da função. Gerenciado pelo PHP
     * @param ([array $newData]) - Campos/valores a serem alterados
     */
    public function __call($function, $newData = null) {
        $data = null;
        // Resgata o nome do model
        $model = str_replace('save', '', $function);

        // Inicializa o model 
        if (!isset($this->$model)) {
            $this->getModel($model);
        } else {
            $this->$model->create();
        }

        // Determina a origem dos dados a serem inseridos e armazena em $data
        if (empty($newData) || empty($newData[0])) { // Se $data estiver vazio, usa os dados padrão
            $getMethod = 'get' . $model;
            $data = $this->$getMethod();
            if (!$data || empty($data)) { // Se o retorno for inválido
                throw new InternalErrorException("Erro: não foi possível encontrar os dados padrão de $model.");
            }
        } else { // Faz um merge do array recebido com o array desta classe
            $getMethod = 'get' . $model;
            $methodData = $this->$getMethod();
            $data[$model] = array_merge($methodData[$model], $newData[0][$model]);
        }

        // Insere os dados na base
        if ($this->$model->save($data)) {
            $modelId = strtolower($model) . 'Id';
            $this->$modelId = $this->$model->id;
            return $this->$model->id;
        } else {
            $error = !empty($this->$model->validationErrors) ? $this->$model->validationErrors : '';
        }
    }

    /**
     * Salva um registro de um model considerando o plugin
     * $plugin - plugin a ser utilizado
     * $model - model a ser utilizado
     * $data - os dados a serem salvos
     */
    public function saveThis($plugin, $model, $data) {
        // Inicializa o model 
        if (!isset($this->$model)) {
            $this->getModel($plugin.'.'.$model);
        } else {
            $this->$model->create();
        }

        $this->$model->save($data);
    }

    public function getCliente() {

        $tipo_pessoa = rand(0, 1);

        $nome = 'cliente ' . rand(1,10000);
        $rawPassword = 'panfletovia';

        return array(
            'Cliente' => array(
                'cpf_cnpj'        => $tipo_pessoa ? $this->randomCpf(true) : $this->randomCnpj(true),
                'pessoa'          => $tipo_pessoa ? 'FISICA' : 'JURIDICA',
                'nome'            => $nome,
                'fantasia'        => 'Fantasia '. $nome,
                'data_nascimento' => $this->randomDate(),
                'telefone'        => $this->randomPhone(),
                'cep'             => $this->randomCep(),
                'logradouro'      => 'Rua logradouro',
                'numero'          => rand(1,10000),
                'compl'           => null,
                'cidade'          => 'Novo Hamburgo',
                'bairro'          => 'Centro',
                'uf'              => 'RS',
                'ativo'           => 1,
                'tipo'            => 'CLIENTE',
                'criado_em'       => $this->randomDate('Y-m-d H:i:s', 0, 1),
                'login'           => str_replace(' ', '', $nome) . '@panfletovia.com.br',
                'senha'           => $this->createPasswordEncrypt($rawPassword),
                'sexo'            => 'M',
                'raw_password'     => $rawPassword
        ));
    }// End Method 'getCliente'

    public function getClientePerfil($perfilId = 1){

        return array(
            'ClientePerfil' => array(
                'cliente_id' => $this->clienteId,
                'perfil_id' => $perfilId
            )
        );
    }// End Method 'getClientePerfil'

    public function createPasswordEncrypt($password){
         // Atribui o valor da senha
        $newPassword = $password;
        // Calcula a quantidade de vezes a senha para gerar o hash
        for ($x = 0; $x < 66; $x++) {
            $newPassword = md5(BEFORE_ENCRYPT . $newPassword . AFTER_ENCRYPT);
        }
        // Retorna a nova senha
        return $newPassword;
    }

    public function randomCpf ($compontos = false) {
        $n1 = rand(0,9);
        $n2 = rand(0,9);
        $n3 = rand(0,9);
        $n4 = rand(0,9);
        $n5 = rand(0,9);
        $n6 = rand(0,9);
        $n7 = rand(0,9);
        $n8 = rand(0,9);
        $n9 = rand(0,9);
        $d1 = $n9*2 + $n8*3 + $n7*4 + $n6*5 + $n5*6 + $n4*7 + $n3*8 + $n2*9 + $n1*10;
        $d1 = 11 - ( $this->mod($d1,11) );
        if ( $d1 >= 10 ) {
            $d1 = 0;
        }
           
        $d2 = $d1*2 + $n9*3 + $n8*4 + $n7*5 + $n6*6 + $n5*7 + $n4*8 + $n3*9 + $n2*10 + $n1*11;
        $d2 = 11 - ( $this->mod($d2,11) );
        if ($d2>=10) {
            $d2 = 0 ;
        }
        $retorno = '';
        if ($compontos) {
            $retorno = ''.$n1.$n2.$n3.".".$n4.$n5.$n6.".".$n7.$n8.$n9."-".$d1.$d2;
        } else {
            $retorno = ''.$n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$d1.$d2;
        }
        return $retorno;
    }// End Method 'randomCpf'

    public function randomCnpj ($compontos = false) {
        $n1 = rand(0,9);
        $n2 = rand(0,9);
        $n3 = rand(0,9);
        $n4 = rand(0,9);
        $n5 = rand(0,9);
        $n6 = rand(0,9);
        $n7 = rand(0,9);
        $n8 = rand(0,9);
        $n9 = 0;
        $n10= 0;
        $n11= 0;
        $n12= 1;
        $d1 = $n12*2 + $n11*3 + $n10*4 + $n9*5 + $n8*6 + $n7*7 + $n6*8 + $n5*9 + $n4*2 + $n3*3 + $n2*4 + $n1*5;
        $d1 = 11 - ( $this->mod($d1,11) );
        if ( $d1 >= 10 )
        { $d1 = 0 ;
        }
        $d2 = $d1*2 + $n12*3 + $n11*4 + $n10*5 + $n9*6 + $n8*7 + $n7*8 + $n6*9 + $n5*2 + $n4*3 + $n3*4 + $n2*5 + $n1*6;
        $d2 = 11 - ( $this->mod($d2,11) );
        if ($d2>=10) {
            $d2 = 0 ;
        }
        $retorno = '';
        if ($compontos==1) {
            $retorno = ''.$n1.$n2.".".$n3.$n4.$n5.".".$n6.$n7.$n8."/".$n9.$n10.$n11.$n12."-".$d1.$d2;
        } else {
            $retorno = ''.$n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$n10.$n11.$n12.$d1.$d2;}
        return $retorno;
    }// End Method 'randomCnpj'

    public function randomDate($format = 'Y-m-d', $min_years = 0, $max_years = 10) {
        return date($format, (time() - rand( ($min_years * 60 * 60 * 24 * 365), ($max_years * 60 * 60 * 24 * 365) )));
    }// End Method 'randomDate'

    public function randomPhone() {
        return '(51)' . rand(1000,9999) . '-' . rand(1000,9999);
    }// End Method 'randomPhone'

    public function randomCep(){
        $n1 = rand(0,9);
        $n2 = rand(0,9);
        $n3 = rand(0,9);
        $n4 = rand(0,9);
        $n5 = rand(0,9);
        $n6 = rand(0,9);
        $n7 = rand(0,9);
        $n8 = rand(0,9);

        return $n1.$n2.$n3.$n4.$n5.'-'.$n6.$n7.$n8;
    }

    private function mod($dividendo,$divisor) {
        return round($dividendo - (floor($dividendo/$divisor)*$divisor));
    }

 //    public function concedeLimitePos($entidadeId, $valor) {
 //        if (!isset($this->Limite)) {
 //            $this->Limite = $this->getModel('Limite');
 //        }
 //        $this->Limite->id = $this->Limite->field('id', array('entidade_id' => $entidadeId));
 //        $this->Limite->saveField('pos_liberado', $valor);
 //    }

 //    public function concedeLimitePre($entidadeId, $valor) {
 //        if (!isset($this->Limite)) {
 //            $this->Limite = $this->getModel('Limite');
 //        }
 //        $this->Limite->id = $this->Limite->field('id', array('entidade_id' => $entidadeId));
 //        $this->Limite->saveField('pre_creditado', $valor);
 //    }

 //    public function clearPendente() {
 //        $this->query('DELETE FROM pendente');
 //    }

 //    public function efetuaRecargaAssociado($customParams = array()) {
 //        $params = array(
 //                'equipamento_id' => $this->equipamentoId,
 //                'nsu' => rand(100,500), 
 //                'pagamento' => 'DINHEIRO',
 //                'identificacao' => 'CONTA',
 //                'produto_id' => null, 
 //                'bolsa_id' => BOLSA_CONTA_CORRENTE, 
 //                'valor' => rand(10,99), 
 //                'pedido_id' => null,
 //                'entidade_id' => $this->clienteId, 
 //                'cpf_cnpj' => null, 
 //                'senha_hash' => null, 
 //                'rps' => null, 
 //                'park_placa' => null, 
 //                'park_vaga' => null, 
 //                'park_servico_id' => null, 
 //                'park_operador_id' => null, 
 //                'park_tipo' => null, 
 //                'park_area_id' => null, 
 //                'park_cobranca_id_original' => null, 
 //                'park_periodos' => null, 
 //                'park_veiculo' => 'CARRO', 
 //                'park_debito_automatico' => 0, 
 //                'park_avaria' => null, 
 //                'park_pertences' => null,
 //                'park_prisma' => 0, 
 //                'park_marca_id' => null, 
 //                'park_model_id' => null, 
 //                'park_cor_id' => null, 
 //                'park_antecipado' => 0, 
 //                'bin_nsu_reserva' => null
 //            );
 //        $finalParams = array_merge($params, $customParams);
 //        return $this->callProcedure('movimenta_conta', array_values($finalParams));
 //    }

 //    public function efetuaRecargaDinheiroPosto($customParams = array(), $valorRecarga = 0.00) {

 //        if(empty($valorRecarga)){
 //            $valorRecarga = rand(10,99);
 //        }

 //        $params = array(
 //                'equipamento_id' => $this->equipamentoId,
 //                'nsu' => rand(100,500), 
 //                'pagamento' => 'DINHEIRO',
 //                'identificacao' => 'NENHUM',
 //                'produto_id' => null, 
 //                'bolsa_id' => BOLSA_CONTA_CORRENTE, 
 //                'valor' => $valorRecarga, 
 //                'pedido_id' => null,
 //                'entidade_id' => $this->clienteId, 
 //                'cpf_cnpj' => null, 
 //                'senha_hash' => null, 
 //                'rps' => null, 
 //                'park_placa' => null, 
 //                'park_vaga' => null, 
 //                'park_servico_id' => null, 
 //                'park_operador_id' => null, 
 //                'park_tipo' => null, 
 //                'park_area_id' => null, 
 //                'park_cobranca_id_original' => null, 
 //                'park_periodos' => null, 
 //                'park_veiculo' => 'CARRO', 
 //                'park_debito_automatico' => 0, 
 //                'park_avaria' => null, 
 //                'park_pertences' => null,
 //                'park_prisma' => 0, 
 //                'park_marca_id' => null, 
 //                'park_model_id' => null, 
 //                'park_cor_id' => null, 
 //                'park_antecipado' => 0, 
 //                'bin_nsu_reserva' => null
 //            );
 //        $finalParams = array_merge($params, $customParams);
 //        return $this->callProcedure('movimenta_conta', array_values($finalParams));
 //    }

 //    /**
 //     * Vende um ticket comprado por CPF_CNPJ
 //     */
 //    public function venderTicketEstacionamentoCpfCnpj($valor, $placa, $vaga = 0, $codigoTarifa = 1) {
 //        return $this->callProcedure('movimenta_conta', array(
 //            $this->equipamentoId, $this->nsu(),'PRE', 'CONTA', $this->produtoId, BOLSA_CONTA_CORRENTE, $valor, NULL,
 //            $this->clienteId, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, $vaga, $this->servicoId, $this->operadorId,'UTILIZACAO', $this->areaId, $this->cobrancaId, $codigoTarifa, 'CARRO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,NULL));
 //    }// End Method 'venderTicketEstacionamentoCpfCnpj'

 //    /**
 //     * Vende um ticket comprado por DINHEIRO
 //     */
 //    public function venderTicketEstacionamentoDinheiro($valor, $placa, $vaga = 0, $codigoTarifa = 1) {
 //        return $this->callProcedure('movimenta_conta', array(
 //            $this->equipamentoId, $this->nsu(),'DINHEIRO', 'NENHUM', $this->produtoId, NULL, $valor, NULL,
 //            NULL, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, $vaga, $this->servicoId, $this->operadorId,'UTILIZACAO', $this->areaId, $this->cobrancaId, $codigoTarifa, 'CARRO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,
 //            NULL));
 //    }// End Method 'venderTicketEstacionamentoDinheiro'

 //    public function venderTicketEstacionamentoPosto($valor, $placa, $vaga = 0, $codigoTarifa = 1){
 //        return $this->callProcedure('movimenta_conta', array(
 //            $this->equipamentoId, $this->nsu(),'DINHEIRO', 'NENHUM', $this->produtoId, NULL, $valor, NULL,
 //            NULL, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, $vaga, NULL, NULL,'UTILIZACAO', $this->areaId, $this->cobrancaId, $codigoTarifa, 'CARRO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,
 //            NULL));
 //    }// End Method 'venderTicketEstacionamentoPosto'

 //    /**
 //     * Vende um ticket comprado por DINHEIRO
 //     */
 //    public function venderTicketEstacionamentoDinheiroMoto($valor, $placa, $vaga = 0, $codigoTarifa = 1) {
 //        return $this->callProcedure('movimenta_conta', array(
 //            $this->equipamentoId, $this->nsu(),'DINHEIRO', 'NENHUM', $this->produtoId, NULL, $valor, NULL,
 //            NULL, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, $vaga, $this->servicoId, $this->operadorId,'UTILIZACAO', $this->areaId, $this->cobrancaId, $codigoTarifa, 'MOTO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,
 //            NULL));
 //    }// End Method 'venderTicketEstacionamentoDinheiroMoto'

 //    /**
 //     * Vende um ticket comprado por PREPAGO
 //     */
 //    public function venderTicketEstacionamentoPre($valor, $placa, $codigoTarifa = 1) {
 //        return $this->callProcedure('movimenta_conta', array(
 //            $this->equipamentoId, $this->nsu(),'PRE', 'CONTA', $this->produtoId, NULL, $valor, NULL,
 //            $this->clienteId, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, 0, $this->servicoId, $this->operadorId,'UTILIZACAO', $this->areaId, $this->cobrancaId, $codigoTarifa, 'CARRO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,
 //            NULL));
 //    }// End Method 'venderTicketEstacionamentoPre'

 //    public function quitarIrregularidades($valor, $placa, $codigoTarifa = 1, $ticketId = NULL) {
 //        // Se a irregularidade foi paga pelo site o ticketId não é nulo, e não deve ter operadorId nem equipamentoId para pagamento
 //        $operadorId = $ticketId ? NULL : $this->operadorId;
 //        $equipamentoId = $ticketId ? NULL : $this->equipamentoId;

 //        $this->callProcedure('movimenta_conta', array(
 //            $equipamentoId, $this->nsu(), 'DINHEIRO', 'NENHUM', $this->produtoId, BOLSA_CONTA_CORRENTE, $valor, NULL,
 //            $this->clienteId, NULL, NULL,NULL,
 //            //Parâmetros parking
 //            $placa, 0, $this->servicoId, $operadorId,'IRREGULARIDADE', $this->areaId, $this->cobrancaId, $codigoTarifa, 'CARRO', 0, NULL, NULL, 0, NULL, NULL, NULL, 0,
 //            NULL));
 //    }

 //    /**
 //     * Executa a verificação de um veículo
 //     */
 //    public function verificaVeiculo($placa, $vaga = 0) {
 //        $this->callProcedure('park_verifica_veiculo', array($placa, $vaga, $this->cobrancaId, $this->areaId, $this->equipamentoId, $this->nsu(), 'CARRO', null, null));
 //    }// End Method 'verificaVeiculo'

 //    /**
 //     * Executa a verificação de um Moto
 //     */
 //    public function verificaMoto($placa, $vaga = 0) {
 //        $this->callProcedure('park_verifica_veiculo', array($placa, $vaga, $this->cobrancaId, $this->areaId, $this->equipamentoId, $this->nsu(), 'MOTO', null, null));
 //    }// End Method 'verificaMoto'

 //    /**
 //     * Emite uma irregularidade para o veículo
 //     */
 //    public function emiteIrregularidade($placa, $vaga = 0, $motivo_irregularidade = 'SEM_TICKET', $setSleep = false) {
 //        $this->verificaVeiculo($placa, $vaga);
 //        if($setSleep){
 //            sleep(1);
 //        }
 //        return $this->callProcedure('park_emitir_irregularidade', array($this->areaId, $placa, $this->cobrancaId, $this->equipamentoId, $this->nsu(), $vaga, 'CARRO', $motivo_irregularidade, null, null, null, null));
 //    }// End Method 'emiteIrregularidade'

 //    /**
 //     * [devolverTicket description]
 //     * @param  [type] $ticketId      [description]
 //     * @param  [type] $equipamentoId [description]
 //     * @return [type]                [description]
 //     */
 //    public function devolverTicket($ticketId, $equipamentoId) {
 //        $this->callProcedure('park_devolver_ticket', array($ticketId, $equipamentoId, null, null));
 //    }// End Method 'devolverTicket'

 //    public function estornarAutorizacao($autorizacao, $ticketId, $equipamentoId) {
 //        $this->callProcedure('estorno_autorizacao', array($autorizacao, $ticketId, $this->nsu(), $equipamentoId));
 //        $this->commit();
 //    }// End Method 'estornarAutorizacao'


 //    /**
 //     * @return multitype:multitype:number string NULL
 //     */
 //    public function getVerificacao() {
 //        return array(
 //            'Verificacao' => array(
 //                'verificado_em' 			=> $this->getDateTime(),
 //                'servico_sequencia' 		=> 1,
 //                'servico_id' 				=> $this->servicoId,
 //                'operador_id' 				=> $this->operadorId,
 //                'area_id' 					=> $this->areaId,
 //                'vaga' 						=> rand(1,9999),
 //                'tipo_vaga' 				=> 'NORMAL',
 //                'periodos' 					=> 1,
 //                'administrador_id' 			=> ADMIN_PARKING_ID,
 //                'lancado' 					=> rand(0,1),
 //                'trocou_vaga' 				=> 0,
 //                'placa' 					=> $this->randomPlaca(),
 //                'ticket_id' 				=> $this->ticketId,
 //                'tolerancia_ate_externo' 	=> $this->getDateTime('-' . rand(1,5) . ' Hours'),
 //                'codigo_externo' 			=> rand(1000,9999)
 //            )
 //        );
 //    }

 //    public function getMovimento() {
 //        return array('Movimento' => array(
 //                'limite_id' => $this->limiteId,
 //                'conta' => 'PRE',
 //                'tipo' => 'BOLSA',
 //                'valor' => 5,
 //                'valor_original' => 5,
 //                'pre_creditado' => 5,
 //                'operacao_id' => 1107
 //            )
 //        );
 //    }

    

 //    public function getAreaPonto() {

 //        if(!isset($this->setorId)){
 //            $this->saveSetor();
 //        }

 //        return array(
 //            'AreaPonto' => array(
 //                'codigo'        => rand(1,9999),
 //                'area_id'       => $this->areaId,
 //                'tipo'          => 'VAGA',
 //                'tipo_vaga'     => 'NORMAL',
 //                'latitude'      => $this->randomLatitude(),
 //                'longitude'     => $this->randomLongitude(),
 //                'informacao'    => 'Vaga ' . rand(0,99999999),
 //                'setor_id'      => $this->setorId
 //            )
 //        );
 //    }

 //    public function getParkConfiguracao() {
 //        return array(
 //            'ParkConfiguracao' => array(
 //                'nome' => 'CONFIRMAR_PLACA',
 //                'valor' => 1
 //            )
 //        );
 //    }

 //    public function getPagamento() {
 //        return array(
 //            'Pagamento' => array(
 //                'descricao' => 'PAGSEGURO',
 //                'forma' => 'PAGSEGURO',
 //                'posto_id' => $this->postoId,
 //                'disponivel_recarga' => 1
 //            )
 //        );
 //    }

 //    public function getTransacao() {
 //        return array(
 //            'Transacao' => array(
 //                'adquirente' => 'CIELO',
 //                'aplicacao' => 'com.versul.s2parking',
 //                'ec_acquirer' => '0010000244470001',
 //                'codigo_aid' => 'A0000000031010',
 //                'codigo_resposta' => '000',
 //                'ec_venda' => '0010000244470001',
 //                'nsu' => '645351',
 //                'tipo_transacao' => '1',
 //                'codigo_autorizacao' => '133718',
 //                'referencia' => 'Recarga S2Way',
 //                'pan' => '498442-7150',
 //                'id_transacao' => '5456',
 //                'data_requisicao' => '150125145200',
 //                'valor' => '540',
 //                'nome_aplicacao' => 'S2Parking',
 //                'codigo_produto_secundario' => '204',
 //                'codigo_produto_matriz' => '4',
 //                'fluxo' => '4',
 //                'data_servidor' => '150125145200',
 //                'versao_app_financeira' => '1.5.1',
 //                'retorno_aplicacao' => 'R00',
 //                'captura' => 'ONL-C',
 //                'modo_captura' => '603010204080',
 //                'pedido_id' => null
 //            )
 //        );
 //    }


 //    // @@@ TALONARIO - inicio
 //    public function getPais()
 //    {
 //        return array('Pais' => array('id' => '1', 'descricao' => 'PHPUNIT'));
 //    }

 //    public function getTalonarioOperador() {
 //        return array('Pais' => array('id' => '1', 'descricao' => 'PHPUNIT'));
 //    }
    
 //    public function getMedida()
 //    {
 //        return array('Medida' => array('id' => '1', 'descricao' => 'PHPUNIT'));
 //    }
    
 //    public function getTipoVeiculo()
 //    {
 //        return array('TipoVeiculo' => array('id' => '1', 'descricao' => 'PHPUNIT'));
 //    }

 //    public function getEnquadramento()
 //    {
 //        return array('Enquadramento' => array('id' => '1', 'descricao' => 'PHPUNIT', 'ctb' => 'Artigo 1', 'infrator' => 'Pedestre'));
 //    }

 //    public function getEnquadramentoMedida()
 //    {
 //        return array('EnquadramentoMedida' => array('enquadramento_id' => 1, 'medida_id' => 1));
 //    }

 //    public function getAitNumeracao()
 //    {
 //        return array('AitNumeracao' => array('serie' => 'UNI', 'no_inicial' => 1, 'no_final' => 100));
 //    }

 //    public function getOperadorFaixa()
 //    {
 //        return array('OperadorFaixa' => array('serie' => 'UNI', 'no_inicial' => 1, 'no_final' => 100, 'situacao' => 'EM_PROCESSAMENTO', 'operador_id' => 1));
 //    }
    
 //    /**
 //     * Retorna a representação de uma reserva de vagas
 //     */
 //    public function getReserva() {

 //    	if (!isset($this->setorId)) {
 //    		$this->saveSetor();
 //    	}
    	
 //    	$reserva = array(
 //    			'Reserva' => array(
 //    					'data_inicio' 	=> $this->getDateTime('+1 day'),
 //    					'data_fim' 		=> $this->getDateTime('+2day'),
 //    					'setor_id' 		=> $this->setorId,
 //    					'descricao' 	=> 'Foo',
 //    					'associado_id' 	=> ADMIN_PARKING_ID,
 //    					'area_id' 		=> $this->areaId,
 //    					'inativo' 		=> FALSE,
 //                        'ticket_id'     => null
 //    			)
 //    	);
    	
 //    	return $reserva;
 //    }


 //    public function getReservaVaga(){

 //        if(!isset($this->areapontoId)){
 //            $areaPonto = $this->getAreaPonto();
 //            $this->saveAreaPonto($areaPonto);
 //        } else {
 //            $areaPonto = $this->AreaPonto->findById($this->areapontoId);
 //        }

 //        $codigo = $areaPonto['AreaPonto']['codigo'];

 //        if(!isset($this->reservaId)){
 //            $this->saveReserva();
 //        }

 //        $reservaVaga = array('ReservaVaga' => array(
 //            'reserva_id' => $this->reservaId,
 //            'vaga' => $codigo
 //        ));

 //        return $reservaVaga;
 //    }

 //    // @@@ TALONARIO - fim

 //    // @@@ TAXI - inicio
 //    public function getRegra()
 //    {
 //        return array('Regra' => array('tarifa_corrida_aceita' => 0.09));
 //    }

 //    public function chamada($clienteId, $servico) 
 //    {        
 //        $this->save('Chamada',array('passageiro_id' => $clienteId,
 //                                       'cliente_id' => $clienteId,
 //                                        'criado_em' => $this->getDateTime(),
 //                                        'longitude' => -51.1306,                                                                                
 //                                         'latitude' => -29.6800,
 //                                          'servico' => $servico));
 //    }
 //    // @@@ TAXI - fim

 //    /* 
 //     * Esta função simula a inserção de tickets com parâmetros aleatórios
 //     * @param $numTickets - Número de tickets a serem gerados
 //     * @param $auxNum - Número de cadastros auxiliares (Posto, Área, Preço, etc)
 //     * @param $numVagas - Número de vagas a serem criadas
 //    */
 //    public function geraTicketsRelatorio($numTickets = 10, $auxNum = 5, $numVagas = 20, $pgtoEmAssociado = false) {
 //        // Cria os cadastros necessários e armazena os ids
 //        $sortValues = array();
 //        for ($i = 0; $i < $auxNum; $i++) {
        	
 //            $this->savePosto();
 //            $this->saveArea();
 //            $this->saveSetor();
 //            $this->savePreco();
 //            $this->saveParkTarifa();
 //            $this->saveProduto();
 //            $this->saveCobranca();
 //            $this->saveEquipamento();
 //            $this->saveOperador();
 //            $this->saveServico();
 //            $sortValues['posto_id'][] = $this->postoId;
 //            $sortValues['area_id'][] = $this->areaId;
 //            $sortValues['preco_id'][] = $this->precoId;
 //            $sortValues['cobranca_id'][] = $this->cobrancaId;
 //            $sortValues['equipamento_id'][] = $this->equipamentoId;
 //            $sortValues['operador_id'][] = $this->operadorId;
 //            $sortValues['servico_id'][] = $this->servicoId;
 //            $sortValues['entidade_id_pagamento'][] = $pgtoEmAssociado ? ADMIN_PARKING_ID : $this->postoId;
 //        }
 //        // Cria as vagas necessárias
 //        for ($i = 0; $i < $numVagas; $i++) {
 //            $areaId = $sortValues['area_id'][rand(0,$auxNum-1)];
 //            $codigo = rand(1000, 9999);
 //            $this->saveAreaPonto(array('AreaPonto' => array('area_id' => $areaId, 'codigo' => $codigo)));
 //            $sortValues['vaga']['area_id'][] = $areaId;
 //            $sortValues['vaga']['codigo'][] = $codigo;
 //        }
 //        // Adiciona outras opções ao array
 //        $sortValues['situacao'] = array('PAGO', 'AGUARDANDO', 'CANCELADO');
 //        // $sortValues['veiculo'] = array('CARRO', 'MOTO'); // A implementar
 //        $sortValues['tipo'] = array('UTILIZAÇÃO', 'IRREGULARIDADE');
 //        $sortValues['forma_pagamento'] = array('DINHEIRO', 'PRE');
 //        $sortValues['entidade_id_pagamento'][] = ADMIN_PARKING_ID;
 //        // Gera os tickets com valores randômicos, usando rand() como index do array
 //        for ($i = 0; $i < $numTickets; $i++) {
 //            $fixedIndex = rand(0,$auxNum-1);
 //            $vagasIndex = rand(0,$numVagas-1);
 //            // $dateInicio = $this->getDateTime('-' . $fixedIndex . ' Day');
 //            // $dateFim = $this->getDateTime('-' . $fixedIndex . ' Day, -1 Hour');
 //            $dateInicio = $this->getDateTime('- 2 Hour');
 //            $dateFim = $this->getDateTime('-1 Hour');
 //            $valor = rand(100, 1000)/100;
 //            $ticket = array(
 //                'Ticket' => array(
 //                    'situacao'                  => $sortValues['situacao'][rand(0,2)],
 //                    'valor'                     => $valor,
 //                    'criado_em'                 => $dateInicio,
 //                    'tipo'                      => $sortValues['tipo'][rand(0,1)],
 //                    'equipamento_id_origem'     => $sortValues['equipamento_id'][$fixedIndex],
 //                    'equipamento_id_pagamento'  => $sortValues['equipamento_id'][$fixedIndex],
 //                    'nsu_origem'                => rand(10,9999),
 //                    'data_inicio'               => $dateInicio,
 //                    'data_fim'                  => $dateFim,
 //                    'entidade_id_origem'        => $sortValues['posto_id'][rand(0,$auxNum-1)],
 //                    'entidade_id_pagamento'     => $sortValues['entidade_id_pagamento'][rand(0,$auxNum)],
 //                    'preco_id'                  => $sortValues['preco_id'][rand(0,$auxNum-1)],
 //                    'area_id'                   => $sortValues['vaga']['area_id'][$vagasIndex],
 //                    'servico_id_pagamento'      => $sortValues['servico_id'][$fixedIndex],
 //                    'periodos'                  => 1,
 //                    'vaga'                      => $sortValues['vaga']['codigo'][$vagasIndex],
 //                    'pago_em_dinheiro'          => rand(0,1),
 //                    'operador_id_origem'        => $sortValues['operador_id'][$fixedIndex],
 //                    'operador_id_pagamento'     => $sortValues['operador_id'][$fixedIndex],
 //                    'motivo_irregularidade'     => 'NENHUM',
 //                    'administrador_id'          => ADMIN_PARKING_ID,
 //                    // 'veiculo'                => $sortValues['veiculo'][rand(0,1)],
 //                    'cobranca_id'               => $sortValues['cobranca_id'][rand(0,$auxNum-1)],
 //                    'forma_pagamento'           => $sortValues['forma_pagamento'][rand(0,1)],
 //                    'valor_original'            => $valor
 //                )
 //            );
 //            $ticket['Ticket']['pago_em'] = $ticket['Ticket']['situacao'] == 'PAGO' ? $dateFim : null;
 //            // Vencimento da notificação: 1 dia depois
 //            $ticket['Ticket']['notificacao_vencimento'] = $ticket['Ticket']['tipo'] == 'IRREGULARIDADE' ? $this->getDateTime('-' . $fixedIndex-1 . ' Day') : null;
 //            // Transmissão da notificação: 30 min depois
 //            $ticket['Ticket']['notificacao_transmitida_em'] = $ticket['Ticket']['tipo'] == 'IRREGULARIDADE' ? $this->getDateTime('-' . $fixedIndex . ' Day, +30 Minutes') : null;
 //            $this->saveTicket($ticket);
 //        }
 //    }

 //    public function getRecibo($app = true) {
 //        if ($app) {
 //            $recibo = array(
 //                'Recibo' => array(
 //                    'modelo' => '{center}{w}teste $(nsu){/w}{br}',
 //                    'administrador_id' => ADMIN_ID,
 //                    'leiaute_id' => 1,
 //                    'aplicativo_id' => APLICATIVO_PARKING,
 //                    'descricao' => 'Recibo teste',
 //                    'alvo' => 'DATECS'
 //                )
 //            );
 //        } else {
 //            $recibo = array();
 //        }
 //        return $recibo;
 //    }

 //    public function getBonusRecarga() {
 //        return array('BonusRecarga' => 
 //            array(
 //                'faixa_min' => 5.00,
 //                'faixa_max' => 10.00,
 //                'valor_bonus' => 5.00,
 //                'perc_bonus' => 0.00
 //            )
 //        );
 //    }

 //    public function getBolsa(){
 //        return array('Bolsa' =>
 //                array(
 //                    'descricao' => 'Bolsa Teste',
 //                    'pre' => 1,
 //                    'pos' => 1,
 //                    'desativado' => 0,
 //                    'dias_corte_fechamento' => 5,
 //                    'pgto_min_perc' => 20 
 //                )
 //            );
 //    }

 //    public function getParkTarifa() {
    	
 //    	if (!isset($this->precoId)) {
 //    		$this->savePreco();
 //    	}
    	
 //        return array(
 //            'ParkTarifa' => array(
 //                'preco_id' => $this->precoId,
 //                'minutos' => 15,
 //                'valor' => 2.00,
 //                'vender_posto' => 1,
 //                'vender_associado' => 1,
 //                'vender_internet' => 1,
 //                'codigo' => 1,
 //            )
 //        );
 //    }

 //    public function getSensor() {
 //        return array(
 //            'Sensor' => array (
 //                'uin' => $this->randomUIN(),
 //                'epc' => $this->randomEPC(),
 //                'corrente_carga_bateria' => rand (0, 9999),
 //                'corrente_descarga_bateria' => rand (0, 9999),
 //                'tensao_bateria' => (rand (0, 9999) / 100.0),
 //                'tensao_fotocelula' => (rand (0, 9999) / 100.0),
 //                'temperatura_placa' => rand (0, 99),
 //                'vaga_ocupada' => 0,
 //                'jamming_magnetico' => 1,
 //                'superaquecimento' =>0,
 //                'vibracao' =>0,
 //                'lowbat' =>0,
 //                'comando' =>'NENHUM'
 //            )
 //        );
 //    }

 //    public function getSetor() {
    	
 //    	if (!isset($this->areaId)) {
 //    		$this->saveArea();
 //    	}
    	
 //        return array(
 //            'Setor' => array(
 //                'nome' 		=> 'Setor Teste',
 //                'area_id' 	=> $this->areaId
 //            )
 //        );
 //    }

 //    public function posto($cpf = '753.424.154-54', $telefone = '(51)1234-6597') {
 //        return $this->save('Entidade', array(
 //            'cpf_cnpj'        => $cpf,
 //            'nome'            => 'PHPUNIT',
 //            'pessoa'          => 'FISICA',
 //            'tipo'            => 'POSTO',
 //            'telefone'        => $telefone,
 //            'versao_contrato' => 1,
 //            'criado_em'       => $this->getDateTime()
 //        ));
 //    }

 //    /*
 //     * Insere um cliente com as $placas vinculadas à sua conta
 //     * @param $placas As placas que devem ser vinculadas ao cliente
 //     * @return Id do cliente
 //     */
 //    public function cliente($cpf, $telefone, $placas = array()) {
 //        $saved = $this->save('Entidade', array(
 //            'cpf_cnpj'         => $cpf,
 //            'nome'             => 'PHPUNIT',
 //            'pessoa'           => 'FISICA',
 //            'tipo'             => 'CLIENTE',
 //            'telefone'         => $telefone,
 //            'versao_contrato'  => 1,
 //            'autorizar_debito' => 1,
 //            'criado_em'        => $this->getDateTime()
 //        ));
        
 //        //Vincula as placas especificadas ao cliente
 //        foreach ($placas as $placa) {
 //            $saved = $this->save('Placa', array(
 //                'placa'       => $placa,
 //                'entidade_id' => $this->Entidade->id,
 //                'tipo'        => 'CARRO',
 //                'criado_em'   => $this->getDateTime()
 //            ));
 //        }
 //        return $this->Entidade->id;
 //    }

 //    /**
 //     * Insere veiculos plugin car
 //     */
 //    public function veiculo($placa) {
 //        return $saved = $this->save('Car.Veiculo', array(
 //            'placa'         => $placa
 //        ));
 //    }

 //    /**
 //     * Insere um operador para o associado e área especificados
 //     */
 //    public function operador($associadoId, $areaId) {
 //        $saved = $this->save('Operador', array(
 //            'nome'         => 'PHPUNIT',
 //            'usuario'      => 'PHPUNIT',
 //            'senha'        => md5('PHPUNIT'),
 //            'area_id'      => $areaId,
 //            'associado_id' => $associadoId
 //        ));
 //        return $this->Operador->id;
 //    }

 //    /**
 //     * Insere um serviço aberto
 //     */
 //    public function servico($associadoId, $areaId, $equipamentoId, $operadorId) {
 //        $id = $this->save('Servico', array(
 //            'data_abertura'    => $this->getDateTime(),
 //            'equipamento_id'   => $equipamentoId,
 //            'area_id'          => $areaId,
 //            'administrador_id' => ADMIN_PARKING_ID,
 //            'operador_id'      => $operadorId
 //        ));
 //        return $id;
 //    }

 //    /**
 //     * Insere um associado do tipo especificado
 //     */
 //    public function associado($tipo = 'PARKING') {
 //        $id = $this->save('Entidade', array(
 //            'tipo'            => $tipo,
 //            'nome'            => 'PHPUNIT',
 //            'cpf_cnpj'        => ASSOCIADO_CPF,
 //            'pessoa'          => 'FISICA',
 //            'telefone'        => ASSOCIADO_TELEFONE,
 //            'versao_contrato' => 1,
 //            'negocio_id'      => 70,
 //            'criado_em' => $this->getDateTime()
 //        ));
 //        return $id;
 //    }

 //    /**
 //     * Insere um equipamento no associado especificado  
 //     */
 //    public function equipamento($associadoId) {
 //        return $this->save('Equipamento', array(
 //                    'no_serie'         => 'PHPUNIT',
 //                    'tipo'             => 'POS',
 //                    'situacao'         => 'ATIVO',
 //                    'administrador_id' => ADMIN_PARKING_ID,
 //                ));
 //    }

 //    /**
 //     * Insere um preço de rotativo com algumas configurações pré-definidas
 //     */
 //    public function preco($associadoId, $duracaoPeriodo = 60, $valorPeriodo = 1) {
 //        return $this->save('Parking.Preco', array(
 //                    'nome'                      => 'PHPUNIT',
 //                    'tipo'                      => 'ROTATIVO',
 //                    'cobranca_periodos'         => 1,
 //                    'duracao_periodo'           => $duracaoPeriodo,
 //                    'tempo_max_periodos'        => $duracaoPeriodo * 2, //Máximo de 2 períodos
 //                    'tolerancia_cancelamento'   => 5, //5 minutos para cancelar
 //                    'excedente_periodo_minutos' => $duracaoPeriodo,
 //                    'excedente_periodo_valor'   => $valorPeriodo,
 //                    'irregularidade'            => 'NOTIFICACAO',
 //                    'valor_irregularidade'      => 5.00, //Notificação de R$ 5
 //                    'tempo_livre'               => 5, //5 minutos de tolerância
 //                    'administrador_id'          => $associadoId,
 //                    'cobranca_id'               => $this->cobrancaId
 //                ));
 //    }

 //    /**
 //     * Insere um produto com comissões zeradas para o associado especificado
 //     */
 //    public function produto($associadoId, $aplicativoId) {
 //        $this->save('Produto', array(
 //            'descricao'         => 'PHPUNIT',
 //            'bolsa_id'          => BOLSA_CONTA_CORRENTE,
 //            'administrador_id'  => $associadoId,
 //            'venda_inicio'      => '2001-01-01',
 //            'venda_termino'     => '2030-01-01',
 //            'venda_em_dinheiro' => 1,
 //            'venda_em_pre'      => 1,
 //            'venda_em_pos'      => 1,
 //            'aplicativo_id'     => $aplicativoId,
 //        ));
 //        $this->save('Comissao', array(
 //            'produto_id' => $this->Produto->id
 //        ));
 //        return $this->Produto->id;
 //    }


 //    /**
 //     * Insere uma cobrança simples que em todas as situações cobra o preçol $precoId especificado
 //     * @param $associadoId Código do associado responsável por esta cobrança
 //     * @param $precoId O preço especificado
 //     * @param $produtoId Id do produto utilizado para esta cobrança
 //     * @return Retorna o id da cobrança inserida
 //     */
 //    public function cobranca($associadoId, $precoId, $produtoId = null) {
 //        $this->save('Parking.Cobranca', array(
 //            'tipo'                                        => 'ROTATIVO',
 //            'produto_id'                                  => $produtoId,
 //            'preco_id_carro'                              => $precoId,
 //            'preco_id_moto'                               => $precoId,
 //            'preco_id_vaga_farmacia'                      => $precoId,
 //            'preco_id_vaga_idoso'                         => $precoId,
 //            'preco_id_irregularidade_vencido'             => $precoId,
 //            'preco_id_irregularidade_sem_ticket'          => $precoId,
 //            'preco_id_irregularidade_fora_vaga'           => $precoId,
 //            'preco_id_irregularidade_ticket_incompativel' => $precoId,
 //            'administrador_id'                            => $associadoId,
 //        ));
 //        return $this->Cobranca->id;
 //    }

 //    /**
 //     * Insere uma área de ROTATIVO no associado especificado
 //     * @param $associadoId Associado responsável pela área
 //     * @return Retorna o id da área inserida
 //     */
 //    public function area($associadoId) {
 //        return $this->save('Parking.Area', array(
 //            'nome'             => 'PHPUNIT',
 //            'tipo'             => 'ROTATIVO',
 //            'administrador_id' => $associadoId,
 //            'cobranca_id'      => $this->cobrancaId

 //        ));
 //    }


 //    public function tarifa() {  
 //        return $this->save('Tarifa', array(
 //            'processadora_id' => 1,
 //            'bolsa_id'        => 1,
 //            'inicio'          => '2000-01-01',
 //            'fim'             => '2030-01-01'
 //        ));
 //    }

 //    /**
 //     * Insere uma vaga com latitude e longitude 0 na área especificada
 //     * @param $areaId Id da área
 //     * @param $vaga Número da vaga
 //     * @param $tipo Tipo da vaga
 //     */
 //    public function vaga($areaId, $vaga = 1, $tipo = TIPO_VAGA, $overwrite = array()) {
 //        $this->save('AreaPonto', array(
 //            'codigo'    => $vaga,
 //            'area_id'   => $areaId,
 //            'tipo'      => 'VAGA',
 //            'tipo_vaga' => $tipo,
 //            'latitude'  => $this->randomLatitude(),
 //            'longitude' => $this->randomLongitude()
 //        ));
 //        return $this->AreaPonto->id;
 //    }
    
 //    /**
 //     * Modelo de email
 //     */
 //    public function getModeloEmail() {
 //    	$tipo = array('DEBITO_AUTOMATICO','IRREGULARIDADE', 'CADASTRO_CLIENTE_DEBITO_AUTOMATICO','CADASTRO_CLIENTE_PLACA', 'PEDIDO_RECARGA_PAG_SEGURO', 'PAGAMENTO_RECARGA_PAG_SEGURO', 'AVISO_CLIENTE_SALDO_MINIMO');
 //    	return array(
 //    		'ModeloEmail' => array(
 //    			'assunto' 			=> 'Assunto modelo ' . time() . rand (1, 99999), 	//varchar(50)
 //    			'aplicativo_id' 	=> APLICATIVO_ID_PARKING, 							//int(10)
 //    			'tipo' 				=> $tipo[rand(0, (count($tipo) - 1))], 				//enum(DEBITO_AUTOMATICO,IRREGULARIDADE)
 //    			'modelo' 			=> 'fhnwiuohf iwoeuhf iweufh sdkljhfiuwjhf wlks', 	//text
 //    		)
 //    	);
 //    }
    
 //    public function getMonitor()
 //    {
 //    	$situacao = array('LIVRE','OCUPADO','INDISPONIVEL');
 //    	return array(
 //    			'Monitor' => array(
 //    					'equipamento_id' 	=> $this->equipamentoId,
 //    					'situacao' 			=> $situacao[rand(0, (count($situacao) - 1))],
 //    					'hodometro' 		=> '0.0',
 //    					'longitude' 		=> $this->randomLongitude(),
 //    					'latitude' 			=> $this->randomLatitude(),
 //    					'velocidade' 		=> 0,
 //    					'entradas' 			=> 0,
 //    					'saidas_recebidas' 	=> 0,
 //    					'saidas_enviadas' 	=> 0,
 //    					'atualizado_em' 	=> null,
 //    					'chamada_id' 		=> null,
 //    					'corrida_id' 		=> null,
 //    					'mensagem' 			=> null,
 //    					'direcao' 			=> 0,
 //    					'operador_id' 		=> $this->operadorId,
 //    					'area_id' 			=> $this->areaId
 //    			)
 //    	);
 //    }

 //    /**
 //     * Os métodos a seguir retornam valores padrão para 
 //     * os cadastros necessários para os testes de S2Way
 //     */
 //    public function getNegocio() {
 //        return array(
 //            'Negocio' => array(
 //                'id'        => rand(101, 999),
 //                'descricao' => 'Neg ' . rand(0, 999999)
 //            )
 //        );
 //    }
    
 //    /**
 //     * Retorna um registro de park_historico. É necessário que o teste e o controller importem Parking.Historico
 //     */
 //    public function getHistorico()
 //    {
 //    	return array(
 //    			'Historico' => array(
 //    					'placa' 				    => $this->randomPlaca(),
 //    					'inserido_em' 			    => $this->getDateTime(),
 //    					'pago_ate' 				    => $this->getDateTime(),//TODO
 //    					'tolerancia_ate' 		    => $this->getDateTime(),//TODO
 //    					'removido_em' 			    => $this->getDateTime(),//TODO
 //    					'ultima_verificacao' 	    => $this->getDateTime(),//TODO
 //    					'verificacoes' 			    => rand(0, 99),
 //    					'periodos' 				    => rand(0, 99),
 //    					'equipamento_id' 		    => $this->equipamentoId,
 //    					'tolerancia_conversao' 	    => NULL,
 //    					'situacao' 				    => 'LANCADO',
 //    					'vaga' 					    => rand(1, 100),
 //    					'convenio' 				    => '0',
 //    					'irregularidades' 		    => '0',
 //    					'autuado' 				    => '0',
 //    					'area_id' 				    => $this->areaId,
 //    					'posto_id' 				    => $this->postoId, //Não é obrigatório
 //    					'preco_id' 				    => $this->precoId,
 //    					'servico_id' 			    => $this->servicoId, 
 //    					'servico_sequencia' 	    => '1',
 //    					'modelo_id' 			    => '1',//Outros
 //    					'marca_id' 				    => '1',//Outros
 //    					'prisma' 				    => rand(1, 100),
 //    					'avarias' 				    => NULL,
 //    					'pertences' 			    => NULL,
 //    					'cor_id' 				    => '1',//Outro
 //    					'contrato_id' 			    => NULL,
 //    					'veiculo' 				    => 'CARRO',
 //    					'cobranca_id' 			    => $this->cobrancaId,
 //    					'administrador_id' 		    => ADMIN_PARKING_ID,
 //    					'setor_id' 				    => $this->setorId,
 //    					'cobranca_id_original' 	    => NULL,
 //    					'preco_id_original'         => NULL,
 //                        'tempo_total'               => '0',
 //                        'iregularidades_emitidas'   => '0',
 //                        'ultima_troca_em'           => NULL,
 //                        'ticket_incompativel'       => NULL
 //    			)
 //    	);
 //    }

 //    public function getLimite() {
 //        return array(
 //            'Limite' => array(
 //                'entidade_id'           => $this->postoId,
 //                'bolsa_id'              => BOLSA_CONTA_CORRENTE,
 //                'pre_creditado'         => 5000.00,
 //                'pre_utilizado'         => -500.00,
 //                'pre_cancelado'         => 0.00,
 //                'pre_desconto'          => 0.00,
 //                'pre_creditado_estorno' => 0.00,
 //                'pre_utilizado_estorno' => 0.00,
 //                'pos_liberado'          => 5000.00,
 //                'pos_reduzido'          => 0.00,
 //                'pos_creditado'         => 0.00,
 //                'pos_utilizado'         => 0.00,
 //                'pos_cancelado'         => 0.00,
 //                'periodo_vencimento'    => 'P10',
 //                'pos_utilizado_estorno' => 0.00,
 //                'pos_creditado_estorno' => 0.00,
 //            )
 //        );
 //    }

 //    public function getItem() {
 //        return array('Item' => array(
 //                'situacao' => 'EM_PROCESSAMENTO',
 //                'criado_em' => $this->getDatetime()
 //            )
 //        );
 //    }

 //    public function getPedido() {
 //        return array(
 //            'Pedido' => array(
 //                'situacao'        => 'EM_PROCESSAMENTO',
 //                'criado_em'       => $this->getDatetime()
 //            )
 //        );
 //    }

 //    /**
 //     * Os métodos a seguir retornam valores padrão para 
 //     * os cadastros necessários para os testes de Parking
 //     * 
 //     * @param $rotativo Gera uma área do tipo privado
 //     */
 //    public function getArea($rotativo = true) {
    	
 //    	if (!isset($this->cobrancaId)) {
 //    		$this->saveCobranca();
 //    	}
    	
 //    	$data = array(
 //            'Area' => array(
 //                'nome' 									=> 'PHPUNIT ' . rand(1,99999),
 //                'cor' 									=> '#000000', //$this->randomColor(),
 //                'ruas' 									=> NULL,
 //                'area' 									=> '0',
 //                'vagas' 								=> '100',
 //                'vagas_isentas' 						=> '30',
 //                'vagas_especiais' 						=> '30',
 //                'horas_sabado' 							=> '0',
 //                'no_verif_sabado' 						=> '10',
 //                'tmp_verif_sabado' 						=> 30,
 //                'horas_domingo' 						=> '0',
 //                'no_verif_domingo' 						=> '1',
 //                'tmp_verif_domingo' 					=> 30,
 //                'horas_uteis' 							=> '0',
 //                'no_verif_uteis' 						=> '10',
 //                'tmp_verif_uteis' 						=> 30,
 //                'tmp_auto_remocao' 						=> '10',
 //                'sabado_inicio' 						=> '06:00',
 //                'duracao_sabado' 						=> 1020,
 //                'domingo_inicio' 						=> '06:00',
 //                'duracao_domingo' 						=> 1020,
 //                'uteis_inicio' 							=> '06:00',
 //                'duracao_uteis' 						=> 1020,
 //                'informar_placa' 						=> '1',
 //                'informar_vaga' 						=> '1',
 //                'informar_marca_modelo' 				=> '1',
 //                'informar_prisma' 						=> '1',
 //                'informar_pertences' 					=> '1',
 //                'informar_avarias' 						=> '1',
 //                'informar_convenio' 					=> '1',
 //                'informar_cor' 							=> '1',
 //                'trabalhar_rps' 						=> '1',
 //                'administrador_id' 						=> ADMIN_PARKING_ID,
 //                'tipo_estacionamento' 					=> $rotativo ? 'ROTATIVO' : 'PRIVADO',
 //                'bloquear_compra_apos_irregularidade' 	=> '1',
 //                'inicio_periodo' 						=> 'LANCAMENTO',
 //                'lista_fiscalizacao' 					=> 'VENCIDOS',
 //                'renovar_tolerancia_troca_vaga' 		=> '0',
 //                'tipo_periodo' 							=> 'VAGA',
 //                'inicio_irregularidade' 				=> 'LANCAMENTO',
 //                'bloquear_compra_fora_horario' 			=> '1',
 //                'informar_placa_numerica' 				=> '1',
 //                'informar_marca_modelo_irregularidade' 	=> '1',
 //                'informar_cor_irregularidade' 			=> '1',
 //                'notificacao_manter_debito' 			=> '1',
 //                'foto_irregularidade'                   => '0',
 //                'notificacao_dias_vencimento' 			=> '1',
 //                'renovar_limite_periodos_troca_vaga' 	=> '1',
 //                'informar_tipo_veiculo' 				=> '1',
 //                'lancar_veiculo_ocr'                    => '0',
 //                'debito_automatico_apos_tolerancia' 	=> '1',
 //                'bloquear_irregularidade_sem_vaga' 		=> '0',
 //                'no_periodos_sabado' 					=> '10',
 //                'no_periodos_domingo' 					=> '10',
 //                'no_periodos_uteis' 					=> '10',
 //                'no_irregularidades_sabado' 			=> '10',
 //                'no_irregularidades_domingo' 			=> '10',
 //                'no_irregularidades_uteis' 				=> '10',
 //                'devolucao_periodo' 					=> '1',
 //                'tempo_minimo_devolucao' 				=> '15',
 //                'emitir_irregularidade_auto' 			=> '1',
 //                'aceitar_tickets_terceiros' 			=> '1',
 //                'intervalo_inicio'                      => '00:00',
 //                'intervalo_fim'                         => '00:00',
 //            	'area_pontos' 							=> '',
 //                'consumir_eticket'                      => 0,
 //                'cobranca_id'                           => $this->cobrancaId,
 //                'interromper_notificacao_paga'          => 0
 //            )
 //        );
    	
 //    	if ($rotativo) {
	//     	$tamAreaPonto = rand ( 4, 8 );
	//     	$json = array ();
	    	 
	//     	for($i = 0; $i < $tamAreaPonto; $i ++) {
	//     		array_push ( $json, array (
	// 	    		'AreaPonto' => array (
	// 		    		'latitude' 		=> $this->randomLatitude (),
	// 		    		'longitude' 	=> $this->randomLongitude ()
	// 	    		)
	//     		) );
	//     	}
 //        	$data['Area']['area_pontos'] = json_encode($json);
 //        }
    	
 //        return $data;
 //    }

 //    public function getAplicativo() {
 //        return array(
 //            'Produto' => array(
 //                 'equipamento_id' => $this->equipamentoId,
 //                 'nsu' => 1,
 //                 'identificacao' => 'NENHUM',
 //                 'pagamento' => 'DINHEIRO',
 //                 'tipo' => 'CONSUMO',
 //                 'bolsa_id'=> BOLSA_CONTA_CORRENTE,
 //                 'valor_original' => 0,
 //                 'no_parcelas' => 0,
 //                 'limite_id'=> 0,
 //                 'dados' => '',
 //                 'token' => '',
 //                 'cartao_id'=> NULL,
 //                 'senha_informada' => 1,
 //                 'entidade_id'=> empty($this->clienteId) ? $this->entidadeId : $this->clienteId,
 //                 'compl' => '',
 //                 'posto_id'=> empty($this->postoId) ? $this->associadoId : $this->postoId,
 //                 'negocio_id'=> null,
 //                 'criado_em' => $this->getDatetime(),
 //                 'valor' => 0 ,
 //                 'situacao' =>  'APROVADO',
 //                 'tentativas' => 1,
 //                 'valor_parcelas' => 0,
 //                 'valor_parcela_1' => 0,
 //                 'processadora_id'=> 1,
 //                 'iin_baixa' => 0 ,
 //                 'tarifa_id'=> $this->tarifaId,
 //                 'convenio_id'=> NULL,
 //                 'desconto' => 0,
 //                 'operacao_id'=> NULL,
 //                 'produto_id'=> $this->produtoId,
 //                 'externo_id'=> 0,
 //                 'respondido_em' => $this->getDateTime(),
 //                 'confirmado_em' => '2000-01-01 00:00:00',
 //                 'equipamento_ok' => 1,
 //                 'cartao_ok' => 2,
 //                 'administrador_id'=> empty($this->associadoId) ? $this->postoId : $this->associadoId,
 //                 'limite_id_cliente' => 0,
 //                 'percentual_devolucao' => 1
 //            )
 //        );
 //    }

 //    public function getAutorizacao() {
 //        return array(
 //            'Autorizacao' => array(
 //                'descricao' 		=> 'PHPUNIT',
 //                'bolsa_id' 			=> BOLSA_CONTA_CORRENTE,
 //                'administrador_id' 	=> ADMIN_PARKING_ID,
 //                'venda_inicio' 		=> '2001-01-01',
 //                'venda_fim' 		=> '2030-01-01',
 //                'venda_em_dinheiro' => 1,
 //                'venda_em_pre' 		=> 1,
 //                'venda_em_pos' 		=> 1,
 //                'aplicativo_id' 	=> APLICATIVO_ID_PARKING,
 //                'criado_em'         => $this->getDateTime()
 //            )
 //        );
 //    }

 //    public function getPlaca() {
 //        // Caso do cliente tenha sido salvo pela entidade.
 //        $entidade_id = !empty($this->clienteId) ? $this->clienteId : $this->entidadeId;

 //        return array(
 //            'Placa' => array(
 //                'placa' => $this->randomPlaca(),
 //                'entidade_id' => $entidade_id,
 //                'tipo' => 'CARRO',
 //                'ativacoes' => 0,
 //                'inativo' => 0,
 //                'criado_em' => $this->getDateTime()
 //            )
 //        );
 //    }

 //    public function getPlugin($origem = 'AREA') {
 //        // Caso do cliente tenha sido salvo pela entidade.
 //        $entidade_id = empty($this->entidadeId) ? $this->clienteId : $this->entidadeId;
 //        return array(
 //            'Plugin' => array(
 //                'entidade_id' => $entidade_id,
 //                'origem'      => $origem,
 //                'dado'        => $this->areaId,
 //                'ordem'       => 0,
 //                'padrao'      => 0,
 //                'dado2'       => NULL,
 //                'dado3'       => NULL,
 //                'dado4'       => NULL
 //            )
 //        );
 //    }
    
 //    public function getTicket() {
 //        return array(
 //            'Ticket' => array(
 //                'placa' 						=> $this->randomPlaca(),
 //                'situacao' 						=> 'PAGO',
 //                'valor' 						=> 10.00,
 //                'criado_em' 					=> $this->getDateTime(),
 //                'pago_em' 						=> $this->getDateTime(),
 //                'tipo' 							=> rand(1, 5) % 5 == 1 ? 'IRREGULARIDADE' : 'UTILIZACAO',// 1/5 irregularidade 
 //                'equipamento_id_origem' 		=> $this->equipamentoId,
 //                'equipamento_id_pagamento' 		=> $this->equipamentoId,
 //                'nsu_origem' 					=> rand(300,9999),
 //                'data_inicio' 					=> $this->getDateTime(),
 //                'data_fim' 						=> $this->getDateTime(),
 //                'entidade_id_origem' 			=> $this->postoId,
 //                'entidade_id_pagamento' 		=> $this->postoId,
 //                'preco_id' 						=> $this->precoId,
 //                'preco_id_original' 			=> $this->precoId,
 //                'area_id' 						=> $this->areaId,
 //                'servico_id_origem'             => empty($this->servicoId) ? null : $this->servicoId,
 //                'servico_id_pagamento' 			=> empty($this->servicoId) ? null : $this->servicoId,
 //                'numero_autuacao' 				=> rand(0,9999),
 //                'periodos' 						=> 1,
 //                'motivo_irregularidade' 		=> 'NENHUM',
 //                'veiculo' 						=> 'CARRO',
 //                'vaga' 							=> 0,
 //                'notificacao_transmitida_em' 	=> $this->getDateTime('-' . rand(0,23) . 'Hours'),
 //                'pago_em_dinheiro' 				=> 1,
 //                'operador_id_origem' 			=> empty($this->operadorId) ? null : $this->operadorId,
 //                'operador_id_pagamento' 		=> empty($this->operadorId) ? null : $this->operadorId,
 //                'administrador_id' 				=> ADMIN_PARKING_ID,
 //                'cobranca_id' 					=> $this->cobrancaId,
 //                'cobranca_id_original' 			=> $this->cobrancaId,
 //                'forma_pagamento' 				=> 'DINHEIRO',
 //                'valor_original' 				=> 10.00
 //            )
 //        );
 //    }
	
	// /**
	//  * Gera dados aleatórios de uma tarifa
	//  */
	// public function getTarifa()	{
 //        $now = new DateTime();
 //        $now->modify('-1 year');
 //        return array(
	// 		'Tarifa' => array(
	// 			'bolsa_id' 					=> BOLSA_CONTA_CORRENTE,	//Conta corrente
	// 			'processadora_id' 			=> 1,	//LOCAL
	// 			'inicio' 					=> $now->format('Y-m-d'),
	// 			'fim' 						=> '2030-01-01', 
	// 			'multa_atraso' 				=> $this->randomFloat(0, 9),
	// 			'juros_rotativo' 			=> $this->randomFloat(0, 9),
	// 			'juros_mora' 				=> $this->randomFloat(0, 9),
	// 			'juros_parcelado' 			=> $this->randomFloat(0, 9),
	// 			'iof_dia' 					=> $this->randomFloat(0, 9,5),
	// 			'tarifa_recarga' 			=> $this->randomFloat(0, 9),
 //                'perc_tarifa_recarga'       => $this->randomFloat(0, 9),
	// 			'tarifa_transferencia' 		=> $this->randomFloat(0, 9),
	// 			'perc_taxa_adm_pre' 		=> $this->randomFloat(0, 9),
	// 			'valor_taxa_adm_pre' 		=> 0,
	// 			'perc_taxa_adm_pos' 		=> $this->randomFloat(0, 9),
	// 			'valor_taxa_adm_pos' 		=> 0,
	// 			'valor_recarga_minima' 		=> $this->randomFloat(1, 5),
	// 			'valor_recarga_maxima' 		=> $this->randomFloat(6, 9),
	// 			'saldo_maximo' 				=> 0,
	// 			'valor_primeira_compra' 	=> 0,
	// 			'tarifa_emissao_cartao' 	=> $this->randomFloat(0, 9),
 //                'posto_id'                  => null
	// 		)
	// 	);
	// }
	
 //    public function getEquipamento(){
 //        return array(
 //            'Equipamento' => array(
 //                'no_serie' 				=> 'PHP' . rand(0,99999999),
 //                'tipo' 					=> 'POS',
 //                'modelo' 				=> 'VX520',
 //                'nsu' 					=> 1,
 //                'no_serie_antena' 		=> 100,
 //                'versao_atual' 			=> 100,
 //                'versao_atualizar' 		=> 0,
 //                'atualizar' 			=> 0,
 //                'atualizar_recibos' 	=> 0,
 //                'atualizar_logotipos' 	=> 0,
 //                'situacao' 				=> 'ATIVO',
 //                'administrador_id' 		=> ADMIN_PARKING_ID,
 //                'posto_id' 				=> null,
 //                'conexao_id' 			=> CONEXAO_ID
 //            )
 //        );
 //    }

 //    public function getEquipamentoURA(){
 //        $equipamentoURA = $this->getEquipamento();
 //        $equipamentoURA['Equipamento']['tipo']     = 'URA';
 //        $equipamentoURA['Equipamento']['no_serie'] = 'URA';
 //        return $equipamentoURA;
 //    }

 //    public function getServico() {
 //        return array(
 //            'Servico' => array(
 //                'data_abertura' 	=> $this->getDateTime(),
 //                'data_fechamento' 	=> null,
 //                'sequencia' 		=> 1,
 //                'equipamento_id' 	=> $this->equipamentoId,
 //                'area_id' 			=> $this->areaId,
 //                'cobranca_id' 		=> $this->cobrancaId,
 //                'preco_id' 			=> $this->precoId,
 //                'operador_id' 		=> $this->operadorId,
 //                'administrador_id' 	=> ADMIN_PARKING_ID
 //            )
 //        );
 //    }

 //    public function getDireito() {
 //        return array(
 //            'Direito' => array(
 //                'aplicativo_id' => 2,
 //                'direito_1'     => 1,
 //                'direito_2'     => 1,
 //                'direito_3'     => 1,
 //                'direito_4'     => 1,
 //                'direito_5'     => 1,
 //                'direito_6'     => 1,
 //                'direito_7'     => 1,
 //                'direito_8'     => 1,
 //                'direito_9'     => 1,
 //                'direito_10'    => 0,
 //                'direito_11'    => 0,
 //                'direito_12'    => 0,
 //                'direito_13'    => 0,
 //                'direito_14'    => 0,
 //                'direito_15'    => 0
 //            )
 //        );
 //    }

 //    public function getOperador() {
 //        return array(
 //            'Operador' => array(
 //                'usuario' 			=> rand(1000,9999),
 //                'senha' 			=> '112299',
 //                'direito_1' 		=> '1',
 //                'direito_2' 		=> '1',
 //                'direito_3' 		=> '1',
 //                'direito_4' 		=> '1',
 //                'direito_5' 		=> '1',
 //                'direito_6' 		=> '1',
 //                'direito_7' 		=> '1',
 //                'direito_8' 		=> '1',
 //                'direito_9' 		=> '1',
 //                'direito_10' 		=> '1',
 //                'direito_11' 		=> '1',
 //                'direito_12' 		=> '1',
 //                'direito_13' 		=> '1',
 //                'direito_14' 		=> '1',
 //                'direito_15' 		=> '1',
 //                'nome' 				=> 'OpTeste ' . time(),
 //                'administrador_id' 	=> ADMIN_PARKING_ID
 //            )
 //        );
 //    }

 //    public function getAreaEquipamento(){
 //        return array(
 //            'AreaEquipamento' => array(
 //                'equipamento_id'            => $this->equipamentoId,
 //                'area_id'                   => $this->areaId,
 //                'atualizar_marcas_modelos'  => 0,
 //                'administrador_id'          => ADMIN_PARKING_ID
 //            )
 //        );
 //    }

 //    public function getContrato() {
 //        return array(
 //            'Contrato' => array(
 //                'data_inicio'       => '2000-01-01',
 //                'data_fim'          => '2050-01-01',
 //                'hora_entrada'      => '00:00:01',
 //                'hora_duracao'      => '23',
 //                'placa'             => '',
 //                'area_id'           => $this->areaId,
 //                'cobranca_id'       => NULL,
 //                'administrador_id'  => ADMIN_PARKING_ID,
 //                'inativo'           => 0,
 //                'descricao'         => 'Contrato Teste PHPUNIT'
 //            )
 //        );
 //    }

 //    public function getContratoPlaca($placa = NULL) {
 //        return array(
 //            'ContratoPlaca' => array(
 //                'contrato_id'   => $this->contratoId,
 //                'placa'         => $placa
 //            )
 //        );
 //    }

 //    public function getCobranca() {
    	
 //        return array(
 //            'Cobranca' => array(
 //                'nome'                                          => 'Teste PHP',
 //                'tipo'                                          => 'ROTATIVO',
 //                'produto_id'                                    => $this->produtoId,
 //                'preco_id_carro'                                => $this->precoId,
 //                'preco_id_moto'                                 => $this->precoId,
 //                'preco_id_vaga_farmacia'                        => $this->precoId,
 //                'preco_id_vaga_idoso'                           => $this->precoId,
 //                'preco_id_irregularidade_vencido'               => $this->precoId,
 //                'preco_id_irregularidade_sem_ticket'            => $this->precoId,
 //                'preco_id_irregularidade_fora_vaga'             => $this->precoId,
 //                'preco_id_irregularidade_ticket_incompativel'   => $this->precoId,
 //                'administrador_id'                              => ADMIN_PARKING_ID
 //            )
 //        );
 //    }

 //    public function getProduto() {
 //    	$dist = array(
 //    		'NACIONAL',
 //    		'ESTADUAL',
 //    		'MUNICIPAL',
 //    		'REGIONAL'
	// 	);
		
	// 	$restricao = array(
	// 		'NENHUMA',
	// 		'POSTO',
	// 		'NEGOCIO'
	// 	);
		
 //        return array(
 //            'Produto' => array(
 //                'descricao' 				=> 'Produto PHPUnit' . rand(0,10000),
 //                'venda_inicio' 				=> '2000-01-01 00:00:00',
 //                'venda_termino' 			=> '2020-01-01 00:00:00',
 //                'bolsa_id' 					=> '1',
 //                'venda_em_dinheiro' 		=> '1',
 //                'venda_em_pre' 				=> '1',
 //                'venda_em_pos' 				=> '1',
 //                'aplicativo_id' 			=> '2',
 //                'distribuicao' 				=> $dist[rand(0, count($dist)-1)], 
 //                'administrador_id' 			=> ADMIN_PARKING_ID,
 //                'regiao_id_distribuicao' 	=> null,
 //                'restricao' 				=> $restricao[rand(0, count($restricao)-1)],
 //                'distribuicao_cidade' 		=> 'Cidade',
 //                'distribuicao_uf' 			=> 'RS'
 //            )
 //        );
		
 //    }
	
	// public function getComissao() {
 //        $postoId = isset($this->postoId) ? $this->postoId : null;
	// 	return array(
	// 		'Comissao' => array (
	// 			'perc_comissao_posto' 	=> $this->randomFloat(0, 5),
	// 			'valor_comissao_posto' 	=> 0,
	// 			'perc_taxa_adm' 		=> $this->randomFloat(0, 5),
	// 			'valor_taxa_adm' 		=> 0,
 //                'produto_id'            => $this->produtoId,
 //                'posto_id'              => $postoId
	// 		)
 //        );
	// }
	
	// public function getComunicacao(){
	// 	return array(
	// 		'Comunicacao' => array(
	// 				'criado_em' => $this->getDateTime('-1 minute'),
	// 				'respondido_em' => $this->getDateTime('-1 minute'),
	// 				'requisicao' => 'ping',
	// 				'resposta' => '{"ping": "pong"}',
	// 				'mti' => 0,
	// 				'versao' => 0,
	// 				'nsu' => 1,
	// 				'equipamento_id' => $this->equipamentoId,
	// 				'no_serie' => '1234567890',
	// 				'comando' => 'HA',
	// 				'content_type' => 'text',
	// 				'controller' => 'contr',
	// 				'action' => 'act',
	// 				'status_code' => 200
	// 		)
	// 	);
	// }

 //    public function getPreco() {
 //        return array(
 //            'Preco' => array(
 //                'tipo' 							=> 'ROTATIVO',
 //                'nome' 							=> 'PHPUnit' . rand(0,9999),
 //                'cobranca_periodos' 			=> '0',
 //                'faixa_1_minutos' 				=> '60',
 //                'faixa_1_valor' 				=> '2.00',
 //                'faixa_1_tolerancia' 			=> '5',
 //                'faixa_2_minutos' 				=> '0',
 //                'faixa_2_valor' 				=> '0.00',
 //                'faixa_2_tolerancia' 			=> '0',
 //                'faixa_3_minutos' 				=> '0',
 //                'faixa_3_valor' 				=> '0.00',
 //                'faixa_3_tolerancia' 			=> '0',
 //                'faixa_4_minutos' 				=> '0',
 //                'faixa_4_valor' 				=> '0.00',
 //                'faixa_4_tolerancia' 			=> '0',
 //                'faixa_5_minutos' 				=> '0',
 //                'faixa_5_valor' 				=> '0.00',
 //                'faixa_5_tolerancia' 			=> '0',
 //                'excedente_periodo_minutos' 	=> '10',
 //                'excedente_periodo_valor' 		=> '2.00',
 //                'cobranca_turnos' 				=> '1',
 //                'turno_valor' 					=> '1.00',
 //                'turno_minutos' 				=> '1',
 //                'tolerancia_1_turno' 			=> '0',
 //                'tolerancia_proximos_turnos' 	=> '0',
 //                'cobrar_turnos_apenas' 			=> '0',
 //                'tempo_livre' 					=> 5,
 //                'tempo_max_periodos' 			=> '240',
 //                'tolerancia_cancelamento' 		=> '5',
 //                'irregularidade' 				=> 'NOTIFICACAO',
 //                'tempo_irregularidade' 			=> '20',
 //                'valor_irregularidade' 			=> '5.00',
 //                'administrador_id' 				=> ADMIN_PARKING_ID,
 //                'cobrar_antecipado' 			=> '0',
 //                'cobranca_antecipada_valor' 	=> '0',
 //                'cobranca_antecipada_pago_ate' 	=> '00:00:00',
 //                'cobranca_antecipada_tempo' 	=> '0',
 //                'tarifa_codigo_debito_automatico' => 1
 //            )
 //        );
 //    }

 //    public function getConfiguracao() {
 //        return array(
 //            'Configuracao' => array(
 //                'id'        =>  '12',
 //                'chave'     =>  'UTILIZAR_ENVIO_SMS',
 //                'valor'     =>  '1'
 //                )
 //            );
 //    }

 //    public function getAssociado() {
 //        $data['Associado']['cpf_cnpj']              = $this->randomCnpj();
 //        $data['Associado']['pessoa']                = 'JURIDICA';
 //        $data['Associado']['nome']                  = 'Associado PHP';
 //        $data['Associado']['fantasia']              = 'PHP';
 //        $data['Associado']['data_nascimento']       = '2000-01-01';
 //        $data['Associado']['email']                 = rand(100, 99999) . '@versul.com.br';
 //        $data['Associado']['telefone']              = $this->randomPhone();
 //        $data['Associado']['receber_email']         = '1';
 //        $data['Associado']['receber_sms']           = '0';
 //        $data['Associado']['autorizar_debito']      = '0';
 //        $data['Associado']['versao_contrato']       = 1;
 //        $data['Associado']['cep']                   = '93310-110';
 //        $data['Associado']['logradouro']            = 'Marcilio Dias';
 //        $data['Associado']['numero']                = '000';
 //        $data['Associado']['compl']                 = 'Emp';
 //        $data['Associado']['cidade']                = 'Novo Hamburgo';
 //        $data['Associado']['bairro']                = 'Centro';
 //        $data['Associado']['uf']                    = 'RS';
 //        $data['Associado']['desativado']            = '0';
 //        $data['Associado']['tipo']                  = 'PARKING';
 //        $data['Associado']['negocio_id']            = 70;
 //        $data['Associado']['criado_em']             = '2000-01-01 02:00:01';
 //        $data['Associado']['senha_site']            = md5('121212');
 //        $data['Associado']['aplicativo_id']         = 2;
 //        $data['Associado']['erro_senha_site']       = 0;
 //        $data['Associado']['expira_em']             = '2013-10-10 14:44:42';
 //        $data['Associado']['vende_em_dinheiro']     = 1;
 //        $data['Associado']['aceita_pre']            = 1;
 //        $data['Associado']['aceita_pos']            = 1;
 //        $data['Associado']['aceita_parcelado']      = 1;
 //        $data['Associado']['aceita_cartao_debito']  = 1;
 //        $data['Associado']['aceita_cartao_credito'] = 1;
 //        return $data;
 //    }

 //    public function getTransparencia() {
 //        $data['Transparencia']['cpf_cnpj']              = $this->randomCpf(true);
 //        $data['Transparencia']['pessoa']                = 'FISICA';
 //        $data['Transparencia']['nome']                  = 'Transparência PHP';
 //        $data['Transparencia']['fantasia']              = 'PHP';
 //        $data['Transparencia']['data_nascimento']       = '2000-01-01';
 //        $data['Transparencia']['email']                 = rand(100, 99999) . '@versul.com.br';
 //        $data['Transparencia']['telefone']              = $this->randomPhone();
 //        $data['Transparencia']['receber_email']         = 0;
 //        $data['Transparencia']['receber_sms']           = 0;
 //        $data['Transparencia']['autorizar_debito']      = 0;
 //        $data['Transparencia']['versao_contrato']       = 1;
 //        $data['Transparencia']['cep']                   = '93310-110';
 //        $data['Transparencia']['logradouro']            = 'Marcilio Dias';
 //        $data['Transparencia']['numero']                = '000';
 //        $data['Transparencia']['compl']                 = 'Emp';
 //        $data['Transparencia']['cidade']                = 'Novo Hamburgo';
 //        $data['Transparencia']['bairro']                = 'Centro';
 //        $data['Transparencia']['uf']                    = 'RS';
 //        $data['Transparencia']['desativado']            = '0';
 //        $data['Transparencia']['tipo']                  = 'PARKING_FISCALIZACAO';
 //        $data['Transparencia']['negocio_id']            = 70;
 //        $data['Transparencia']['criado_em']             = $this->getDatetime();
 //        $data['Transparencia']['senha_site']            = md5('121212');
 //        $data['Transparencia']['aplicativo_id']         = 2;
 //        $data['Transparencia']['erro_senha_site']       = 0;
 //        $data['Transparencia']['expira_em']             = NULL;
 //        $data['Transparencia']['vende_em_dinheiro']     = 0;
 //        $data['Transparencia']['aceita_pre']            = 0;
 //        $data['Transparencia']['aceita_pos']            = 0;
 //        $data['Transparencia']['aceita_parcelado']      = 0;
 //        $data['Transparencia']['aceita_cartao_debito']  = 0;
 //        $data['Transparencia']['aceita_cartao_credito'] = 0;
 //        return $data;
 //    }

 //    public function getEntidadeDireito() {
 //        return array(
 //            'EntidadeDireito' => array(
 //                'entidade_id'             => $this->transparenciaId,
 //                'direito_fiscalizacao_id' => rand(1,5)
 //            )
 //        );
 //    }


    
 //    public function getPosto($comPontos = false)
 //    {
 //        $tipo_pessoa = rand(0, 1);
        
 //        $data['Posto']['id']                        = NULL;
 //        $data['Posto']['cpf_cnpj']                  = $tipo_pessoa ? $this->randomCpf($comPontos) : $this->randomCnpj($comPontos);
 //        $data['Posto']['pessoa']                    = $tipo_pessoa ? 'FISICA' : 'JURIDICA';
 //        $data['Posto']['nome']                      = 'Posto' . rand(1, 10000);
 //        $data['Posto']['data_nascimento']           = $this->randomDate();
 //        $data['Posto']['email']                     = 'teste' . rand(0,10000) . '@versul.com.br';
 //        $data['Posto']['telefone']                  = $this->randomPhone(); 
 //        $data['Posto']['receber_email']             = rand(0, 1);
 //        $data['Posto']['receber_sms']               = rand(0, 1);
 //        $data['Posto']['autorizar_debito']          = rand(0, 1);
 //        $data['Posto']['versao_contrato']           = rand(0, 1);
 //        $data['Posto']['cep']                       = '93123-456'; //TODO random
 //        $data['Posto']['logradouro']                = 'Rua Logradouro';
 //        $data['Posto']['bairro']                    = 'Bairro Vila'; 
 //        $data['Posto']['numero']                    = rand(1, 1000);
 //        $data['Posto']['compl']                     = '';
 //        $data['Posto']['cidade']                    = 'NOVO HAMBURGO';
 //        $data['Posto']['uf']                        = 'RS';
 //        $data['Posto']['desativado']                = 0;
 //        $data['Posto']['tipo']                      = 'POSTO';
 //        $data['Posto']['negocio_id']                = NEGOCIO_ID_POSTO;
 //        $data['Posto']['criado_em']                 = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //        $data['Posto']['senha_site']                = '123123';
 //        $data['Posto']['erros_senha_site']          = 0; //TODO
 //        $data['Posto']['data_senha_site']           = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //        $data['Posto']['expira_em']                 = '2030-01-01';
 //        $data['Posto']['troca_senha_ate']           = '';
 //        $data['Posto']['latitude']                  = -29.68 . rand(0, 999);
 //        $data['Posto']['longitude']                 = -51.12 . rand(0, 999);
 //        $data['Posto']['vende_em_dinheiro']         = rand(0, 1);
 //        $data['Posto']['aceita_pre']                = rand(0, 1);
 //        $data['Posto']['aceita_pos']                = rand(0, 1);
 //        $data['Posto']['aceita_pre_parcelado']      = rand(0, 1);
 //        $data['Posto']['aceita_pos_parcelado']      = rand(0, 1);
 //        $data['Posto']['aceita_cartao_debito']      = rand(0, 1);
 //        $data['Posto']['aceita_cartao_credito']     = rand(0, 1);
 //        $data['Posto']['aplicativo_id']             = APLICATIVO_ID_EC;
 //        $data['Posto']['regiao_id']                 = NULL;
 //        $data['Posto']['fantasia']                  = 'Fantasia Posto Teste' . rand(1,9999);
		
 //        return $data;
 //    }


 //    public function getParquimetro(){
 //        $data['Parquimetro']['cpf_cnpj']                  = rand(10000, 99999);
 //        $data['Parquimetro']['pessoa']                    = 'FISICA';
 //        $data['Parquimetro']['nome']                      = 'Parquimetro ' . $data['Parquimetro']['cpf_cnpj'];
 //        $data['Parquimetro']['data_nascimento']           = $this->randomDate();
 //        $data['Parquimetro']['email']                     = null;
 //        $data['Parquimetro']['telefone']                  = null; 
 //        $data['Parquimetro']['receber_email']             = 0;
 //        $data['Parquimetro']['receber_sms']               = 0;
 //        $data['Parquimetro']['autorizar_debito']          = 0;
 //        $data['Parquimetro']['versao_contrato']           = 0;
 //        $data['Parquimetro']['cep']                       = '93123-456'; //TODO random
 //        $data['Parquimetro']['logradouro']                = 'Rua Logradouro';
 //        $data['Parquimetro']['bairro']                    = 'Bairro Vila'; 
 //        $data['Parquimetro']['numero']                    = rand(1, 1000);
 //        $data['Parquimetro']['compl']                     = '';
 //        $data['Parquimetro']['cidade']                    = 'NOVO HAMBURGO';
 //        $data['Parquimetro']['uf']                        = 'RS';
 //        $data['Parquimetro']['desativado']                = 0;
 //        $data['Parquimetro']['tipo']                      = 'PARQUIMETRO';
 //        $data['Parquimetro']['negocio_id']                = NULL;
 //        $data['Parquimetro']['criado_em']                 = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //        $data['Parquimetro']['senha_site']                = null;
 //        $data['Parquimetro']['erros_senha_site']          = 0; //TODO
 //        $data['Parquimetro']['data_senha_site']           = null;
 //        $data['Parquimetro']['expira_em']                 = null;
 //        $data['Parquimetro']['troca_senha_ate']           = '';
 //        $data['Parquimetro']['latitude']                  = -29.68 . rand(0, 999);
 //        $data['Parquimetro']['longitude']                 = -51.12 . rand(0, 999);
 //        $data['Parquimetro']['vende_em_dinheiro']         = 0;
 //        $data['Parquimetro']['aceita_pre']                = 0;
 //        $data['Parquimetro']['aceita_pos']                = 0;
 //        $data['Parquimetro']['aceita_pre_parcelado']      = 0;
 //        $data['Parquimetro']['aceita_pos_parcelado']      = 0;
 //        $data['Parquimetro']['aceita_cartao_debito']      = 0;
 //        $data['Parquimetro']['aceita_cartao_credito']     = 0;
 //        $data['Parquimetro']['aplicativo_id']             = APLICATIVO_ID_EC;
 //        $data['Parquimetro']['regiao_id']                 = NULL;
 //        $data['Parquimetro']['fantasia']                  = $data['Parquimetro']['nome'];
 //        return $data;
 //    }

 
    
 //    public function getSac() {
 //    	$tipo_pessoa = true; //física
    
 //    	$data['Sac']['id']                    = NULL;
 //    	$data['Sac']['cpf_cnpj']              = $tipo_pessoa ? $this->randomCpf() : $this->randomCnpj();
 //    	$data['Sac']['pessoa']                = $tipo_pessoa ? 'FISICA' : 'JURIDICA';
 //    	$data['Sac']['nome']                  = 'Sac' . rand(1, 10000);
 //    	$data['Sac']['data_nascimento']       = $this->randomDate();
 //    	$data['Sac']['email']                 = 'teste' . rand(0,10000) . '@versul.com.br';
 //    	$data['Sac']['telefone']              = $this->randomPhone();
 //    	$data['Sac']['receber_email']         = rand(0, 1);
 //    	$data['Sac']['receber_sms']           = rand(0, 1);
 //    	$data['Sac']['autorizar_debito']      = rand(0, 1);
 //    	$data['Sac']['versao_contrato']       = rand(0, 1);
 //    	$data['Sac']['cep']                   = '93123-456'; //TODO random
 //    	$data['Sac']['logradouro']            = 'Rua Logradouro';
 //    	$data['Sac']['bairro']                = 'Bairro Vila';
 //    	$data['Sac']['numero']                = rand(1, 1000);
 //    	$data['Sac']['compl']                 = '';
 //    	$data['Sac']['cidade']                = 'NOVO HAMBURGO';
 //    	$data['Sac']['uf']                    = 'RS';
 //    	$data['Sac']['desativado']            = 0;
 //    	$data['Sac']['tipo']                  = 'SAC';
 //    	$data['Sac']['negocio_id']            = NEGOCIO_ID_SAC;
 //    	$data['Sac']['criado_em']             = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //    	$data['Sac']['senha_site']            = '123123';
 //    	$data['Sac']['erros_senha_site']      = 0; //TODO
 //    	$data['Sac']['data_senha_site']       = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //    	$data['Sac']['expira_em']             = '2030-01-01';
 //    	$data['Sac']['troca_senha_ate']       = '';
 //    	$data['Sac']['latitude']              = -29.68 . rand(0, 999);
 //    	$data['Sac']['longitude']             = -51.12 . rand(0, 999);
 //    	$data['Sac']['aplicativo_id']         = NULL;
 //    	$data['Sac']['regiao_id']             = NULL;
 //    	$data['Sac']['fantasia']              = '';
 //    	return $data;
 //    }

 //    public function getCliente($isFormatted = true, $isPessoaFisica = true) {
 //        $email = 'cliente' . rand(0, 10000) . '@versul.com.br';
 //        $tipo_pessoa = $isPessoaFisica; //física
 //        $data['Cliente']['id']                      = NULL;
 //        $data['Cliente']['cpf_cnpj']                = $tipo_pessoa ? $this->randomCpf($isFormatted) : $this->randomCnpj($isFormatted);
 //        $data['Cliente']['pessoa']                  = $tipo_pessoa ? 'FISICA' : 'JURIDICA';
 //        $data['Cliente']['nome']                    = 'Cliente ' . rand(0, 10000);
 //        $data['Cliente']['data_nascimento']         = $this->randomDate('Y-m-d', 19, 50);
 //        $data['Cliente']['email']                   = $email;
 //        $data['Cliente']['confirmacao_email']       = $email;
 //        $data['Cliente']['check_email']             = 0;
 //        $data['Cliente']['telefone']                = $this->randomPhone();
 //        $data['Cliente']['receber_email']           = rand(0, 1);
 //        $data['Cliente']['receber_sms']             = rand(0, 1);
 //        $data['Cliente']['autorizar_debito']        = 1;
 //        $data['Cliente']['versao_contrato']         = 1;
 //        $data['Cliente']['cep']                     = '93123-456'; //TODO random
 //        $data['Cliente']['logradouro']              = 'Rua Logradouro';
 //        $data['Cliente']['bairro']                  = 'Bairro Vila';
 //        $data['Cliente']['numero']                  = rand(1, 1000);
 //        $data['Cliente']['compl']                   = '';
 //        $data['Cliente']['cidade']                  = 'NOVO HAMBURGO';
 //        $data['Cliente']['uf']                      = 'RS';
 //        $data['Cliente']['desativado']              = 0;
 //        $data['Cliente']['tipo']                    = 'CLIENTE';
 //        $data['Cliente']['negocio_id']              = NEGOCIO_ID_CLIENTE;
 //        $data['Cliente']['criado_em']               = $this->getDateTime();
 //        $data['Cliente']['raw_password']            = 123456;
 //        $data['Cliente']['senha_site']              = $this->generatePassword($data['Cliente']['raw_password'], $data['Cliente']['cpf_cnpj']);
 //        $data['Cliente']['erros_senha_site']        = 0; //TODO
 //        $data['Cliente']['data_senha_site']         = $this->randomDate('Y-m-d H:i:s', 0, 1);
 //        $data['Cliente']['expira_em']               = '2030-01-01 00:00:00';
 //        $data['Cliente']['troca_senha_ate']         = NULL;
 //        $data['Cliente']['latitude']                = -29.68 . rand(0, 999);
 //        $data['Cliente']['longitude']               = -51.12 . rand(0, 999);
 //        $data['Cliente']['aplicativo_id']           = NULL;
 //        $data['Cliente']['regiao_id']               = NULL;
 //        $data['Cliente']['fantasia']                = '';        
 //        $data['Cliente']['estrangeiro']             = 0;

 //        return $data;
 //    }

 //    public function getClienteEstrangeiro() {        
 //        $data['ClienteEstrangeiro']['id']                      = NULL;
 //        $data['ClienteEstrangeiro']['cpf_cnpj']                = rand(1000000000, 99999999999);;
 //        $data['ClienteEstrangeiro']['pessoa']                  = 'FISICA';
 //        $data['ClienteEstrangeiro']['nome']                    = '';
 //        $data['ClienteEstrangeiro']['data_nascimento']         = '1990-01-01';
 //        $data['ClienteEstrangeiro']['email']                   = NULL;
 //        $data['ClienteEstrangeiro']['confirmacao_email']       = '';
 //        $data['ClienteEstrangeiro']['check_email']             = 0;
 //        $data['ClienteEstrangeiro']['telefone']                = $data['ClienteEstrangeiro']['cpf_cnpj'];
 //        $data['ClienteEstrangeiro']['receber_email']           = 0;
 //        $data['ClienteEstrangeiro']['receber_sms']             = 1;
 //        $data['ClienteEstrangeiro']['autorizar_debito']        = 1;
 //        $data['ClienteEstrangeiro']['versao_contrato']         = 1;
 //        $data['ClienteEstrangeiro']['cep']                     = '';
 //        $data['ClienteEstrangeiro']['logradouro']              = '';
 //        $data['ClienteEstrangeiro']['bairro']                  = '';
 //        $data['ClienteEstrangeiro']['numero']                  = NULL;
 //        $data['ClienteEstrangeiro']['compl']                   = '';
 //        $data['ClienteEstrangeiro']['cidade']                  = '';
 //        $data['ClienteEstrangeiro']['uf']                      = '';
 //        $data['ClienteEstrangeiro']['desativado']              = 0;
 //        $data['ClienteEstrangeiro']['tipo']                    = 'CLIENTE';
 //        $data['ClienteEstrangeiro']['negocio_id']              = 1;
 //        $data['ClienteEstrangeiro']['criado_em']               = '2014-11-21 13:50:52';
 //        $data['ClienteEstrangeiro']['raw_password']            = 123456;
 //        $data['ClienteEstrangeiro']['senha_site']              = $this->generatePassword($data['ClienteEstrangeiro']['raw_password'], $data['ClienteEstrangeiro']['cpf_cnpj']);
 //        $data['ClienteEstrangeiro']['erros_senha_site']        = 0;
 //        $data['ClienteEstrangeiro']['data_senha_site']         = '2000-01-01 02:00:00';
 //        $data['ClienteEstrangeiro']['expira_em']               = '2014-11-23 13:00:52';
 //        $data['ClienteEstrangeiro']['troca_senha_ate']         = NULL;
 //        $data['ClienteEstrangeiro']['latitude']                = NULL;
 //        $data['ClienteEstrangeiro']['longitude']               = NULL;
 //        $data['ClienteEstrangeiro']['aplicativo_id']           = NULL;
 //        $data['ClienteEstrangeiro']['regiao_id']               = NULL;
 //        $data['ClienteEstrangeiro']['fantasia']                = '';
 //        $data['ClienteEstrangeiro']['estrangeiro']             = 1;

 //        return $data;
 //    }

 //    public function getLote() {
 //        return array(
 //            'Lote' => array(
 //                'descricao' => 'Lote PHP' . rand(1,9999),
 //                'numeracao_inicial' => 1,
 //                'numeracao_final' => 100,
 //                'area_id' => $this->areaId,
 //                'concedente_id' => CONCEDENTE_ID,
 //                'usar_numeracao' => 0,
 //                'criado_em' => $this->getDateTime()
 //            )
 //        );
 //    }

 //    public function geraEtickets($numEtickets = 20, $skipArea = null) {
 //        if (!$skipArea) {
 //            $this->saveArea(array('Area' => array('consumir_eticket' => 1)));
 //        }
 //        $this->saveLote(array('Lote' => array('numeracao_final' => $numEtickets)));
 //        $this->callProcedure(PROCEDURE_PARK_GERA_ETICKETS);
 //    }

 //    public function saveEquipamentoPosto() {
 //        $this->saveEquipamento(
 //            array(
 //                'Equipamento' => array(
 //                    'posto_id' => $this->postoId,
 //                    'administrador_id' => null,
 //                )
 //            )
 //        );
 //    }

 //    public function saveEquipamentoAssociado() {
 //        $this->saveEquipamento(
 //            array(
 //                'Equipamento' => array(
 //                    'posto_id' => null,
 //                    'administrador_id' => ADMIN_PARKING_ID,
 //                )
 //            )
 //        );
 //    }
    
 //    /**
 //     * Função módulo para geração de cpf ou cnpj
 //     * créditos: damico@dcon.com.br
 //     * @tutorial http://rotinadigital.net/gerador-de-cpf-e-cnpj-codigos-fonte-em-c-php-e-javascript/
 //     * 
 //     * @param number $dividendo
 //     * @param number $divisor
 //     * @return number
 //     */
    
    
 //    /**
 //     * Gera um cpf válido de forma aleatória
 //     * créditos: damico@dcon.com.br
 //     * @tutorial http://rotinadigital.net/gerador-de-cpf-e-cnpj-codigos-fonte-em-c-php-e-javascript/
 //     * 
 //     * @param boolean $compontos
 //     * @return string
 //     */
 
    
 //    /**
 //     * Gera um cnpj válido de forma aleatória
 //     * créditos: damico@dcon.com.br
 //     * @tutorial http://rotinadigital.net/gerador-de-cpf-e-cnpj-codigos-fonte-em-c-php-e-javascript/
 //     * 
 //     * @param boolean $compontos
 //     * @return string
 //     */
 //   

 //    public function generatePassword($senha, $cpfCnpj) {
 //        $s = "{$senha}{$cpfCnpj}";
 //        for ($i = 0; $i < 100; $i++) {
 //            $s = md5($s);
 //        }
 //        return $s;
 //    }
    


 
    
 //    public function randomTipoEntidade()
 //    {
 //    	$tipoPosto = array(
 //    		'POSTO',
 //    		'CLIENTE',
 //    		'ADQUIRENTE',
 //    		'MANUTENCAO',
 //    		'ANALISTA',
 //    		'SAC',
 //    		'ADMINISTRADOR',
 //    		'TALAO',
 //    		'DEPENDENTE',
 //    		'PARKING',
 //    		'TICKET',
 //    		'LOCAL'
 //    	);
    	
 //    	return $tipoPosto[rand(0, (sizeof($tipoPosto) - 1))];
 //    }
    
    
 //    public function randomColor()
 //    {
 //    	$hexadecimal = '0123456789abcdef';
    	
 //    	$color = '#';
    	
 //    	for ($i = 0; $i < 6; $i++) {
 //    		$color .= substr($hexadecimal, rand(0, strlen($hexadecimal) -1), 1);
 //    	}
    	
 //    	return $color;
 //    }
    
 //    /**
 //     * Gera uma latitude aleatória, os valores padrão se referem a Novo Hamburgo, Brasil
 //     * 
 //     * @param number $min
 //     * @param number $max
 //     * @return number
 //     */
 //    public function randomLatitude($min = -29718619, $max = -29651062)
 //    {
 //    	$i = rand($min, $max);
    	
 //    	$i = $i / 1000000;
    	
 //    	return $i;
 //    }

 //    /**
 //     * Gera códigos de identificação do sensor.
 //     */
 //    public function randomUIN() {
 //        $hexadecimal = '0123456789abcdef';
        
 //        $uin = '#';
        
 //        for ($i = 0; $i < 6; $i++) {
 //            $uin .= substr($hexadecimal, rand(0, strlen($hexadecimal) -1), 1);
 //        }
        
 //        return $uin;
 //    }

 //    /**
 //     * Gera códigos de identificação da vaga.
 //     * Por exemplo: VAGA LIVRE, JAMMING e etc..
 //     */
 //    public function randomEPC() {
 //        $hexadecimal = '0123456789abcdef';
        
 //        $epc = '#';
        
 //        for ($i = 0; $i < 6; $i++) {
 //            $epc .= substr($hexadecimal, rand(0, strlen($hexadecimal) -1), 1);
 //        }
        
 //        return $epc;
 //    }
    
 //    /**
 //     * Gera uma latitude aleatória, os valores padrão se referem a Novo Hamburgo, Brasil
 //     * 
 //     * @param number $min
 //     * @param number $max
 //     * @return number
 //     */
 //    public function randomLongitude($min = -51166935, $max = -51089044)
 //    {
 //    	$i = rand($min, $max);
 //    	$i = $i / 1000000;
 //    	return $i;
 //    }
    
 //    /**
 //     * Gera uma coordenada geográfica aleatória
 //     * 
 //     * @todo Por enquanto gera uma coordenada em NH apenas
 //     * @return array
 //     */
 //    function randomCoordenada()
 //    {
 //    	return array(
 //    		'lat' => $this->randomLatitude(),
 //    		'lon' => $this->randomLongitude()
 //    	);
 //    }

 //    public function randomPlaca() {
 //        $placa = '';
 //        $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
 //        for($i = 0; $i < 3; $i++) {
 //            $placa .= $seed[rand(0,25)];
 //        }
 //        return "$placa-" . rand(1000,9999);
 //    }

 //    /**
 //    * Gera valor randômico de preço. 
 //    * @param faixa mínima de valor
 //    * @oaram faixa máxima de valor
 //    * @return valor final
 //    */
 //    public function randomValorPreco($valueMin = 0, $valueMax = 999){
 //        $value = rand(0, 999);
 //        $cents = rand(0, 99);

 //        if(strlen($cents) == 1 ){
 //            $cents = '0' . $cents;
 //        }

 //        return $value . '.' . $cents;
 //    }



 //    /**
 //     * Cria dados para o mapa de calor de vagas. Cria uma área, várias vagas e insere ocupações, ocupações pagas e irregularidades
 //     *  
 //     */
 //    function geraCalorVagas($maxVagas = 20, $maxVerificacoes = 100)
 //    {
 //    	$this->saveArea();
 //    	$this->saveSetor();
 //    	$this->saveOperador();
 //    	$this->saveEquipamento();
 //    	$this->savePreco();
 //    	$this->saveServico();
    	
 //    	//area
 //    	$totalArestas = rand(3, 10);
 //    	for ($i = 0; $i < $totalArestas; $i++) {
 //    		$this->saveAreaPonto(array('AreaPonto' => array('codigo' => 0, 'tipo' => 'AREA')));
 //    	}
    
 //    	//vagas
 //    	$totalVagas = rand(10, $maxVagas);
 //        $longitude = -51.12730;
 //        $latitude = -29.7;
 //        $step = (29.7 - 29.70333) / $maxVagas;
 //        for ($i = 0; $i < $totalVagas; $i++) {
 //            $this->saveAreaPonto(array('AreaPonto' => array('codigo' => $i, 'tipo' => 'VAGA', 'latitude' => $latitude, 'longitude' => $longitude)));
 //            $latitude += $step;
    		
	//     	//movimentações
	//     	$totalVerificacoes = rand(0, $maxVerificacoes);
	//     	for ($j = 0; $j < $totalVerificacoes; $j++) {
 //    			$this->saveVerificacao(array('Verificacao' => array(
 //    				'verificado_em' => $this->getDateTime('-' . rand(1,30) . ' Days'),
 //    				'vaga' => $i,
 //    				'periodos' => 1
 //    			)));
	//     	}
 //    	}
    
 //    }
	
 //    /**
 //     * Gera um ponto flutuante aleatório
 //     * 
 //     * @param unknown $min
 //     * @param unknown $max
 //     * @param number $precisao
 //     * @return number
 //     */
	// public function randomFloat($min, $max, $precisao = 2)
	// {
	// 	$denominador = 1;
	// 	for ($i = 0; $i < $precisao; $i++) {
	// 		$denominador = $denominador * 10;
	// 		$min = $min * 10;
	// 		$max = $max * 10;
	// 	}
		
	// 	$f = rand($min, $max) / $denominador;
		
	// 	return $f;
	// }

 //    /**
 //     * Retorna um nsu randômico
 //     */
 //    public function nsu() {
 //        return rand(1, 999999999);
 //    }

 //    /**
 //     * Executa um commit
 //     */
 //    public function commit() {
 //        $this->query('COMMIT');
 //    }

 //    /**
 //     * Método para criar um registro na park_historico simulando uma adição de veículo no sistema
 //     */
 //    public function lancaVeiculo($placa, $vaga, $isVencido = false){

 //        $dateTime = !$isVencido ? $this->getDateTime() : $this->getDateTime('-1 hour');

 //        // Cria array com os dados necessários para lançar o veículo
 //        $parkHistorico = array('Historico' => array(
 //            'administrador_id'     => ADMIN_PARKING_ID,
 //            'area_id'              => $this->areaId,
 //            'cobranca_id_original' => $this->cobrancaId,
 //            'preco_id_original'    => $this->precoId,
 //            'equipamento_id'       => $this->equipamentoId,
 //            'situacao'             => 'LANCADO',
 //            'placa'                => $placa,
 //            'vaga'                 => $vaga,
 //            'removido_em'          => NULL,
 //            'pago_ate'             => NULL,
 //            'inserido_em'          => $dateTime,
 //            'tolerancia_ate'       => $dateTime,
 //            'periodos'             => 0,
 //        ));

 //        // Salva veículo na base
 //        $this->saveHistorico($parkHistorico);
 //    }// End Method 'estacionaCarro'


 //    /**
 //     * Método que cria uma vaga de deficiente.
 //     * @return Objeto AreaPonto com dados da vaga de deficiente
 //     */
 //    public function createDeficientSpot(){
 //        return $this->createSpots('DEFICIENTE');
 //    }// End Method 'createDeficientSpot'

 //    /**
 //     * Método que cria uma vaga de carro
 //     * @return Objeto AreaPonto com dados da vaga de carro
 //     */
 //    public function createNormalSpot(){
 //        return $this->createSpots('NORMAL');
 //    }// End Method 'createNormalSpot'

 //    /**
 //     * Método que cria uma vaga de farmácia
 //     * @return Objeto AreaPonto com dados da vaga de farmácia
 //     */
 //    public function createPhamarcySpot(){
 //        return $this->createSpots('FARMACIA');
 //    }// End Method 'createNormalSpot'

 //    /**
 //     * Método que cria uma vaga de idoso
 //     * @return Objeto AreaPonto com dados da vaga de idoso
 //     */
 //    public function createElderlySpot(){
 //        return $this->createSpots('IDOSO');
 //    }// End Method 'createNormalSpot'

 //    /**
 //     * Método que cria uma vaga isenta
 //     * @return Objeto AreaPonto com dados da vaga isenta
 //     */
 //    public function createFreeSpot(){
 //        return $this->createSpots('ISENTO');
 //    }// End Method 'createNormalSpot'

 //    /**
 //     * Método para criar registro na tabela 'park_area_ponto'
 //     */
 //    private function createSpots($type = 'NORMAL'){
 //        $parkAreaPonto = $this->getAreaPonto();
 //        $parkAreaPonto['AreaPonto']['tipo_vaga'] = $type;
 //        $this->saveAreaPonto($parkAreaPonto);
 //        return $parkAreaPonto;
 //    }// End Method 'createSpots'

 //    /**
 //     * Método para buscar o saldo pre do cliente
 //     */
 //    public function getSaldoPreUsuario(){
 //        // Busca saldo
 //        $saldo = $this->Limite->find('first', array(
 //            'fields' => array('(Limite.pre_creditado + Limite.pre_utilizado + Limite.pre_creditado_estorno + Limite.pre_utilizado_estorno) as saldo_pre'),
 //            'conditions' => array('Limite.entidade_id' => $this->clienteId)
 //        ));
 //        // Retorna saldo
 //        return $saldo['0']['saldo_pre'];
 //    }// End Method 'getSaldoPreUsuario'


 //    public function getOperadorCliente($valorRecarga = 0){

 //        return array(
 //            'OperadorCliente' => array(
 //                'criado_em' => $this->getDateTime(),
 //                'cliente_id' => $this->clienteId,
 //                'operador_id' => $this->operadorId,
 //                'recarga_inicial' => $valorRecarga
 //            )
 //        );

 //    }// End Method 'getOperadorCliente'

 //    /**
 //     * Método para encerrar o caixa de um equipamento
 //     */
 //    public function callParkCashClosing(){
 //        $this->callProcedure('park_cash_closing', array($this->equipamentoId, 1));
 //    }// End Method 'callParkCashClosing'


 //    public function criaCadastrosClienteOperador($valorRecarga = 0) {
 //        $this->saveCliente();
 //        $OperadorCliente = $this->getOperadorCliente($valorRecarga);
 //        $this->saveOperadorCliente($OperadorCliente);
 //    }// End Method 'criaCadastrosOperador'

}// End Class 'DataGenerator.php'