<?php

class VPCourseConflictsFilter implements VPFilter
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
        return ["course_conflicts" => _("Überschneidende Termine")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $checkbox = new CourseConflictsSelector();
        $checkbox->class = "courses";
        $checkbox->addLayoutCSSClass("courses");
        return $checkbox;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_CONFLICTS', Request::get("course_conflicts"));
        if (Request::get("course_conflicts")) {
            $query->join(
                "termine2",
                "termine",
                "`termine2`.`termin_id` != `termine`.`termin_id`",
                'LEFT JOIN',
                'seminare'
            );
            $query->join(
                "seminare",
                "(`seminare`.`Seminar_id` = `termine`.`range_id` OR `seminare`.`Seminar_id` = `termine2`.`range_id`)"
            );
            $query->where(
                'conflicting_dates',
                "((`termine2`.`date` <= `termine`.`date` AND `termine2`.`end_time` >= `termine`.`date`) OR (`termine2`.`date` <= `termine`.`end_time` AND `termine2`.`end_time` >= `termine`.`end_time`) OR (`termine2`.`date` <= `termine`.`end_time` AND `termine2`.`date` >= `termine`.`date`))"
            );
        }


    }
}
