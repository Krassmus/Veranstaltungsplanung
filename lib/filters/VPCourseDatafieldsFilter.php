<?php

class VPCourseDatafieldsFilter implements VPFilter
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
        $datafields = DataField::findBySQL("`object_type` = 'sem' ORDER BY `priority` ASC");
        $output = [];
        foreach ($datafields as $datafield) {
            $output['datafield_'.$datafield->getId()] = sprintf(_("Datenfeld '%s'"), $datafield['name']);
        }
        return $output;
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        list($bla, $datafield_id) = explode("_", $index);
        $datafield = DataField::find($datafield_id);
        if ($datafield['object_type'] === "sem") {
            switch ($datafield['type']) {
                case "bool":
                    $widget = new SelectWidget(
                        sprintf(_("Datenfeld '%s'"), $datafield['name']),
                        PluginEngine::getURL("veranstaltungsplanung/planer/change_type"),
                        $index
                    );
                    $widget->addLayoutCSSClass("courses");
                    $widget->addElement(new SelectElement(
                        "",
                        "",
                        false),
                        'select-'
                    );
                    $widget->addElement(new SelectElement(
                        "ja",
                        _("Ja"),
                        $GLOBALS['user']->cfg->getValue("ADMIN_COURSES_DATAFIELDS_".$datafield_id) === "ja"),
                        'select-ja'
                    );
                    $widget->addElement(new SelectElement(
                        "nein",
                        _("Nein"),
                        $GLOBALS['user']->cfg->getValue("ADMIN_COURSES_DATAFIELDS_".$datafield_id) === "nein"),
                        'select-nein'
                    );
                    return $widget;
                    break;
                default:
                    $widget = new SearchWidget();
                    $widget->setTitle(sprintf(_("Datenfeld '%s'"), $datafield['name']));
                    $widget->addNeedle(
                        $datafield['name'],
                        $index,
                        true,
                        null,
                        null,
                        $GLOBALS['user']->cfg->getValue("ADMIN_COURSES_DATAFIELDS_".$datafield_id)
                    );
                    $widget->class = "courses";
                    $widget->addLayoutCSSClass("courses");
                    return $widget;
                    break;
            }
        }
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        list($bla, $datafield_id) = explode("_", $index);
        $datafield = DataField::find($datafield_id);
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_DATAFIELDS_'.$datafield_id, Request::get($index));
        if (Request::get($index) && $datafield) {
            $query->join("df_".$datafield->getId(), "datafields_entries", "`df_".$datafield->getId()."`.`range_id` = `seminare`.`Seminar_id`");
            switch ($datafield['type']) {
                case "bool":
                    $query->where($index, "(`df_".$datafield->getId()."`.`datafield_id` = '".$datafield->getId()."' AND df_".$datafield->getId().".`content` = :df_".$datafield->getId()."_content)", [
                        'df_'.$datafield->getId().'_content' => Request::get($index) === "ja" ? 1 : 0
                    ]);
                    break;
                default:
                    $query->where($index, "(`df_".$datafield->getId()."`.`datafield_id` = '".$datafield->getId()."' AND df_".$datafield->getId().".`content` LIKE :df_".$datafield->getId()."_content)", [
                        'df_'.$datafield->getId().'_content' => '%'.Request::get($index).'%'
                    ]);
            }
        }
    }
}
