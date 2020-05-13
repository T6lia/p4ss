<?php

/**
 * Class controllers pour les webservices interne de l'application
 * @url http://xxx/systemes/ws/...
 * @vue ./apps/modules/systemes/views/ws/...
 */
class WSController extends Controller
{
    /*
     * Retourne si le role en question est rattacher ou non a des utilisateurs 
     * @url http://xxx/systemes/ws/getroleattachment/id_role/xx
     */
    public function getroleattachment() {
        $iIdRole = $this->getRequest()->getParam('id_role');
        print(Apps::getModel('User')->countUserBiRole($iIdRole));
        $this->setNoRender();
    }
}
