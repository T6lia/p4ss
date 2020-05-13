<?php


class PackController extends Controller
{
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
     * page pour lister toutes les packs
     */
    public function liste() {
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Listes des pack', 'url' => '/systemes/pack/liste', 'isActive' => true),
        ));

        /** @var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');

        $dataSearch = array('nom_pack','nombre_affilie', 'bonus');
        $tRequestSearch = array();
        foreach ($dataSearch as $column) {
            $tRequestSearch[$column] = Commons::getRequestParameter($column);
        }
        $data = $oModel->findAll($tRequestSearch);
        $this->session = new Session();
        $user = $this->session->getUser();

        $tParams = [
            'pack' => $data['data'],
            'pagination' => $data['pagination'],
            'data' => $tRequestSearch,
            'user' => $user
        ];

        $this->getView()->addVar($tParams);
    }

    /**
     * page pour la creation d'un pack
     */
    public function create() {

        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Listes des pack', 'url' => '/systemes/pack/liste', 'isActive' => false),
            array('label' => 'Création', 'url' => '/systemes/pack/create', 'isActive' => true)
        ));

        /** @var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');

        if(Request::getInstance()->isPost()) {

            $data = Request::getInstance()->getPost();
            $oModel->create($data);

            Session::addMessage('Succès !', 'success');
            $this->getView()->redirect('/systemes/pack/liste/');
        }
    }

    /**
     * fonction pour supprimer un pack
     */
    public function supprimer() {

        $this->session->isAutorisedRole();
        $id = $this->getRequest()->getParam('id');

        /** @var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');
        if ('' != $id) {

            $oModel->remove($id);
            Session::addMessage('Pack supprimer', 'success');
            $this->getView()->redirect('/systemes/pack/liste');
        }
    }

    /**
     * détail d'un pack
     */
    public function detail() {

        $id = $this->getRequest()->getParam('id');
        Layout::setLayout('ajax');
        /** @var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');
        $pack = $oModel->getBy(['id' => $id], true);

        $niveau = $pack['niveau'];
        $largeur = $pack['largeur'];
        $bd = $pack['bonus_direct'];
        $bi = $pack['bonus_indirect'];
        $prix = $pack['prix'];

        $prixNiveau1 = ($prix*$bd)/100;
        $valueNiveau1 = $prixNiveau1 * $largeur;

        if ($niveau <= 2) {

            $prixNiveau2 = ($prix*$bi)/100;
            $valueNiveau2 = $prixNiveau2*(pow($largeur, $niveau));
            $finalValue = $valueNiveau1 + $valueNiveau2;

        } elseif ($niveau > 2) {

            $gain = 0;
            for ($i = 2; $i <= $niveau; $i++) {

                $prixNiveau2 = ($prix*$bi)/100;
                $valueNiveau2 = $prixNiveau2*(pow($largeur, $i));
                $gain += $valueNiveau2;
            }

            $finalValue = $valueNiveau1 + $gain;
        }

        $tParams = [
            'pack' => $pack,
            'gain' => $finalValue
        ];
        $this->getView()->addVar($tParams);
    }

    /**
     * Modification d'un pack
     */
    public function modifier() {

        $this->session->isAutorisedRole();
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Listes des pack', 'url' => '/systemes/pack/liste', 'isActive' => false),
            array('label' => 'Modification', 'url' => '/systemes/pack/modifier', 'isActive' => true)
        ));

        $id = $this->getRequest()->getParam('id');
        /** @var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');
        $data = $oModel->getById($id);

        if (Request::getInstance()->isPost()) {

            $post = Request::getInstance()->getPost();
            $oModel->update($post);

            $this->getView()->redirect('/systemes/pack/liste');

        }

        $aParams = [
            'data' => $data,
        ];
        $this->getView()->addVar($aParams);
    }

}