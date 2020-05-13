<?php


class RoleController extends Controller
{
    private $session;

    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->user = $this->session->getUser();
    }

    public function liste() {

        Layout::addCrumbs(
            array(
                array('label' => 'Profil utilisateurs', 'url' => '#', 'isActive' => false),
                array('label' => 'Inscription', 'url' => '/systemes/user/liste', 'isActive' => true),
            )
        );

        Layout::setLayout('inscription');

        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        /** @var Systemes_PackModel $oModelPack */
        $oModelPack = Apps::getModel('Systemes_Pack');

        $this->session = new Session();
        $user = $this->session->getUser();

        $packRole = $oModel->getPackRoles();
        $pack = $oModelPack->getAllPack();

        if (Request::getInstance()->isPost()) {
            $post = Request::getInstance()->getPost();
            $username = $oModel->getBy(['username_appli' => $post['username_appli']]);
            if ('' != $username) {
                $oModel->create($post);
                Session::addMessage('Vous êtes inscrite sur le réseaux passvola', 'success');
                $this->getView()->redirect('/roles/role/confirmation');

            } else {

                Session::addMessage('Vous etes déja sur un réseaux', 'danger');
                $this->getView()->redirect('/roles/role/liste');
            }
        }
        /**
         * verifie si l'utilisateur est déja inscrite alors on affiche sur la liste
         * On affiche ensuite les info nécessaire sur la liste
         */
        $userInList = $oModel->getBy(['username_appli' => $user->username]);

        $tParams = [
            'packRoles' => $packRole,
            'packs' => $pack,
            'user' => $user,
            'info_user' => $userInList,
            'check' => $oModel->getIfCheck($user->id)
        ];

        $this->getView()->addVar($tParams);
    }

    /**
     * Call back creation parrain
     */
    public function createParrain() {

        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        $request = $this->getRequest();

        if ($request->isPost()) {

            $post = $request->getPost();
            $username = $oModel->getBy(['username_appli' => $post['username_appli']]);

            if (empty($username)) {
                $oModel->createFirst($post);
                Session::addMessage('Vous êtes inscrite sur le réseaux passvola', 'success');
                $this->getView()->redirect('/roles/role/choixpack');
            } else {

                Session::addMessage('Vous etes déja sur un réseaux', 'danger');
                $this->getView()->redirect('/roles/role/liste');
            }
        }
    }

    public function choixpack() {
        Layout::addCrumbs(
            array(
                array('label' => 'Profil utilisateurs', 'url' => '#', 'isActive' => false),
                array('label' => 'Inscription', 'url' => '/systemes/user/liste', 'isActive' => true),
            )
        );
        Layout::setLayout('inscription');
        $this->session = new Session();
        $user = $this->session->getUser();
        $request = $this->getRequest();

        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');

        /**@var Affilie_AffilieModel $oAffilie */
        $oAffilie = Apps::getModel('Affilie_Affilie');

        /** @var Systemes_PackModel $oModelPack */
        $oModelPack = Apps::getModel('Systemes_Pack');

        $pack = $oModelPack->getAllPack();
        //get role_passvola to find pack
        $role = $oModel->getRoles($user->id);
        $packUser = $oModelPack->getPackByName($role['pack']);

        if ($request->isPost()) {

            $post = $request->getPost();

            if ($post['username_parent'] == "") {

                $oAffilie->savePackForParent($post);
                $tree = [
                    'id_parent' => null,
                    'id' => $post['id'],
                    'username_parent' => null,
                    'username' => $post['username'],
                ];
                $oAffilie->saveTree($tree);

            } else {

                $tree = [
                    'id_parent' => $post['id_parent'],
                    'id' => $post['id'],
                    'username_parent' => $post['username_parent'],
                    'username' => $post['username']
                ];

                $oAffilie->saveTree($tree);
            }

            Session::addMessage('Vous êtes inscrite sur le réseaux passvola', 'success');
            $this->getView()->redirect('/roles/role/info');
        }

        $aParams = [
            'packs' => $pack,
            'roles' => $role,
            'packUser' => $packUser
        ];
        //Commons::dump($aParams); die;
        $this->getView()->addVar($aParams);
        
    }

    public function info() {
        Layout::addCrumbs(
            array(
                array('label' => 'Profil utilisateurs', 'url' => '#', 'isActive' => false),
                array('label' => 'Inscription', 'url' => '/systemes/user/liste', 'isActive' => true),
            )
        );

        Layout::setLayout('inscription');
        $this->session = new Session();
        $user = $this->session->getUser();
        $request = $this->getRequest();
        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');

        if ($request->isPost()) {

            $post = $request->getPost();

            $data = [
                'id_appli' => $user->id,
                'username_appli' => $user->username,
                'nom' => $post['nom'],
                'prenom' => $post['prenom'],
                'mail' => $post['mail'],
                'telephone' => $post['telephone'],
                'adresse' => $post['adresse'],
                'identite' => $post['identite']
            ];

            $oModel->createThird($data);
            Session::addMessage('Vous êtes inscrite sur le réseaux passvola', 'success');
            $this->getView()->redirect('/roles/role/confirmation');
        }

    }

    /**
     * @url: /roles/role/confirmation
     * fonction pour confirmer utilisateur après avoir payer par Mvola
     */
    public function confirmation() {

        Layout::addCrumbs(
            array(
                array('label' => 'Rôles utilisateurs', 'url' => '#', 'isActive' => false),
                array('label' => 'Confirmation', 'url' => '/roles/role/confirmation', 'isActive' => true),
            )
        );

        Layout::setLayout('inscription');
        $this->session = new Session();
        $user = $this->session->getUser();
        /**@var Roles_RolesModel $oRoles */
        $oRoles = Apps::getModel('Roles_Roles');
        /**@var Systemes_PackModel $oPack */
        $oPack = Apps::getModel('Systemes_Pack');
        /**@var PhonePayement_NumeroModel $oNumero */
        $oNumero = Apps::getModel('PhonePayement_Numero');

        $userPack = $oRoles->getPackByUserName($user->username);
        $prixPack = $oPack->getPackByName($userPack['pack']);
        $numeroTelma = $oNumero->getBy(['operateur' => 'Telma']);

        $aParams = [
            'infopack' => $prixPack,
            'mVola' => $numeroTelma[0],
        ];

        $this->getView()->addVar($aParams);
    }

    public function attente() {

        Layout::setLayout('inscription');
        $this->session = new Session();
        $user = $this->session->getUser();

        $request = Request::getInstance();

        if ($request->isPost()) {

            $post = $request->getPost();

            /**@var Roles_RolesModel $oModel */
            $oModel = Apps::getModel('Roles_Roles');
            $userInfo = $oModel->getById($post['id']);

            if ($userInfo['confirme_check'] == '') {

                /*Session::addMessage("Vous n'êtes pas encore validé, veuillez attendre le sms de confirmation", 'danger');
                $this->getView()->redirect('/roles/role/attente');*/

                $this->setNoRender();
                $session = new Session();
                $session->disconnected();
                Session::addMessage('Veuillez se connecter', 'success');
                $URL = '/index/login';
                $this->getView()->redirect($URL);
            }
        }

        $aParams = [
            'user' => $user
        ];
        $this->getView()->addVar($aParams);

    }

    public function profil() {

        $this->session = new Session();
        $user = $this->session->getUser();
        $id = $user->id;

        /**@var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        $data = $oModel->getById($user->id);

        /** @var Comptabilite_ComptabiliteModel $oCompta */
        $oCompta = Apps::getModel('Comptabilite_Comptabilite');

        /** @var Systemes_PackModel $oPack */
        $oPack = Apps::getModel('Systemes_Pack');
        $pack = $oPack->getPackByName($data['pack']);

        $affilie = $oCompta->debitDirect($id);
        ($affilie == "")? $affilie = []: $affilie = $oCompta->debitDirect($id);
        //Commons::dump($affilie); die;

        $affilieMax = $pack['largeur'];
        $affilieUser = (empty($affilie))? 0 : $affilie['direct'];
        $affilieRestant = $affilieMax - $affilieUser;

        $aParams = [
            'data' => $data,
            'user' => $user,
            'pack' => $pack,
            'affilieMax' => $affilieMax,
            'affilieUser' => $affilieUser,
            'affilieRestant' => $affilieRestant,
        ];
        //Commons::dump($aParams); die;
        $this->getView()->addVar($aParams);
    }

    public function modifier() {

        $id = $this->getRequest()->getParam('id');
        /**@var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        $data = $oModel->getDetailById($id);

        $request = Request::getInstance();

        if ($request->isPost()) {

            $post = $request->getPost();
            $oModel->updateBy($post, ['id' => $post['id'], 'id_appli' => $post['id_appli']]);
            Session::addMessage('Vous êtes inscrite sur le réseaux passvola', 'success');
            $this->getView()->redirect('/roles/role/profil');
        }

        $aParams = [
            'data' => $data,
        ];
        $this->getView()->addVar($aParams);


    }

}