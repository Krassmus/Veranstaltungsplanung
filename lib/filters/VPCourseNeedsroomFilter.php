<?php

class VPCourseNeedsroomFilter implements VPFilter
{
    static public function context()
    {
        return "courses";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getNames()
    {
        return ["needsroom" => _("Termine ohne Raum")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $visibility = new SelectWidget(
            _("Termine ohne Raum"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "needsroom"
        );
        $visibility->addLayoutCSSClass("courses");
        $visibility->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        $visibility->addElement(new SelectElement(
            "needsroominfo",
            _("Ohne Raum und Raumangabe"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_NEEDSROOM === "needsroominfo"),
            'select-needsroominfo'
        );
        $visibility->addElement(new SelectElement(
            "needsresource",
            _("Ohne zugeordnete Ressource"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_NEEDSROOM === "needsresource"),
            'select-needsresource'
        );
        return $visibility;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_NEEDSROOM', Request::get("needsroom"));
        if (Request::get("needsroom")) {
            $query->join(
                "resource_bookings",
                "`resource_bookings`.`range_id` = `termine`.`termin_id`",
                'LEFT JOIN'
            );
            $query->join(
                "resources",
                "`resource_bookings`.`resource_id` = `resources`.`id`",
                'LEFT JOIN'
            );
            if (Request::get("needsroom") === 'needsroominfo') {
                $query->where("visibility", "((`termine`.`raum` IS NULL) OR (TRIM(`termine`.`raum`) = '')) AND `resources`.`id` IS NULL");
            } else {
                $query->where("visibility", "`resources`.`id` IS NULL");
            }
        }
    }
}
