<?php


class Affilie_AffilieModel extends Model_Abstract
{
    protected $_tablename = 'users_tree';
    protected $_relatedTable = 'users_passvola';

    /**
     * @param $data
     * @return bool
     * Sauvegarder les id du parent et des enfants
     */
    public function saveTree($data) {

        $query = "INSERT INTO {$this->_tablename} (id_up, id_child, username_up, username_child) 
                 VALUES (:id_up, :id_child, :username_up, :username_child) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_up', $data['id_up']);
        $stmt->bindParam(':id_child', $data['id_child']);
        $stmt->bindParam(':username_up', $data['username_up']);
        $stmt->bindParam(':username_child', $data['username_child']);

        return $stmt->execute();
    }

    /**
     * @param $data
     * @return array
     * Recupere l'arborescence du membre
     */
    public function getAllMembers($data) {

        $query = "SELECT * FROM {$this->_tablename} tree 
                  WHERE tree.id_parent =:id ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->execute();

        $direct = $stmt->fetchAll(2);

        $result = [];
        foreach ($direct as $item) {

            //Commons::dump($item);

            $childQuery = "SELECT * FROM {$this->_tablename} tree WHERE id_parent = ".$item['id'];
            $stmt = Database::prepare($childQuery);
            $stmt->execute();
            $result = $stmt->fetchAll(2);

        }
        $tree = array_merge($direct, $result);
        //Commons::dump($tree);
        return $tree;
    }
}