<?php

class AlllTest extends PHPUnit_Framework_TestSuite {

    public static function suite() {
        $suite = new CakeTestSuite('All');
        $controllersDir = APP . DS . 'Plugin' . DS . 'Api' . DS . 'Test' . DS . 'Case';
        $suite->addTestDirectory($controllersDir);
        return $suite;
    }
}