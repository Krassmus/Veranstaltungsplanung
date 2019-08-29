<?php

class VPUserRoleFilter implements VPFilter
{
    static public function context()
    {
        return "persons";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Rollen-Filter");
    }

    /**
     * Name of the Parameter in URL and in widget.
     * @return string "course_search"
     */
    public function getParameterName()
    {
        return "person_status";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $person_status = new SelectWidget(
            _("Rollen-Filter"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "person_status",
            "get",
            true
        );
        $person_status->addLayoutCSSClass("persons");
        $status_config = $GLOBALS['user']->cfg->ADMIN_USER_STATUS ? (array) unserialize($GLOBALS['user']->cfg->ADMIN_USER_STATUS) : array();
        foreach (array("user", "autor", "tutor", "dozent", "admin", "root") as $status) {
            $person_status->addElement(new SelectElement(
                $status,
                ucfirst($status),
                in_array($status, $status_config)
            ));
        }
        foreach (RolePersistence::getAllRoles() as $role) {
            if (!$role->getSystemType()) {
                $person_status->addElement(new SelectElement(
                    $role->getRoleid(),
                    $role->getRolename(),
                    in_array($role->getRoleid(), $status_config)
                ));
            }
        }
        return $person_status;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $status = json_decode(Request::get("person_status"), true);
        $GLOBALS['user']->cfg->store('ADMIN_USER_STATUS', serialize($status));
        if (Request::get("person_status")) {
            $person_status = array_intersect($status, words("user autor tutor dozent admin root"));
            $person_roles = array_diff($status, words("user autor tutor dozent admin root"));

            if (count($person_status) + count($person_roles) > 0) {
                if (count($person_roles)) {
                    $query->join(
                        "roles_user",
                        "roles_user",
                        "`auth_user_md5`.`user_id` = `roles_user`.`userid`",
                        "LEFT JOIN"
                    );
                }
                if (count($person_roles)) {
                    $query->where("person_status", "`auth_user_md5`.`perms` IN (:person_status) OR `roles_user`.`roleid` IN (:person_roles) ", array(
                        'person_status' => $person_status,
                        'person_roles' => $person_roles
                    ));
                } else {
                    $query->where("person_status", "`auth_user_md5`.`perms` IN (:person_status)", array(
                        'person_status' => $person_status
                    ));
                }
            }
        }
    }
}