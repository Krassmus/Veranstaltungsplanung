<?php

require_once __DIR__."/../lib/StudyAreaSelector.php";

class PlanerController extends PluginController
{

    static protected $widgets = null;

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("admin")) {
            throw new AccessDeniedException();
        }
    }

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

        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/study-area-tree.js");

        $this->filters = $this->getWidgets();
    }

    public function change_event_action()
    {
        list($type, $termin_id) = explode("_", Request::option("termin_id"), 2);
        $termin = CourseDate::find($termin_id);
        $start = (int) (Request::get("start"));
        $end = (int) (Request::get("end"));
        $output = array();
        $output['type'] = $type;
        if ($type === "termine" && $GLOBALS['perm']->have_studip_perm("dozent", $termin['range_id'])) {
            $termin['date'] = $start;
            $termin['end_time'] = $end;
            $termin->store();
            $output['test'] = 1;
        } else {

        }
        $this->render_json($output);
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

        $termine = array();
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
        $query->groupBy("`termine`.`termin_id`");

        switch (Request::get("object_type")) {
            case "courses":
                $query->join("seminare", "`seminare`.`Seminar_id` = `termine`.`range_id`");
                $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::get("institut_id"));
                if (Request::get("institut_id") && Request::get("institut_id") !== "all") {
                    $query->join("Institute", "`Institute`.`Institut_id` = `seminare`.`Institut_id`");
                    $query->where("heimat_institut", "`seminare`.`Institut_id` = :institut_id OR `Institute`.`fakultaets_id` = :institut_id", array(
                        'institut_id' => Request::get("institut_id")
                    ));
                }
                $GLOBALS['user']->cfg->store('MY_COURSES_SELECTED_CYCLE', Request::get("semester_id"));
                if (Request::get("semester_id") && Request::get("semester_id") !== "all") {
                    $semester = Semester::find(Request::get("semester_id"));
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
                $GLOBALS['user']->cfg->store('ADMIN_COURSES_VISIBILITY', Request::get("visibility"));
                if (Request::get("visibility")) {
                    $query->where("visibility", "`seminare`.`visible` = :visible", array(
                        'visible' => Request::get("visibility") === "visible" ? 1 : 0
                    ));
                }
                break;
            case "persons":
                //$query->join("seminare", "`seminare`.`Seminar_id` = `termine`.`range_id`");
                $query->join("seminar_user", "`seminar_user`.`Seminar_id` = `termine`.`range_id`");
                if (Request::get("person_status")) {
                    $status = json_decode(Request::get("person_status"));
                    $GLOBALS['user']->cfg->store('ADMIN_USER_STATUS', serialize($status));

                    $person_status = array_intersect($status, compact("user autor tutor dozent admin root"));
                    $person_roles = array_diff($status, compact("user autor tutor dozent admin root"));

                    if (count($person_status) + count($person_roles) > 0) {
                        $query->join("auth_user_md5", "`seminar_user`.`user_id` = `auth_user_md5`.`user_id`");
                        $query->join("roles_user", "`seminar_user`.`user_id` = `roles_user`.`userid`");
                        $query->where("person_status", "`auth_user_md5`.`perms` IN (:person_status) OR `roles_user`.`roleid` IN (:person_roles) ", array(
                            'person_status' => array_intersect($status, compact("user autor tutor dozent admin root")),
                            'person_roles' => array_diff($status, compact("user autor tutor dozent admin root"))
                        ));
                    }
                }

                //Zweiter Query für private Termine:
                break;
            case "resources":
                break;
        }
        foreach (PluginManager::getInstance()->getPlugins("VeranstaltungsplanungFilter") as $plugin) {
            foreach ($plugin->getVeranstaltungsplanungFilter() as $name => $filter) {
                $this->filters[$name] = $filter;
            }
        }

        foreach ($query->fetchAll("CourseDate") as $termin) {
            $termine[] = array(
                'id' => "termine_".$termin->getId(),
                'title' => $termin->course['name'],
                'start' => date("r", $termin['date']),
                'end' => date("r", $termin['end_time']),
                'classes' => array("course_".$termin['range_id'])
            );
        }

        $this->render_json($termine);
    }

    public function get_collisions_action() {
        list($type, $termin_id) = explode("_", Request::option("termin_id"), 2);
        $termin = CourseDate::find($termin_id);
        $start = strtotime(Request::get("start"));
        $end = strtotime(Request::get("end"));


        $output = array('events' => array());

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

            $statusgruppen_ids = $termin->statusgruppen->pluck("statusgruppe_id");

            if (count($statusgruppen_ids) > 0) {
                $statement = DBManager::get()->prepare("
                    SELECT termine.*
                    FROM termine
                        LEFT JOIN termin_related_persons ON (termine.termin_id = termin_related_persons.range_id)
                        LEFT JOIN seminar_user ON (seminar_user.Seminar_id = termine.range_id)
                        LEFT JOIN termin_related_groups ON (termine.termin_id = termin_related_persons.termin_id)
                    WHERE termine.termin_id != :termin_id
                        AND termine.`date` <= :end
                        AND termine.`end_time` >= :start
                        AND (
                            (termin_related_persons.user_id IN (:teacher_ids)) 
                            OR (termin_related_persons.user_id IS NULL AND seminar_user.user_id IN (:teacher_ids))
                        )
                        AND (
                            (termin_related_groups.statusgruppe_id IN (:statusgruppen_ids))
                            OR (termin_related_groups.statusgruppe_id IS NULL AND termine.range_id = :seminar_id)
                        )
                ");
                $statement->execute(array(
                    'termin_id' => $termin->getId(),
                    'start' => $start,
                    'end' => $end,
                    'teacher_ids' => $teacher_ids,
                    'statusgruppen_ids' => $statusgruppen_ids,
                    'seminar_id' => $termin['range_id']
                ));
            } else {
                $statement = DBManager::get()->prepare("
                SELECT termine.*
                FROM termine
                    LEFT JOIN termin_related_persons ON (termine.termin_id = termin_related_persons.range_id)
                    LEFT JOIN seminar_user ON (seminar_user.Seminar_id = termine.range_id)
                WHERE termine.termin_id != :termin_id
                    AND termine.`date` <= :end
                    AND termine.`end_time` >= :start
                    AND (
                        (termin_related_persons.user_id IN (:teacher_ids)) 
                        OR (termin_related_persons.user_id IS NULL AND seminar_user.user_id IN (:teacher_ids))
                    )
             ");
                $statement->execute(array(
                    'termin_id' => $termin->getId(),
                    'start' => $start,
                    'end' => $end,
                    'teacher_ids' => $teacher_ids
                ));
            }


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
                    AND termine.termin_id NOT IN (:termin_ids)
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

        if ($termin['metadate_id']) {
            //coursedate is repeating date and we need to check for all dates
            $statement = DBManager::get()->prepare("
                SELECT termine.*
                FROM termine 
                    LEFT JOIN termine AS my_termine ON (FLOOR(termine.end_time / 86400 * 7) = FLOOR(my_termine.end_time / 86400 * 7))
                    LEFT JOIN seminar_user ON (seminar_user.Seminar_id = termine.range_id)
                    LEFT JOIN seminar_user AS my_dozent ON (my_dozent.Seminar_id = my_termine.range_id AND my_dozent.status = 'dozent' AND seminar_user.user_id = my_dozent.user_id)
                WHERE termine.termin_id NOT IN (:termin_ids)
                    AND my_termine.metadate_id = :metadate_id
            ");
            $statement->execute(array(
                'metadate_id' => $termin['metadate_id'],
                'termin_ids' => $termin_ids
            ));
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
            _('Freie Suche'),
            'course_search',
            true,
            null,
            null,
            $GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT
        );
        $textsearch->class = "courses";
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



        $study_area = new StudyAreaSelector();
        $study_area->class = "courses";
        $filters['study_area_ids'] = array(
            'widget' => $study_area,
            'object_type' => "courses",
            'value' => $GLOBALS['user']->cfg->ADMIN_COURSES_STUDYAREAS
        );


        $visibility = new SelectWidget(
            _("Sichtbarkeit"),
            PluginEngine::getURL($this->plugin, array(), "planer/change_type"),
            "visibility"
        );
        $visibility->class = "courses";
        $visibility->addElement(new SelectElement(
            "",
            "",
            false),
            'select-'
        );
        $visibility->addElement(new SelectElement(
            "visible",
            _("Nur sichtbare"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_VISIBILITY === "visible"),
            'select-visible'
        );
        $visibility->addElement(new SelectElement(
            "invisible",
            _("Nur versteckte"),
            $GLOBALS['user']->cfg->ADMIN_COURSES_VISIBILITY === "invisible"),
            'select-invisible'
        );
        $filters['visibility'] = array(
            'widget' => $visibility,
            'object_type' => "courses",
            'value' => $GLOBALS['user']->cfg->ADMIN_COURSES_VISIBILITY
        );


        $person_status = new SelectWidget(
            _("Rollen-Filter"),
            PluginEngine::getURL($this->plugin, array(), "planer/change_type"),
            "person_status",
            "get",
            true
        );
        $person_status->class = "persons";
        $status_config = $GLOBALS['user']->cfg->ADMIN_USER_STATUS ? unserialize($GLOBALS['user']->cfg->ADMIN_USER_STATUS) : array();
        foreach (array("user", "autor", "tutor", "dozent", "admin", "root") as $status) {
            $person_status->addElement(new SelectElement(
                $status,
                ucfirst($status),
                in_array($status, $status_config)
            ));
        }
        foreach (RolePersistence::getAllRoles() as $role) {
            if (!$role->getSystemType()) {
                $person_status->addElement(new SelectElement(
                    $role->getRoleid(),
                    $role->getRolename(),
                    in_array($role->getRoleid(), $status_config)
                ));
            }
        }
        $filters['person_status'] = array(
            'widget' => $person_status,
            'object_type' => "persons",
            'value' => json_encode($status_config)
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