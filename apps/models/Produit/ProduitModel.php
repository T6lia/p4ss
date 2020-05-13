<?php


class Produit_ProduitModel extends Model_Abstract
{
    protected $_tablename = 'produit';
    protected $_relatedTable = 'produit_type';

    /**
     * @return array
     * recupere les produits
     */
    public function getProduitType() {

        $query = "SELECT * FROM {$this->_relatedTable} ";
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(2);
    }

    public function create($data) {

        $query = "INSERT INTO {$this->_tablename} (type_produit, numero, proprietaire, distributeur) 
                  VALUES (:type_produit, :numero, :proprietaire, :distributeur) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':type_produit', $data['type_produit']);
        $stmt->bindParam(':numero', $data['numero']);
        $stmt->bindParam(':proprietaire', $data['proprietaire']);
        $stmt->bindParam(':distributeur', $data['distributeur']);
        return $stmt->execute();
    }

    public function findAll($requestSearch) {

        $tWhere = array();
        $tParam = array();
        $tJoin = array();
        $sWhere = '';

        $tColumns = array('numero' => 'LIKE', 'proprietaire' => 'LIKE', 'distributeur' => 'LIKE');

        if ($requestSearch['numero'] != NULL) {
            $tWhere[] = " p.numero LIKE :numero";
            $tParam['numero'] = '%' . $requestSearch['numero'] . '%';
        }
        if ($requestSearch['proprietaire'] != NULL) {
            $tWhere[] = " p.proprietaire LIKE :proprietaire";
            $tParam['proprietaire'] = '%' . $requestSearch['proprietaire'] . '%';
        }
        if ($requestSearch['distributeur'] != NULL) {
            $tWhere[] = " p.distributeur LIKE :distributeur";
            $tParam['distributeur'] = '%' . $requestSearch['distributeur'] . '%';
        }

        $sJoin = implode(' ', $tJoin);
        if (count($tWhere) > 0) {
            $sWhere = 'WHERE';
            $sWhere .= implode(' AND', $tWhere);
        }

        $query = "SELECT * FROM {$this->_tablename} as p $sJoin $sWhere";
        $queryCount = "SELECT count(*) FROM {$this->_tablename} as p $sJoin $sWhere";

        $oPagination = Apps::usePlugin('FilterData');
        $oPagination->initialize($this, $tColumns);
        $tData = $oPagination->paginateQuery($query, $queryCount, $tParam, false);

        return $tData;
    }

}