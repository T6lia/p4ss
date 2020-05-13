<?php

/**
 * Class controllers pour la gestion des liens et permission de l'application
 * @url http://xxx/systemes/link/...
 * @vue ./apps/modules/systemes/views/link/...
 */
class MenuController extends Controller {

    private $session;

    /**
     * Verification permission pour l'accès au controlleur
     */
    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->user = $this->session->getUser();
    }

    public function index() {
        die('No access here.....!');
    }

    /**
     * Fonction public pour le rootage vers la liste des liens
     * @url : http://xxx/systemes/menu/liste
     */
    public function liste() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Gestion des menus', 'url' => '/systemes/menu/liste', 'isActive' => true),
        ));

        /** @var Systemes_MenuModel $oModel */
        $oModel = Apps::getModel('Systemes_Menu');

        $dataMenuSearch = array('titre', 'role_name');
        $aRequestSearch = array();
        foreach ($dataMenuSearch as $column) {
            $aRequestSearch[$column] = Commons::getRequestParameter($column);
        }

        $oRequest = Request::getInstance();
        if ($oRequest->isPost()) {
            $aPost = $oRequest->getPost();
            $oModel->update($aPost);
        }
        $data = $oModel->findAll($aRequestSearch);
        $aRoles = Apps::getModel('Role')->getLikeBy(['role_name' =>  $aRequestSearch['role_name']]);

        $aParam = array(
            'aMenu' => $oModel->traitementMenu($data['data']),
            'pagination' => $data['pagination'],
            'aData' => $aRequestSearch,
            'aRole' => $aRoles,
            'bAuthModifier' => Apps::getModel('Systemes_Acces')->checkAutorisation('systemes/menu/modifier', $this->user->role_id)
        );
        $this->getView()->addVar($aParam);
    }

    public function extraire() {
        $this->session->isAutorisedRole();
        $dataSearch = array('titre', 'role_name');
        $aRequestSearch = array();
        foreach ($dataSearch as $column) {
            $aRequestSearch[$column] = Commons::getRequestParameter($column);
        }
        $oModel = Apps::getModel('Systemes_Menu');
        $data = $oModel->findAll($aRequestSearch, false);
        $aRoles = Apps::getModel('Role')->getLikeBy(['role_name' =>  $aRequestSearch['role_name']]);
        $oModel->extraire($oModel->traitementMenu($data), $aRoles);
        exit();
    }

    public function exporter() {
        $this->session->isAutorisedRole();
        $dataSearch = array('titre', 'role_name');
        $aRequestSearch = array();
        foreach ($dataSearch as $column) {
            $aRequestSearch[$column] = Commons::getRequestParameter($column);
        }
        //pour l'export csv aucun distinction par rôle doit être fait pour differencier permission non autorisé ou s'il s'agit seulement d'une filtre lors de l'affichage
        $aRequestSearch['role_name'] = ''; 
        $oModel = Apps::getModel('Systemes_Menu');
        $aData = $oModel->findAll($aRequestSearch, false, true);

        $oExporter = Apps::usePlugin('ExportData/CSV');
        $oExporter->initialize('browser', 'menu_droit_lien_' . date('Ymd_Hm') . '.csv');

        $oExporter->addRow(array("Code menu", "Titre menu", "Label menu", "Icon menu", "Slug menu", "Module menu",
            "Controller menu", "Action menu", "Menu sur consolide", "Menu sur cible", "Ordre menu", "Permission menu",
            "Code sous menu", "Titre sous menu", "Label sous menu", "Icon sous menu", "Slug sous menu", "Module sous menu",
            "Controller sous menu", "Action sous menu", "Sous menu sur consolide", "Sous menu sur cible", "Ordre sous menu", "Permission sous menu",
            "Lien", "Deletable", "Titre lien", "Permission lien"));

        foreach ($aData as $item) {
            $data = [
                $item['menu_code'], $item['menu_titre'], $item['menu_label'], $item['menu_icon'], $item['menu_slug'], $item['menu_module'],
                $item['menu_controller'], $item['menu_action'], $item['menu_consolide'], $item['menu_cible'], $item['menu_ordre'], $item['menu_role']
                , $item['code'], $item['titre'], $item['label'], $item['icon'], $item['slug'], $item['module'],
                $item['controller'], $item['action'], $item['consolide'], $item['cible'], $item['ordre_child'], $item['sub_menu_role']
                , $item['url_segment'], $item['url_notDeletable'], $item['url'], $item['link_role']
            ];
            $oExporter->addRow($data);
        }
        $oExporter->finalize();
        $this->setNoRender();
    }

    public function importer() {
        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Gestion des menus', 'url' => '/systemes/menu/liste', 'isActive' => false),
            array('label' => 'Importer', 'url' => '#', 'isActive' => true),
        ));

        $message = array();
        $request = Request::getInstance();
        $oModel = Apps::getModel('Systemes_Menu');

        if ($request->isPost()) {
            $file = $_FILES['fichier'];
            $infosfichiers = pathinfo($file['name']);
            $extension = $infosfichiers['extension'];
            if ($extension == 'csv') {
                $importer = Apps::usePlugin('ImportData/CSV');
                $importer->initialize($file['tmp_name']);
                $data = $importer->getData();

                $validHeader = array("Code menu", "Titre menu", "Label menu", "Icon menu", "Slug menu", "Module menu",
                    "Controller menu", "Action menu", "Menu sur consolide", "Menu sur cible", "Ordre menu", "Permission menu",
                    "Code sous menu", "Titre sous menu", "Label sous menu", "Icon sous menu", "Slug sous menu", "Module sous menu",
                    "Controller sous menu", "Action sous menu", "Sous menu sur consolide", "Sous menu sur cible", "Ordre sous menu", "Permission sous menu",
                    "Lien", "Deletable", "Titre lien", "Permission lien");

                if ($validHeader == $data[0]) {
                    unset($data[0]);
                    $oModel->importer($data);
                    $message = ['success', 'Succès !'];
                    Session::addMessage($message[1], $message[0]);
                    $this->getView()->redirect('/systemes/menu/liste/');
                } else
                    $message = ['danger', "Erreur : Fichier invalide !"];
            } else
                $message = ['danger', "Erreur : extension !"];
        }

        if (!empty($message))
            Session::addMessage($message[1], $message[0]);
    }

    public function submenu() {
        $menu = $this->getRequest()->getParam('menu');
        Layout::setLayout('ajax');
        $aSubMenu = Apps::getModel('Systemes_Menu')->getSubMenu($menu);

        $aParam = array(
            'aSubmenu' => $aSubMenu
        );
        $this->getView()->addVar($aParam);
    }

}
