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

    }
}