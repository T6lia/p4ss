<?php

/**
 * Class controllers pour la gestion historique changement de role utilisateur
 * @url http://application.soctam.loc/etatsecurute/...
 * @vue ./apps/modules/default/views/etatsecurute/...
 */
class EtatsecuriteController extends Controller {

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
     * Fonction public pour le rootage vers la liste des utilisateurs
     * @url : http://application.soctam.loc/logroleuser/liste
     */
    public function liste() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(
                array(
                    array('label' => 'Système', 'url' => '#', 'isActive' => false),
                    array('label' => 'Etat sécurite', 'url' => '', 'isActive' => TRUE),
                )
        );

        $oModel = Apps::getModel('User');
        //Initialisation du filtre
        $dataSearch = array('user', 'username', 'first_connect', 'role', 'tache', 'debut', 'fin');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModel->etatSecurite($aRequestSearch);

        $aParam = array(
            'aSecuriy' => $data['data'],
            'pagination' => $data['pagination'], //Paramètre pour la pagination
            'data' => $aRequestSearch,
            'aUser' => $oModel->getUniqueUserByName(),
            'aRole' => Apps::getModel('Role')->getBy(),
            'aTache' => Apps::getModel('Systemes_Link')->getBy()
        );
        $this->getView()->addVar($aParam);
    }

    public function exporter() {
        $this->session->isAutorisedRole();
        $oModel = Apps::getModel('User');

        $dataSearch = array('user', 'username', 'first_connect', 'role', 'tache', 'debut', 'fin');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModel->etatSecurite($aRequestSearch, false);

        $oModel->reporting($data);
        exit();
    }

}
