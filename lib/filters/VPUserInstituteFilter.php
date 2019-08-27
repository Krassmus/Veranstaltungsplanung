<?php

class VPUserInstituteFilter implements VPFilter
{
    static public function context() {
        return "persons";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Einrichtung");
    }

    public function getParameterName()
    {
        return "user_institut_id";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $institutes = new SelectWidget(
            _("Einrichtung"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "user_institut_id"
        );
        $institutes->addLayoutCSSClass("persons");
        $institutes->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        foreach (Institute::getMyInstitutes() as $institut) {
            $institutes->addElement(new SelectElement(
                $institut['Institut_id'],
                ($institut['is_fak'] ? "" : "  ").$institut['Name'],
                $institut['Institut_id'] === $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT),
                'select-'.$institut['Institut_id']
            );
        }
        return $institutes;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::get("user_institut_id"));
        if (Request::get("user_institut_id") && Request::get("user_institut_id") !== "all") {
            $query->join("user_inst", "`user_inst`.`user_id` = `seminar_user`.`user_id`");
            $query->join("Institute", "`Institute`.`Institut_id` = `user_inst`.`Institut_id`");
            $query->where("user_inst", "`user_inst`.`Institut_id` = :institut_id OR `Institute`.`fakultaets_id` = :institut_id", array(
                'institut_id' => Request::get("user_institut_id")
            ));
        }
    }
}