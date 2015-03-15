<?php

class ApiException extends CakeException {

    private $params = array();

    public function __construct($message, $code, $params = array()) {
        parent::__construct($message, $code);
        $this->params = $params;
    }

    public function getParams() {
        return $this->params;
    }
}