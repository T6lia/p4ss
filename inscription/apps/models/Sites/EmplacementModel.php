<?php

class Sites_EmplacementModel extends Model_Abstract {

    protected $_tablename = 'ref_sites_emplacements';
    public $_id = 'code';

    /**
     * @return array
     */
    public function getEmplacements() {
        $query = "SELECT code, nom, entrepot_id FROM {$this->_tablename} ORDER BY entrepot_id";

        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * @param $id
     * @return $this|bool|mixed
     */
    public function getEmplacementByCode($code) {
        $query = "SELECT code, nom, entrepot_id FROM {$this->_tablename} WHERE code = :code ORDER BY entrepot_id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    /**
     * @param $entrepot_id
     * @return array
     */
    public function getEmplacementsByEntrepot($entrepot_id) {
        $query = "SELECT code, nom, entrepot_id FROM {$this->_tablename} "
        . "WHERE entrepot_id = :entrepot_id ORDER BY entrepot_id";

        $stmt = Database::prepare($query);

        $stmt->bindParam(':entrepot_id', $entrepot_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }
    
    public function getEmplacementsWithStock($site,$entrepot,$article) {
         $query = "SELECT DISTINCT s.emplacement_id as code, e.nom "
                . "FROM ref_articles_inventaires as s "
                . "LEFT OUTER JOIN " . $this->_tablename . " e ON e.code = s.emplacement_id AND e.entrepot_id = s.entrepot_id "
                . "WHERE s.site_id = :site AND s.article_id = :article AND s.entrepot_id = :entrepot";

        $stmt = Database::prepare($query);

        $stmt->execute(array('site' => $site, 'entrepot' => $entrepot, 'article' => $article));

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * @param $site_id
     * @return array
     */
    public function getEmplacementsBySite($site) {
        $query = "SELECT emp.code, emp.nom, emp.entrepot_id FROM {$this->_tablename} emp "
                . " LEFT OUTER JOIN ref_sites_entrepots ent ON emp.entrepot_id = ent.code "
                . "WHERE ent.site_id = :site_id ";
        
        $stmt = Database::prepare($query);

        $stmt->bindParam(':site_id', $site);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * @param $entrepot_id
     */
    public function removeByEntrepotId($entrepot_id) {
        $query = "DELETE FROM {$this->_tablename} WHERE entrepot_id = :entrepot_id";

        $stmt = Database::prepare($query);

        $stmt->bindParam(':entrepot_id', $entrepot_id);
        $stmt->execute();
    }

    /**
     * @param $data
     * @return int
     */
    public function create($data) {
        $query = "INSERT INTO {$this->_tablename} values(:code, :nom, :entrepot_id)";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':entrepot_id', $data['entrepot_id']);

        $stmt->execute();

        return $data['code'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function update($data) {
        $query = "UPDATE {$this->_tablename} SET nom = :nom WHERE entrepot_id = :entrepot_id AND code = :code";

        $stmt = Database::prepare($query);

        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':entrepot_id', $data['entrepot_id']);

        $stmt->execute();

        return $data['code'];
    }

    public function exportByParam(array $param) {
        $aWhere = [' 1=1 '];
        $aParam = [];

        if ($param['code'] != null) {
            $aWhere[] = " code = :code ";
            $aParam['code'] = $param['code'];
        }

        if ($param['nom'] != null) {
            $aWhere[] = " nom = :nom ";
            $aParam['nom'] = $param['nom'];
        }

        if ($param['entrepot_id'] != null) {
            $aWhere[] = " entrepot_id = :entrepot_id ";
            $aParam['entrepot_id'] = $param['entrepot_id'];
        }


        $query = "SELECT * FROM {$this->_tablename} WHERE ";

        $sWhere = implode(' AND ', $aWhere);

        $query .= $sWhere;

        $stmt = Database::prepare($query);
        $stmt->execute($aParam);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }


}




