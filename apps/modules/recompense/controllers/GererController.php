<?php


class GererController extends Controller
{
    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->user = $this->session->getUser();
    }

    public function liste() {

        $this->session = new Session();
        $user = $this->session->getUser();
        $id = $user->id;

        /** @var Comptabilite_ComptabiliteModel $oComptabilite */
        $oComptabilite = Apps::getModel('Comptabilite_Comptabilite');
        $credit = $oComptabilite->getAllCredit($id);
        $debit = $oComptabilite->getAllDebit($id);
        $totalCredit = $oComptabilite->getCredit($id);
        $totalDebit = $oComptabilite->getDebit($id);

        //pack de l'utilisateur
        $pack = $this->getPack($id);

        //Debit pour nombre d'enfant
        $debitDirect = $oComptabilite->debitDirect($id);
        ($debitDirect == "")? $debitDirect = 0 : $debitDirect = $oComptabilite->debitDirect($id);

        //Calcul du solde
        if ($debitDirect == "") {

            $soldeUser = 0; //pas d'enfant

        } else {
            $soldeUser = $this->debitUser($pack, $debitDirect);
        }

        $aParams = [
            'user' => $user,
            'credit' => $credit,
            'debit' => $debit,
            'totalcredit' => $totalCredit,
            'totaldebit' => $totalDebit,
            'debitDirect' => $debitDirect,
            'soldeUser' => $soldeUser,
        ];
        $this->getView()->addVar($aParams);
        
    }

    private function getPack($id) {

        /**@var Systemes_PackModel $oModel */
        $oModel = Apps::getModel('Systemes_Pack');

        /**@var Roles_RolesModel $oModelRole */
        $oModelRole = Apps::getModel('Roles_Roles');

        $packUser = $oModelRole->getBy(['id_appli' => $id]);
        $pack = $oModel->getPackByName($packUser[0]['pack']);
        $dataReturn = [
            'niveau' => $pack['niveau'],
            'largeur' => $pack['largeur'],
            'bonus_direct' => $pack['bonus_direct'],
            'bonus_indirect' => $pack['bonus_indirect'],
            'prix' => $pack['prix']
        ];

        return $dataReturn;
    }

    private function debitUser($pack, $child) {
        $message = [];

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
            $finalValue = $valueNiveau1 + $valueNiveau2;
            $gain = 0;

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

}