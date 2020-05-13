<?php


class Systemes_PackModel extends Model_Abstract
{
    protected $_tablename = 'pack';
    public $_id = 'id';

    /**
     * @param $data
     * @return mixed
     * Créér pack
     */
    public function create($data) {

        $query = "INSERT INTO {$this->_tablename} (nom_pack, bonus_direct, 
                bonus_indirect, niveau, largeur,  bonus, prix, validite_fin, description) 
                VALUES (:nom_pack, :bonus_direct, :bonus_indirect, :niveau, :largeur, :bonus, :prix, 
                :validite_fin, :description) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':nom_pack', $data['nom_pack']);
        $stmt->bindParam(':bonus_direct', $data['bonus_direct']);
        $stmt->bindParam(':bonus_indirect', $data['bonus_indirect']);
        $stmt->bindParam(':niveau', $data['niveau']);
        $stmt->bindParam(':largeur', $data['largeur']);
        $stmt->bindParam(':bonus', $data['bonus']);
        $stmt->bindParam(':prix', $data['prix']);
        $stmt->bindParam(':validite_fin', $data['validite_fin']);
        $stmt->bindParam(':description', $data['description']);

        $stmt->execute();

        return $data['nom_pack'];
    }

    public function findAll($requestSearch) {

        $tWhere = array();
        $tParam = array();
        $tJoin = array();
        $sWhere = '';

        $tColumns = array('nom_pack' => '=', 'bonus' => '=', 'prix' => '=');

        if ($requestSearch['nom_pack'] != NULL) {
            $tWhere[] = " p.nom_pack =:nom_pack";
            $tParam['nom_pack'] = $requestSearch['nom_pack'];
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

    /**
     * @param $id
     * @return bool
     * Suppression d'un pack
     */
    public function remove($id) {
        $query = "DELETE FROM {$this->_tablename} WHERE {$this->_id} = :id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function update($data) {

        $query = "UPDATE {$this->_tablename} "
            . "SET nom_pack =:nom_pack, bonus_direct =:bonus_direct, bonus =:bonus, prix =:prix, "
            . "bonus_indirect =:bonus_indirect, niveau =:niveau, largeur =:largeur, "
            . "validite_fin =:validite_fin "
            . "WHERE id =:id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':nom_pack', $data['nom_pack']);
        $stmt->bindParam(':bonus_direct', $data['bonus_direct']);
        $stmt->bindParam(':bonus_indirect', $data['bonus_indirect']);
        $stmt->bindParam(':niveau', $data['niveau']);
        $stmt->bindParam(':largeur', $data['largeur']);
        $stmt->bindParam(':bonus', $data['bonus']);
        $stmt->bindParam(':prix', $data['prix']);
        $stmt->bindParam(':validite_fin', $data['validite_fin']);
        $stmt->bindParam(':id', $data['id']);

        return $stmt->execute();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id) {

        $query = "SELECT * FROM {$this->_tablename} WHERE id =:id ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(2);
    }

    /**
     * @return array
     * Liste de toutes les pack
     */
    public function getAllPack() {

        $query = "SELECT * FROM {$this->_tablename} ";
        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

    public function getPackByName($name) {

        $query = "SELECT validite_fin, prix FROM {$this->_tablename} WHERE nom_pack =:nompack ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':nompack', $name);
        $stmt->execute();

        return $stmt->fetch(2);
    }

}