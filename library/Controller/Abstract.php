<?php

abstract class Controller_Abstract
{   
    protected $_oRequest;

    protected $_bRedirected;

    protected $_oView;

    protected $_oLayout;
    

    public function __construct(Request $oRequest, View $oView)
    {
        global $oLayout;

        $this->_oRequest  = $oRequest;
        $this->_oView = $oView;
        $this->_bRedirected = false;
        $this->_oLayout = $oLayout;
    }

    protected function _actionExists($sAction)
    {
        try{
            $oMethod = new ReflectionMethod(get_class($this),$sAction);
            return ($oMethod->isPublic() && !$oMethod->isConstructor());
        }catch (Exception $oE){
            return false;
        }
    }

    public function redirect($sUrl)
    {
        if ($this->_bRedirected == true){
            throw new Exception('Une redirection a été déja demandée');
        }
        $this->_oView->redirect($sUrl);
        $this->_bRedirected = true;
    }

    protected function _render($sFile, $bIsView = true)
    {        
        if($bIsView){
                return $this->_oView->render(APPS_PATH . '/modules/' . $this->_oRequest->getModule() . '/views/' . $sFile . '.phtml') ;
        }else{
            $this->_oView->setBody(
                $this->_oView->render(APPS_PATH . '/layouts/' . $sFile . '.phtml')
            );
        }

    }

    protected function _renderLayout($sFile, $sViewContent)
    {
        $this->_oView->addVar('content', $sViewContent);
        $this->_oView->setBody(
                $this->_oView->render( $sFile )
        );
    }

    public function __get($sParam)
    {
        return $this->_oView->getVar($sParam);
    }

    public function __set($sName,$sParam)
    {
        $this->_oView->addVar($sName, $sParam);
    }

    public function getView()
    {
        return $this->_oView;
    }

    public function getRequest()
    {
        return $this->_oRequest;
    }
}