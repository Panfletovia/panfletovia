<?php

App::uses('AppModel', 'Model');

class Cliente extends AppModel {

    public $hasMany = array(
        'ClientePerfil' => array(
            'className' => 'ClientePerfil',
            'conditions' => array('ClientePerfil.cliente_id' => 'Cliente.id')
        )
    );

    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'cliente';

    /**
     * Busca cliente a partir do usuário e senha
     */
    public function findClient($username, $password, $fieldsQuery = array()){
        $comparatorPassword = $this->passwordHash($password);
        return $this->find('first', array(
            'fields' => $fieldsQuery,
            'conditions' => array(
                'ativo' => 1,
                'login' => $username,
                'senha' => $comparatorPassword
            )
        ));
    }// End Method 'findClient'

    /**
     * Create hash da senha do usuário
     */
    public function passwordHash ($password) {
        // Atribui o valor da senha
        $newPassword = $password;
        // Calcula a quantidade de vezes a senha para gerar o hash
        for ($x = 0; $x < 66; $x++) {
            $newPassword = md5(BEFORE_ENCRYPT . $newPassword . AFTER_ENCRYPT);
        }
        // Retorna a nova senha
        return $newPassword;
    }// End Method 'passwordHash'
    
    /**
     * Display fieldP
     *
     * @var string
     */
    // public $displayField = 'nome';
    // public $validate = array(
    //     'cpf_cnpj' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         // 'unique' => array('rule' => array('unique', 'Entidade'), 'message' => 'CPF/CNPJ já cadastrado. '),
    //         'validateCpfCnpj' => array('rule' => 'validateCpfCnpj', 'message' => 'Documento inválido.'),
    //         'uniquePostoCliente' => array('rule' => 'uniquePostoCliente', 'message' => 'CPF/CNPJ já cadastrado.')
    //     ),
    //     'nome' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         'completeName' => array('rule' => 'completeName', 'message' => 'Informe o nome completo.')
    //     ),
    //     'data_nascimento' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         'majority' => array('rule' => 'majority', 'message' => 'Você deve ter 18 anos ou mais para efetuar este cadastro.'),
    //         'date' => array('rule' => 'date', 'message' => 'Campo inválido.')
    //     ),
    //     'email' => array(
    //        'validateEmail' => array('rule' => 'validateEmail', 'message' => 'E-mail inválido.'),
    //        'unique' => array('rule' => array('unique', 'Entidade', false), 'message' => 'E-mail já cadastrado.')
    //     ),
    //     'confirmacao_email' => array(
    //         'validateEmail' => array('rule' => 'validateEmail', 'message' => 'Confirmação de e-mail não confere.')
    //     ),
    //     'telefone' => array(
    //         'between' => array('rule' => array('between', 13, 14), 'message' => 'Deve conter 10 ou 11 números.'),
    //         'unique' => array(
    //             'rule' => array('unique', 'Entidade'),
    //             'message' => 'Telefone já cadastrado.',
    //         )
    //     ),
    //     'bairro' => array(
    //         'rule' => array('maxLength', 20), 'message' => 'Máximo de 20 caracteres excedido.',
    //         'allowEmpty' => true
    //     ),
    //     // Validações removidas, para simplificação do cadastro
    //     /*'cep' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //         'between' => array('rule' => array('between', 9, 9), 'message' => '9 dígitos.')
    //     ),
    //     'logradouro' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'bairro' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'compl' => array(),
    //     'numero' => array(
    //         'notEmpty' => array(
    //             'rule' => 'notEmpty',
    //             'message' => 'Campo obrigatório.'
    //         ),
    //         'numeric' => array(
    //             'rule' => 'numeric',
    //             'message' => 'Apenas números.'
    //         ),
    //         'between' => array(
    //             'rule' => array('between', 1, 5),
    //             'message' => 'Entre 1 e 5 números.'
    //         )
    //     ),
    //     'cidade' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     'uf' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     */
    //     'senha_site' => array(
    //         'notEmpty' => array('rule' => 'notEmpty', 'message' => 'Campo obrigatório.'),
    //     ),
    //     'repita_senha' => array(
    //         'repeat' => array(
    //             'rule' => 'repeatPassword',
    //             'message' => 'Por favor, repita a senha neste campo.'
    //         )
    //     )
    // );

    // //The Associations below have been created with all possible keys, those that are not needed can be removed

    // /**
    //  * belongsTo associations
    //  *
    //  * @var array
    //  */
    // public $belongsTo = array(
    //     'Negocio' => array(
    //         'className' => 'Negocio',
    //         'foreignKey' => 'negocio_id',
    //         'conditions' => '',
    //         'fields' => '',
    //         'order' => ''
    //     )
    // );

    // public $hasAndBelongsToMany = array(
    //     'Dependente' => array(
    //         'className' => 'Dependente',
    //         'joinTable' => 'vinculo',
    //         'with' => 'Vinculo',
    //         'foreignKey' => 'entidade_id_pai',
    //         'associationForeignKey' => 'entidade_id_filho',
    //         'unique' => true
    //     )
    // );

    // public function beforeSave($options = array()) {
    //     parent::beforeSave($options);
    //     if (isset($this->data['Cliente'])) {
    //         $this->data['Cliente']['tipo'] = 'CLIENTE';
    //     }
        
    //     /*Remove a máscara do documento CPF/CNPJ.*/
    //     $this->data['Cliente']['cpf_cnpj'] = str_replace(array(".","/","-"), "", $this->data['Cliente']['cpf_cnpj']);
    //     /*Adiciona máscara conforme o tipo de pessoa.*/        
    //     if($this->data['Cliente']['pessoa'] == 'FISICA') {
    //         $this->data['Cliente']['cpf_cnpj'] = $this->mask($this->data['Cliente']['cpf_cnpj'], '999.999.999-99');
    //     } else {
    //         $this->data['Cliente']['cpf_cnpj'] = $this->mask($this->data['Cliente']['cpf_cnpj'], '99.999.999/9999-99');
    //     }
    //     return true;
    // }

    // public function repeatPassword() {
    //     if (isset($this->data['Cliente']['senha_site'])) {
    //         return $this->data['Cliente']['senha_site'] == '' || ($this->data['Cliente']['senha_site'] == $this->data['Cliente']['repita_senha']);
    //     } else {
    //         return true;
    //     }
    // }

    // /**
    //  * Return TRUE se o e-mail for igual ao confirmação de email, caso contrário return FALSE.
    //  * @return boolean
    //  */
    // public function validateEmail() {
        
    //     if ($this->data['Cliente']['check_email'] == 1) {
    //         unset($this->data['Cliente']['email']);
    //         unset($this->data['Cliente']['confirmacao_email']);
    //         unset($this->data['Cliente']['check_email']);
    //         return true;
    //     } else {
    //         $ret = $this->repeatField('Cliente', 'email', 'confirmacao_email');
    //         unset($this->data['Cliente']['confirmacao_email']);
    //         unset($this->data['Cliente']['check_email']);
    //         return $ret;
    //     }
    // }

    // public function validateCpfCnpj() {
    //     return $this->validaCpfCnpj($this->data['Cliente']['pessoa'], $this->data['Cliente']['cpf_cnpj']);
    // }

    

}// End Class