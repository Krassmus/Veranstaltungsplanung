<?php

class VPCourseDatetypeFilter implements VPFilter
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
        return ['date_type' => _("Termintyp")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $types = new SelectWidget(
            _("Termintyp"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
            "date_type"
        );
        $types->addLayoutCSSClass("courses");
        $types->addElement(new SelectElement(
            "all",
            _("Alle"),
            false),
            'select-'
        );
        foreach ($GLOBALS['TERMIN_TYP'] as $id => $typdata) {
            $types->addElement(new SelectElement(
                $id,
                $typdata['name'],
                $id === $GLOBALS['user']->cfg->VPLANER_DATETYPE),
                'select-'.$id
            );
        }
        return $types;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('VPLANER_DATETYPE', Request::get("date_type") ?: "all");
        if (Request::get("date_type")
                && (Request::get("date_type") !== "all")) {
            $query->where("date_typ", "`termine`.`date_typ` = :date_type", [
                'date_type' => Request::get("date_type")
            ]);
        }
    }
}
