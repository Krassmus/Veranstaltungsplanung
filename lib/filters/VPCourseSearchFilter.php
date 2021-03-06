<?php

class VPCourseSearchFilter implements VPFilter
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
        return ["course_search" => _("Suche")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $textsearch = new SearchWidget();
        $textsearch->addNeedle(
            _('Freie Suche'),
            'course_search',
            true,
            null,
            null,
            $GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT
        );
        $textsearch->class = "courses";
        $textsearch->addLayoutCSSClass("courses");
        return $textsearch;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_SEARCHTEXT', Request::get("course_search"));
        if (Request::get("course_search")) {
            $query->join("dozenten_su", "seminar_user", "`seminare`.`Seminar_id` = dozenten_su.Seminar_id AND dozenten_su.status = 'dozent'");
            $query->join("dozent", "auth_user_md5", "dozent.user_id = dozenten_su.user_id");
            $query->where("search", "`seminare`.name LIKE :search OR `seminare`.`VeranstaltungsNummer` LIKE :search OR CONCAT(dozent.Vorname, ' ', dozent.Nachname, ' ', dozent.username, ' ', dozent.Email) LIKE :search", [
                'search' => "%".Request::get("course_search")."%"
            ]);
        }
    }
}
