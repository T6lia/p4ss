<?php

/**
 * Class controllers pour la gestion des utilisateurs(CRUD)
 * @url http://application.soctam.loc/user/...
 * @vue ./apps/modules/default/views/user/...
 */
class UserController extends Controller
{

    public function __construct(\Request $oRequest, \View $oView)
    {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->user = $this->session->getUser();
    }

    public function index()
    {
        die('No access here.....!');
    }

    /**
     * Fonction public pour le rootage vers la liste des utilisateurs
     * @url : http://application.soctam.loc/user/liste
     */
    public function liste()
    {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------

        Layout::addCrumbs(
            array(
                array('label' => 'Système', 'url' => '#', 'isActive' => false),
                array('label' => 'Utilisateurs', 'url' => '', 'isActive' => TRUE),
            )
        );

        $oModele = Apps::getModel('User');
        //Initialisation du filtre
        $dataSearch = array('role_id');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModele->findAll($aRequestSearch);
        $aParam = array(
            'users' => $data['data'],
            'pagination' => $data['pagination'], //Paramètre pour la pagination
            'data' => $aRequestSearch,
            'roles' => Apps::getModel('Role')->getRoles(),
            'bAuthStatut' => Apps::getModel('Systemes_Acces')->checkAutorisation('systemes/user/statut', $this->user->role_id)
        );
        $this->getView()->addVar($aParam);
    }

    /**
     * Fonction public pour le rootage vers le formulair de création des utilisateur
     * @url : http://application.soctam.loc/user/create
     */
    public function create() {

        Layout::addCrumbs(
            array(
                array('label' => 'Système', 'url' => '#', 'isActive' => false),
                array('label' => 'Utilisateurs', 'url' => '/systemes/user/liste', 'isActive' => false),
                array('label' => 'Création', 'url' => '/systemes/user/create', 'isActive' => true)
            )
        );

        $request = Request::getInstance();
        /**@var Affilie_AffilieModel $oAffilie */
        $oAffilie = Apps::getModel('Affilie_Affilie');

        /**@var UserModel $oUser */
        $oUser = Apps::getModel('User');

        /**@var Roles_RolesModel $oRoles */
        $oRoles = Apps::getModel('Roles_Roles');

        if ($request->isPost()) {

            $post = $request->getPost();

            $data = [
                'role_id' => 15,
                'username' => $post['username'],
                'password' => $post['password'],
                'lastname' => '',
                'firstname' => '',
                'user_site_code' => ''
            ];

            $checkUser = $oUser->checkIfUserExist($post['username']);

            if (!$checkUser) {

                $oUser->initializeUser($data);
            }

            $userInfo = $oUser->getBy(['username' => $post['username']]);
            if ($post['parent_username'] == "") {

                $oAffilie->savePackForParent($post);
                $tree = [
                    'id_parent' => null,
                    'id' => $userInfo[0]['id'],
                    'username_parent' => null,
                    'username' => $post['username']
                ];

                $oAffilie->saveTree($tree);
            } else {

                $tree = [
                    'id_parent' => $post['parent_id'],
                    'id' => $userInfo[0]['id'],
                    'username_parent' => $post['parent_username'],
                    'username' => $post['username']
                ];

                $info = [
                    'id_appli' => $userInfo[0]['id'],
                    'username_appli' => $post['username'],
                    'parent_id' => $post['parent_id'],
                    'parent_username' => $post['parent_username'],
                    'nom' => $post['nom'],
                    'prenom' => $post['prenom'],
                    'mail' => $post['mail'],
                    'telephone' => $post['telephone'],
                    'adresse' => $post['adresse'],
                    'identite' => $post['identite'],
                    'pack' => $post['pack'],
                    'confirme_check' => 'validee',
                    'date_confirmation' => date('Y-m-d H:i:s')
                ];

                $oAffilie->saveTree($tree);
                $oRoles->createFromAdmin($info);
            }

            $this->getView()->redirect('/systemes/profil/liste');
        }

    }

    // 1jout des données apres
    public function save()
    {

        //-----------fin----------
        $message = ['Paramètres erronés', 'danger'];
        //Vérification si une formulaire de type POST est soumise
        if (Request::getInstance()->isPost()) {
            //Récupération de la post

            $post = Request::getInstance()->getPost();
            $oRole = Apps::getModel('Role');
            $roleId = $oRole->getRole_idByCode($post['role_code']);
            if ($roleId->id != '' && $roleId->id != NULL) {
                $oUser = Apps::getModel('User');
                unset($post['passwordconfirmation']);
                unset($post['role_code']);
                $post['lastname'] = strtoupper($post['lastname']);
                $post['role_id'] = (int)$roleId->id;
                //Si modification
                $id = 0;
                if (isset($post['id'])) {
                    //Si il y a un changement de rôle => historisé
                    if ($post['last_role'] != $post['role_id']) {
                        Apps::getModel('Systemes_LogRoleUser')->insertOne(array(
                            'user' => $post['id'],
                            'old_role' => $post['last_role'],
                            'new_role' => $post['role_id'],
                            'updated_at' => date('Y-m-d h:i:s'),
                            'resolution' => $post['resolution'],
                            'type' => 'Modification rôle',
                            'motif' => $post['motif']
                        ));
                    }
                    $id = $oUser->update($post);
                    $message = ['Modifications réussies', 'success'];
                } else {
                    if (!$oUser->getBy(array('username' => $post['username']), true)) {
                        $compteExist = $oUser->getBy(array('lastname' => $post['lastname'], 'firstname' => $post['firstname']), true);
                        $id = $oUser->create($post);
                        Apps::getModel('Systemes_LogRoleUser')->insertOne(array(
                            'user' => $id,
                            'new_role' => $post['role_id'],
                            'updated_at' => date('Y-m-d h:i:s'),
                            'type' => $compteExist === false ? 'Création' : 'Ajout rôle'
                        ));
                        $message = ['Ajout réussi', 'success'];
                    } else {
                        $message = ['Nom utilisateur existant!', 'danger'];
                    }
                }
                if (!empty($message)) {
                    Session::addMessage($message[0], $message[1]);
                }
                if (isset($post['hidden'])) {
                    if ($post['hidden'] == 'add')
                        $this->getView()->redirect('/systemes/user/create');
                    elseif ($post['hidden'] == 'save' || $post['hidden'] == '')
                        $this->getView()->redirect('/systemes/user/liste');
                } else {
                    $this->getView()->redirect('/systemes/user/liste');
                }
            }
        }
    }

    /**
     * Fonction public pour le rootage vers le formulair de modification des utilisateur
     * @url : http://application.soctam.loc/user/edit
     */
    public function edit()
    {
        $session = new Session();
        $session->isAutorisedRole();
        Layout::addCrumbs(
            array(
                array('label' => 'Système', 'url' => '#', 'isActive' => false),
                array('label' => 'Utilisateurs', 'url' => '/systemes/user/liste', 'isActive' => false),
                array('label' => 'Modification', 'url' => '/systemes/user/edit', 'isActive' => true)
            )
        );

        $id = $this->getRequest()->getParam('id');
        $oRole = Apps::getModel('Role');
        $roles = $oRole->getRoles();

        if ($id !== '') {
            $oUser = Apps::getModel('User');
            $user = $oUser->getById($id);
            $user->password = Apps::decryptIt($user->password);
            $this->getView()->addVar('datas', ['user' => $user, 'roles' => $roles]);
        }
    }

    /**
     * Fonction public pour le rootage vers le formulair de suppression d'un utilisateur
     * @url : http://application.soctam.loc/user/delete
     */
    public function delete()
    {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        //Récuperation de la valeur get
        $request = $this->getRequest();
        $id = $request->getParam('id');

        if ($request->isPost()) {

            if ($id !== '') {
                $oUser = Apps::getModel('User');

                $user = $oUser->getBy(array('id' => $id), true);

                if (!is_null($user['first_connect'])) {
                    Session::addMessage('Cette utilisateur ne peut pas être supprimée', 'warning');
                    $this->getView()->redirect('/systemes/user/liste');
                }

                if ($oUser->isAdmin($id)) {
                    Session::addMessage('Un administrateur ne doit pas être supprimé', 'warning');
                    $this->getView()->redirect('/systemes/user/liste');
                    return;
                }
                $isDeleted = $oUser->remoove($id);
                if ($isDeleted) {
                    Apps::getModel('Systemes_LogRoleUser')->removeBy(array('user' => $id));
                    Session::addMessage('Compte supprimé', 'success');
                    $this->getView()->redirect('/systemes/user/liste');
                } else {
                    Session::addMessage('Une érreur est survénue lors de la suppréssion', 'warning');
                    $this->getView()->redirect('/systemes/user/liste');
                }
            }
        }
        Layout::setLayout('ajax');
        $this->getView()->addVar([
            'action' => '/systemes/user/delete/id/' . $id,
        ]);
    }

    /**
     * Fonction public pour le rootage vers le formulair de suppression d'un utilisateur
     * @url : http://application.soctam.loc/user/delete
     */
    public function statut()
    {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        //Récuperation de la valeur get
        $request = $this->getRequest();
        $id = $request->getParam('id');

        $oModel = Apps::getModel('User');
        $oUser = $oModel->getBy(array('id' => $id), true);

        if ($oUser) {
            if ($request->isPost()) {
                $disabled = is_null($oUser['is_disabled']) ? date('Y-m-d H:i:s') : null;
                $oModel->updateBy(array('is_disabled' => $disabled), array('id' => $id));
                Session::addMessage('Statut changée!', 'success');
                $this->getView()->redirect('/systemes/user/liste');
            }
        } else {
            Session::addMessage('L\'utilisateur n\'existe pas !', 'warning');
        }

        Layout::setLayout('ajax');
        $this->getView()->addVar([
            'user' => $oUser,
            'action' => '/systemes/user/statut/id/' . $id,
        ]);
    }


}
