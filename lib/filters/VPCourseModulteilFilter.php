<?php

class VPCourseModulteilFilter implements VPFilter
{
    static public function context()
    {
        return "courses";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("MVV Modulteile");
    }

    /**
     * Name of the Parameter in URL and in widget.
     * @return string "modulteil_id"
     */
    public function getParameterName()
    {
        return "modulteil_id";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $modulteilwidget = new SelectWidget(
            _("Modulteile"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_studiengangteil"),
            "stgteil_id"
        );
        $modulteilwidget->addLayoutCSSClass("courses");
        $modulteilwidget->addLayoutCSSClass("modulteilfilter");
        $modulteile = [];
        if ($GLOBALS['user']->cfg->ADMIN_COURSES_STGTEIL) {
            $modulteilwidget->addElement(
                new SelectElement(
                    "",
                    _("Alle"),
                    false
                ),
                'select-'
            );
            $statement = DBManager::get()->prepare("
                SELECT mvv_modulteil.*
                FROM mvv_modulteil
                    INNER JOIN mvv_modulteil_stgteilabschnitt ON (mvv_modulteil_stgteilabschnitt.modulteil_id = mvv_modulteil.modulteil_id)
                    INNER JOIN mvv_stgteilabschnitt ON (mvv_stgteilabschnitt.abschnitt_id = mvv_modulteil_stgteilabschnitt.abschnitt_id)
                    INNER JOIN mvv_stgteilversion ON (mvv_stgteilversion.version_id = mvv_stgteilabschnitt.version_id)
                WHERE mvv_stgteilversion.stgteil_id = :stgteil_id
            ");
            $statement->execute([
                'stgteil_id' => Request::option("stgteil_id", $GLOBALS['user']->cfg->ADMIN_COURSES_STGTEIL)
            ]);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $modulteile[] = Modulteil::buildExisting($data);
            }
        } else {
            $modulteilwidget->addElement(
                new SelectElement(
                    "",
                    _("Wählen Sie erst eine Einrichtung und Studiengangteil aus"),
                    false
                ),
                'select-'
            );
        }
        foreach ($modulteile as $modulteil) {
            $modulteilwidget->addElement(
                new SelectElement(
                    $modulteil->getId(),
                    $modulteil->getDisplayName(),
                    $modulteil->getId() === $GLOBALS['user']->cfg->ADMIN_COURSES_MODULTEIL
                ),
                'select-'.$modulteil->getId()
            );
        }
        return $modulteilwidget;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_MODULTEIL', Request::get("modulteil_id"));
        if (Request::get("modulteil_id")) {
            $query->join("mvv_lvgruppe_seminar", "`mvv_lvgruppe_seminar`.`seminar_id` = `seminare`.`Seminar_id`");
            $query->join("mvv_lvgruppe_modulteil", "`mvv_lvgruppe_modulteil`.`lvgruppe_id` = `mvv_lvgruppe_seminar`.`lvgruppe_id`");

            $query->where("modulteil_id", "`mvv_lvgruppe_modulteil`.`modulteil_id` = :modulteil_id", array(
                'modulteil_id' => Request::get("modulteil_id")
            ));
        }
    }
}
