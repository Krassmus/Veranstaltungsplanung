<?php

class VPCourseFachsemesterFilter implements VPFilter
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
        return ["fachsemester" => _("Fachsemester")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $fachsemester_filter = new SelectWidget(
            _("Fachsemester"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "fachsemester"
        );
        $fachsemester_filter->addLayoutCSSClass("courses");
        $fachsemester_filter->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        for ($i = 1; $i <= 30; $i++) {
            $fachsemester_filter->addElement(new SelectElement(
                $i,
                $i,
                $GLOBALS['user']->cfg->ADMIN_COURSES_FACHSEMESTER == $i),
                'select-'.$i
            );
        }

        return $fachsemester_filter;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_FACHSEMESTER', Request::get("fachsemester"));
        if (Request::get("fachsemester")) {
            $query->join("mvv_lvgruppe_seminar", "`mvv_lvgruppe_seminar`.`seminar_id` = `seminare`.`Seminar_id`");
            $query->join("mvv_lvgruppe_modulteil", "`mvv_lvgruppe_modulteil`.`lvgruppe_id` = `mvv_lvgruppe_seminar`.`lvgruppe_id`");
            $query->join("mvv_modulteil", "`mvv_modulteil`.`modulteil_id` = `mvv_lvgruppe_modulteil`.`modulteil_id`");
            $query->join('mvv_modulteil_stgteilabschnitt', '`mvv_modulteil_stgteilabschnitt`.`modulteil_id` = `mvv_modulteil`.`modulteil_id`');
            $query->where("fachsemester", "`mvv_modulteil_stgteilabschnitt`.`fachsemester` = :fachsemester", [
                'fachsemester' => Request::get("fachsemester")
            ]);
        }
    }
}
