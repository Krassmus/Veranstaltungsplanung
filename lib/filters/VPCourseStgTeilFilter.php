<?php

class VPCourseStgTeilFilter implements VPFilter
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
        return _("MVV Studiengangteile");
    }

    /**
     * Name of the Parameter in URL and in widget.
     * @return string "course_search"
     */
    public function getParameterName()
    {
        return "stgteil_id";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $stgteilwidget = new SelectWidget(
            _("Studiengangteile"),
            PluginEngine::getURL("veranstaltungsplanung/planer/change_studiengangteil"),
            "stgteil_id"
        );
        $stgteilwidget->addLayoutCSSClass("courses");
        $stgteilwidget->addLayoutCSSClass("stgteilfilter");
        $stgteile = [];
        if ($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT && $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT !== "all") {
            $stgteilwidget->addElement(
                new SelectElement(
                    "",
                    _("Alle"),
                    false
                ),
                'select-'
            );
            $statement = DBManager::get()->prepare("
                SELECT mvv_stgteil.*
                FROM mvv_stgteil
                    INNER JOIN mvv_stg_stgteil ON (mvv_stgteil.stgteil_id = mvv_stg_stgteil.stgteil_id)
                    INNER JOIN mvv_studiengang ON (mvv_stg_stgteil.studiengang_id = mvv_studiengang.studiengang_id)
                WHERE mvv_studiengang.institut_id = :institut_id
            ");
            $statement->execute(['institut_id' => $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT]);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $stgteile[] = StudiengangTeil::buildExisting($data);
            }
        } else {
            $stgteilwidget->addElement(
                new SelectElement(
                    "",
                    _("Wählen Sie erst eine Einrichtung aus"),
                    false
                ),
                'select-'
            );
        }
        foreach ($stgteile as $stgteil) {
            $stgteilwidget->addElement(
                new SelectElement(
                    $stgteil->getId(),
                    $stgteil->getDisplayName(),
                    $stgteil->getId() === $GLOBALS['user']->cfg->ADMIN_COURSES_STGTEIL
                ),
                'select-'.$stgteil->getId()
            );
        }
        return $stgteilwidget;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_STGTEIL', Request::get("stgteil_id"));
        if (Request::get("stgteil_id") && !Request::get("modulteil_id")) {
            //possibly add all sub-items
            $query->join("mvv_lvgruppe_seminar", "`mvv_lvgruppe_seminar`.`seminar_id` = `seminare`.`Seminar_id`");
            $query->join("mvv_lvgruppe_modulteil", "`mvv_lvgruppe_modulteil`.`lvgruppe_id` = `mvv_lvgruppe_seminar`.`lvgruppe_id`");
            $query->join("mvv_modulteil_stgteilabschnitt", "`mvv_modulteil_stgteilabschnitt`.`modulteil_id` = `mvv_lvgruppe_modulteil`.`modulteil_id`");
            $query->join("mvv_stgteilabschnitt", "`mvv_stgteilabschnitt`.`abschnitt_id` = `mvv_modulteil_stgteilabschnitt`.`abschnitt_id`");
            $query->join("mvv_stgteilversion", "`mvv_stgteilabschnitt`.`version_id` = `mvv_stgteilversion`.`version_id`");

            $query->where("stgteil_id", "`mvv_stgteilversion`.`stgteil_id` = :stgteil_id", array(
                'stgteil_id' => Request::get("stgteil_id")
            ));
        }
    }
}
