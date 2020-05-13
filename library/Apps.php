<?php

class Apps {

    protected static $_oInstance;

    public static function getInstance() {
        if (null === self::$_oInstance) {
            self::$_oInstance = new self();
        }
        return self::$_oInstance;
    }

    public function dispatch() {
        try {
            $oRequest = Request::getInstance();
            $oView = View::getInstance();
            Controller::process($oRequest, $oView)->printOut();
        } catch (Exception $oE) {
            Controller::processException($oRequest, $oView, $oE)->printOut();
        }
    }

    public static function start() {
        global $oLayout;

        require 'apps/configs/config.inc.php';
        $oApplication = Apps::getInstance();
        $oApplication->autoloader();

        Session::start();
        $oLayout = Layout::getInstance();
        return $oApplication;
    }

    public function autoloader() {
        // Set include path
        $sPath = (string) get_include_path();
        $sPath .= (string) (PATH_SEPARATOR . LIBRARY_PATH );
        $sPath .= (string) (PATH_SEPARATOR . APPS_PATH );
        $sPath .= (string) (PATH_SEPARATOR . EXTENSIONS_PATH );
        $sPath .= (string) (PATH_SEPARATOR . APPS_PATH . '/models' );
        $sPath .= (string) (PATH_SEPARATOR . EXTENSIONS_PATH );
        $sPath .= (string) (PATH_SEPARATOR . LIBRARY_PATH . '/Model' );

        set_include_path($sPath);
        spl_autoload_register(array('Apps', 'loadClass'));
    }

    public function loadClass($sClassName) {
        $sClassName = (string) str_replace('_', DIRECTORY_SEPARATOR, $sClassName);
        include_once($sClassName . '.php');
    }

    /**
     * Apps::getModel('Test');
     * @param string $sModelName
     * @return object Model_Abstract
     */
    public static function getModel($sModelName) {
        $tModelDirectory = explode('_', $sModelName);
        array_pop($tModelDirectory);
        if (count($tModelDirectory) > 1) {
            $sPath = (string) get_include_path();
            $sPath .= (string) (PATH_SEPARATOR . APPS_PATH .
                    '/models/' . str_replace('_', '/', $sModelName));

            $sPathToAdded = APPS_PATH . '/models/' . str_replace('_', '/', $sModelName);
            $tPaths = explode(PATH_SEPARATOR, get_include_path());
            if (!in_array($sPathToAdded, $tPaths)) {
                set_include_path($sPath);
            }
        }

        $sClassModel = $sModelName . 'Model';
        return new $sClassModel;
    }

    /**
     * Apps::getResourceModel('Acl');
     * @param string $sModelName
     * @return object Model_Abstract
     */
    public static function usePlugin($sModelName) {
        $sClassModel = str_replace('/', '_', $sModelName);
        return new $sClassModel;
    }

    public static function encryptIt($q) {
        $cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
        $qEncoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), $q, MCRYPT_MODE_CBC, md5(md5($cryptKey))));
        return( $qEncoded );
    }

    public static function decryptIt($q) {
        $cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
        $qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), base64_decode($q), MCRYPT_MODE_CBC, md5(md5($cryptKey))), "\0");
        return( $qDecoded );
    }

    public static function convertDate($date, $format = 'Y-m-d') {
        return date($format, strtotime(str_replace('/', '-', $date)));
    }

    public static function month($mois = null) {
        $month = array(
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
        );
        if (!is_null($mois)) {
            return $month[$mois];
        }

        return $month;
    }

    public static function weekOfMonth($mois, $annee) {
        $lastday = strftime("%W", mktime(0, 0, 0, $mois + 1, 0, $annee));
        $firstday = strftime("%W", mktime(0, 0, 0, $mois, 1, $annee));
        $weeks = array();
        for ($i = (int) $firstday; $i <= $lastday; $i++)
            $weeks[] = $i;

        return $weeks;
    }

    public static function writelog($logname, $_content) {

        $content = str_replace(array('<br/>', '<br>'), "\r\n", $_content);
        $file = $logname . '.txt';
        $dir = ABSOLUTE_DIR . '/var/log/' . $file;


        if (file_exists($dir)) {
            $filePointer = fopen($dir, "a+");
        } else {
            $filePointer = fopen($dir, "w+");
        }
        fwrite($filePointer, $content);
        fclose($filePointer);
        
        return true;
    }

    public static function printlog($file) {
        $file = $file . '.txt';
        $dir = ABSOLUTE_DIR . '/var/log/' . $file;
        if (file_exists($dir)) {

            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            readfile($dir);
        }
    }
    
    public static function getMenu($role){
        /** @var Systemes_MenuModel $oModel */
        $oModel = Apps::getModel('Systemes_Menu');
        return $oModel->getMenu($role, IS_ADMIN);
    }

    public static function insertElementToArray($arr = array(), $element = null, $index = 0)
    {
        if ($element == null) {
            return $arr;
        }

        $arrLength = count($arr);
        $j = $arrLength - 1;

        while ($j >= $index) {
            $arr[$j+1] = $arr[$j];
            $j--;
        }

        $arr[$index] = $element;

        return $arr;
    }

    /**
     * @param array $tableau
     * @param string $citere
     *
     * @return array
     */
    public static function sortArray($tableau, $citere = 'id') {
        $newTableau = [];
        foreach($tableau as $key => $value){
            $newTableau[$value[$citere]][$key] = $value;
        }

        return $newTableau;
    }

    /**
     * @param $number
     * @return string
     */
    public static function convertMoney($number) {

        return number_format($number, '0', '.', ' ');
    }

}
