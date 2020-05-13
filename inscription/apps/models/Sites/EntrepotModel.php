<?php

class Sites_EntrepotModel extends Model_Abstract
{

    protected $_tablename = 'ref_sites_entrepots';
    public $_id = 'code';

    public function getEntrepots() {
        $query = "SELECT code, site_id, description FROM {$this->_tablename} ORDER BY site_id ASC";

        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function getEntrepotsBySite($site_id) {
        $query = "SELECT code, site_id, description FROM {$this->_tablename} WHERE site_id = :site_id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':site_id', $site_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function getEntrepotsWithStockBySite($siteId, $article) {
        $query = "SELECT DISTINCT(s.entrepot_id) as code, e.description "
                . "FROM ref_articles_inventaires as s "
                . "LEFT OUTER JOIN " . $this->_tablename . " e ON e.code = s.entrepot_id "
                . "WHERE s.site_id = :site AND s.article_id = :article";

        $stmt = Database::prepare($query);
        $stmt->execute(array('site' => $siteId, 'article' => $article));
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function getEntrepotByCode($code) {
        $query = "SELECT code, site_id, description FROM {$this->_tablename} WHERE code = :code ORDER BY site_id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    /**
     * Fonction de création d'entrepôt dans la base
     */
    public function create($data) {
        $query = "INSERT INTO {$this->_tablename} values(:code, NULL, :site_id, :description, :ferme)";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':site_id', $data['site_id']);
        $stmt->bindParam(':ferme', $data['ferme']);

        $stmt->execute();

        return $data['code'];
    }

    /**
     * Fonction de suppression d'entrepôt
     */
    public function remove($code) {
        $oEmplacement = Apps::getModel('Sites_Emplacement');
        $oEmplacement->removeByEntrepotId($code);

        $query = "DELETE FROM {$this->_tablename} WHERE code = :code";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $code);

        $stmt->execute();
    }

    /**
     * Fonction de modification d'entrepôt
     */
    public function update($data) {
        $query = "UPDATE {$this->_tablename} SET description = :description, site_id = :site_id, ferme = :ferme WHERE code = :code";

        $stmt = Database::prepare($query);

        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':site_id', $data['site_id']);
        $stmt->bindParam(':ferme', $data['ferme']);

        $stmt->execute();

        return $data['code'];
    }

    /**
     * Fonction de suppression d'entrepôts selon le site_id
     */
    public function removeBySiteId($site_id) {
        $query = "DELETE FROM {$this->_tablename} WHERE site_id = :site_id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':site_id', $site_id);
        $stmt->execute();
    }

    public function getEmplacements() {
        $tEmplacements = array();
        $tListeEmplacement = Apps::getModel('Sites_Emplacement')->getEmplacementsByEntrepot($this->getData('code'));
        foreach ($tListeEmplacement as $tEmplacement) {
            $oEmplacement = Apps::getModel('Sites_Emplacement');
            foreach ($tEmplacement as $k => $data) {
                $oEmplacement->setData($k, $data);
            }
            $tEmplacements[] = $oEmplacement;
        }

        return $tEmplacements;
    }

    public function getAll(array $params = [])
    {
        $aWhere = [" 1 = 1 "];
        $aParam = [];

        if ($params['site_id'] != null) {
            $aWhere[] = " site_id = :site_id ";
            $aParam['site_id'] = $params['site_id'];
        }
        if ($params['code'] != null) {
            $aWhere[] = " code LIKE :code ";
            $aParam['code'] = "%" . $params['code'] . "%" ;
        }
        if ($params['description'] != null) {
            $aWhere[] = " description LIKE :description ";
            $aParam['description'] = "%" . $params['description'] . "%";
        }



        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "SELECT * FROM "
            . $this->_tablename
            . " WHERE $sWhere ";

        $sQueryCount = "SELECT count(code) FROM "
            . $this->_tablename
            . " WHERE $sWhere ";


        $aColumns = ['code' => 'LIKE', 'description' => 'LIKE', 'site_id' => '='];
        $oPagination = Apps::usePlugin('FilterData');
        $oPagination->initialize($this, $aColumns);
        $aData = $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, false);
        return $aData;
    }

    public function exportByParam(array $param) {
        $aWhere = [' 1=1 '];
        $aParam = [];

        if ($param['code'] != null) {
            $aWhere[] = " code = :code ";
            $aParam['code'] = $param['code'];
        }

        if ($param['site_id'] != null) {
            $aWhere[] = " site_id = :site_id ";
            $aParam['site_id'] = $param['site_id'];
        }

        if ($param['description'] != null) {
            $aWhere[] = " description = :description ";
            $aParam['description'] = $param['description'];
        }


        $query = "SELECT * FROM {$this->_tablename} WHERE ";

        $sWhere = implode(' AND ', $aWhere);

        $query .= $sWhere;

        $stmt = Database::prepare($query);
        $stmt->execute($aParam);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

}
