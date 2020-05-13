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
        /**@var Affilie_AffilieModel $oModel */
        $oModel = Apps::getModel('Affilie_Affilie');
        /**@var Roles_RolesModel $oRoles */
        $oRoles = Apps::getModel('Roles_Roles');
        /**@var Systemes_PackModel $oPack */
        $oPack = Apps::getModel('Systemes_Pack');

        $userInfo = $oRoles->getBy(['id_appli' => $user->id]);

        if (($user->role_id == 2)) { //for admin

            $membre = $oModel->getAllMembers(['id' => $user->id]);
            $resultNiveau = $this->jsonListe($membre);

        } else { // for users

            $membre = $oModel->getAllMembersUser(['id' => $user->id]);
            if ($membre == "") {

                $resultNiveau = [];

            } else {

                $pack = $oPack->getPackByName($userInfo[0]['pack']);
                $niveau = $pack['niveau'];
                $largeur = $pack['largeur'];

                $allData = $this->jsonListe($membre['result_final']);
                $directData = $this->jsonListe($membre['direct']);
                $allDataCount = count($membre['result_final']) - 1;
                $directDataCount = count($membre['direct']) - 1;
                
                $resultDirect = [];
                $resultLevel = [];

                //Recupérer résultat niveau 1 avec controle du largeur du pack

                if ($directDataCount < $largeur) {

                    $res1 = $directData;

                } else {

                    for ($i = 0; $i < $largeur; $i++) {

                        $resultDirect[] = $directData[$i];
                    }
                    $res1 = $resultDirect;
                }
                //Recuperer niveau précédent avec controle niveau du pack
                if ($allDataCount <= $niveau) {

                    $res2 = $allData;

                } else {

                    for ($i = $directDataCount + 1; $i < ($directDataCount + $niveau); $i++) {

                        $resultLevel[] = $allData[$i];
                    }
                    $res2 = $resultLevel;
                }

                $resultNiveau = array_merge($res1, $res2);
            }
        }

        $dataUser[] = [
            'key' => $user->id,
            'parent' => '',
            'name' => $userInfo[0]['username_appli'],
            'source' => "/themes/dist/img/avatar6.png"
        ];
        $result = array_merge($dataUser, $resultNiveau);
        //Commons::dump($result); die;
        $aParams = ['data' => $result];
        $this->getView()->addVar($aParams);

    }

    public function jsonListe($datas) {

        $returnData = [];
        foreach ($datas as $data) {

            $t = array();
            $t['key'] = $data ['id'];
            $t['parent'] = $data ['id_parent'];
            $t['name']  = $data ['username'];
            $t['source'] = "/themes/dist/img/avatar6.png";

            $returnData[] = $t;
        }

        return ($returnData);
    }

}