<?php

class IndexController extends Controller {

    /**
     * Fonction public pour le rootage.
     * @url : http://application.soctam.loc/index.php
     */
    public function index() {

        $session = new Session();
        $dataSession = $session->getUser();
        $aParams = [
            'user' => $dataSession
        ];
        $this->getView()->addVar($aParams);
    }

    public function login() {

        Layout::setLayout('login');
        $request = Request::getInstance();
        /**@var UserModel $oModel */
        $oModel = Apps::getModel('User');

        if ($request->isPost()) {

            $post = $request->getPost();
            $checkUser = $oModel->checkIfUserExist($post['username']);

            if ($checkUser) {

                Session::addMessage("Nom d'utilisateur: " .$post['username']. " existe déja! veuillez créer une autre", 'danger');
                $this->getView()->redirect('/inscription');

            } else {

                $data = [
                    'role_id' => 15,
                    'username' => $post['username'],
                    'password' => $post['password'],
                    'lastname' => '',
                    'firstname' => '',
                    'user_site_code' => ''
                ];
                $oModel->initializeUser($data);
                $userInfo = $oModel->getUserLogin($post['username'], $post['password']);
                unset($oModel->password);
                $session = new Session();
                $session->setUser($userInfo);

                $this->getView()->redirect('/roles/role/liste');
            }
        }

        $this->getView()->addVar('message', Session::getMessage());
    }

    public function connect() {

        $this->setNoRender();
        if (Request::getInstance()->isPost()) {
            $post = Request::getInstance()->getPost();
            $oUser = Apps::getModel('User');

            if ('connexion' == $post['hidden']) {

                $userIfos = $oUser->getUserLogin($post['username'], $post['password']);

                if ($userIfos != FALSE) {
                    if (!is_null($userIfos->is_disabled)) {
                        Session::addMessage('L\'utilisateur est mis en veille!', 'danger');
                        $this->getView()->redirect('/index/login/');
                    }
                    if ($userIfos->first_connect == null) {
                        $oUser->updateBy(array('first_connect' => date('Y-m-d H:i:s')), array('id' => $userIfos->id));
                    }
                    unset($userIfos->password);
                    $session = new Session();
                    $session->setUser($userIfos);
                    $URL = '/index';
                    $this->getView()->redirect($URL);
                } else {
                    Session::addMessage('Données utilisateur invalides', 'danger');
                    $URL = '/index/login/';
                    $this->getView()->redirect($URL);
                }
            } else {


                $data = [
                    'role_id' => 15,
                    'username' => $post['username'],
                    'password' => $post['password'],
                    'lastname' => '',
                    'firstname' => '',
                    'user_site_code' => ''
                ];

                $checkUser = $oUser->checkIfUserExist($post['username']);

                if ($checkUser) {

                    Session::addMessage("Nom d'utilisateur: " .$post['username']. " existe déja! veuillez créer une autre", 'danger');
                    $this->getView()->redirect('/index');

                } else {

                    $oUser->initializeUser($data);
                    Session::addMessage('Bienvenue sur passvola! Votre compte a été bien enregistrer! Veuillez se connecter', 'success');
                    $URL = '/index';
                    $this->getView()->redirect($URL);

                }
            }
        }
    }

    public function logout() {
        $this->setNoRender();
        $session = new Session();
        $session->disconnected();
        Session::addMessage('Vous venez de vous déconnecter', 'success');
        $URL = '/index/login/';
        $this->getView()->redirect($URL);
    }


}
