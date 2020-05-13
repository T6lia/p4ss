<?php

class Layout {

    protected $_sTitle;
	
	public $_sCrunbs = array();

    protected $_tStylesheet = array();
    protected $_tJavascript = array();
    protected $_sPath;
    protected $_sViewContent;
    protected $_sViewVars = array();
    public $_tArgs = array();
    protected static $_oInstance;

    public static function getInstance() {
        if (null === self::$_oInstance) {
            self::$_oInstance = new self();
        }
        return self::$_oInstance;
    }

    public function __construct() {
        $this->_sPath = APPS_PATH . '/layouts/index.phtml';
    }

    public function setLayoutUpdate($sLayoutUpdate) {
        $this->_sPath = APPS_PATH . '/layouts/' . $sLayoutUpdate . '.phtml';
    }

    public function getPath() {
        return $this->_sPath;
    }

    public function getViewContent() {
        return $this->_sViewContent;
    }

    public function setViewContent($sViewContent) {
        $this->_sViewContent = $sViewContent;
    }

    public function setViewVars($sViewVars) {
        $this->_sViewVars = $sViewVars;
    }

    public function getViewVars() {
        return $this->_sViewVars;
    }

    public function getArgs() {
        return $this->_tArgs;
    }

    /*     * *****STATIC-FUNCTION******** */

    public static function setLayout($sLayout) {
        global $oLayout;
        $oLayout->setLayoutUpdate($sLayout);
        return $oLayout;
    }

    public static function renderView($sContent) {
        print $sContent;
    }

    public static function render($sBlock, $var = array()) {
        global $oLayout;
        if (!empty($var))
            extract($var);

        extract($oLayout->getViewVars());
        ob_start();
        include( APPS_PATH . "/layouts/$sBlock.phtml");
        $sHtml = ob_get_contents();
        ob_end_clean();
        print $sHtml;
    }

    public static function getJavascript() {
        global $oLayout;
    }

    public static function getStyleSheet() {
        global $oLayout;
    }

    public static function getTitle() {
        global $oLayout;
        $sController = $oLayout->_tArgs['controllers'];
        $sAction = $oLayout->_tArgs['action'];
        return SEO::getMetaName('title', $sController, $sAction);
    }

    public static function getMeta($MetaName) {
        global $oLayout;
        $sController = $oLayout->_tArgs['controllers'];
        $sAction = $oLayout->_tArgs['action'];
        return "<meta name='$MetaName'  content='" . SEO::getMetaName($MetaName, $sController, $sAction) . "' />";
    }
	
	public static function addCrumbs($crumb=array()){
		global $oLayout;
		$oLayout->_sCrunbs[] = $crumb;
	}
	
	public static function getCrumbs(){
		global $oLayout;
		return $oLayout->_sCrunbs;
	}
}