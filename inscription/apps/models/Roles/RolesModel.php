<?php


class Roles_RolesModel extends Model_Abstract
{
    protected $_tablename = 'users_passvola';
    protected $_retatedTable = 'pack_role';
    public $_id = 'id';

    /**
     * @return array
     * Recupere les listes des pack roles
     */
    public function getPackRoles() {

        $query = "SELECT code, libelle FROM {$this->_retatedTable} ";
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(2);
    }

    /**
     * @return array
     * Recupere toutes les information sur l'utilisateur passvola
     */
    public function getAllRoles() {

        $query = "SELECT * FROM {$this->_tablename} ";
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetch(2);
    }

    /**
     * @param $data
     * @return bool
     * Création d'un utilisateur passvola
     * Création sur le réseaux passvola
     */
    public function create($data) {

        $query = "INSERT INTO {$this->_tablename} (id_appli, username_appli, pack, role_passvola, 
                  nom, prenom, identite, mail, telephone, parent_username) 
                  VALUES (:id_appli, :username_appli, :pack, :role_passvola, :nom, :prenom, 
                  :identite, :mail, :telephone, :parent_id, :parent_username) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_appli', $data['id_appli']);
        $stmt->bindParam(':username_appli', $data['username_appli']);
        $stmt->bindParam(':pack', $data['pack']);
        $stmt->bindParam(':role_passvola', $data['role_passvola']);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':prenom', $data['prenom']);
        $stmt->bindParam(':identite', $data['identite']);
        $stmt->bindParam(':mail', $data['mail']);
        $stmt->bindParam(':telephone', $data['telephone']);
        //$stmt->bindParam(':parent_id', $data['parent_id']);

        return $stmt->execute();
    }

    public function getAllUsers($requestSearch) {
        $tWhere = array();
        $tParam = array();
        $tJoin = array();
        $sWhere = '';

        $tColumns = array('nom' => 'LIKE', 'prenom' => 'LIKE', 'username_appli' => 'LIKE');

        if ($requestSearch['nom'] != NULL) {
            $tWhere[] = " p.nom LIKE :nom";
            $tParam['nom'] = '%' . $requestSearch['nom'] . '%';
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
     * @param $username
     * @return mixed
     * Filtrer un utilisateur par son pack
     */
    public function getPackByUserName($username) {

        $query = "SELECT pack, username_appli, id_appli FROM {$this->_tablename} WHERE username_appli =:username ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    public function checkUser($data) {

        $query = "UPDATE {$this->_tablename} SET confirme_check =:confirme_check, date_confirmation =:date_confirmation WHERE id =:id";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':confirme_check', $data['confirme_check']);
        $stmt->bindParam(':date_confirmation', $data['date_confirmation']);
        $stmt->bindParam(':id', $data['id']);

        return $stmt->execute();
    }

    public function getIfCheck($id) {

        $query = "SELECT * FROM {$this->_tablename} WHERE id_appli =:id ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(2);
    }

    /**
     * @param $data
     * @return bool
     */
    public function createFirst($data) {

        $query = "INSERT INTO {$this->_tablename} (id_appli, username_appli, role_passvola) 
                  VALUES (:id_appli, :username_appli, :role_passvola) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_appli', $data['id_appli']);
        $stmt->bindParam(':username_appli', $data['username_appli']);
        $stmt->bindParam(':role_passvola', $data['role_passvola']);

        return $stmt->execute();
    }

    /**
     * @param $data
     * @return bool
     */
    public function createSecond($data) {

        $query = "UPDATE {$this->_tablename} SET parent_username =:parent_username, pack =:nom_pack, 
                  parent_id =:parent_id WHERE id_appli =:id_appli AND username_appli =:username_appli ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':parent_username', $data['parent_username']);
        $stmt->bindParam(':nom_pack', $data['nom_pack']);
        $stmt->bindParam(':username_appli', $data['username_appli']);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        $stmt->bindParam(':id_appli', $data['id_appli']);

        return $stmt->execute();
    }

    public function createThird($data) {

        $query = "UPDATE {$this->_tablename} SET nom =:nom, prenom =:prenom, mail =:mail, telephone =:telephone, 
                 adresse =:adresse, identite =:identite WHERE id_appli =:id_appli AND username_appli =:username_appli ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':prenom', $data['prenom']);
        $stmt->bindParam(':mail', $data['mail']);
        $stmt->bindParam(':telephone', $data['telephone']);
        $stmt->bindParam(':adresse', $data['adresse']);
        $stmt->bindParam(':identite', $data['identite']);
        $stmt->bindParam(':id_appli', $data['id_appli']);
        $stmt->bindParam(':username_appli', $data['username_appli']);

        return $stmt->execute();
    }

    /**
     * @param $id
     * @return mixed
     * Recupere le role ie : avec parrain ou sans parrain
     */
    public function getRoles($id) {

        $query = "SELECT role_passvola, pack FROM {$this->_tablename} WHERE id_appli =:id ";
        $stmt = Database::prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    /**
     * @param $id
     * @return mixed
     * Get user by his id
     */
    public function getById($id) {

        $query = "SELECT * FROM {$this->_tablename} WHERE id =:id ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(2);
    }

}