<?php


class GererController extends Controller
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
                array('label' => 'Gestion des produits', 'url' => '#', 'isActive' => false),
                array('label' => 'Gérer les produits', 'url' => '/produit/gerer/liste', 'isActive' => true),
            )
        );
        /** @var Produit_ProduitModel $oModel */
        $oModel = Apps::getModel('Produit_Produit');
        $dataSearch = array('numero','proprietaire', 'distributeur');
        $tRequestSearch = array();
        foreach ($dataSearch as $column) {
            $tRequestSearch[$column] = Commons::getRequestParameter($column);
        }
        $data = $oModel->findAll($tRequestSearch);

        $tParams = [
            'produit' => $data['data'],
            'pagination' => $data['pagination'],
            'data' => $tRequestSearch
        ];

        $this->getView()->addVar($tParams);
    }

    public function create() {
        Layout::addCrumbs(
            array(
                array('label' => 'Gestion des produits', 'url' => '#', 'isActive' => false),
                array('label' => 'Attribuer un produit', 'url' => '/produit/gerer/liste', 'isActive' => false),
                array('label' => 'Créer', 'url' => '/produit/gerer/liste', 'isActive' => true),
            )
        );

        /** @var Produit_ProduitModel $oModel */
        $oModel = Apps::getModel('Produit_Produit');
        $produit = $oModel->getProduitType();
        $this->session = new Session();
        $user = $this->session->getUser();

        if (Request::getInstance()->isPost()) {

            $data = Request::getInstance()->getPost();
            $numero = $oModel->getBy(['numero' => $data['numero']]);

            if ($numero) {
                Session::addMessage('Cette carte est déja prise par un propriétaire', 'danger');
                $this->getView()->redirect('/produit/gerer/create');
            } else {

                $oModel->create($data);
                $this->getView()->redirect('/produit/gerer/liste');
            }
        }

        $tParams = [
            'produit' => $produit,
            'user' => $user
        ];
        $this->getView()->addVar($tParams);
    }

}