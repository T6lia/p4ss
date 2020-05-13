<?php

class Session
{

    static function start() {
        session_start();
    }

    static function getNameSpace() {
        return (self::isAdminUser()) ? "admin" : "front";
    }

    static function isAdminUser() {

        if ($tUser = Session::getUser()) {
            return ($tUser['is_admin']);
        }
        return FALSE;
    }

    static function setUser($tUser) {
        $_SESSION['user'] = $tUser;
        //$permissions = $tUser->getPermissions();
        //self::setPermissions($permissions);
    }

    static function getUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : FALSE;
    }

    static function isConnected() {
        return self::getUser();
    }

    static function disconnected() {
        unset($_SESSION['user']);
        session_destroy();
    }

    static function matchAccount($tParams) {

        $oUser = false;
        //@todo : recup�ration des donn�es du table users
        return $oUser;
    }

    static function addMessage($msg, $code) {
        $_SESSION['msg'] = array('message' => $msg, 'code' => $code);
    }

    static function getMessage() {
        if (isset($_SESSION['msg'])) {
            $msg = $_SESSION['msg'];
            unset($_SESSION['msg']);
            return $msg;
        }
        return FALSE;
    }

    static function setPermissions($permissions) {
        $_SESSION['permissions'] = $permissions; //array of module/controllers/action
    }

    static function getPermissions() {
        return $_SESSION['permissions']; //array of module/controllers/action
    }

    static function isAutorisedRole() {
        $occurenceUrlValide = 0;
        $userIfos = self::getUser();
        if (count($userIfos) > 0) {
//			$userModel = new UserModel();
//			$RoleUrls = $userModel->getAuthorisedUrlBiRole($userIfos->role_id);
            $request = new Request();
            $module = $request->getModule();
            $controllerRout = $request->getController();
            $actionRout = $request->getAction();
            if ($module == "default") {
                $segments = strtolower($controllerRout . "/" . $actionRout);
            } else {
                $segments = strtolower($module . "/" . $controllerRout . "/" . $actionRout);
            }
            $acces = Apps::getModel('Systemes_Acces')->checkAutorisation($segments, $userIfos->role_id);
//			foreach ($RoleUrls as $url){
//				if($segments == $url->urls_segment){
//					$occurenceUrlValide = 1;
//				}
//			}
            if (!$acces) {
                /* Session::addMessage('Page "'.$segments .'" non autorisée.', 'danger'); */
                Session::addVar("accessMsg", 'Page "' . $segments . '" non autorisée.');
                header("Location:" . Router::getSiteUrl() . "acces");
                exit();
            }
        } else {
            Session::addMessage('Connectez-vous avant de continuer.', 'danger');
            header("Location:" . Router::getSiteUrl() . "index/login/");
        }
    }

    static function getRolesId() {
        $oRole = new RoleModel();
        return $oRole->getRolesID();
    }

    static function addVar($key, $data) {
        $_SESSION[$key] = $data;
    }

    static function getVar($key, $remove = true) {
        if (isset($_SESSION[$key])) {

            $data = $_SESSION[$key];
            if ($remove) {
                self::removeVar($key);
            }

            return $data;
        }

        return null;
    }

    static function removeVar($key) {
        unset($_SESSION[$key]);
    }

}
