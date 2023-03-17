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
    public function getNames()
    {
        return ["semester_id" => _("Semester")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $semester_ids = json_decode($GLOBALS['user']->cfg->MY_COURSES_SELECTED_SEMESTERS, true);
        if (!$semester_ids) {
            $semester_ids = [];
        }
        $semester_select = new SelectWidget(
            _("Semester"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "semester_id",
            'get',
            true
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
                in_array($semester->getId(), $semester_ids),
                'select-'.$semester->getId()
            ));
        }
        return $semester_select;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_SEMESTERS', Request::get("semester_id"));
        if (Request::get("semester_id")) {
            $semester_ids = json_decode(Request::get("semester_id"), true);
            if (count($semester_ids)) {
                $query->join(
                    'semester_courses',
                    'semester_courses',
                    '`semester_courses`.`course_id` = `seminare`.`Seminar_id`',
                    'LEFT JOIN'
                );
                $query->where("semester_select", "`semester_courses`.`semester_id` IN (:semester_ids) OR `semester_courses`.`semester_id` IS NULL", [
                    'semester_ids' => $semester_ids
                ]);
            }
        }
    }
}
