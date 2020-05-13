<?php
abstract class Request_Abstract
{
    protected $_sRequestMethod;

    protected $_sModule;

    protected $_sController;

    protected $_sAction;

    protected $_tArgs;

    protected $_isPrematuredSessionEnd = FALSE;

    public function  __construct()
    {
        $this->_sRequestMethod = $_SERVER['REQUEST_METHOD'];
    }

    public static function setParam($sKey, $sValue)
    {
        $_REQUEST[$sKey] = $sValue;
    }

    public function getRequestParams()
    {
        return filter_var_array($this->getTaintedParams(), FILTER_SANITIZE_STRING);
    }

    public function getTaintedParams()
    {
        return $_REQUEST;
    }

    public function getRequestParam($sKey)
    {
        if( is_array($this->getTaintedParam($sKey)) ){
            return filter_var_array($this->getTaintedParam($sKey), FILTER_SANITIZE_STRING);
        }else {
            return filter_var($this->getTaintedParam($sKey), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
    }

    public function getTaintedParam($sKey)
    {
        return isset($_REQUEST[$sKey]) ? $_REQUEST[$sKey] : FALSE ;
    }

    public function getController()
    {
        return ucfirst($this->_sController);
    }

    public function getAction()
    {
        return ucfirst($this->_sAction);
    }

    public function getModule()
    {
        return $this->_sModule;
    }

    public function setController($sValue)
    {
        $this->_sController = $sValue;
        return $this;
    }

    public function setAction($sValue)
    {
        $this->_sAction = $sValue;
        return $this;
    }

    public function setModule($sValue)
    {
        $this->_sModule = $sValue;
        return $this;
    }

    public function getParams()
    {
        return $this->_tArgs;
    }

    public function getParam( $sKey )
    {
        return isset($this->_tArgs[$sKey]) ? $this->_tArgs[$sKey] : NULL;
    }

    public function setPrematuredSessionEnd()
    {
        $this->_isPrematuredSessionEnd = TRUE ;
        return $this;
    }

    public function isPrematuredSessionEnd()
    {
        return $this->_isPrematuredSessionEnd;
    }
}
