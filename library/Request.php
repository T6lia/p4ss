<?php
class Request extends Request_Abstract
{   
    protected static $_oInstance;

    public static function getInstance()
    {
        if (null === self::$_oInstance) {
            self::$_oInstance = new self();
        }
        return self::$_oInstance;
    }
    
    public function  __construct()
    {
        if(Router::useRewriteEngine())
        {
            Router::parse();
        }

        $this->_initialize();
        $this->_tArgs = $this->getRequestParam('args');
    }
    
    private function _initialize()
    {
        $this->_sModule = ( $this->getRequestParam('module') ) ?
                ($this->getRequestParam('module')) : ('default') ;
        $this->_sController = ( $this->getRequestParam('controllers') ) ?
                ($this->getRequestParam('controllers')) : ('index') ;
        $this->_sAction = ( $this->getRequestParam('action') ) ?
                ($this->getRequestParam('action')) : ('index') ;
    }

    /**
     * @param string, int $sVariable
     * @return mixed
     */
    public function getPost($sKey = null)
    {
        if (isset($sKey)) {
            return isset($_POST[$sKey]) ? $_POST[$sKey] : null;
        } else {
            return $_POST;
        }
    }

    public function isPost()
    {
        return count($_POST);
    }

    public static function getUrl($params = array())
    {
        $module = ($params['module'] == 'default') ? '' : '/' . $params['module'];
        $controller = $params['controller'];
        $action = $params['action'];

        return "$module/$controller/$action";
    }

    public static function isCurrentModule($module)
    {
        $request = self::getInstance();
        return (strtolower(Commons::slugify($module)) == strtolower($request->getModule()));
    }


    public static function isCurrentController($link = array())
    {
        $request = self::getInstance();
        $module = strtolower($request->getModule());
        $controller = strtolower($request->getController());

        return ($module == $link['module'] && $controller == $link['controller']);
    }

    public static function isCurrentAction($link = array())
    {
        $request = self::getInstance();
        $module = strtolower($request->getModule());
        $controller = strtolower($request->getController());
        $action = strtolower($request->getAction());

        return ($module == $link['module'] && $controller == $link['controller'] && $action == $link['action']);
    }
}