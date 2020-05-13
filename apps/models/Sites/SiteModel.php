<?php
class Sites_SiteModel extends Model_Abstract
{
    protected $_tablename = 'ref_sites';
    public $_id = 'code';

    /**
     * Fonction pour l'insertion d'un site
     * @param array $data
     * @return mixed
     */
    public function create( array $data) {

        $this->insertOne($data);

        return $data['code'];
    }

    /**
     * Fonction de modification d'un site
     * @param array $data
     * @return mixed
     */
    public function update(array $data) {

        $this->updateBy($data, ['code' => $data['code']]);
    
        return $data['code'];
    }

    /**
     * Fonction de suppression d'un site
     */
    public function remove($code) {
        $oEntrepot = Apps::getModel('Sites_Entrepot');
        $oEntrepot->removeBySiteCode($this->_id);

        $query = "DELETE FROM {$this->_tablename} WHERE code = :code";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
    }

    /**
     * Fonction de lecture d'un site selon l'ID
     */
    public function getSiteByCode($code)
    {
        $query = "SELECT code, site_name FROM {$this->_tablename} WHERE code = :code";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();

        return $stmt->fetch(2);
    }
    
    /**
     * Fonction de liste des sites
     */
    public function getRegion()
    {
        $query = "SELECT * FROM {$this->_tablename} WHERE region_code IS NOT NULL AND region_code <> ''";
        
        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

    /**
     * Fonction de liste des sites
     */
    public function getSites()
    {
        $query = "SELECT code, site_name FROM {$this->_tablename}";
        
        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function getEntrepots()
    {
        $tEntrepots = array();
        $tListeEntrepot = Apps::getModel('Sites_Entrepot')->getEntrepotsBySite($this->getData('code'));
        foreach ($tListeEntrepot as $tEntrepot) {
            $oEntrepot = Apps::getModel('Sites_Entrepot');
            foreach ($tEntrepot as $k => $data) {
                $oEntrepot->setData($k, $data);
            }
            $tEntrepots[] = $oEntrepot;
        }

        return $tEntrepots;
    }

    public function exportByParam(array $param) {
        $aWhere = [' 1=1 '];
        $aParam = [];

        if ($param['code'] != null) {
            $aWhere[] = " code = :code ";
            $aParam['code'] = $param['code'];
        }

        if ($param['site_name'] != null) {
            $aWhere[] = " site_name = :site_name ";
            $aParam['site_name'] = $param['site_name'];
        }


        $query = "SELECT * FROM {$this->_tablename} WHERE ";

        $sWhere = implode(' AND ', $aWhere);

        $query .= $sWhere;

        $stmt = Database::prepare($query);
        $stmt->execute($aParam);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
}