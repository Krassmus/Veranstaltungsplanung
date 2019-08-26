<?php

class VPSemesterFilter implements VPFilter
{
    static public function context() {
        return "courses";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Semester");
    }

    public function getParameterName()
    {
        return "semester_id";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $semester_select = new SelectWidget(
            _("Semester"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "semester_id"
        );
        $semester_select->addLayoutCSSClass("courses");
        $semester_select->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        foreach (array_reverse(Semester::getAll()) as $semester) {
            $semester_select->addElement(new SelectElement(
                $semester->getId(),
                $semester['name'],
                $semester->getId() === $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE),
                'select-'.$semester->getId()
            );
        }
        return $semester_select;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_CYCLE', Request::get("semester_id"));
        if (Request::get("semester_id") && Request::get("semester_id") !== "all") {
            $semester = Semester::find(Request::get("semester_id"));
            $query->where("semester_select", "`seminare`.`start_time` <= :semester_start AND (`seminare`.`duration_time` = -1 OR `seminare`.`duration_time` + `seminare`.`start_time` >= :semester_start OR (`seminare`.`duration_time` = '0' AND `seminare`.`start_time` = :semester_start))", array(
                'semester_start' => $semester['beginn']
            ));
        }
    }
}