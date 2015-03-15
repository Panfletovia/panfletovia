<?php

App::uses('AppHelper', 'View/Helper');
App::uses('Util', 'Lib');

class UtilHelper extends AppHelper {

    public function img($img, $options = array()) {
        $data = $this->imgData($img);
        $style = @$options['style'];
        $title = @$options['title'];
        $class = @$options['class'];
        if (!$style)
            $style = '';
        if (!$title)
            $title = '';
        if (!$class)
            $class = '';
        return '<img class="' . $class . '" style="' . $style . '" title="' . $title . '" src="' . $data . '"/>';
    }

    public function imgData($img) {
        $tmp = explode('.', $img);
        $ext = $tmp[sizeof($tmp) - 1];
        return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents(WWW_ROOT . "img/$img"));
    }
    
    /**
     * Calcula a interpolação entre duas cores (inicial e final) de acordo com o percentual fornecido
     * @param type $initialColor Cor inicial formatada em um array ou hexstring
     * @param type $finalColor Cor final formatada em um array ou hexstring
     * @param type $percentage Percentual de interpolação onde 0 é igual à cor inicial e 1 é igual à cor final
     * 
     * Formato do array de cores: 
     * array('r' => (int), 'g' => (int) 'b' => (int))
     */
    public function colorInterpolation($initialColor, $finalColor, $percentage = 0.5) {
        return Util::colorInterpolation($initialColor, $finalColor, $percentage);
    }
    /**
     * Converte um array de componentes de cores (ver formato abaixo)
     * em uma string hexadecimal
     * @param type $colorComponents Formato: array('r' => (int), 'g' => (int) 'b' => (int))
     * @param bool $prependSharp Se verdadeiro, adiciona um # no início da string
     * @return type
     */
    public function colorToHex($colorComponents, $prependSharp = true) {
        return Util::colorToHex($colorComponents, $prependSharp);
    }
    
    /**
     * Converte uma cor hexadecimal (com ou senha # no início) em um array de cor no formato abaixo:
     * array('r' => (int), 'g' => (int) 'b' => (int))
     * @param type $hexString
     * @return type
     */
     public function hexToColor($hexString) {
         return Util::hexToColor($hexString);
    }

}