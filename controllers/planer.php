<?php

class PlanerController extends PluginController
{

    static protected $widgets = null;

    public function index_action()
    {
        Navigation::activateItem("/browse/va_planer");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/veranstaltungsplanung.js");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/core/main.js");
        PageLayout::addStylesheet($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/core/main.css");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/interaction/main.js");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/daygrid/main.js");
        PageLayout::addStylesheet($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/daygrid/main.css");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/timegrid/main.js");
        PageLayout::addStylesheet($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/timegrid/main.css");

        $this->filters = $this->getWidgets();

    }

    public function change_type_action()
    {
        $this->redirect("planer/index");
    }

    public function fetch_dates_action()
    {
        $start = strtotime(Request::get("start"));
        $end = strtotime(Request::get("end"));

        $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_DEFAULTDATE', $start + floor(($end - $start) / 2));
        $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_OBJECT_TYPE', Request::get("object_type"));

        $query = new \Veranstaltungsplanung\SQLQuery(
            "termine",
            "veranstaltungsplanung_termine"
        );

        $query->where("start", "`termine`.`end_time` >= :start", array(
            'start' => $start
        ));
        $query->where("end", "`termine`.`date` <= :end", array(
            'end' => $end
        ));

        switch (Request::get("object_type")) {
            case "courses":
                $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::get("institut_id"));
                if (Request::get("institut_id") && Request::get("institut_id") !== "all") {
                    $query->join("seminare", "`seminare`.`Seminar_id` = `termine`.`range_id`");
                    $query->where("heimat_institut", "`seminare`.`Institut_id` = :institut_id", array(
                        'institut_id' => Request::get("institut_id")
                    ));
                }
                $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_CYCLE', Request::get("semester_id"));
                if (Request::get("semester_id") && Request::get("semester_id") !== "all") {
                    $semester = Semester::find(Request::get("semester_id"));
                    $query->join("seminare", "`seminare`.`Seminar_id` = `termine`.`range_id`");
                    $query->where("semester_select", "`seminare`.`start_time` <= :semester_start AND (`seminare`.`duration_time` = -1 OR `seminare`.`duration_time` + `seminare`.`start_time` >= :semester_start OR (`seminare`.`duration_time` = '0' AND `seminare`.`start_time` = :semester_start))", array(
                        'semester_start' => $semester['beginn']
                    ));
                }
                $GLOBALS['user']->cfg->store('ADMIN_COURSES_SEARCHTEXT', Request::get("course_search"));
                if (Request::get("course_search")) {
                    $query->join("dozenten_su", "seminar_user", "`seminare`.`Seminar_id` = dozenten_su.Seminar_id AND dozenten_su.status = 'dozent'");
                    $query->join("dozent", "auth_user_md5", "dozent.user_id = dozenten_su.user_id");
                    $query->where("search", "`seminare`.name LIKE :search OR `seminare`.`VeranstaltungsNummer` LIKE :search OR CONCAT(dozent.Vorname, ' ', dozent.Nachname, ' ', dozent.username, ' ', dozent.Email) LIKE :search", array(
                        'search' => "%".Request::get("course_search")."%"
                    ));
                }
                break;
            case "teachers":
                break;
            case "resources":
                break;
        }
        foreach (PluginManager::getInstance()->getPlugins("VeranstaltungsplanungFilter") as $plugin) {
            foreach ($plugin->getVeranstaltungsplanungFilter() as $name => $filter) {
                $this->filters[$name] = $filter;

            }
        }
        $termine = array();
        foreach ($query->fetchAll("CourseDate") as $termin) {
            $termine[] = array(
                'id' => $termin->getId(),
                'title' => $termin->course['name'],
                'start' => date("r", $termin['date']),
                'end' => date("r", $termin['end_time']),
                'classes' => array("course_".$termin['range_id'])
            );
        }

        $this->render_json($termine);
    }

    public function get_collisions_action() {
        $termin = CourseDate::find(Request::option("termin_id"));
        $start = strtotime(Request::get("start"));
        $end = strtotime(Request::get("end"));


        $output = array('events' => array());
        if (!$termin['metadate_id']) {
            $teacher_ids = array();
            $termin_ids = array($termin->getId());
            foreach ($termin->dozenten as $dozent) {
                $teacher_ids[] = $dozent['user_id'];
            };
            if (!count($teacher_ids)) {
                $statement = DBManager::get()->prepare("
                    SELECT user_id 
                    FROM seminar_user
                    WHERE Seminar_id = ?
                        AND status = 'dozent'
                ");
                $statement->execute(array($termin['range_id']));
                $teacher_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

                $statement = DBManager::get()->prepare("
                    SELECT termine.*
                    FROM termine
                        LEFT JOIN termin_related_persons ON (termine.termin_id = termin_related_persons.range_id)
                        LEFT JOIN seminar_user ON (seminar_user.Seminar_id = termine.range_id)
                    WHERE termine.termin_id != :termin_id
                        AND termine.`date` <= :end
                        AND termine.`end_time` >= :start
                        AND ((termin_related_persons.user_id IN (:teacher_ids)) OR (termin_related_persons.user_id IS NULL AND seminar_user.user_id IN (:teacher_ids)))
                 ");
                $statement->execute(array(
                    'termin_id' => $termin->getId(),
                    'start' => $start,
                    'end' => $end,
                    'teacher_ids' => $teacher_ids
                ));
                $termine_data = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($termine_data as $data) {
                    $termin_ids[] = $data['termin_id'];
                    $output['events'][] = array(
                        'id' => $data['termin_id'],
                        'start' => date("r", $data['date']),
                        'end' => date("r", $data['end_time']),
                        'conflict' => "teacher_room",
                        'reason' => _("Lehrender hat dort keine Zeit")
                    );
                }


            }
            //check for blocked resource:
            if ($termin->room_assignment) {
                DBManager::get()->prepare("
                    SELECT termine.*
                    FROM termine
                        INNER JOIN resources_assign ON (resources_assign.assign_user_id = termine.termin_id)
                    WHERE resources_assign.resource_id = :resource_id
                        termine.termin_id NOT IN (:termin_ids)
                        AND termine.`date` <= :end
                        AND termine.`end_time` >= :start
                ");
                $statement->execute(array(
                    'termin_ids' => $termin_ids,
                    'start' => $start,
                    'end' => $end,
                    'resource_id' => $termin->room_assignment['resource_id']
                ));
                $termine_data = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($termine_data as $data) {
                    $termin_ids[] = $data['termin_id'];
                    $output['events'][] = array(
                        'id' => $data['termin_id'],
                        'start' => date("r", $data['date']),
                        'end' => date("r", $data['end_time']),
                        'reason' => _("Raum ist dort schon belegt.")
                    );
                }
            }
        }

        $output['events'][] = array(
            'id' => $termin['termin_id'],
            'start' => date("r", $termin['date']),
            'end' => date("r", $termin['end_time']),
            'conflict' => "original"
        );
        $this->render_json($output);
    }

    public function getWidgets() {
        if (self::$widgets) {
            return self::$widgets;
        }

        $filters = array();

        $textsearch = new SearchWidget();
        $textsearch->addNeedle(
            _("Veranstaltung suchen"),
            "course_search",
            null,
            null,
            null,
            $GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT
        );
        $filters['course_search'] = array(
            'widget' => $textsearch,
            'object_type' => "courses",
            'value' => $GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT
        );


        $semester_select = new SelectWidget(
            _("Semester"),
            PluginEngine::getURL($this->plugin, array(), "planer/change_type"),
            "semester_id"
        );
        $semester_select->class = "courses";
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
        $filters['semester_id'] = array(
            'widget' => $semester_select,
            'object_type' => "courses",
            'value' => $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE
        );


        $institutes = new SelectWidget(
            _("Einrichtung"),
            PluginEngine::getURL($this->plugin, array(), "planer/change_type"),
            "institut_id"
        );
        $institutes->class = "courses";
        $institutes->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        foreach (Institute::getMyInstitutes() as $institut) {
            $institutes->addElement(new SelectElement(
                $institut['Institut_id'],
                ($institut['is_fak'] ? "" : "  ").$institut['Name'],
                $institut['Institut_id'] === $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT),
                'select-'.$institut['Institut_id']
            );
        }

        $filters['institut_id'] = array(
            'widget' => $institutes,
            'object_type' => "courses",
            'value' => $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT
        );

        foreach (PluginManager::getInstance()->getPlugins("VeranstaltungsplanungFilter") as $plugin) {
            foreach ($plugin->getVeranstaltungsplanungFilter() as $name => $filter) {
                $this->filters[$name] = $filter;
            }
        }

        self::$widgets = $filters;
        return $filters;
    }


    public function settings_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_LINE', Request::get("line"));

            $GLOBALS['user']->cfg->store(
                'VERANSTALTUNGSPLANUNG_HIDDENDAYS',
                json_encode(array_map(function ($i) { return (int) $i; }, Request::getArray("hidden_days")))
            );
            $all_filters = array_keys($this->getWidgets());
            $filters = array_values(array_diff($all_filters, Request::getArray("filter")));
            $GLOBALS['user']->cfg->store(
                'VERANSTALTUNGSPLANUNG_DISABLED_FILTER',
                json_encode($filters)
            );
            PageLayout::postSuccess(_("Einstellungen wurden gespeichert."));
            $this->redirect("planer/index");
        }
    }
}