<?php

class VPCourseInstituteFilter implements VPFilter
{
    static public function context()
    {
        return "courses";
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
        return "institut_id";
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
            "institut_id"
        );
        $institutes->addLayoutCSSClass("courses");
        $institutes->addElement(new SelectElement(
            "all",
            _("Alle"),
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
        $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::get("institut_id") ?: "all");
        if (Request::get("institut_id")
                && (Request::get("institut_id") !== "all")
                && !Request::get("stgteil_id")) {
            $query->join("Institute", "`Institute`.`Institut_id` = `seminare`.`Institut_id`");
            $query->where("heimat_institut", "`seminare`.`Institut_id` = :institut_id OR `Institute`.`fakultaets_id` = :institut_id", array(
                'institut_id' => Request::get("institut_id")
            ));
        }
    }
}
