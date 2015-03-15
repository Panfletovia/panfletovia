<?php
App::uses('ApiAppController', 'Api.Controller');

class ApiController extends ApiAppController {

	  public $components = array(
        'RequestHandler'
    );

	/**
	* Processa a entrada de dados 
	*/	
	public function index() {
		die(var_dump('teste'));
		$this->data = array('version' => API_VERSION);
		$this->data['datetime'] = $this->getDateTime();
		
		/*
		if ($this->request->is('post')) {
			$this->data['post_params'] = $_POST;
		} else if ($this->request->is('get')) {
			$this->data['get_params'] = $_GET;
		}
		*/

	}
}
