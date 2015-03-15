<?php

class AllControllersTest extends PHPUnit_Framework_TestSuite {

    public static function suite() {
        $suite = new CakeTestSuite('All Controllers');
        $controllersDir = APP . DS . 'Plugin' . DS . 'Api' . DS . 'Test' . DS . 'Case' . DS . 'Controller';
        $suite->addTestDirectory($controllersDir);
        return $suite;
    }
}