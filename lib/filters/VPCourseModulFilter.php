<?php

class VPCourseModulFilter implements VPFilter
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
        return ["modul_ids" => _("Module")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $filter = new ModulSelector();
        $filter->addLayoutCSSClass("courses");
        return $filter;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_COURSES_MODUL', Request::get("modul_ids"));

        if (Request::get("modul_ids")) {
            $ids = explode(",", Request::get("modul_ids"));

            $query->join("mvv_lvgruppe_seminar", "`mvv_lvgruppe_seminar`.`seminar_id` = `seminare`.`Seminar_id`");
            $query->join("mvv_lvgruppe_modulteil", "`mvv_lvgruppe_modulteil`.`lvgruppe_id` = `mvv_lvgruppe_seminar`.`lvgruppe_id`");
            $query->join("mvv_modulteil", "`mvv_modulteil`.`modulteil_id` = `mvv_lvgruppe_modulteil`.`modulteil_id`");
            $query->join("mvv_modul", "`mvv_modul`.`modul_id` = `mvv_modulteil`.`modul_id`");

            $modul_ids = [];
            $studiengangteilabschnitt_ids = [];
            $studiengangteil_ids = [];
            $studiengang_ids = [];
            foreach ($ids as $id) {
                list($type, $i) = explode("_", $id);
                if ($type === "studiengang") {
                    $studiengang_ids[] = $i;
                } elseif ($type === "studiengangteil") {
                    $studiengangteil_ids[] = $i;
                } elseif ($type === "studiengangteilabschnitt") {
                    $studiengangteilabschnitt_ids[] = $i;
                } elseif ($type === "modul") {
                    $modul_ids[] = $i;
                }
            }

            if (count($modul_ids) + count($studiengangteilabschnitt_ids) + count($studiengangteil_ids) + count($studiengang_ids) > 0) {

                $where = "(";
                $params = [];
                if (count($studiengangteilabschnitt_ids) + count($studiengangteil_ids) + count($studiengang_ids) > 0) {
                    $query->join("mvv_stgteilabschnitt_modul", "`mvv_stgteilabschnitt_modul`.`modul_id` = `mvv_modul`.`modul_id`");
                }
                if (count($studiengangteil_ids) + count($studiengang_ids) > 0) {
                    $query->join("mvv_stgteilabschnitt", "`mvv_stgteilabschnitt`.`abschnitt_id` = `mvv_stgteilabschnitt_modul`.`abschnitt_id`");
                    $query->join("mvv_stgteilversion", "`mvv_stgteilversion`.`version_id` = `mvv_stgteilabschnitt`.`version_id`");
                }
                if (count($studiengang_ids) > 0) {
                    $query->join("mvv_stg_stgteil", "`mvv_stg_stgteil`.`stgteil_id` = `mvv_stgteilversion`.`stgteil_id`");
                }

                if (count($studiengang_ids) > 0) {
                    if (count($params) > 0) {
                        $where .= " OR ";
                    }
                    $where .= "`mvv_stg_stgteil`.`studiengang_id` IN (:studiengang_ids)";
                    $params['studiengang_ids'] = $studiengang_ids;
                }
                if (count($studiengangteil_ids) > 0) {
                    if (count($params) > 0) {
                        $where .= " OR ";
                    }
                    $where .= "`mvv_stgteilversion`.`stgteil_id` IN (:studiengangteil_ids)";
                    $params['studiengangteil_ids'] = $studiengangteil_ids;
                }
                if (count($studiengangteilabschnitt_ids) > 0) {
                    if (count($params) > 0) {
                        $where .= " OR ";
                    }
                    $where .= "`mvv_stgteilabschnitt_modul`.`abschnitt_id` IN (:studiengangteilabschnitt_ids)";
                    $params['studiengangteilabschnitt_ids'] = $studiengangteilabschnitt_ids;
                }
                if (count($modul_ids) > 0) {
                    if (count($params) > 0) {
                        $where .= " OR ";
                    }
                    $where .= "(`mvv_modul`.`modul_id` IN (:modul_ids)) AND (`mvv_modul`.`stat` = 'genehmigt')";
                    $params['modul_ids'] = $modul_ids;
                }
                $where .= ")";
                $query->where("modul_id", $where, $params);
            }
        }
    }
}
