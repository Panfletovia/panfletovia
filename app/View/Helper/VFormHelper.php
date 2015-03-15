<?php

App::uses('FormHelper', 'View/Helper');

class VFormHelper extends FormHelper {

    public function input($fieldName, $options = array()) {
        if (isset($options['custom'])) {
            
        } else {
            return parent::input($fieldName, $options);
        }
    }
    
    public function number($fieldName) {
        return '<input type="number" format="*N" />';
    }
}