<?php

class VPCourseVisibilityFilter implements VPFilter
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
        return ["visibility" => _("Sichtbarkeit")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $visibility = new SelectWidget(
            _("Sichtbarkeit"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "visibility"
        );
        $visibility->addLayoutCSSClass("courses");
        $visibility->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        $visibility->addElement(new SelectElement(
            "visible",
            _("Nur sichtbare"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_VISIBILITY === "visible"),
            'select-visible'
        );
        $visibility->addElement(new SelectElement(
            "invisible",
            _("Nur versteckte"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_VISIBILITY === "invisible"),
            'select-invisible'
        );
        return $visibility;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_VISIBILITY', Request::get("visibility"));
        if (Request::get("visibility")) {
            $query->where("visibility", "`seminare`.`visible` = :visible", [
                'visible' => Request::get("visibility") === "visible" ? 1 : 0
            ]);
        }
    }
}
