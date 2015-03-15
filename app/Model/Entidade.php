<?php

App::uses('AppModel', 'Model');

/**
 * Entidade Model
 *
 * @property Negocio $Negocio
 * @property Cartao $Cartao
 * @property Limite $Limite
 * @property Pedido $Pedido
 */
class Entidade extends AppModel {

    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'entidade';
    // public $displayField = 'nome';
    // public $validate = array(
    //     'cpf_cnpj' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         // 'unique' => array('rule' => array('unique', 'Entidade'), 'message' => 'CPF/CNPJ já cadastrado. '),
    //         'validateCpfCnpj' => array('rule' => 'validateCpfCnpj', 'message' => 'Documento inválido.'),
    //         'uniquePostoCliente' => array('rule' => 'validateCpfCnpjUnique', 'message' => 'CPF/CNPJ já cadastrado.')
    //     ),
    //     'email' => array(
    //         'email' => array('rule' => 'email', 'allowEmpty' => true, 'message' => 'E-mail inválido.',),
    //         'isUnique' => array('rule' => 'isUnique', 'message' => 'E-mail já cadastrado.',)
    //     ),
    //     'confirmacao_email' => array(
    //         'validateEmail' => array('rule' => 'validateEmail', 'message' => 'Confirmação de e-mail não confere.')
    //     ),
    //     'telefone' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         'between' => array('rule' => array('between', 13, 14), 'message' => '14 dígitos.'),
    //         'isUnique' => array('rule' => 'isUnique', 'message' => 'Telefone já cadastrado.')
    //     ),
    //     'cep' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         'between' => array('rule' => array('between', 9, 9), 'message' => '9 dígitos.')
    //     ),
    //     'logradouro' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'bairro' => array(
    //         'rule' => 'notEmpty', 'message' => 'Campo obrigatório.',
    //         'rule' => array('maxLength', 20), 'message' => 'Máximo de 20 caracteres excedido.'
    //     ),
    //     'numero' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'cidade' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'uf' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'repita_senha' => array('rule' => 'repeatPassword', 'message' => 'Por favor, repita a senha neste campo.'),
    	
    // 	'fantasia' => array('rule' => 'validateFantasia', 'message' => 'Nome fantasia inválido')
    // );

    public $virtualFields = array();

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        // $this->virtualFields['veiculos'] = "IF({$this->alias}.tipo='CLIENTE', (SELECT GROUP_CONCAT(park_placa.placa SEPARATOR ', ') FROM park_placa WHERE inativo = 0 AND entidade_id = {$this->alias}.id), '')";
        // $this->virtualFields['cpf_cnpj_hash'] = "LOWER(MD5({$this->alias}.cpf_cnpj))";

    }

    // public function beforeSave($options = array()) {
    //     parent::beforeSave($options);
    //     if (isset($this->data['Entidade']['senha_site'])) {
    //         $this->data['Entidade']['senha_site'] = __hashPassword($this->data['Entidade']['senha_site'], $this->data['Entidade']['cpf_cnpj']);
    //     }
    //     return;
    // }

    // public function repeatPassword() {
    //     if (isset($this->data['Entidade']['senha_site'])) {
    //         return $this->data['Entidade']['senha_site'] == '' || ($this->data['Entidade']['senha_site'] == $this->data['Entidade']['repita_senha']);
    //     } else {
    //         return true;
    //     }
    // }

    // public $belongsTo = array(
    //     'Negocio' => array(
    //         'className' => 'Negocio',
    //         'foreignKey' => 'negocio_id'
    //     ),
    //     'Aplicativo' => array(
    //         'className' => 'Aplicativo',
    //         'foreignKey' => 'aplicativo_id'
    //     )
    // );
    // public $hasOne = array(
    //     'LimiteContaCorrente' => array(
    //         'className' => 'Limite',
    //         'foreignKey' => 'entidade_id',
    //         'dependent' => false,
    //         'conditions' => array('LimiteContaCorrente.bolsa_id' => 1),
    //         'fields' => '',
    //         'order' => '',
    //         'limit' => '',
    //         'offset' => '',
    //         'exclusive' => '',
    //         'finderQuery' => '',
    //         'counterQuery' => ''
    //     ),
    // );

    // /**
    //  * hasMany associations
    //  *
    //  * @var array
    //  */
    // public $hasMany = array(
    //     'Cartao' => array(
    //         'className' => 'Cartao',
    //         'foreignKey' => 'entidade_id',
    //         'dependent' => false,
    //         'conditions' => '',
    //         'fields' => '',
    //         'order' => '',
    //         'limit' => '',
    //         'offset' => '',
    //         'exclusive' => '',
    //         'finderQuery' => '',
    //         'counterQuery' => ''
    //     ),
    //     'Pedido' => array(
    //         'className' => 'Pedido',
    //         'foreignKey' => 'entidade_id',
    //         'dependent' => false,
    //         'conditions' => '',
    //         'fields' => '',
    //         'order' => '',
    //         'limit' => '',
    //         'offset' => '',
    //         'exclusive' => '',
    //         'finderQuery' => '',
    //         'counterQuery' => ''
    //     )
    // );

    // /**
    //  * Return TRUE se o e-mail for igual ao confirmação de email, caso contrário return FALSE.
    //  * @return boolean
    //  */
    // public function validateEmail() {
    //     return $this->repeatField('Entidade', 'email', 'confirmacao_email');
    // }

    // public function validateCpfCnpj() {
    //     return $this->validaCpfCnpj($this->data['Entidade']['pessoa'], $this->data['Entidade']['cpf_cnpj']);
    // }
    
    // public function validateFantasia()
    // {
    // 	if ('JURIDICA' == $this->data['Entidade']['pessoa']) {
    // 		if ('' == $this->data['Entidade']['fantasia']) {
    // 			return false;
    // 		}
    // 	}
   	// 	return true;
    // }

    // /**
    //  * Ao salvar uma entidade deverá validar seu tipo, pois um cliente e um posto podem ter cpf's duplicados. 
    //  * Mas apenas nesta situação. Caso contrário deverá lançar erro
    //  */
    // public function validateCpfCnpjUnique(){

    //     // Caso exista o campo tipo no request
    //     if(isset($this->data['Entidade']['tipo'])){

    //         // Extrai o tipo de entidade a ser salvo
    //         $tipo = $this->data['Entidade']['tipo'];
    //         // Caso diferente de posto e diferente de cliente, nem efetua a  procura por cpf_cnpj duplicado
    //         if ($tipo <> 'POSTO' && $tipo <> 'CLIENTE') {
    //             return true;
    //         }

    //         $idCondition = array();
    //         if (isset($this->data['Entidade']['id'])) {
    //             $idCondition = array("Entidade.id <>" => $this->data['Entidade']['id']);
    //         }

    //         // Caso não existir um cliente, valida se já existe uma entidade cadastrada. Caso sim, não permite cadastro.
    //         if(!$this->fieldsUniqueModel('Entidade', array('cpf_cnpj' => $this->data['Entidade']['cpf_cnpj'], 'tipo <>' => 'CLIENTE', $idCondition))){
    //         	if (!isset($this->data['Entidade']['id']) && isset($this->data['Entidade']['senha_site'])) {
    //                 return false;
    //             }
    //         }
    //     }
        
    //     return true;
    // }// End Method 'validateCpfCnpjUnique'


    // /**
    //  * Gera fatura individual do posto/associado ao ser inativado.
    //  */
    // public function geraFaturaIndividual($limiteId = NULL){

    //     // Em um fechamento individual da fatura o limite id da entidade não pode ser nula
    //     if (empty($limiteId)) {
    //         return false;
    //     }

    //     // Efetua chamada da procedure
    //     $this->query("CALL processa_fechamento($limiteId, null, null);");
    //     // Caso query não lance erro, retorna true
    //     return true;
    // }// End Method 'geraFaturaIndividual'

}// End Class