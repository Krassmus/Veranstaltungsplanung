<?php

require_once __DIR__."/../lib/StudyAreaSelector.php";
require_once __DIR__."/../lib/ResourceSelector.php";

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

        $this->vpfilters = Veranstaltungsplanung::getFilters();
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
            $output['rejected'] = 1;
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
        $object_type = Request::get("object_type") ?: "courses";

        $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_DEFAULTDATE', $start + floor(($end - $start) / 2));
        $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_OBJECT_TYPE', $object_type);

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

        switch ($object_type) {
            case "courses":
                $query->join("seminare", "`seminare`.`Seminar_id` = `termine`.`range_id`");

                break;
            case "persons":
                $query->join(
                    "termin_related_persons",
                    "termin_related_persons",
                    "`termin_related_persons`.`range_id` = `termine`.`termin_id`",
                    "LEFT JOIN"
                );
                $query->join(
                    "seminar_user",
                    "seminar_user",
                    "`seminar_user`.`Seminar_id` = `termine`.`range_id` AND (termin_related_persons.user_id IS NULL OR termin_related_persons.user_id = seminar_user.user_id)"
                );
                $query->join(
                    "auth_user_md5",
                    "auth_user_md5",
                    "`seminar_user`.`user_id` = `auth_user_md5`.`user_id`"
                );


                //Zweiter Query für private Termine:
                break;
            case "resources":
                $query->join("resource_bookings", "`resource_bookings`.`range_id` = `termine`.`termin_id`");
                $query->join("resources", "`resource_bookings`.`resource_id` = `resources`.`id`");
                break;
        }
        $this->vpfilters = Veranstaltungsplanung::getFilters();
        foreach ((array) $this->vpfilters[$object_type] as $filter) {
            $filter->applyFilter($query);
        }

        foreach ($query->fetchAll("CourseDate") as $termin) {
            $title = $termin->course['name'];
            if ($object_type === "resources") {
                $title = $termin->room_booking->resource['name'].": ".$title;
            }
            if ($object_type === "persons") {
                $dozenten = $termin->dozenten;
                if (!count($dozenten)) {
                    $dozenten = $termin->course->members->filter(function ($m) { return $m['status'] === "dozent"; });
                    $dozenten = implode(", ", $dozenten->pluck("nachname"));
                } else {
                    $dozenten = implode(", ", array_map(function($d) { return $d['nachname']; }, $dozenten->toArray()));
                }
                $title = $dozenten.": ".$title;
            }
            $termine[] = array(
                'id' => "termine_".$termin->getId(),
                'title' => $title,
                'start' => date("c", $termin['date']),
                'end' => date("c", $termin['end_time']),
                'classNames' => array("course_".$termin['range_id'])
            );
        }

        if ($object_type === "persons") {
            $query = new \Veranstaltungsplanung\SQLQuery(
                "event_data",
                "private_termine"
            );
            $query->where("start", "`event_data`.`start` >= :start", array(
                'start' => $start
            ));
            $query->where("end", "`event_data`.`end` <= :end", array(
                'end' => $end
            ));
            $query->join(
                "auth_user_md5",
                "auth_user_md5",
                "`event_data`.`author_id` = `auth_user_md5`.`user_id`"
            );
            $query->groupBy("`event_data`.`event_id`");
            foreach ($this->vpfilters['persons'] as $filter) {
                $filter->applyFilter($query);
            }

            foreach ($query->fetchAll("EventData") as $termin) {
                $termine[] = array(
                    'id' => "eventdata_".$termin->getId(),
                    'title' => $termin->author['nachname'].": ".($termin['class'] === "PRIVATE" ? _("Privater Termin") : $termin['summary'].($termin['class'] === "CONFIDENTIAL" ? _(" (vertraulich)") : "")),
                    'start' => date("c", $termin['start']),
                    'end' => date("c", $termin['end']),
                    'classNames' => array("event_data")
                );
            }
        }

        $this->render_json($termine);
    }

    public function get_collisions_action() {
        list($type, $termin_id) = explode("_", Request::option("termin_id"), 2);
        $termin = CourseDate::find($termin_id);
        $start = strtotime(Request::get("start"));
        $end = strtotime(Request::get("end"));

        $output = array();
        $output['events'] = $this->getCollisions($termin, $start, $end);

        //original event, which gets displayed in yellow:
        $output['events'][] = array(
            'id' => $termin['termin_id'],
            'start' => date("c", $termin['date']),
            'end' => date("c", $termin['end_time']),
            'conflict' => "original"
        );
        $this->render_json($output);
    }

    public function getCollisions($termin, $start, $end) {
        $events = array();
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
                $events[] = array(
                    'id' => $data['termin_id'],
                    'start' => date("c", $data['date']),
                    'end' => date("c", $data['end_time']),
                    'conflict' => "teacher_room",
                    'reason' => _("Lehrender hat dort keine Zeit")
                );
            }
        }

        //check for blocked resource:
        if ($termin->room_assignment) {
            $statement = DBManager::get()->prepare("
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
                $events[] = array(
                    'id' => $data['termin_id'],
                    'start' => date("c", $data['date']),
                    'end' => date("c", $data['end_time']),
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

        return $events;
    }



    public function settings_action()
    {
        $this->filters = Veranstaltungsplanung::getFilters();
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_LINE', Request::get("line"));

            $GLOBALS['user']->cfg->store(
                'VERANSTALTUNGSPLANUNG_HIDDENDAYS',
                json_encode(array_map(function ($i) { return (int) $i; }, Request::getArray("hidden_days")))
            );
            $all_filters = array_merge(
                array_map(function ($f) { return get_class($f); }, (array) $this->filters['courses']),
                array_map(function ($f) { return get_class($f); }, (array) $this->filters['persons']),
                array_map(function ($f) { return get_class($f); }, (array) $this->filters['resources'])
            );
            $filters = array_values(array_diff($all_filters, Request::getArray("filter")));
            $GLOBALS['user']->cfg->store(
                'VERANSTALTUNGSPLANUNG_DISABLED_FILTER',
                json_encode($filters)
            );
            PageLayout::postSuccess(_("Einstellungen wurden gespeichert."));
            $this->redirect("planer/index");
        }
    }

    public function create_date_action() {
        $this->start = Request::int("start");
        $this->end = Request::int("end");

        if (Request::isPost()) {
            //Add course date or booking of resource or personal event?
            if (Request::option("metadate")) {
                $cycledate = new SeminarCycleDate();
                $cycledate['seminar_id'] = Request::option("course_id");
                $cycledate['start_time'] = date("H:i:s", $this->start);
                $cycledate['end_time'] = date("H:i:s", $this->end);
                $cycledate['weekday'] = date("w", $this->start) > 0 ? date("w", $this->start) : 7;
                $cycledate['cycle'] = 0;
                $cycledate->store();
                $cycledate->generateNewDates();
                $dozenten = Course::find(Request::option("course_id"))->members->filter(function ($m) { return $m['status'] === "dozent"; });
                foreach ($cycledate->getAllDates() as $date) {
                    $date['date_typ'] = Request::option("dateType");
                    if (!Request::option("resource_id")) {
                        $date['raum'] = Request::get("freeRoomText");
                    }
                    $date->store();
                    if (Request::getArray("durchfuehrende_dozenten") && count(Request::getArray("durchfuehrende_dozenten")) !== count($dozenten)) {
                        $statement = DBManager::get()->prepare("
                            INSERT IGNORE INTO termin_related_persons
                            SET user_id = :user_id,
                                range_id = :termin_id
                        ");
                        foreach (Request::getArray("durchfuehrende_dozenten") as $user_id) {
                            $statement->execute(array(
                                'user_id' => $user_id,
                                'termin_id' => $date->getId()
                            ));
                        }
                    }
                    if (Request::option("resource_id")) {
                        //Raumbuchung
                        $assignment = new ResourceAssignment();
                        $assignment['resource_id'] = Request::option("resource_id");
                        $assignment['assign_user_id'] = $date->getId();
                        $assignment['begin'] = $this->start;
                        $assignment['end'] = $this->end;
                        $assignment['repeat_end'] = $this->end;
                        $assignment['repeat_quantity'] = 0;
                        $assignment['repeat_interval'] = 0;
                        $assignment['repeat_month_of_year'] = 0;
                        $assignment['repeat_day_of_month'] = 0;
                        $assignment['repeat_week_of_month'] = 0;
                        $assignment['repeat_day_of_week'] = 0;
                        $assignment->store();
                    }
                }
            } else {
                $date = new CourseDate();
                $date['range_id'] = Request::option("course_id");
                $date['autor_id'] = $GLOBALS['user']->id;
                $date['date'] = $this->start;
                $date['end_time'] = $this->end;
                $date['date_typ'] = Request::option("dateType");
                if (!Request::option("resource_id")) {
                    $date['raum'] = Request::get("freeRoomText");
                }
                $date->store();
                $dozenten = Course::find(Request::option("course_id"))->members->filter(function ($m) { return $m['status'] === "dozent"; });
                if (Request::getArray("durchfuehrende_dozenten") && count(Request::getArray("durchfuehrende_dozenten")) !== count($dozenten)) {
                    $statement = DBManager::get()->prepare("
                        INSERT IGNORE INTO termin_related_persons
                        SET user_id = :user_id,
                            range_id = :termin_id
                    ");
                    foreach (Request::getArray("durchfuehrende_dozenten") as $user_id) {
                        $statement->execute(array(
                            'user_id' => $user_id,
                            'termin_id' => $date->getId()
                        ));
                    }
                }
                if (Request::option("resource_id")) {
                    //Raumbuchung
                    $assignment = new ResourceAssignment();
                    $assignment['resource_id'] = Request::option("resource_id");
                    $assignment['assign_user_id'] = $date->getId();
                    $assignment['begin'] = $this->start;
                    $assignment['end'] = $this->end;
                    $assignment['repeat_end'] = $this->end;
                    $assignment['repeat_quantity'] = 0;
                    $assignment['repeat_interval'] = 0;
                    $assignment['repeat_month_of_year'] = 0;
                    $assignment['repeat_day_of_month'] = 0;
                    $assignment['repeat_week_of_month'] = 0;
                    $assignment['repeat_day_of_week'] = 0;
                    $assignment->store();
                }

            }
            //Dialog schließen und Fullcalendar neu laden:
            $this->response->add_header("X-Dialog-Execute", "STUDIP.Veranstaltungsplanung.reloadCalendar");
            $this->response->add_header("X-Dialog-Close", 1);
            $this->render_nothing();
            return;
        }

        PageLayout::setTitle(sprintf(_("Termin erstellen %s - %s"), date("d.m.Y H:i", $this->start), date((floor($this->start / 86400) == floor($this->end / 86400) ? "H:i" : "d.m.Y H:i "), $this->end)));

        switch (Request::get("object_type")) {
            case "courses":
                $query = new \Veranstaltungsplanung\SQLQuery(
                    "seminare",
                    "veranstaltungsplanung_courses"
                );
                $query->groupBy("`seminare`.`Seminar_id`");
                $query->orderBy("`seminare`.name ASC");

                $this->vpfilters = Veranstaltungsplanung::getFilters();
                foreach ($this->vpfilters[Request::get("object_type")] as $filter) {
                    $filter->applyFilter($query);
                }
                $this->courses = $query->fetchAll("Course");
                break;
            case "persons":
                $query = new \Veranstaltungsplanung\SQLQuery(
                    "auth_user_md5",
                    "veranstaltungsplanung_persons"
                );
                $query->groupBy("`auth_user_md5`.`user_id`");
                $query->orderBy("`auth_user_md5`.Nachname ASC, `auth_user_md5`.Vorname ASC");

                $this->vpfilters = Veranstaltungsplanung::getFilters();
                foreach ($this->vpfilters[Request::get("object_type")] as $filter) {
                    $filter->applyFilter($query);
                }
                $this->persons = $query->fetchAll("User");
                break;
            case "resources":
                $query = new \Veranstaltungsplanung\SQLQuery(
                    "resources",
                    "veranstaltungsplanung_resources"
                );
                $query->groupBy("`resources`.`id`");
                $query->orderBy("`resources`.name ASC");

                $this->vpfilters = Veranstaltungsplanung::getFilters();
                foreach ($this->vpfilters[Request::get("object_type")] as $filter) {
                    $filter->applyFilter($query);
                }
                $this->resources = $query->fetchAll();
                break;
        }

        $this->setAvailableRooms(); //Todo: implement this on my own.
        $this->semester = Semester::findByTimestamp($this->start);
        $this->in_semester = $this->semester && ($this->semester['vorles_beginn'] <= $this->start && $this->semester['vorles_ende'] >= $this->end);
        $this->render_template("planer/create_date_".Request::get("object_type"));
    }

    protected function setAvailableRooms()
    {
        if (Config::get()->RESOURCES_ENABLE) {
            //Check for how many rooms the user has booking permissions.
            //In case these permissions exist for more than 50 rooms
            //show a quick search. Otherwise show a select field
            //with the list of rooms.

            $current_user = User::findCurrent();
            $current_user_is_resource_admin = ResourceManager::userHasGlobalPermission(
                $current_user,
                'admin'
            );
            $begin = $this->start;
            $end = $this->end;
            $this->selectable_rooms = [];
            $rooms_with_booking_permissions = 0;
            if ($current_user_is_resource_admin) {
                $rooms_with_booking_permissions = Room::countAll();
            } else {
                $user_rooms = RoomManager::getUserRooms($current_user);
                foreach ($user_rooms as $room) {
                    if ($room->userHasBookingRights($current_user, $begin, $end)) {
                        $rooms_with_booking_permissions++;
                        $this->selectable_rooms[] = $room;
                    }
                }
            }

            if ($rooms_with_booking_permissions > 50) {
                $room_search_type = new RoomSearch();
                $room_search_type->setAcceptedPermissionLevels(
                    ['autor', 'tutor', 'admin']
                );
                $room_search_type->setAdditionalDisplayProperties(
                    ['seats']
                );
                $this->room_search = new QuickSearch(
                    'room_id',
                    $room_search_type
                );
            } else {
                if (ResourceManager::userHasGlobalPermission($current_user, 'admin')) {
                    $this->selectable_rooms = Room::findAll();
                }
            }
        }
    }

    public function get_dozenten_action($seminar_id)
    {
        $this->dozenten = Course::find($seminar_id)->members->filter(function ($m) { return $m['status'] === "dozent"; });
    }
}
