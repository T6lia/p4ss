<?php

/**
 * Class controllers pour la gestion historique changement de role utilisateur
 * @url http://application.soctam.loc/logroleuser/...
 * @vue ./apps/modules/default/views/logroleuser/...
 */
class LogroleuserController extends Controller {

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
                    array('label' => 'Historique changement de rôle', 'url' => '', 'isActive' => TRUE),
                )
        );

        $oModele = Apps::getModel('Systemes_LogRoleUser');
        //Initialisation du filtre
        $dataSearch = array('lastname', 'firstname', 'username', 'debut', 'fin', 'type');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModele->findAll($aRequestSearch);

        $aParam = array(
            'aLog' => $data['data'],
            'pagination' => $data['pagination'], //Paramètre pour la pagination
            'aData' => $aRequestSearch,
            'roles' => Apps::getModel('Role')->getRoles()
        );
        $this->getView()->addVar($aParam);
    }

    public function exporter() {
        $this->session->isAutorisedRole();
        $oModel = Apps::getModel('Systemes_LogRoleUser');
        $dataSearch = array('lastname', 'firstname', 'username', 'debut', 'fin', 'type');
        $aRequestSearch = array();
        foreach ($dataSearch as $column)
            $aRequestSearch[$column] = Commons::getRequestParameter($column);

        $data = $oModel->findAll($aRequestSearch, false);
        $oModel->reporting($data);
        exit();
    }

}
