<?php

class Systemes_LinkModel extends Model_Abstract {

    protected $_tablename = 'liste_urls'; //tablename
    public $_id = 'id'; //table primary key

    /*
     * Creation d'un lien
     * @param array
     * @return int
     */

    public function create($data) {
        $sQuery = "INSERT INTO " . $this->_tablename
                . " (urls_segment, titre, menu) "
                . "VALUES(:urls_segment, :titre, :menu)";
        $oStmt = Database::prepare($sQuery);
        $oStmt->bindParam(':urls_segment', $data['urls_segment']);
        $oStmt->bindParam(':titre', $data['titre']);
        $oStmt->bindParam(':menu', $data['menu']);
        $oStmt->execute();

        return Database::lastInsertId($this->_tablename);
    }

    /*
     * Modification d'un lien
     * @param array
     * @return int
     */

    public function update($aData) {
        $sQuery = "UPDATE " . $this->_tablename
                . " SET urls_segment = :urls_segment, titre = :titre, menu = :menu  "
                . "WHERE id = :id";

        $oStmt = Database::prepare($sQuery);
        $oStmt->bindParam(':id', $aData['id']);
        $oStmt->bindParam(':urls_segment', $aData['urls_segment']);
        $oStmt->bindParam(':titre', $aData['titre']);
        $oStmt->bindParam(':menu', $aData['menu']);
        $oStmt->execute();

        return Database::lastInsertId($this->_tablename);
    }

    public function update1() {
        $aData = $this->getBy();

        foreach ($aData as $item) {
            $sQuery = "UPDATE " . $this->_tablename
                    . " SET menu = :menu , titre = :titre "
                    . "WHERE id = :id";

            $oStmt = Database::prepare($sQuery);
            $url = explode('/', $item['urls_segment']);
            $oStmt->execute(array(
                'menu' => $url[0] . '_' . $url[1],
                'titre' => isset($url[2]) ? $url[2] . ' ' . $url[1] : $url[1],
                'id' => $item['id']
            ));
        }
    }

    public function findAll($aRequestSearch) {
        $aWhere = array('1=1');
        $aParam = array();

        $aColumns = array('username' => '=', 'lastname' => '=', 'firstname' => '=', 'role' => '=');

        if ($aRequestSearch['urls_segment'] != null) {
            $aWhere[] = " l.urls_segment LIKE :urls_segment ";
            $aParam['urls_segment'] = "%" . $aRequestSearch['urls_segment'] . "%";
        }
        if ($aRequestSearch['titre'] != null) {
            $aWhere[] = " l.titre LIKE :titre ";
            $aParam['titre'] = "%" . $aRequestSearch['titre'] . "%";
        }

        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "SELECT l.* FROM " . $this->_tablename . " as l "
                . "WHERE $sWhere ";

        $sQueryCount = "SELECT count(l.id) FROM " . $this->_tablename . " as l WHERE $sWhere ";

        $oPagination = Apps::usePlugin('FilterData');
        $oPagination->initialize($this, $aColumns);
        $aData = $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, false);

        return $aData;
    }

    public function find($iId) {
        $sQuery = "SELECT l.*, m.parent "
                . "FROM " . $this->_tablename . " as l "
                . "LEFT OUTER JOIN system_menu as m ON m.code = l.menu "
                . "WHERE l.id = :id ";

        $oStmt = Database::prepare($sQuery);
        $oStmt->execute(array('id' => $iId));
        return $oStmt->fetch(PDO::FETCH_ASSOC);
    }

}
