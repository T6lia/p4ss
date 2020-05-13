<?php
class Acl
{
    private $db;

    private $userEmpty = false;

    //initialize the database object here
    function __construct()
    {
        $this->permissions = Session::getPermissions();
    }

    function checkPermission($oRequest)
    {
        $module = $oRequest->getModule();
        $controller = $oRequest->getController();
        $action = $oRequest->getAction();

        return in_array("$module/$controller/$action", $this->permissions);
    }
}