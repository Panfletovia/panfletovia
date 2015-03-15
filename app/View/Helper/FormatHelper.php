<?php

App::uses('AppHelper', 'View/Helper');
App::uses('AppController', 'Controller');
App::uses('Format', 'Lib');

/**
 * Helper de formatação de dados (data, dinheiro, enums, etc)
 * Os métodos são atalhos para a Lib Format
 */
class FormatHelper extends AppHelper {

    public function enumD($enum, $value) {
        return AppController::$enums[$enum][$value];
    }

    public function enumV($enum, $desc) {
        return array_search($desc, AppController::$enums[$enum]);
    }

    public static function addEspace($value, $size, $before = true){
        return Format::addEspace($value, $size, $before);
    }
    public static function human($desc) {
        return Format::human($desc);
    }
    public static function slug($desc) {
        return Format::slug($desc);
    }
    public function bool($bool, $type = 'icon') {
        return Format::bool($bool, $type);
    }

    public static function interval($date1, $date2 = null, $regressive = false, $seconds = false) {
        return Format::interval($date1, $date2, $regressive, $seconds);
    }

    public static function symbol($number = null) {
        return Format::symbol($number);
    }

    public function shortNumberFormat($number, $decimals = 2) {
        return Format::shortNumberFormat($number, $decimals);
    }

    public static function money($number, $symbol = true) {
        return Format::money($number, $symbol);
    }

    public static function decimal($number, $decimals = 2) {
        return Format::decimal($number, $decimals);
    }

    public static function shortMoney($number, $symbol = true) {
        return Format::shortMoney($number, $symbol);
    }

    public static function perc($perc, $decimals = 2, $symbol = true) {
        return Format::perc($perc, $decimals, $symbol);
    }

    public static function num($num) {
        return Format::num($num);
    }

    public static function shortDt($dateTime) {
        return Format::shortDt($dateTime);
    }
    public static function shortDtSeconds($dateTime) {
        return Format::shortDtSeconds($dateTime);
    }

    public static function shortDate($dateTime) {
        return Format::shortDate($dateTime);
    }

    public function shortDateTime($dateTime) {
        return Format::shortDateTime($dateTime);
    }

    public static function dateTime($dateTime) {
        return Format::dateTime($dateTime);
    }

    public static function date($date) {
        return Format::date($date);
    }

    public static function dmy($date) {
        return Format::dmy($date);
    }

    public static function my($date) {
        return Format::my($date);
    }

    public static function time($time) {
        return Format::time($time);
    }

    public static function shortTime($time) {
        return Format::shortTime($time);
    }
    
    public static function emailMask($email){
        return Format::emailMask($email);
    }

    /**
     * Escreve '-' se o o valor informado for nulo
     */
    public static function dash($value) {
        return empty($value) ? '-' : $value;
    }

    /**
     * Adiciona zeros à esquerda do valor informado
     */
    public static function addZeros($value, $zeros = 6) {
        $length = strlen($value);
        if ($length < $zeros) {
            return str_pad($value, $zeros, '0', STR_PAD_LEFT);
        } else {
            return $value;
        }
    }

    /**
     * Traduz o período de vencimento do limite para um formato humano
     */
    public static function periodoVencimento($periodo) {
        $periodicidade = substr($periodo, 0 , 1);

        if ($periodicidade == 'P') {
            $quantidade = substr($periodo, 1);
            return "A cada $quantidade dia(s)";
        } else if ($periodicidade == 'W') {
            return 'Semanalmente';
        } else {
            return 'Mensalmente';
        }
    }

    /**
     * Método para converter a forma de pagamento do banco de dados para forma usuário
     */
    public static function humanizePaymentForm($enum){
        switch($enum){
            case 'DINHEIRO':
                return 'Dinheiro';
            case 'PRE':
                return 'Pré';
            case 'POS':
                return 'Pós';
            case 'CPF_CNPJ':
                return 'Cpf ou Cnpj';
            case 'DEBITO_AUTOMATICO':
                return 'Débito Automático';
            case 'CARTAO':
                return 'Cartão';
            default:
                return 'Outros';
        }
    }// End Method 'humanizeFormaPagamento'
}
