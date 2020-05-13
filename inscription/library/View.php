<?php

class View extends View_Abstract
{

    protected static $_oInstance;

    public static function getInstance() {
        if (null === self::$_oInstance) {
            self::$_oInstance = new self();
        }
        return self::$_oInstance;
    }

    public function redirect($sUrl, $bPermanent = false) {
        if ($bPermanent) {
            $this->_tHeaders['Status'] = '301 Moved Permanently';
        } else {
            $this->_tHeaders['Status'] = '302 Found';
        }
        $this->_tHeaders['location'] = $sUrl;

        foreach ($this->_tHeaders as $sKey => $sValue) {
            header($sKey . ':' . $sValue);
        }
        exit();
    }

    public function printOut() {
        foreach ($this->_tHeaders as $sKey => $sValue) {
            header($sKey . ':' . $sValue);
        }
        echo $this->_sBody;
    }

    public function render($sFile) {
        extract($this->_tVars);
        ob_start();
        include($sFile);
        $sHtml = ob_get_contents();
        ob_end_clean();
        return $sHtml;
    }

    public function urlToSort($column) {
        $_sort_value = Commons::getRequestParameter("sort_$column");
        $url = $_SERVER['REQUEST_URI'];
        $pattern = '/&sort_(\w+)=(\w+)/';
        $replacement = "";
        $url = preg_replace($pattern, $replacement, $url);
        $_new_sort_value = "ASC";
        if ($_sort_value) {
            if ($_sort_value == "ASC")
                $_new_sort_value = "DESC";
            else
                $_new_sort_value = "ASC";
        }
        $pos = strpos($url, '?');
        if ($pos === false) {
            $url = substr($url, -1, 1) != '/' && substr($url, -1, 1) != '?' && substr($url, -1, 1) != '&' ? $url . '/' : $url;
            $url = strpos($url, '?') === false ? $url . '?' : $url;
        }

        $separateur = substr($url, -1, 1) != '&' ? '&' : '';
        $url = $url . $separateur . "sort_$column=$_new_sort_value";
        return $url;
    }

    public function ToLettre($chiffre, $unite = "Ariary") {
        $oLettre = Apps::usePlugin('ChiffreLettre');
        $value = explode('.', str_replace(',', '.', $chiffre));
        $decimal = isset($value[1])&& $value[1] > 0 ? ' ' . $oLettre->convert($value[1]) : '';
        return $oLettre->convert($value[0]) . " $unite" . $decimal;
    }

    public function getDataIn($array, $key) {
        return key_exists($key, $array) ? $array[$key] : '';
    }

    public function checkError($error, $key, $c = 'col-md-4') {
        $html = '';
        $class = 'at-error '; //class par defaut
        $class .= $c;
        if (key_exists($key, $error)) {
            $html = "<span class='$class'>$error[$key]</span>";
        }
        return $html;
    }

    public function weekToDate($annee, $semaine) {
        $date = new DateTime($annee . '-01-01');
        $date->modify('first monday');
        if ($date->format('W') != '01')
            $date->modify('last monday');
        $date->modify('+' . ($semaine - 1) . ' week');
        return $date;
    }

    public function monnaie($iValue, $bUnite = true, $decimal = 0) {
        $sUnite = $bUnite ? ' ' . UNITE_MONETAIRE : '';
        return number_format($iValue, $decimal, ',', ' ') . $sUnite;
    }

    public function round($iValue) {
        return round($iValue, 2, PHP_ROUND_HALF_DOWN);
    }
    
    public function format($iValue, $decimal = 2) {
        return number_format($iValue, $decimal, '.', '');
    }

}
