<?php

/**
 * Class controllers pour la gestion des liens et permission de l'application
 * @url http://xxx/systemes/link/...
 * @vue ./apps/modules/systemes/views/link/...
 */
class LinkController extends Controller {

    private $session;

    /**
     * Verification permission pour l'accès au controlleur
     */
    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
    }

    public function index() {
        die('No access here.....!');
    }

    /**
     * Fonction public pour le rootage vers la liste des liens
     * @url : http://xxx/systemes/link/liste
     */
    public function liste() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Gestion des liens', 'url' => '/systemes/link/liste', 'isActive' => true),
        ));

        /** @var Systemes_LinkModel $oModel */
        $oModel = Apps::getModel('Systemes_Link');

        $dataSearch = array('urls_segment', 'titre');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModel->findAll($aRequestSearch);

        $aParam = array(
            'aLinks' => $data['data'],
            'pagination' => $data['pagination'],
            'aData' => $aRequestSearch
        );
        $this->getView()->addVar($aParam);
    }

    /**
     * Fonction public pour le rootage vers le formulaire de création de lien
     * @url : http://xxx/systemes/link/create
     */
    public function create() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Gestion des liens', 'url' => '/systemes/link/liste', 'isActive' => false),
            array('label' => 'Création', 'url' => '/systemes/link/create', 'isActive' => true)
        ));

        if (Request::getInstance()->isPost()) {
            $aPost = Request::getInstance()->getPost();
            $oLinkModel = Apps::getModel('Systemes_Link');
            if (isset($aPost['id'])) {
                $oLinkModel->update($aPost);
            } else {
                $oLinkModel->create($aPost);
            }
            Session::addMessage('Succès !', 'success');
            if (isset($aPost['hidden'])) {
                if ($aPost['hidden'] == 'add') {
                    $this->getView()->redirect('/systemes/link/create');
                } elseif ($aPost['hidden'] == 'save') {
                    $this->getView()->redirect('/systemes/link/liste');
                }
            } else {
                $this->getView()->redirect('/systemes/link/liste');
            }
        }
        $aParam = array(
            'aMenu' => Apps::getModel('Systemes_Menu')->getMenuParent(),
        );
        $this->getView()->addVar($aParam);
    }

    /**
     * Fonction public pour le rootage vers le formulaire de modification de lien
     * @url :http://xxx/systemes/link/edit/id/xx
     */
    public function edit() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Gestion des liens', 'url' => '/systemes/link/liste', 'isActive' => false),
            array('label' => 'Edition', 'url' => '/systemes/link/edit', 'isActive' => true)
        ));

        $iId = $this->getRequest()->getParam('id');
        $oLinkModel = Apps::getModel('Systemes_Link');
        $aData = $oLinkModel->find($iId);
        $this->getView()->addVar(array(
            'aData' => $aData,
            'aMenu' => Apps::getModel('Systemes_Menu')->getMenuParent(),
            'aSubMenu' => Apps::getModel('Systemes_Menu')->getSubMenu($aData['parent'])
        ));
    }

    /**
     * Fonction public pour le rootage vers la suppression d'un utilisateur
     * @url : http://xxx/systemes/link/delete/id/xx
     */
    public function delete() {
        $this->session->isAutorisedRole();
        $iId = $this->getRequest()->getParam('id');
        $sRedirect = $this->getRequest()->getParam('redirect');
        if ($iId !== '') {
            $oLinkModel = Apps::getModel('Systemes_Link');
            $oLinkModel->remove($iId);
            Apps::getModel('Systemes_Acces')->removeByLink($iId);
            Session::addMessage('Succès !', 'success');
            $this->getView()->redirect('/systemes/link/liste/' . $sRedirect);
        }
    }

    /**
     * Fonction public pour le rootage vers le formulaire d'ajout de permission pour un lien
     * Fonction sans layout utiliser dans un modal
     * @url : http://xxx/systemes/link/edit/id/xx
     */
    public function loadpermission() {
        $iId = $this->getRequest()->getParam('id');
        $redirect = $this->getRequest()->getParam('redirect');
        Layout::setLayout('ajax');
        if ($iId !== '') {
            $aLink = Apps::getModel('Systemes_Link')->getBy(array('id' => $iId), TRUE);
            $aRolePermission = Apps::getModel('Role')->getRoleWithPermission($iId);

            $aParam = array(
                'aLink' => $aLink,
                'aPermission' => $aRolePermission,
                'sRedirect' => $redirect
            );
            $this->getView()->addVar($aParam);
        }
    }

    /*
     * Action de sauvegarde de permission d'un lien
     * @url : http://xxx/systemes/link/savepermission
     */

    public function savepermission() {
        $sRedirect = "";
        $oRequest = Request::getInstance();
        if ($oRequest->isPost()) {
            $aData = $oRequest->getPost();
            $iLink = $aData['link'];
            $sRedirect = $aData['redirect'];
            unset($aData['link']);
            unset($aData['redirect']);

            /** @var Systemes_AccesModel $oAccesModel */
            $oAccesModel = Apps::getModel('Systemes_Acces');
            $oAccesModel->removeByLink($iLink);
            $oAccesModel->insert($aData, $iLink);
            Session::addMessage('Succès !', 'success');
        }
        $this->getView()->redirect('/systemes/link/liste/' . $sRedirect);
    }

}
