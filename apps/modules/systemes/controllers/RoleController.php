<?php

/**
 * Class controllers pour la gestion des role(CRUD)
 * @url http://application.soctam.loc/role/...
 * @vue ./apps/modules/default/views/role/...
 */
class RoleController extends Controller
{

    public function index() {
        die('No access here.....!');
    }

    /**
     * Fonction public pour le rootage vers la liste des roles
     * @url : http://application.soctam.loc/role/liste
     */
    public function liste() {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        Layout::addCrumbs(
                array(
                    array('label' => 'Système', 'url' => '#', 'isActive' => false),
                    array('label' => 'Rôles', 'url' => '/systemes/role/liste', 'isActive' => true),
                )
        );

        $oModel = Apps::getModel('Role');
        //Recupération des articles avec pagination
        $aColumns = array('code' => '=', 'role_name' => '=');
        $oFilter = Apps::usePlugin('FilterData');
        $aRequestSearch = $oFilter->initialize($oModel, $aColumns, false);
        $aRole = $oFilter->getData();
        $aPagination = $oFilter->getPagination();

        $aParam = array(
            'roles' => $aRole,
            'pagination' => $aPagination
        );
        $this->getView()->addVar($aParam);
    }

    /**
     * Fonction public pour le rootage vers le formulair de création des utilisateur
     * @url : http://application.soctam.loc/user/create
     */
    public function create() {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        Layout::addCrumbs(
                array(
                    array('label' => 'Système', 'url' => '#', 'isActive' => false),
                    array('label' => 'Rôles', 'url' => '/systemes/role/liste', 'isActive' => false),
                    array('label' => 'Création', 'url' => '/systemes/role/create', 'isActive' => true)
                )
        );
    }

    // 1jout des données apres
    public function save() {
     
        $message = ['Paramètres erronés', 'danger'];
        //-----------fin----------
        //Vérification si une formulaire de type POST est soumise
        if (Request::getInstance()->isPost()) {
            //Récupération de la post
            $post = Request::getInstance()->getPost();
            $oRole = Apps::getModel('Role');
            //Si modification
            $id = 0;
            if (isset($post['id'])) {
                $id = $oRole->update($post);
                $message = ['Modifications réussies', 'success'];
            } else {
                if (!$oRole->getBy(array('code' => $post['code']), true)) {
                    $id = $oRole->create($post);
                    $message = ['Ajout réussi', 'success'];
                } else {
                    $message = ['Rôle existant!', 'danger'];
                }
            }

            if (!empty($message)) {
                Session::addMessage($message[0], $message[1]);
            }
            if (isset($post['hidden'])) {
                if ($post['hidden'] == 'add') {
                    $this->getView()->redirect('/systemes/role/create');
                } elseif ($post['hidden'] == 'save') {
                    $this->getView()->redirect('/systemes/role/liste');
                }
            } else {
                $this->getView()->redirect('/systemes/role/liste');
            }
        }
    }

    /**
     * Fonction public pour le rootage vers le formulair de modification des utilisateur
     * @url : http://application.soctam.loc/user/edit
     */
    public function edit() {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        Layout::addCrumbs(
                array(
                    array('label' => 'Système', 'url' => '#', 'isActive' => false),
                    array('label' => 'Rôles', 'url' => '/systemes/role/liste', 'isActive' => false),
                    array('label' => 'Modification', 'url' => '/systemes/role/edit', 'isActive' => true)
                )
        );

        $id = $this->getRequest()->getParam('id');
        $oRole = Apps::getModel('Role');
        $role = $oRole->getRoleById($id);

        $this->getView()->addVar('role', $role);
    }

    /**
     * Fonction public pour le rootage vers le formulair de suppression d'un utilisateur
     * @url : http://application.soctam.loc/user/delete
     */
    public function delete() {
        /**
         * Vérification de la session et du role
         */
        $session = new Session();
        $session->isAutorisedRole();
        //-----------fin----------
        $message = ['Une erreur est survenue', 'danger'];
        //Récuperation de la valeur get
        $id = $this->getRequest()->getParam('id');
        if ($id !== '') {
            $oUser = Apps::getModel('Role');
            $isDeleted = $oUser->remoove($id);
            if ($isDeleted) {
                $message = ['Suppression réussie', 'success'];
            }
            Session::addMessage($message[0], $message[1]);
            $this->getView()->redirect('/systemes/role/liste');
        }
    }

    public function getroleattachment() {
        $idRole = $this->getRequest()->getParam('id_role');
        print(Apps::getModel('User')->countUserBiRole($idRole));
        $this->setNoRender();
    }

}
