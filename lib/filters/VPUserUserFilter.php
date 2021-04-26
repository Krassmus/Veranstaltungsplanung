<?php

class VPUserUserFilter implements VPFilter
{
    static public function context() {
        return "persons";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getNames()
    {
        return ["user_search" => _("Person")];
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
            'user_search',
            true,
            null,
            null,
            $GLOBALS['user']->cfg->ADMIN_COURSES_USERSEARCH
        );
        $textsearch->class = "persons";
        $textsearch->addLayoutCSSClass("persons");
        return $textsearch;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_USERSEARCH', Request::get("user_search"));
        if ($GLOBALS['user']->cfg->ADMIN_COURSES_USERSEARCH) {
            $query->where("user_name", "CONCAT(`auth_user_md5`.`Vorname`, ' ', `auth_user_md5`.`Nachname`, ' ', `auth_user_md5`.`username`) LIKE :user_name", array(
                'user_name' => "%".$GLOBALS['user']->cfg->ADMIN_COURSES_USERSEARCH."%"
            ));
        }
    }
}
