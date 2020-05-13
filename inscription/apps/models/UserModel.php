<?php

class UserModel extends Model_Abstract {

    protected $_tablename = 'users'; //tablename
    public $_id = 'id'; //table primary key

    /**
     * Fonction pour l'isertion d'un utilisateur
     */

    public function create($data) {
        $query = "INSERT INTO " . $this->_tablename . " (role_id, username, password, lastname, firstname, user_site_code, created_at )
			VALUES(:role_id, :username, :password, :lastname, :firstname, :user_site_code, NOW() )";

        $stmt = Database::prepare($query);
        $password = Apps::encryptIt($data['password']);

        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':firstname', $data['firstname']);
        $stmt->bindParam(':user_site_code', $data['user_site_code']);

        $stmt->execute();

        return Database::lastInsertId($this->_tablename);
    }

    public function update($data) {
        $query = "UPDATE " . $this->_tablename . " ";
        $query .= "SET role_id = :role_id, username = :username, password =:password, lastname = :lastname, firstname = :firstname, user_site_code = :user_site_code ";
        $query .= "WHERE id = :id";

        $stmt = Database::prepare($query);
        $password = (String) Apps::encryptIt($data['password']);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':firstname', $data['firstname']);
        $stmt->bindParam(':user_site_code', $data['user_site_code']);

        $stmt->execute();

        return Database::lastInsertId($this->_tablename);
    }

    public function getUsersAndRole() {
        $query = "SELECT usrs.id, usrs.firstname, usrs.lastname,usrs.username,usrs_rls.role_name FROM " . $this->_tablename . " AS usrs ";
        $query .= "LEFT JOIN users_roles AS usrs_rls ON usrs.role_id = usrs_rls.id ";
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function getById($id) {
        $query = "SELECT usrs.id, usrs.user_site_code, usrs.firstname, usrs.password, usrs.lastname,usrs.username,usrs_rls.id AS role_id FROM " . $this->_tablename . " AS usrs ";
        $query .= "LEFT JOIN users_roles AS usrs_rls ON usrs.role_id = usrs_rls.id ";
        $query .= "WHERE usrs.id=" . $id;
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchObject();
    }

    /**
     *
     * Get Role user by user id
     *
     * @param $id
     * @return mixed
     */
    public function getRoleUsrById($id) {
        $query = "SELECT usrs.username,usrs_rls.code AS role FROM " . $this->_tablename . " AS usrs ";
        $query .= "LEFT JOIN users_roles AS usrs_rls ON usrs.role_id = usrs_rls.id ";
        $query .= "WHERE usrs.id=" . $id;
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchObject();
    }

    public function remoove($id) {
        $query = "DELETE FROM {$this->_tablename} WHERE {$this->_id} = :id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getUserLogin($userName, $password) {
        $query = "SELECT usrs.id, usrs.user_site_code, "
                . "usrs.firstname, usrs.password, "
                . "usrs.lastname,usrs.username,usrs_rls.id AS role_id, usrs_rls.role_name as role_name, usrs.first_connect, is_disabled "
                . "FROM " . $this->_tablename . " AS usrs ";
        $query .= "LEFT JOIN users_roles AS usrs_rls ON usrs.role_id = usrs_rls.id ";
        $query .= "WHERE usrs.username= :username";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':username', $userName);
        $stmt->execute();
        $preselectUser = $stmt->fetchAll(PDO::FETCH_CLASS);
        if (count($preselectUser) > 0) {
            foreach ($preselectUser as $key => $user) {
                if ($password == $user->password) {
                    return $preselectUser[$key];
                }
            }
        }
        return false;
    }

    public function getAuthorisedUrlBiRole($idRole) {
        $query = "SELECT lurl.urls_segment FROM users_roles_liste_urls AS usrs_rl ";
        $query .= "LEFT JOIN  liste_urls AS lurl ON usrs_rl.id_liste_urls = lurl.id ";
        $query .= "WHERE usrs_rl.id_users_roles= :id_users_roles";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_users_roles', $idRole);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    public function findAll($aRequestSearch) {
        $aWhere = array('1=1');
        $aParam = array();
        $aJoin = array(' LEFT OUTER JOIN users_roles as r ON r.id = u.role_id ');

        $aColumns = array('username' => '=', 'lastname' => '=', 'firstname' => '=', 'role' => '=');

        if ($aRequestSearch['role_id'] != null) {
            $aWhere[] = " u.role_id = :role ";
            $aParam['role'] = $aRequestSearch['role_id'];
        }

        $sJoin = implode(' ', $aJoin);
        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "SELECT u.*,r.role_name as role FROM " . $this->_tablename . " as u $sJoin WHERE $sWhere ";
        $sQueryCount = "SELECT count(u.id) FROM " . $this->_tablename . " as u $sJoin WHERE $sWhere ";

        $oPagination = Apps::usePlugin('FilterData');
        $oPagination->initialize($this, $aColumns);
        $aData = $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, false);

        return $aData;
    }

    public function countUserBiRole($idRole) {
        $query = "
			SELECT
				COUNT(usr.role_id) as affected_users FROM " . $this->_tablename . "  AS usr
			WHERE usr.role_id = :role_id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':role_id', $idRole);
        $stmt->execute();
        $return = $stmt->fetchAll(PDO::FETCH_CLASS);

        return $return[0]->affected_users;
    }

    /**
     *
     * test if user in admin or not using $id
     *
     * @param $id
     * @return bool
     */
    public function isAdmin($id) {
        $user = $this->getRoleUsrById($id);

        if ("admin" == $user->role) {
            return true;
        }

        return false;
    }

    public function etatSecurite($aRequestSearch, $with_pagination = true) {
        $aWhere = $aParam = array();

        if ($aRequestSearch['role'] != null) {
            $aWhere[] = " u.role_id = :role ";
            $aParam['role'] = $aRequestSearch['role'];
        }

        if ($aRequestSearch['username'] != null) {
            $aWhere[] = " u.username LIKE :username ";
            $aParam['username'] = "%" . $aRequestSearch['username'] . "%";
        }

        if ($aRequestSearch['debut'] != null) {
            $aWhere[] = " u.first_connect >= :debut ";
            $aParam['debut'] = Apps::convertDate($aRequestSearch['debut']);
        }

        if ($aRequestSearch['fin'] != null) {
            $aWhere[] = " u.first_connect <= :fin ";
            $aParam['fin'] = Apps::convertDate($aRequestSearch['fin']);
        }

        if ($aRequestSearch['user'] != null) {
            $aWhere[] = " u.id IN (SELECT u2.id FROM users u1 LEFT JOIN users u2 ON u2.firstname = u1.firstname AND u2.lastname = u1.lastname WHERE u1.id = :user)";
            $aParam['user'] = $aRequestSearch['user'];
        }

        if ($aRequestSearch['tache'] != null) {
            $aWhere[] = " link.id = :tache ";
            $aParam['tache'] = $aRequestSearch['tache'];
        }

        $sWhere = !empty($aWhere) ? implode(" AND ", $aWhere) : ' 1=1 ';

        $sQuery = "SELECT u.firstname, u.lastname, u.username, u.first_connect, link.titre as tache, r.role_name as role"
                . ", sm.titre as sm_titre, sm.label as sm_label,  m.titre as m_titre, m.label as m_label "
                . "FROM users u "
                . "LEFT OUTER JOIN users_roles_liste_urls p ON p.id_users_roles = u.role_id "
                . "JOIN liste_urls as link ON link.id = p.id_liste_urls "
                . "LEFT JOIN system_menu as sm ON link.menu = sm.code "
                . "LEFT JOIN system_menu as m ON sm.parent = m.code "
                . "JOIN users_roles r ON r.id = u.role_id "
                . "WHERE $sWhere ";

        if ($with_pagination) {
            $sQueryCount = "SELECT count(u.id) "
                    . "FROM users u "
                    . "LEFT OUTER JOIN users_roles_liste_urls p ON p.id_users_roles = u.role_id "
                    . "JOIN liste_urls as link ON link.id = p.id_liste_urls "
                    . "WHERE $sWhere ";

            $oPagination = Apps::usePlugin('FilterData');
            $oPagination->initialize($this, array());
            $aData = $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, false);

            return $aData;
        } else {
            $stmt = Database::prepare($sQuery);
            $stmt->execute($aParam);
            return $stmt->fetchAll(2);
        }
    }

    public function getUniqueUserByName() {

        $query = "SELECT id, lastname, firstname FROM users GROUP BY lastname, firstname";

        $stmt = Database::prepare($query);

        $stmt->execute();
        return $stmt->fetchAll(2);
    }

    public function reporting($aData) {
        $oExporter = Apps::usePlugin('ExportData/Excel');
        $oExporter->initialize('browser', 'Rapport_etat_securite_' . date('dmY') . '.xls');
        $values = $bg = array();
        $titre = array(
            'A1' => "Nom",
            'B1' => "Prénom",
            'C1' => "Nom d'utilisateur",
            'D1' => "Date 1er login",
            'E1' => "Rôle",
            'F1' => "Menu",
            'G1' => "Sous-menu",
            'H1' => "Tâches autorisées"
        );
        $bg['A1:H1'] = '318CE7';
        $oExporter->setFontStyle(array("A1:H1" => array('font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => 'FFFFFF')))));

        $row = 2;
        foreach ($aData as $item) {
            $sous_menu = $item['sm_titre'] ? $item['sm_titre'] : $item['sm_label'];
            $menu = $item['m_titre'] ? $item['m_titre'] : $item['m_label'];
            if (is_null($menu)) {
                $menu = $sous_menu;
                $sous_menu = "";
            }

            if ($row % 2 == 1)
                $bg["A$row:H$row"] = 'e4ebfc';
            $values["A$row"] = $item['lastname'];
            $values["B$row"] = $item['firstname'];
            $values["C$row"] = $item['username'];
            $values["D$row"] = $item['first_connect'] ? date('d/m/Y', strtotime($item['first_connect'])) : '';
            $values["E$row"] = $item['role'];
            $values["F$row"] = $menu;
            $values["G$row"] = $sous_menu;
            $values["H$row"] = $item['tache'];
            $row ++;
        }

        $border_all = array('A1:H' . ($row - 1) => 'solid');
        $dimension = array('A' => 30, 'B' => 30, 'C' => 15, 'D' => 15, 'E' => 38, 'F' => 35, 'G' => 35, 'H' => 55);

        $oExporter->addCells(array_merge($titre, $values));
        $oExporter->setDimension($dimension);
        $oExporter->setBorder($border_all);
        $oExporter->setBg($bg);
        $oExporter->finalize();
    }

    /**
     * @param array $data
     * @return int
     * Fonction pour s'inscire directement de la page d'accueil
     */
    public function initializeUser(array $data) {

        $sQuery = "INSERT INTO " . $this->_tablename . " 
                    (role_id, username, password, lastname, firstname, user_site_code, created_at )
			        VALUES(:role_id, :username, :password, :lastname, :firstname, :user_site_code, NOW() )";

        $stmt = Database::prepare($sQuery);

        //$password = Apps::encryptIt($data['password']);

        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $data['password']);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':firstname', $data['firstname']);
        $stmt->bindParam(':user_site_code', $data['user_site_code']);

        $stmt->execute();

        return Database::lastInsertId($this->_tablename);
    }

    public function checkIfUserExist($username) {

        $query = "SELECT * FROM {$this->_tablename} WHERE username =:user ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':user', $username);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

}
