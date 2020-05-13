<?php

/*
 * Class controller pour la gestion des secteurs (Insertion de masse)
 * @url http://application.soctam.loc/systemes/secteur/...
 * @vue ./apps/modules/systemes/views/secteur/...
 *  
 * @author tahina.lalaina
 */
class SecteurController extends Controller {

    private $session;

    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->session->isAutorisedRole();
    }
    /**
     * Route pour la liste des secteur
     * @url /systemes/secteur/liste
     */
    public function liste() {
        Layout::addCrumbs(
                array(
                    array('label' => 'Système', 'url' => '#', 'isActive' => false),
                    array('label' => 'Secteurs', 'url' => '#', 'isActive' => true),
                )
        );

        $tColumns = array('code' => '=','nom' => '=','region' => '=');
        $oModel = Apps::getModel('Systeme_Secteur');
        $oFilter = Apps::usePlugin('FilterData');
        $oFilter->initialize($oModel, $tColumns);
        $aSecteur = $oFilter->getData();
        $tPagination = $oFilter->getPagination();

        $tParam = array(
            'aSecteur' => $aSecteur,
            'pagination' => $tPagination,
        );

        $this->getView()->addVar($tParam);
    }
    
    public function importer() {
        Layout::addCrumbs(array(
            array('label' => 'Système', 'url' => '#', 'isActive' => false),
            array('label' => 'Secteur', 'url' => '/systeme/secteur/liste/', 'isActive' => false),
            array('label' => 'Importer', 'url' => '', 'isActive' => true),
                )
        );
        $message = array();
        $request = Request::getInstance();
        $oModel = Apps::getModel('Systeme_Secteur');

        if ($request->isPost()) {
            $file = $_FILES['fichier'];
            $infosfichiers = pathinfo($file['name']);
            $extension = $infosfichiers['extension'];
            if ($extension == 'csv') {
                $importer = Apps::usePlugin('ImportData/CSV');
                $importer->initialize($file['tmp_name']);
                $data = $importer->getData();
                if (count($data[0]) == 3) {
                    $validHeader = array('Code', 'Nom', 'Region');
                    if ($validHeader == $data[0]) {
                        $aSecteurInsert = array();
                        $aSecteurUpdate = array();
                        $aCodes = array();
                        unset($data[0]); // Enlever 1ère ligne => titre de chaque colone du csv
                        foreach ($data as $item)
                            $aCodes[] = "'$item[0]'";
                        
                        //Récupère les codes articles déja existant dans la base de données
                        $aExistant = $oModel->getByCodes($aCodes);
                        foreach ($data as $item) {
                            if (in_array($item[0], $aExistant))
                                $aEtapeUpdate[] = $item;
                            else
                                $aEtapeInsert[] = $item;
                        }
                        //Insertion des nouveaux articles
                        $oModel->insert($aEtapeInsert);
                        //Update des existants articles
                        $oModel->update($aEtapeUpdate);
                        $message = ['success', 'Succès !'];
                        $this->getView()->redirect('/systemes/secteur/liste/');
                    } else
                        $message = ['danger', "Erreur : Fichier invalide !"];
                } else
                    $message = ['danger', "Erreur : Nombre colonne"];
            } else
                $message = ['danger', "Erreur : extension !"];
        }

        if (!empty($message))
            Session::addMessage($message[1], $message[0]);
    }

    public function exporter() {

        $oModel = Apps::getModel('Systeme_Secteur');

        $aParams = [
            'code' => Commons::getRequestParameter('code'),
            'nom' => Commons::getRequestParameter('nom'),
            'region' => Commons::getRequestParameter('region')
        ];

        $tSecteur = $oModel->exportByParams($aParams);

        $oExporter = Apps::usePlugin('ExportData/CSV');
        $oExporter->initialize('browser', 'secteur_export_' . date('Ymd_Hm') . '.csv');

        $oExporter->addRow([
            'Code',
            'Nom',
            'Region'
        ]);
        foreach ($tSecteur as $item) {

            $data = [
                $item['code'],
                $item['nom'],
                $item['region'],
            ];
            $oExporter->addRow($data);
        }
        $oExporter->finalize();
        $this->setNoRender();
    }


}

