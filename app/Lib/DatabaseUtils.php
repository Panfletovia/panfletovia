<?php

class DatabaseUtils {


    

    /**
     * Limpa o banco de dados excluindo os models de todos os Plugins
     */
    public function clearDatabase() {
        $plugins = array('App', 'Api');
        $this->query('SET foreign_key_checks = 0');
        $this->deleteTablesByDataBase();

        foreach ($plugins as $plugin) {
            $models = $plugin === 'App' ? App::objects('Model') : App::objects("$plugin.Model");
            foreach ($models as $model) {
                if (!in_array($model, array('AppModel')))  {
                    $modelName = $plugin === 'App'? $model : $plugin . '.' . $model;
                    $modelClass = ClassRegistry::init($modelName);
                }
            }
        }
        $this->query('SET foreign_key_checks = 1');
    }

    /**
     * Método para buscar a lista de tabelas de acordo com base de dados
     */
    public function deleteTablesByDataBase() {
        $db = $this->getDataSource()->config['database'];
        $tables = $this->query("SHOW FULL TABLES IN $db WHERE TABLE_TYPE = 'BASE TABLE'")->fetchAll(PDO::FETCH_ASSOC);
        
        $this->query('SET foreign_key_checks = 0');
        foreach ($tables as $table) {
            // if (in_array($table["Tables_in_$db"], $ignoredTables)) {
            //     continue;
            // }
            // $this->query("TRUNCATE TABLE {$table["Tables_in_$db"]}");
            $this->query("DELETE FROM {$table["Tables_in_$db"]}");
            $this->query("ALTER TABLE {$table["Tables_in_$db"]} AUTO_INCREMENT = 1");
        }
        $this->query('SET foreign_key_checks = 1');
    }

    /**
     * Retorna o DataSource de teste
     */
    public function getDataSource() {
        return ConnectionManager::getDataSource('test');
    }

        /**
     * Insere os dados padrões do banco (administrador, aplicativo, ...)
     */
    public function insertDefaultData() {
        $this->query(
            "
            INSERT INTO `perfil` VALUES ('1', 'alimentação');
            INSERT INTO `perfil` VALUES ('2', 'supermercados');
            INSERT INTO `perfil` VALUES ('3', 'padarias / bistros / cafés');
            INSERT INTO `perfil` VALUES ('4', 'moda / fantasias');
            INSERT INTO `perfil` VALUES ('5', 'saúde');
            INSERT INTO `perfil` VALUES ('6', 'lazer');
            INSERT INTO `perfil` VALUES ('7', 'festas / eventos / shows / dan');
            INSERT INTO `perfil` VALUES ('8', 'bares');
            INSERT INTO `perfil` VALUES ('9', 'cultural');
            INSERT INTO `perfil` VALUES ('10', 'histórico');
            INSERT INTO `perfil` VALUES ('11', 'Informativo');
            INSERT INTO `perfil` VALUES ('12', 'eletroeletrônicos');
            INSERT INTO `perfil` VALUES ('13', 'esportes');
            INSERT INTO `perfil` VALUES ('14', 'eróticos');
            INSERT INTO `perfil` VALUES ('15', 'casa e construção');
            INSERT INTO `perfil` VALUES ('16', 'beleza');
            INSERT INTO `perfil` VALUES ('17', 'farmácias / drogarias');
            INSERT INTO `perfil` VALUES ('18', 'pousadas / hotéis / Motéis');
            INSERT INTO `perfil` VALUES ('19', 'acadêmico');
            INSERT INTO `perfil` VALUES ('20', 'imobiliárias');
            INSERT INTO `perfil` VALUES ('21', 'petShops');
            INSERT INTO `perfil` VALUES ('22', 'camping / pesca');
            INSERT INTO `perfil` VALUES ('23', 'concessionárias');
            INSERT INTO `perfil` VALUES ('24', 'bancos');
            INSERT INTO `perfil` VALUES ('25', 'brinquedos');
            INSERT INTO `perfil` VALUES ('26', 'musical');
            INSERT INTO `perfil` VALUES ('27', 'perfumaria / cosméticos');
            INSERT INTO `perfil` VALUES ('28', 'empregos / concursos');
            INSERT INTO `perfil` VALUES ('29', 'serviços');
            "
        );

        $this->query(
            "
                INSERT INTO `sub_perfil` VALUES ('1', '5', 'Academia');
                INSERT INTO `sub_perfil` VALUES ('2', '5', 'Pilates');
                INSERT INTO `sub_perfil` VALUES ('3', '5', 'Clínicas');
                INSERT INTO `sub_perfil` VALUES ('4', '6', 'Passeios');
                INSERT INTO `sub_perfil` VALUES ('5', '6', 'Parques');
                INSERT INTO `sub_perfil` VALUES ('6', '9', 'Livrarias');
                INSERT INTO `sub_perfil` VALUES ('7', '9', 'Cinemas');
                INSERT INTO `sub_perfil` VALUES ('8', '9', 'Teatro');
                INSERT INTO `sub_perfil` VALUES ('9', '10', 'Pontos Turísticos');
                INSERT INTO `sub_perfil` VALUES ('10', '10', 'Históricos da Cidade');
                INSERT INTO `sub_perfil` VALUES ('11', '11', 'Pague hoje seu IPVA com 30% de desconto');
                INSERT INTO `sub_perfil` VALUES ('12', '16', 'SPA');
                INSERT INTO `sub_perfil` VALUES ('13', '16', 'Salão de Beleza');
                INSERT INTO `sub_perfil` VALUES ('14', '19', 'Escolas de Idiomas');
                INSERT INTO `sub_perfil` VALUES ('15', '19', 'Cursos de Extensão');
                INSERT INTO `sub_perfil` VALUES ('16', '26', 'Instrumentos');
                INSERT INTO `sub_perfil` VALUES ('17', '26', 'CD\'s');
                INSERT INTO `sub_perfil` VALUES ('18', '26', 'Aulas de Instrumentos');
                INSERT INTO `sub_perfil` VALUES ('19', '26', 'Danças');
                INSERT INTO `sub_perfil` VALUES ('20', '29', 'Manutenção');
                INSERT INTO `sub_perfil` VALUES ('21', '29', 'Encanador');
                INSERT INTO `sub_perfil` VALUES ('22', '29', 'Pedreiro');
                INSERT INTO `sub_perfil` VALUES ('23', '29', 'Detetização');
            "
        );

    }











	/**
	 * Retorna a instância de um model pelo nome (pode conter o plugin, ex: Parking.Ticket)
	 */
    protected function getModel($name) {
        list($plugin, $model) = pluginSplit($name);
        if (!isset($this->$model)) {
            $this->$model = ClassRegistry::init($name);
        }
        return $this->$model;
    }

    /**
     * Cria os registros de todos os models inicializados na variável $uses
     */
    public function saveCurrent($uses) {
        $modelIds = array();
        foreach ($uses as $model) {
            $this->getModel($model);
            $data = strtolower($model);
            if (isset($this->$data)) {
                $this->assertTrue((bool)$this->$model->save($this->$data), "Erro ao inserir os dados de $model");
                $modelIds[] = array($model => array('id' => $this->$model->id));
            } else {
                $this->assertTrue(false, "Variável $this->$data não encontrada.") ;
            }
        }
        return $modelIds;
    }

	/**
     * Insere/atualiza um registro com os dados $data do model $model. Erros de validação são exibidos na tela caso ocorrerem.
     *  
     * @param $model Nome do model
     * @param $data Dados a serem inseridos no model
     * @param $stopOnError Se true, falha o teste caso ocorra um erro de validação
     * @return Retorna o id do registro em caso de sucesso ou NULL em caso defalha
     */
    public function save($model, $data, $stopOnError = true) {
        $modelClass = $this->getModel($model);
        $modelClass->create();
        $saved = $modelClass->save($data);
        if ($saved) {
            return $modelClass->id;
        } else {
            if ($modelClass->validationErrors) {
                if ($stopOnError) {
                    $errors = '';
                    foreach ($modelClass->validationErrors as $field => $error) {
                        $errors .= "$field: " . implode(', ', $error) . ' ';
                    }
                    throw new Exception($errors);
                }
            }
            if ($stopOnError) {
                throw new Exception('Erro ao salvar model: ' . $model);
            }
            return null;
        }
    }

    
    /**
     * Retorna o "call error" de uma exception. Ver detalhes dentro da classe Util
     */
    public function getSQLCallError(Exception $ex) {
        return Util::getSQLCallError($ex);
    }
    
    /**
    * Adicionado parâmetro para receber a data que será modificado
    */
    public function getDateTime($modify = null, $date = null, $format = 'Y-m-d H:i:s') {

        if (empty($date)) {
            $result = $this->getDataSource()->rawQuery('SELECT NOW() AS date_time');
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
            $date = new Datetime($result[0]['date_time']);
        }

        if (!empty($modify)) {
            $date->modify($modify);
        }

        return $date->format($format);
    }

    public function getDate($modify = null, $format = 'd/m/Y') {
        return $this->getDateTime($modify, null, $format);
    }

    public function getTime($modify = null, $format = 'H:i:s') {
        return $this->getDateTime($modify, null, $format);
    }

    public function toSQLDate($date, $format = 'd/m/Y') {
        $newDate = DateTime::createFromFormat($format, $date);
        return $newDate->format('Y-m-d');
    }

    /**
     * Executa uma $query com os parâmetros $params especificados. Atalho para o método execute() do DataSource
     */
    public function execute($query, $params = array()) {
        return $this->getDataSource()->execute($query, array(), $params);
    }

    /**
     * Executa uma $query com os parâmetros $params especificados. Atalho para o método rawQuery() do DataSource
     */
    public function query($query, $params = array()) {
        return $this->getDataSource()->rawQuery($query, $params);
    }

    /**
     * Chama uma procedure e retorna o resultado
     * 
     * @param $name Nome da procedure
     * @param $params Parâmetros
     * @return Retorna a saída da procedure (SELECT) caso exista
     */
    public function callProcedure($name, $params = array(), $dontShowWarnings = false) {
        $paramsStr = '';
        for ($i = 0; $i < count($params); $i++) {
            $paramsStr .= '?';
            if ($i != count($params) - 1) {
                $paramsStr .= ', ';
            } 
        }
        $return = $this->execute("CALL $name($paramsStr)", $params);
        
        if (!$dontShowWarnings) {
            $this->showWarningMessages($this->execute('SHOW WARNINGS', array()));
        }

        if ($return === null || $return === true || $return === false) {
            return $return;
        } else {
            return $return->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Testa se há warning messages e as exibe, se houver
     */
    private function showWarningMessages($warnings) {
        $warningMessage = $warnings->fetchAll(PDO::FETCH_ASSOC);
        if ($warningMessage) {
            throw new Exception(print_r($warningMessage[0]['Message'], true));
        } 
    }

    /**
     * Chama uma função e retorna o resultado
     * @param $name Nome da função
     * @param $params Parâmetros
     * @return Retorna a saída da função
     */
    public function callFunction($name, $params = array()) {
        $paramsStr = '';
        for ($i = 0; $i < count($params); $i++) {
            $paramsStr .= '?';
            if ($i != count($params) - 1) {
                $paramsStr .= ', ';
            } 
        }
        $return = $this->execute("SELECT $name($paramsStr)", $params);
        if ($return === null || $return === true || $return === false) {
            return $return;
        } else {
            return $return->fetch(PDO::FETCH_BOTH);
        }
    }

  

    


}