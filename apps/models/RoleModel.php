<?php
class RoleModel extends Model_Abstract
{

	protected $_tablename = 'users_roles'; //tablename
	
	public $_id =  'id'; //table primary key
	
	public function getRoles()
	{
		$query = "SELECT id, code,role_name FROM ". $this->_tablename ." ORDER BY id DESC";
		$stmt = Database::prepare($query);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_CLASS);
	}
	
	public function getRolesID()
	{
		$query = "SELECT id FROM ". $this->_tablename ." ORDER BY id DESC";
		$stmt = Database::prepare($query);
		$stmt->execute();
		$listeId = $stmt->fetchAll(PDO::FETCH_CLASS);
		$listeIdToret = array();
		if(count($listeId)>0){
			
			foreach ($listeId as $id){
				array_push($listeIdToret, $id->id);
			}
		}
		return $listeIdToret;
	}
	
	public function getRole_idByCode($code)
	{
		$query = "SELECT id, code,role_name FROM ". $this->_tablename ." WHERE code = :code";
	
		$stmt = Database::prepare($query);
		$stmt->bindParam(':code', $code);
		$stmt->execute();
		return $stmt->fetchObject();
	}
	public function getRoleWithPermission($iIdLink) {
        $sQuery = "SELECT r.* "
                . ",(SELECT p.id FROM users_roles_liste_urls p "
                . "WHERE p.id_users_roles = r.id AND p.id_liste_urls = :id_link) as permission "
                . "FROM " . $this->_tablename . " r "
                . "WHERE r.id <> 1 "
                . "ORDER BY r.id DESC";
        
        $oStmt = Database::prepare($sQuery);
        $oStmt->execute(array('id_link' => $iIdLink));
        
        return $oStmt->fetchAll(PDO::FETCH_CLASS);
    }
	public function getRoleById($id)
	{
		$query = "SELECT id, code,role_name FROM ". $this->_tablename ." WHERE id = :id";
	
		$stmt = Database::prepare($query);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		return $stmt->fetchObject();
	}
	
	/**
	 * Fonction pour l'isertion d'un role
	 */
	public function create($data){
		$query = "INSERT INTO ". $this->_tablename ." (code, role_name)
			VALUES(:code, :role_name)";

		$stmt = Database::prepare($query);

		$stmt->bindParam(':code', $data['code']);
		$stmt->bindParam(':role_name', $data['role_name']);

		$stmt->execute();

		return Database::lastInsertId($this->_tablename);
	}
	
	public function update($data){
		$query = "UPDATE ". $this->_tablename." ";
		$query .= "SET code = :code, role_name = :role_name ";
		$query .= "WHERE id = :id";

		$stmt = Database::prepare($query);

		$stmt->bindParam(':id', $data['id']);
		$stmt->bindParam(':code', $data['code']);
		$stmt->bindParam(':role_name', $data['role_name']);

		$stmt->execute();

		return Database::lastInsertId($this->_tablename);
	}
	public function remoove($id){
		$query = "DELETE FROM {$this->_tablename} WHERE {$this->_id} = :id";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':id', $id);
		return $stmt->execute();
	}
}