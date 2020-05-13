<?php


class ProfilController extends Controller
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

    public function liste() {

        Layout::addCrumbs(
            array(
                array('label' => 'Système', 'url' => '#', 'isActive' => false),
                array('label' => 'Profil', 'url' => '/systemes/profil/liste', 'isActive' => true),
            )
        );

        $this->session = new Session();
        $user = $this->session->getUser();
        /** @var Roles_RolesModel $oModelRoles */
        $oModelRoles = Apps::getModel('Roles_Roles');
        $userPassvola = $oModelRoles->getBy(['id_appli' => $user->id]);

        $dataSearch = array('nom','prenom', 'username_appli');
        $tRequestSearch = array();
        foreach ($dataSearch as $column) {
            $tRequestSearch[$column] = Commons::getRequestParameter($column);
        }

        $allUsers = $oModelRoles->getAllUsers($tRequestSearch);

        $aParams = [
            'user_info' => $user,
            'passvola_info' => $userPassvola,
            'users' => $allUsers['data'],
            'pagination' => $allUsers['pagination'],
            'data' => $tRequestSearch
        ];

        $this->getView()->addVar($aParams);

    }

    /**
     * Recupere le pack du parent pour les lignee indirectes
     */
    public function getParent() {

        Layout::setLayout('ajax');
        $parent = $this->getRequest()->getParam('username');
        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        $parentList = $oModel->getPackByUserName($parent);
        //$tData = ['parentPack' => $parentList];
        print json_encode($parentList);

    }

    /**
     * Confirmer un utilisateur apres avoir payer en Mvola
     */
    public function confirmer() {

        $id = $this->getRequest()->getParam('id');
        $data = [
            'id' => $id,
            'confirme_check' => 'validee',
            'date_confirmation' => date('Y-m-d h:i:s')
        ];

        /**@var Comptabilite_ComptabiliteModel $oCompatabilite */
        $oCompatabilite = Apps::getModel('Comptabilite_Comptabilite');
        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        /** @var Systemes_PackModel $oPack */
        $oPack = Apps::getModel('Systemes_Pack');

        //validation de l'utilisateur
        $oModel->checkUser($data);

        //Recupere les info utilisateurs et prix pack dans valeur
        //$data = $oModel->getDetailById($id);
        //$prixPack = $oPack->getPackByName($data['pack']);

        /*$mouvement = [

            'mouvement' => 'inscription',
            'valeur' => $prixPack['prix'],
            'mode' => 'credit',
            'id_user' => $data['id_appli'],
            'date_mouvement' => date('Y-m-d'),
        ];*/

        //sauvegarde directement de prix de pack dans porte feuille
        //$oCompatabilite->create($mouvement);

       $this->getView()->redirect('/systemes/profil/liste');

    }

    /**
     *
     * Recuperer détail utilisateur
     */
    public function detail() {

        $id = $this->getRequest()->getParam('id');
        /** @var Roles_RolesModel $oModel */
        $oModel = Apps::getModel('Roles_Roles');
        $data = $oModel->getDetailById($id);

        /** @var Systemes_PackModel $oPack */
        $oPack = Apps::getModel('Systemes_Pack');
        $prixPack = $oPack->getPackByName($data['pack']);

        /** @var Comptabilite_ComptabiliteModel $oComptabilite */
        $oComptabilite = Apps::getModel('Comptabilite_Comptabilite');
        $mouvement = $oComptabilite->getMouvementDetail($data['id_appli']);
        $credit = $oComptabilite->getCredit($data['id_appli']);
        $debit = $oComptabilite->getDebit($data['id_appli']);

        $affilieDirect = $oComptabilite->debitDirect($data['id_appli']);

        $params = [
            'child' => $affilieDirect,
            'pack' => $prixPack
        ];

        $bonus = $this->calculBonus($params);

        $aParams = [
            'data' => $data,
            'prixPack' => $prixPack['prix'],
            'mouvement' => $mouvement,
            'credit' => $credit,
            'debit' => $debit,
            'bonus' => $bonus,
        ];
        //Commons::dump($aParams); die;
        $this->getView()->addVar($aParams); 
    }

    private function calculBonus($params) {

        ($params['child'] == "") ? $child = 0 : $child = $params['child'];
        $pack = $params['pack'];
        $message = [];
        $gain = 0;

        if ($child['direct'] >= $pack['largeur']) {

            $largeur = $pack['largeur'];
            $message[] = 'Votre enfant depasse le largeur de votre pack:'.$pack['largeur'];
        } else {

            $largeur = $child['direct'];
        }

        if ($child['reste'] >= $pack['niveau']) {

            $niveau = $pack['niveau'];

        } else {

            $niveau = $child['reste'];
        }

        $bd = $pack['bonus_direct'];
        $bi = $pack['bonus_indirect'];
        $prix = $pack['prix'];

        $prixNiveau1 = ($prix*$bd)/100;
        $valueNiveau1 = $prixNiveau1 * $largeur;

        if ($niveau <= 2) {

            $prixNiveau2 = ($prix*$bi)/100;
            $valueNiveau2 = $prixNiveau2*(pow($largeur, $niveau));

            if ($valueNiveau1 == 0) {

                $finalValue = $valueNiveau1;
            } else {

                $finalValue = $valueNiveau1 + $valueNiveau2;
            }

        } elseif ($niveau > 2) {

            $gain = 0;
            for ($i = 2; $i <= $niveau; $i++) {

                $prixNiveau2 = ($prix*$bi)/100;
                $valueNiveau2 = $prixNiveau2*(pow($largeur, $i));
                $gain += $valueNiveau2;
            }

            $finalValue = $valueNiveau1 + $gain;
        }
        return $dataFinal = [

            'gainDirect' => $valueNiveau1,
            'gainIndirect' => $gain,
            'finalValue' => $finalValue
        ];

    }

    public function transaction() {

        $id = $this->getRequest()->getParam('id');
        $username = $this->getRequest()->getParam('username');

        $request = Request::getInstance();

        if ($request->isPost()) {

            /** @var Comptabilite_ComptabiliteModel $oModel */
            $oModel = Apps::getModel('Comptabilite_Comptabilite');

            $post = $request->getPost();
            $data = [
                'mouvement' => $post['mouvement'],
                'valeur' => $post['valeur'],
                'mode' => $post['mode'],
                'id_user' => $id,
                'date_mouvement' => date('Y-m-d'),
            ];

            $oModel->create($data);
            $this->getView()->redirect('/systemes/profil/liste');
        }

        $aParams = [
            'username' => $username,
        ];
        $this->getView()->addVar($aParams);

    }


}