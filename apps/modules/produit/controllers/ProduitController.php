<?php


class ProduitController extends Controller
{
    public function __construct(\Request $oRequest, \View $oView) {
        parent::__construct($oRequest, $oView);
        $this->session = new Session();
        $this->user = $this->session->getUser();
    }

    public function liste() {
        die('gerer');
    }

}