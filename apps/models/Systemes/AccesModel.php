<?php

class Systemes_AccesModel extends Model_Abstract
{

    protected $_tablename = 'users_roles_liste_urls'; //tablename
    public $_id = 'id'; //table primary key

    /*
     * Suppression permission par id du lien 
     * @param int
     * @return boolean
     */

    public function removeByLink($iLink) {
        $sQuery = "DELETE FROM {$this->_tablename} WHERE id_liste_urls = :link";
        $oStmt = Database::prepare($sQuery);
        $oStmt->bindParam(':link', $iLink);

        return $oStmt->execute();
    }

    /*
     * Insertion multiple de plusieur permission pour un lien
     * @param array, int
     * @return boolean
     */

    public function insert($aData, $iLink) {
        $aRole = array();
        $aValues = array();
        if (count($aData) > 0) {
            foreach ($aData as $iKey => $item)
                $aRole[] = $iKey;

            foreach ($aRole as $iKey => $role)
                $aValues[] = "(:id_users_roles{$iKey}, :id_liste_urls)";

            $sValues = implode(' ,', $aValues);

            try {
                $sQuery = "INSERT INTO " . $this->_tablename . " (id_users_roles, id_liste_urls) VALUES $sValues";
                $oStmt = Database::prepare($sQuery);
                $oStmt->bindParam(":id_liste_urls", $iLink);
                foreach ($aRole as $iKey => $sItem) {
                    $sValue{$iKey} = $sItem;
                    $oStmt->bindParam(":id_users_roles{$iKey}", $sValue{$iKey});
                }
                $oStmt->execute();
                return TRUE;
            } catch (Exception $e) {
                die($e->getMessage());
                return FALSE;
            }
        }
        return FALSE;
    }

    public function checkAutorisation($link, $role) {
        $sQuery = "SELECT * "
                . "FROM " . $this->_tablename . " acces "
                . "JOIN liste_urls link ON link.id = acces.id_liste_urls "
                . "WHERE link.urls_segment = :url AND acces.id_users_roles = :role";

        $stmt = Database::prepare($sQuery);
        $stmt->execute(array('url' => $link, 'role' => $role));

        return $stmt->fetch(6);
    }

}
