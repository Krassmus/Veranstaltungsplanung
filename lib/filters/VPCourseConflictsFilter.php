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
            //Wir verdoppeln die Query und joinen sie mit sich selbst, um die Konflikte zu finden:
            $query->addSurroundingQuery('
                SELECT DISTINCT `date1`.*
                FROM {{query}} AS `date1`
                    INNER JOIN {{query}} AS `date2` ON (
                        `date2`.`termin_id` != `date1`.`termin_id`
                        AND (
                            (`date2`.`date` <= `date1`.`date` AND `date2`.`end_time` >= `date1`.`date`)
                            OR (`date2`.`date` <= `date1`.`end_time` AND `date2`.`end_time` >= `date1`.`end_time`)
                            OR (`date2`.`date` <= `date1`.`end_time` AND `date2`.`date` >= `date1`.`date`)
                        )
                    )
            ');
        }


    }
}
