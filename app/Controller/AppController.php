<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

	public $components = array('RequestHandler');
	public $helpers = array('App','Util', 'Format', 'Number', 'MagicReport.MagicReport');

  	public function beforeRender() {
        parent::beforeRender();
        // $this->helpers['Acl']['component'] = $this->Acl;
        // $this->helpers['Acl']['group'] = $this->group;

        // //Cria variáveis listas de enums
        // $this->outputEnumOptions();
        // //Converte as datas
        // $this->convertSQLToLocalDate();

        // //Verifica se a sessão deve expirar
        // $lastActivity = (int) $this->Session->read('Session.lastActivity');
        // $arrayPerUser = Configure::read('Session.timeoutPerUser');
        // $inactiveMins = $this->Auth->user('tipo') != null ? (int) $arrayPerUser[strtoupper($this->Auth->user('tipo'))] : 0;
        // //Se está logado
        // if ($lastActivity && $this->Auth->user() != null) {
        //     $interval = time() - $lastActivity;
        //     //Se está autorizado e o tempo de inatividade foi ultrapasado, força logout
        //     if (!$this->Auth->isAuthorized() && $interval > $inactiveMins * 60) {
        //         return $this->redirect('/entidades/logout/inativado');
        //     }
        // }
        // $this->Session->write('Session.lastActivity', time());
    }

    public function getDataSource() {
        $source = Configure::read('test')? 'test' : 'write';
        return ConnectionManager::getDataSource($source);
    }
}