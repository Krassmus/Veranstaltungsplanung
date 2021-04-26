<?php

class VPCourseStudyareaFilter implements VPFilter
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
        return ["study_area_ids" => _("Studienbereiche")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $study_area = new StudyAreaSelector();
        $study_area->addLayoutCSSClass("courses");
        return $study_area;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_STUDYAREAS', Request::get("study_area_ids"));
        if (Request::get("study_area_ids")) {
            $sem_tree_ids = explode(",", Request::get("study_area_ids"));
            //possibly add all sub-items
            $query->join("seminar_sem_tree", "`seminar_sem_tree`.`seminar_id` = `seminare`.`Seminar_id`");
            $query->join("sem_tree", "`sem_tree`.`sem_tree_id` = `seminar_sem_tree`.`sem_tree_id`");
            $query->where("sem_tree_ids", "`seminar_sem_tree`.`sem_tree_id` IN (:sem_tree_ids) OR `sem_tree`.`parent_id` IN (:sem_tree_ids)", array(
                'sem_tree_ids' => $sem_tree_ids
            ));
        }
    }
}
