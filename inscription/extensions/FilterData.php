<?php

/*
 * Fonction de création de pagination avec recherche et tri
 * ********************************************************
 * Utilisation 1 : config depuis controller , pour les requetes basique tel que SELECT * FROM table
 * ********************************************************
 * Dans controller :
 * 
 * $columns = array('champ1' => $operateur,'champ2' => '$operateur',...);
 * valeur valide pour $operateur ('LIKE' ,'=' ,'>' , '>=', '<' ,'<=' )
 * 
 * $filter = Apps::usePlugin('FilterData');
 * $filter->initialize(Apps::getModel('Article'), $columns);
 *
 * $aRequestSearch = $filter->initialize($oModel, $columns); //Retourn tableau de la recherche effectuer
 * $aArticle = $filter->getData(); //Retourne le resultat
 * $pagination = $filter->paginate(); //Retourne les paramètre utiliser pour la pagination
 * 
 * NB : le nom du variable a passer au vue doit être $pagination pour le paramètre de pagination
 * ********************************************************
 * Dans vue :
 * -Afficher $aArticle : Boucle
 * -Afficher la pagination : <?php Layout::render('part/pagination', compact('pagination')); ?>
 * 
 * ********************************************************
 * Utilisation 2 : config depuis model, pour les requetes plus complexe, avec jointure,...
 * ********************************************************
 * $columns = array('champ1' => $operateur,'champ2' => '$operateur',...);
 * valeur valide pour $operateur ('LIKE' ,'=' ,'>' , '>=', '<' ,'<=' )
 * 
 * $filter = Apps::usePlugin('FilterData');
 * $filter->initialize(Apps::getModel('Article'), $columns);
 * 
 * ******
 * $query = requete normal
 * $queryCount = requete count du $query
 * $param = les paramètres
 * ******
 * $data = $pagination->paginateQuery($query,$queryCount, $param);
 * 
 * 
 * Dans le controleur $data return $data['data'] => pour le resultat et $data['pagination'] pour le paramètre de pagination
 * ********************************************************
 * @author tahina.lalaina
 */

class FilterData {

    private $_uri; //url de base du page encours
    private $_model; //modele du table concerné
    private $_limit = 15; //Limite par page
    private $_offset = 0; //debut d'element à considérer
    private $_current = 1; //page encours
    private $_request = array(); //requete concernant la recherche avec le type d'opérateur à utiliser
    private $_dataSearch = array(); // les données du formulaire de recherche
    private $_column = array(); //Colonne valide pour la recherche et pour le tri
    private $ordered;
    private $_where = array();

    /**
     * @param Object $model ****    modele de la table utiliser
     * @param array $columns ****    tableau contenant les colonnes valide pour la recherche avec type d'opérateur utiliser et pour le tri
     * @return array
     */
    public function initialize($model, array $columns, $ordered = true, $where = array()) {

        /* $httpType = 'http'; */

        /* Vérifier si le mode https est activé par la requette */

        /* if (isset($_SERVER['HTTPS'])) {
          $httpType = ('on' == $_SERVER['HTTPS']) ? 'https' : $httpType;
          } */

        /* Affectecation du request_scheme */
        /* $sheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : $httpType; */

        /* affectation de l'url de base */
        /* $uri = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : explode( '?', $_SERVER['REQUEST_URI'] )[0]; */ // Utile si le serveur httpd n'est pas apache2

        $uri = explode('?', $_SERVER['REQUEST_URI'])[0];

        /* Construction de l'url de base complet */
        /* $this->_uri = $sheme.'://'.$_SERVER['HTTP_HOST'].$uri; */

        $this->_uri = $uri;

        $this->ordered = $ordered;
        $this->_model = $model;

        $this->_limit = !is_null(Commons::getRequestParameter('l')) ? Commons::getRequestParameter('l') : $this->_limit;
        $this->_current = !is_null(Commons::getRequestParameter('p')) ? Commons::getRequestParameter('p') : $this->_current;
        $this->_offset = ($this->_current - 1) * $this->_limit;
        $this->_column = $columns;
        $this->_where = $where;
        $this->setRequest();
        $this->setDataSearch();

        return $this->_dataSearch;
    }

    /**
     * Retourne le résultat avec ou sans filtre et/ou tri au nombre limite spécifier et prend en compte la page courrant
     */
    public function getData() {
        $data = $this->_model->paginate($this->_offset, $this->_limit, $this->_request, $this->getOrder(), $this->ordered, $this->_where);

        return $data;
    }

    /*
     * Retourne un tableau contenant les paramètres utiliser pour afficher la pagination
     */

    public function getPagination() {
        $number = $this->_model->countData($this->_request);
        $result = $this->getResultPagination($number, $this->dataToUri());

        return $result;
    }

    public function getPaginationQuery($query, $param) {
        $number = $this->_model->countDataQuery($query, $param);
        $result = $this->getResultPagination($number, $this->dataToUri($param));

        return $result;
    }

    public function getResultPagination($number, $part_uri = '') {
        $page = ceil($number / $this->_limit);
        $result = array(
            'page' => $page,
            'current' => $this->_current,
            'debut' => $this->_current > 5 ? $this->_current - 5 : 1,
            'fin' => ($page - $this->_current) > 5 ? $this->_current + 5 : $this->_current + ($page - $this->_current),
            'uri' => $this->_uri,
            'end_pagination' => $page,
            'part_uri' => $part_uri,
            'nombre_result' => $number,
        );

        return $result;
    }

    /*
     * Transforme les données passer en GET en chaine pour completer l'url de base
     */

    private function dataToUri($param = array()) {
        $aPartUri = array();
//        foreach ($this->_dataSearch as $key => $value) {
//            if ($value != '') {
//                $aPartUri[] = "$key=$value";
//            }
//        }

        foreach ($param as $key => $value)
            if ($value != '')
                $aPartUri[] = "$key=" . str_replace('%', '', $value);

        return implode('&', $aPartUri);
    }

    /*
     * Retourne les paramètres du tri spécifier depuis l'url pour le transformer en un tableau 
     */

    private function getOrder() {
        $sortData = array();
        foreach ($this->_column as $key => $value) {
            $sortData[$key] = Commons::getRequestParameter("sort_$key");
        }

        return $sortData;
    }

    /*
     * Transforme les colonnes valide en requete de recherche 
     * en utilisant les données passer e nGET
     */

    private function setRequest() {
        foreach ($this->_column as $column => $operateur) {
            $this->_request[$column] = [Commons::getRequestParameter($column), $operateur];
        }
    }

    /*
     * Transforme les colonnes valide en tableau de la colone 
     * avec un valeur associer en utilisant les données passer en GET
     */

    private function setDataSearch() {
        foreach ($this->_column as $column => $operateur) {
            $this->_dataSearch[$column] = Commons::getRequestParameter($column);
        }
    }

    /*
     * $query = requete avec jointure ou filtre ou ....
     * $queryCount = count(*) de query
     * $param = parametre a utiliser tableau clé valeur
     * 
     * 
     * return tableau du resultat et paramètre de pagination
     */

    public function paginateQuery($query, $queryCount, $param, $ordered = true, $tOrderBy = array()) {
        //$data = $this->_model->paginateQuery($query, $param, $this->_offset, $this->_limit, $this->getOrder(), $ordered);
        $tOrderBy = (count($tOrderBy) > 0) ? $tOrderBy : $this->getOrder();
        $data = $this->_model->paginateQuery($query, $param, $this->_offset, $this->_limit, $tOrderBy, $ordered);
        $pagination = $this->getPaginationQuery($queryCount, $param);

        $result = array(
            'data' => $data,
            'pagination' => $pagination,
        );

        return $result;
    }

}
