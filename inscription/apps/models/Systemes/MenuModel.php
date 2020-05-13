<?php

class Systemes_MenuModel extends Model_Abstract {

    protected $_tablename = 'system_menu';
    protected $_linkTablename = 'system_menu_role';
    public $_id = 'id';

    /*
     * Donnée brute liste menu, sous menu, lien avec leur permission par rôle => prend en charge un filtre par titre et url
     */

    public function findAll($aData, $with_pagination = true, $isExport = false) {
        $aColumns = array();
        $aParam = array();
        $aWhere = array(" p.parent IS NULL ");
        $sSelect = $isExport ? " p.icon menu_icon, p.slug menu_slug, p.module menu_module, "
                . "p.controller menu_controller, p.action menu_action, p.consolide menu_consolide, "
                . "p.cible menu_cible, p.ordre_parent menu_ordre, IF(url.urls_segment IS NOT NULL, url.urls_segment, url1.urls_segment) url_segment"
                . ", IF(url.notDeletable IS NOT NULL, url.notDeletable, url1.notDeletable) url_notDeletable, " : "";

        if ($aData['titre'] != null) {
            $aWhere[] = " (c.label LIKE :titre OR c.titre LIKE :titre OR p.label LIKE :titre OR p.titre LIKE :titre OR url.titre LIKE :titre) ";
            $aParam['titre'] = "%" . $aData['titre'] . "%";
        }

        $aParam['role_name'] = "%" . $aData['role_name'] . "%";

        if (IS_ADMIN) {
            $aWhere[] = " c.consolide = 1 ";
        } else {
            $aWhere[] = " c.cible = 1 ";
        }
//        $aWhere[] = " c.consolide = " . intval(IS_CONSO) . " ";
//        $aParam['isConso'] = intval(IS_CONSO);

        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "SELECT c.*, p.titre as menu_titre, p.label as menu_label , p.code menu_code , $sSelect "
                . "IF(url.titre IS NOT NULL, url.titre, url1.titre) url, IF(url.id IS NOT NULL, url.id, url1.id) url_id, "
                . "(SELECT GROUP_CONCAT(role) FROM $this->_linkTablename LEFT JOIN users_roles r ON r.id = role WHERE menu = p.code AND r.role_name LIKE :role_name) as menu_role, "
                . "(SELECT GROUP_CONCAT(role) FROM $this->_linkTablename LEFT JOIN users_roles r ON r.id = role WHERE menu = c.code AND r.role_name LIKE :role_name) as sub_menu_role, "
                . "(CASE "
                . "WHEN url.id IS NOT NULL THEN (SELECT GROUP_CONCAT(id_users_roles) FROM users_roles_liste_urls WHERE id_liste_urls = url.id) "
                . "ELSE (SELECT GROUP_CONCAT(id_users_roles) FROM users_roles_liste_urls LEFT JOIN users_roles r ON r.id = id_users_roles WHERE id_liste_urls = url1.id AND r.role_name LIKE :role_name) "
                . "END) as link_role "
                . "FROM " . $this->_tablename . " p "
                . "LEFT OUTER JOIN $this->_tablename c ON p.code = c.parent "
                . "LEFT OUTER JOIN liste_urls url ON url.menu = c.code "
                . "LEFT OUTER JOIN liste_urls url1 ON url1.menu = p.code "
                . "WHERE $sWhere ORDER BY p.ordre_parent ASC, c.ordre_child ASC ";

        if ($with_pagination) {
            $sQueryCount = "SELECT count(*) FROM " . $this->_tablename . " as p "
                    . "LEFT OUTER JOIN $this->_tablename c ON p.code = c.parent "
                    . "LEFT OUTER JOIN liste_urls url ON url.menu = c.code "
                    . "LEFT OUTER JOIN liste_urls url1 ON url1.menu = p.code "
                    . "WHERE $sWhere";

            $oPagination = Apps::usePlugin('FilterData');
            $oPagination->initialize($this, $aColumns);
            return $oPagination->paginateQuery($sQuery, $sQueryCount, $aParam, array());
        } else {
            $stmt = Database::prepare($sQuery);
            $stmt->execute($aParam);
            return $stmt->fetchAll(2);
        }
    }

    /*
     * Liste des menu (parent)
     */

    public function getMenuParent() {
        $query = "SELECT m.* "
                . "FROM {$this->_tablename} m "
                . "WHERE parent IS NULL";

        $stmt = Database::prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Liste sous menu d'un menu spécifié
     */

    public function getSubMenu($menu) {
        $query = "SELECT m.* "
                . "FROM {$this->_tablename} m "
                . "WHERE m.parent LIKE :parent";

        $stmt = Database::prepare($query);

        $stmt->execute(array('parent' => $menu));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Pour facilité l'affichage de la liste des menu, sous menu et lien avec leur permission pour chaque rôle
     */

    public function traitementMenu($aData) {
        $aMenu = array();
        foreach ($aData as $item) {
            if (!isset($aMenu[$item['menu_code']])) {
                $aMenu[$item['menu_code']] = array(
                    'code' => $item['menu_code'],
                    'titre' => $item['menu_titre'],
                    'label' => $item['menu_label'],
                    'roles' => explode(',', $item['menu_role']),
                    'child' => array()
                );
            }

            if (!isset($aMenu[$item['menu_code']]['child'][$item['code']])) {
                $aMenu[$item['menu_code']]['child'][$item['code']] = array(
                    'code' => $item['code'],
                    'titre' => $item['titre'],
                    'label' => $item['label'],
                    'roles' => explode(',', $item['sub_menu_role']),
                    'link' => array()
                );
            }
            if ($item['url'] != null)
                $aMenu[$item['menu_code']]['child'][$item['code']]['link'][] = array(
                    'code' => $item['url_id'],
                    'url' => $item['url'],
                    'roles' => explode(',', $item['link_role'])
                );
        }

        return $aMenu;
    }

    /*
     * Fonctionnalité import global => menu, sous menu, lien avec leur configuration de permission
     */

    public function importer($aData) {
        $aMenu = $aSubmenu = $aParamsMenu = $aParamsSousMenu = $aValueMenu = $aValueSousMenu = $aDeletePermission = $aParamsDeletePermission = array();
        $aValuePermissionMenu = $aParamsInsertPermissionMenu = $aValueLink = $aParamsLink = $aValuePermissionLien = $aParamsInsertPermissionlien = array();
        foreach ($aData as $key => $item) {
            //Menu
            if (!in_array($item[0], $aMenu)) {
                $aMenu[] = $item[0];
                $aValueMenu[] = "(:menu_code{$key}, :menu_titre{$key}, :menu_label{$key}, :menu_icon{$key}, :menu_slug{$key}, :menu_consolide{$key}, :menu_cible{$key}
                    , :menu_module{$key}, :menu_controller{$key}, :menu_action{$key}, :menu_ordre{$key})";

                $aParamsMenu["menu_code{$key}"] = utf8_encode($item[0]);
                $aParamsMenu["menu_titre{$key}"] = utf8_encode($item[1]);
                $aParamsMenu["menu_label{$key}"] = utf8_encode($item[2]);
                $aParamsMenu["menu_icon{$key}"] = $item[3];
                $aParamsMenu["menu_slug{$key}"] = $item[4];
                $aParamsMenu["menu_consolide{$key}"] = $item[8];
                $aParamsMenu["menu_cible{$key}"] = $item[9];
                $aParamsMenu["menu_module{$key}"] = $item[5];
                $aParamsMenu["menu_controller{$key}"] = $item[6];
                $aParamsMenu["menu_action{$key}"] = $item[7];
                $aParamsMenu["menu_ordre{$key}"] = $item[10];

                //Suppression permission menu
//                $aDeletePermission[] = "DELETE FROM system_menu_role WHERE menu = :del_menu_{$key} ";
//                $aParamsDeletePermission["del_menu_{$key}"] = utf8_encode($item[0]);
                $stmtDeletePermission = Database::prepare("DELETE FROM system_menu_role WHERE menu = :del_menu_{$key} ");
                $stmtDeletePermission->execute(array("del_menu_{$key}" => utf8_encode($item[0])));
                //Insert nouveau permission menu
                $aRole = explode(',', $item[11]);
                foreach ($aRole as $key_1 => $role) {
                    $aValuePermissionMenu[] = "(:menu_code{$key}{$key_1}, :menu_role{$key}{$key_1})";
                    $aParamsInsertPermissionMenu["menu_code{$key}{$key_1}"] = utf8_encode($item[0]);
                    $aParamsInsertPermissionMenu["menu_role{$key}{$key_1}"] = $role;
                }
            }
            //Sous menu
            if (!in_array($item[12], $aSubmenu)) {
                $aSubmenu[] = $item[12];
                $aValueSousMenu[] = "(:s_menu_code{$key}, :s_menu_titre{$key}, :s_menu_label{$key}, :s_menu_icon{$key}, :s_menu_slug{$key}, "
                        . ":s_menu_consolide{$key}, :s_menu_cible{$key} , :s_menu_module{$key}, :s_menu_controller{$key}, :s_menu_action{$key}, :s_menu_ordre{$key}, :s_menu_parent{$key})";

                $aParamsSousMenu["s_menu_code{$key}"] = utf8_encode($item[12]);
                $aParamsSousMenu["s_menu_titre{$key}"] = utf8_encode($item[13]);
                $aParamsSousMenu["s_menu_label{$key}"] = utf8_encode($item[14]);
                $aParamsSousMenu["s_menu_icon{$key}"] = $item[15];
                $aParamsSousMenu["s_menu_slug{$key}"] = $item[16];
                $aParamsSousMenu["s_menu_consolide{$key}"] = $item[20];
                $aParamsSousMenu["s_menu_cible{$key}"] = $item[21];
                $aParamsSousMenu["s_menu_module{$key}"] = $item[17];
                $aParamsSousMenu["s_menu_controller{$key}"] = $item[18];
                $aParamsSousMenu["s_menu_action{$key}"] = $item[19];
                $aParamsSousMenu["s_menu_ordre{$key}"] = $item[22];
                $aParamsSousMenu["s_menu_parent{$key}"] = utf8_encode($item[0]);

                //Suppression permission menu
//                $aDeletePermission[] = "DELETE FROM system_menu_role WHERE menu = :del_sous_menu_{$key} ";
//                $aParamsDeletePermission["del_sous_menu_{$key}"] = utf8_encode($item[12]);
                $stmtDeletePermission = Database::prepare("DELETE FROM system_menu_role WHERE menu = :del_sous_menu_{$key}");
                $stmtDeletePermission->execute(array("del_sous_menu_{$key}" => utf8_encode($item[12])));
                //Insert nouveau permission menu
                $aRole = explode(',', $item[23]);

                foreach ($aRole as $key_1 => $role) {
                    $aValuePermissionMenu[] = "(:sous_menu_{$key}{$key_1}, :sous_menu_role{$key}{$key_1})";
                    $aParamsInsertPermissionMenu["sous_menu_{$key}{$key_1}"] = utf8_encode($item[12]);
                    $aParamsInsertPermissionMenu["sous_menu_role{$key}{$key_1}"] = $role;
                }
            }
            //Lien
            $aValueLink[] = "(:urls_segment{$key}, :notDeletable{$key}, :titre{$key}, :menu{$key})";
            $aParamsLink["urls_segment{$key}"] = $item[24];
            $aParamsLink["notDeletable{$key}"] = $item[25];
            $aParamsLink["titre{$key}"] = utf8_encode($item[26]);
            $aParamsLink["menu{$key}"] = $item[12] != '' ? utf8_encode($item[12]) : utf8_encode($item[0]);

            //Suppression permission lien
//            $aDeletePermission[] = "DELETE FROM users_roles_liste_urls WHERE id_liste_urls = (SELECT id FROM liste_urls WHERE urls_segment LIKE :del_link_{$key}) ";
//            $aParamsDeletePermission["del_link_{$key}"] = $item[24];
            $stmtDeletePermission = Database::prepare("DELETE FROM users_roles_liste_urls WHERE id_liste_urls = (SELECT id FROM liste_urls WHERE urls_segment LIKE :del_link_{$key}) ");
            $stmtDeletePermission->execute(array("del_link_{$key}" => utf8_encode($item[24])));
            //Insert nouveau permission lien
            $aRole = explode(',', $item[27]);
            foreach ($aRole as $key_1 => $role) {
                $aValuePermissionLien[] = "(:id_users_roles_{$key}{$key_1}, (SELECT id FROM liste_urls WHERE urls_segment LIKE :id_liste_urls_{$key}{$key_1}))";
                $aParamsInsertPermissionLien["id_users_roles_{$key}{$key_1}"] = $role;
                $aParamsInsertPermissionLien["id_liste_urls_{$key}{$key_1}"] = $item[24];
            }
        }

        //Suppression all permission specifier
//        if (!empty($aDeletePermission)) {
//            $stmtDeletePermission = Database::prepare(implode(';', $aDeletePermission));
//            $stmtDeletePermission->execute($aParamsDeletePermission);
//        }


        $sValueMenu = implode(' ,', $aValueMenu);
        $sQueryMenu = "INSERT INTO " . $this->_tablename . " (code, titre, label, icon, slug, consolide, cible, module, controller, action, ordre_parent) "
                . "VALUES $sValueMenu ON DUPLICATE KEY UPDATE titre = VALUES(titre), label = VALUES(label),icon = VALUES(icon),slug = VALUES(slug),"
                . "consolide = VALUES(consolide),cible = VALUES(cible),module = VALUES(module),controller = VALUES(controller),action = VALUES(action)"
                . ",ordre_parent = VALUES(ordre_parent)";


        $sValueSousMenu = implode(' ,', $aValueSousMenu);
        $sQuerySousMenu = "INSERT INTO " . $this->_tablename . " (code, titre, label, icon, slug, consolide, cible, module, controller, action, ordre_child, parent) "
                . "VALUES $sValueSousMenu ON DUPLICATE KEY UPDATE titre = VALUES(titre), label = VALUES(label),icon = VALUES(icon),slug = VALUES(slug),"
                . "consolide = VALUES(consolide),cible = VALUES(cible),module = VALUES(module),controller = VALUES(controller),action = VALUES(action)"
                . ",ordre_child = VALUES(ordre_child),parent = VALUES(parent)";

        $stmtMenu = Database::prepare($sQueryMenu);
        $stmtMenu->execute($aParamsMenu);

        $stmtSousMenu = Database::prepare($sQuerySousMenu);
        $stmtSousMenu->execute($aParamsSousMenu);



        //insert permission menu et sous menu
        $sValuePermissionMenu = implode(', ', $aValuePermissionMenu);
        $sQueryPermissionMenu = "INSERT INTO system_menu_role (menu, role) "
                . "VALUES $sValuePermissionMenu  ON DUPLICATE KEY UPDATE id = id, menu = VALUES(menu), role = VALUES(role)";

        $stmtPermissionMenu = Database::prepare($sQueryPermissionMenu);
        $stmtPermissionMenu->execute($aParamsInsertPermissionMenu);

        //lien
        $sValueLink = implode(' ,', $aValueLink);
        $sQueryLink = "INSERT INTO liste_urls (urls_segment, notDeletable, titre, menu) "
                . "VALUES $sValueLink ON DUPLICATE KEY UPDATE notDeletable = VALUES(notDeletable), titre = VALUES(titre),menu = VALUES(menu)";
        $stmtLink = Database::prepare($sQueryLink);
        $stmtLink->execute($aParamsLink);

        //permission lien

        $sValuePermissionLien = implode(', ', $aValuePermissionLien);
        $sQueryPermissionMenu = "INSERT INTO users_roles_liste_urls (id_users_roles, id_liste_urls) "
                . "VALUES $sValuePermissionLien ON DUPLICATE KEY UPDATE id_users_roles = VALUES(id_users_roles), id_liste_urls = VALUES(id_liste_urls)";
        $stmtPermissionLink = Database::prepare($sQueryPermissionMenu);
        $stmtPermissionLink->execute($aParamsInsertPermissionLien);
    }

    /*
     * Mise à jour global des permissions
     */

    public function update($aData) {
        if (isset($aData['menu_add'])) {
            $this->addRoleMenu($aData['menu_add']);
        }
        if (isset($aData['menu_remove'])) {
            $this->removeRoleMenu($aData['menu_remove']);
        }
        if (isset($aData['link_add'])) {
            $this->addRoleLink($aData['link_add']);
        }
        if (isset($aData['link_remove'])) {
            $this->removeRoleLink($aData['link_remove']);
        }
    }

    /*
     * Ajout permission a un rôle pour un lien
     */

    private function addRoleLink($aData) {
        $aValues = $aParams = array();
        $key = 0;
        foreach ($aData as $code_link => $item) {
            foreach ($item as $role_id) {
                $aValues[] = "(:link{$key}, :role{$key})";
                $aParams["link{$key}"] = $code_link;
                $aParams["role{$key}"] = $role_id;
                $key++;
            }
        }
        $values = implode(' ,', $aValues);
        $query = "INSERT INTO users_roles_liste_urls (id_liste_urls, id_users_roles) VALUES $values  ON DUPLICATE KEY UPDATE id = id ";
        $stmt = Database::prepare($query);
        $stmt->execute($aParams);
    }

    /*
     * Suppression permission a un rôle pour un lien
     */

    private function removeRoleLink($aData) {
        $aValues = $aParams = array();
        $key = 0;
        foreach ($aData as $code_link => $item) {
            foreach ($item as $role_id) {
                $aValues[] = " (id_liste_urls = :link{$key} AND id_users_roles = :role{$key}) ";
                $aParams["link{$key}"] = $code_link;
                $aParams["role{$key}"] = $role_id;
                $key++;
            }
        }
        $values = implode(' OR ', $aValues);
        $query = "DELETE FROM users_roles_liste_urls WHERE $values ";

        $stmt = Database::prepare($query);
        $stmt->execute($aParams);
    }

    /*
     * Ajout permission a un rôle pour un menu ou sous menu
     */

    private function addRoleMenu($aData) {
        $aValues = $aParams = array();
        $key = 0;
        foreach ($aData as $code_menu => $item) {
            foreach ($item as $role_id) {
                $aValues[] = "(:menu{$key}, :role{$key})";
                $aParams["menu{$key}"] = $code_menu;
                $aParams["role{$key}"] = $role_id;
                $key++;
            }
        }
        $values = implode(' ,', $aValues);
        $query = "INSERT INTO " . $this->_linkTablename . " (menu, role) VALUES $values ON DUPLICATE KEY UPDATE id = id ";
        $stmt = Database::prepare($query);
        $stmt->execute($aParams);
    }

    /*
     * Suppression permission a un rôle pour un menu ou sous menu
     */

    private function removeRoleMenu($aData) {
        $aValues = $aParams = array();
        $key = 0;
        foreach ($aData as $code_menu => $item) {
            foreach ($item as $role_id) {
                $aValues[] = " (menu = :menu{$key} AND role = :role{$key}) ";
                $aParams["menu{$key}"] = $code_menu;
                $aParams["role{$key}"] = $role_id;
                $key++;
            }
        }
        $values = implode(' OR ', $aValues);
        $query = "DELETE FROM " . $this->_linkTablename . " WHERE $values ";
        $stmt = Database::prepare($query);
        $stmt->execute($aParams);
    }

    /**
     * Donnée brute d'un menu avec traitement pour être afficher comme menu
     * @return array
     */
    public function getMenu($role, $is_conso) {
        $sWhere = $is_conso ? " m.consolide = 1 " : " m.cible = 1 ";

        $query = "SELECT m.* "
                . "FROM {$this->_tablename} m "
                . "LEFT OUTER JOIN {$this->_linkTablename} d ON d.menu = m.code "
                . "WHERE d.role = :role AND $sWhere "
                . "ORDER BY CASE m.parent "
                . "WHEN NULL THEN 1 "
                . "ELSE 2 "
                . "END ASC, id ASC, ordre_child ASC";

        $stmt = Database::prepare($query);
        $stmt->execute(array(':role' => $role));

        $aResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aMenu = array();

        foreach ($aResult as $item) {
            if ($item['parent'] == null) {
                $aMenu[$item['code']] = array(
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'slug' => $item['slug'],
                    'link' => FALSE
                );
                if ($item['module'])
                    $aMenu[$item['code']]['link'] = array(
                        'module' => $item['module'],
                        'controller' => $item['controller'],
                        'action' => $item['action']
                    );
                else {
                    $aMenu[$item['code']]['child'] = isset($aMenu[$item['parent']]['child']) ? $aMenu[$item['parent']]['child'] : array();
                }
            } else {
                $aSubMenu = array(
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'slug' => $item['slug'],
                    'link' => array(
                        'module' => $item['module'],
                        'controller' => $item['controller'],
                        'action' => $item['action']
                    )
                );

                if (isset($aMenu[$item['parent']]))
                    $aMenu[$item['parent']]['child'][] = $aSubMenu;
            }
        }
        return $aMenu;
    }

    /*
     * Extraire en xls la visualisation droit par rôle
     */

    public function extraire($aData, $aRole) {
        unset($aRole[0]);
        $oExporter = Apps::usePlugin('ExportData/Excel');
        $oExporter->initialize('browser', 'Droit_' . date('dmY') . '.xls');
        $values = $bg = array();

        $titre = array(
            'A1' => 'Menu',
            'B1' => 'Sous menu',
            'C1' => 'Lien'
        );
        $bg['A1'] = 'aaa5a0';
        $bg['B1'] = 'aaa5a0';
        $bg['C1'] = 'aaa5a0';
        $col = 'D';
        foreach ($aRole as $role) {
            $titre[$col . '1'] = $role['role_name'];
            $bg[$col . '1'] = 'aaa5a0';
            $endcol = $col;
            $col = $this->incrementChar($col, 1);
        }
        $oExporter->setFontStyle(array("A1:" . $endcol . '1' => array('font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => 'FFFFFF')))));

        $row = 2;
        foreach ($aData as $menu) {
            $values["A$row"] = $menu['titre'] ? $menu['titre'] : $menu['label'];
            $bg['A' . $row] = '444444';
            $bg['B' . $row] = '444444';
            $bg['C' . $row] = '444444';
            $col = 'D';
            foreach ($aRole as $role) {
                $values[$col . $row] = in_array($role['id'], $menu['roles']) ? "OUI" : "NON";
                $bg[$col . $row] = '444444';
                $col = $this->incrementChar($col, 1);
            }
            $oExporter->setFontStyle(array("A$row:" . $endcol . $row => array('font' => array('bold' => true, 'size' => 10, 'color' => array('rgb' => 'FFFFFF')))));
            $row++;
            foreach ($menu['child'] as $subMenu) {
                if (!is_null($subMenu['code'])) {
                    $values["B$row"] = $subMenu['titre'] ? $subMenu['titre'] : $subMenu['label'];
                    $bg['A' . $row] = 'EEEEEE';
                    $bg['B' . $row] = 'EEEEEE';
                    $bg['C' . $row] = 'EEEEEE';
                    $col = 'D';
                    foreach ($aRole as $role) {
                        $values[$col . $row] = in_array($role['id'], $subMenu['roles']) ? "OUI" : "NON";
                        $bg[$col . $row] = 'EEEEEE';
                        $col = $this->incrementChar($col, 1);
                    }
                    $row++;
                }
                foreach ($subMenu['link'] as $link) {
                    $values["C$row"] = $link['url'];
                    $col = 'D';
                    foreach ($aRole as $role) {
                        $values[$col . $row] = in_array($role['id'], $link['roles']) ? "OUI" : "NON";
                        $col = $this->incrementChar($col, 1);
                    }
                    $row++;
                }
            }
        }

        $border_all = array('A1:' . $endcol . ($row - 1) => 'solid');
        $dimension = array('A' => 20, 'B' => 32, 'C' => 35, 'D' => 13, 'E' => 13, 'F' => 13, 'G' => 13, 'H' => 13, 'I' => 13, 'J' => 13, 'K' => 13, 'L' => 13, 'M' => 13, 'N' => 13, 'O' => 13);

        $oExporter->addCells(array_merge($titre, $values));
        $oExporter->setDimension($dimension);
        $oExporter->freeze(array('A2', 'D2'));
        $oExporter->setBorder($border_all);
        $oExporter->setBg($bg);
        $oExporter->finalize();
    }

    function ote_accent($string) {
        $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
        return $normalized = $transliterator->transliterate($string);
    }

}
