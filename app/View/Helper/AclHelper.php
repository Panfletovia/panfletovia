<?php

App::uses('AppHelper', 'View/Helper');
App::uses('AppController', 'Controller');

class AclHelper extends AppHelper {

    private $component;
    private $group;

    public function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);
        $this->component = $settings['component'];
        $this->group = $settings['group'];
    }

    public function check($check) {
    	if (is_array($check)) {
    		$ok = false;
    		foreach ($check as $checkItem) {
    			$ok = $ok || $this->component->check($this->group, strtolower($checkItem));
    		}
    		return $ok;
    	} else {
    	    return $this->component->check($this->group, strtolower($check));
    	}
    }

}