<?php

class Systemes_LogRoleUserModel extends Model_Abstract {

    protected $_tablename = 'users_role_log'; //tablename
    public $_id = 'id'; //table primary key

    public function findAll($aRequestSearch, $with_pagination = true) {
        $aWhere = array('1=1');
        $aParam = array();

        $aColumns = array('username' => '=', 'lastname' => '=', 'firstname' => '=');

        if ($aRequestSearch['lastname'] != null) {
            $aWhere[] = " u.lastname LIKE :lastname ";
            $aParam['lastname'] = "%" . $aRequestSearch['lastname'] . "%";
        }
        if ($aRequestSearch['firstname'] != null) {
            $aWhere[] = " u.firstname LIKE :firstname ";
            $aParam['firstname'] = "%" . $aRequestSearch['firstname'] . "%";
        }
        if ($aRequestSearch['username'] != null) {
            $aWhere[] = " u.username LIKE :username ";
            $aParam['username'] = "%" . $aRequestSearch['username'] . "%";
        }
        if ($aRequestSearch['debut'] != null) {
            $aWhere[] = " log.updated_at >= :debut ";
            $aParam['debut'] = Apps::convertDate($aRequestSearch['debut']);
        }
        if ($aRequestSearch['fin'] != null) {
            $aWhere[] = " log.updated_at <= :fin ";
            $aParam['fin'] = Apps::convertDate($aRequestSearch['fin']);
        }
         if ($aRequestSearch['type'] != null) {
            $aWhere[] = " log.type = :type ";
            $aParam['type'] = $aRequestSearch['type'];
        }

        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "SELECT log.updated_at,log.motif, log.resolution, u.lastname, u.firstname, log.type, "
                . "u.username, "
                . "(CASE WHEN log.type = 'Ajout rôle' "
                . " THEN (SELECT GROUP_CONCAT(sub_query.role_name) "
                . "FROM (SELECT us.lastname, us.firstname, us.id, l.updated_at, l.type, r.role_name "
                . "FROM users us JOIN users_role_log l ON us.id = l.user "
                . "LEFT OUTER JOIN users_roles as r ON r.id = l.new_role "
                . "WHERE 1 ) sub_query WHERE sub_query.lastname = u.lastname AND sub_query.firstname = u.firstname AND sub_query.updated_at < log.updated_at  "
                . "HAVING MAX(sub_query.updated_at))"
                . " ELSE r_old.role_name  END) old_role"
                . ", r_new.role_name new_role "
                . "FROM $this->_tablename log "
                . "JOIN users u ON u.id = log.user "
                . "LEFT OUTER JOIN users_roles as r_old ON r_old.id = log.old_role "
                . "LEFT OUTER JOIN users_roles as r_new ON r_new.id = log.new_role "
                . "WHERE $sWhere "
                . "ORDER BY log.updated_at, u.id ASC";

//        echo $sQuery;        var_dump($aParam);die;

        if ($with_pagination) {
            $sQueryCount = "SELECT count(u.id) "
                    . "FROM $this->_tablename log "
                    . "JOIN users u ON u.id = log.user "
                    . "WHERE $sWhere ";

            $oPagination = Apps::usePlugin('FilterData');
            $oPagination->initialize($this, $aColumns);
            $aData = $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, false);

            return $aData;
        } else {
            $stmt = Database::prepare($sQuery);
            $stmt->execute($aParam);
            return $stmt->fetchAll(2);
        }
    }

    public function reporting($aData) {
        $oExporter = Apps::usePlugin('ExportData/Excel');
        $oExporter->initialize('browser', 'Historique_role_user_' . date('dmY') . '.xls');
        $values = $bg = array();
        $titre = array(
            'A1' => "Nom",
            'B1' => "Prénom",
            'C1' => "Nom d'utilisateur",
            'D1' => "Rôle existant",
            'E1' => "Nouveau rôle",
            'F1' => "Type",
            'G1' => "Résolution",
            'H1' => "Motif du remplacement",
            'I1' => "Date de mise à jour",
        );
        $bg['A1:I1'] = '318CE7';
        $oExporter->setFontStyle(array("A1:I1" => array('font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => 'FFFFFF')))));

        $row = 2;
        foreach ($aData as $item) {
            if ($row % 2 == 1)
                $bg["A$row:I$row"] = 'e4ebfc';

            $values["A$row"] = $item['lastname'];
            $values["B$row"] = $item['firstname'];
            $values["C$row"] = $item['username'];
            $values["D$row"] = $item['old_role'];
            $values["E$row"] = $item['new_role'];
            $values["F$row"] = $item['type'];
            $values["G$row"] = $item['resolution'];
            $values["H$row"] = $item['motif'];
            $values["I$row"] = date('d/m/Y', strtotime($item['updated_at']));
            $row ++;
        }

        $border_all = array('A1:I' . ($row - 1) => 'solid');
        $dimension = array('A' => 20, 'B' => 20, 'C' => 15, 'D' => 45, 'E' => 38, 'F' => 17, 'G' => 15, 'H' => 35, 'I' => 16);

        $oExporter->addCells(array_merge($titre, $values));
        $oExporter->setDimension($dimension);
        $oExporter->setBorder($border_all);
        $oExporter->setBg($bg);
        $oExporter->finalize();
    }

}
