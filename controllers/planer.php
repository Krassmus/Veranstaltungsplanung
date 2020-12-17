<?php

require_once __DIR__."/../lib/StudyAreaSelector.php";
require_once __DIR__."/../lib/ResourceSelector.php";
require_once __DIR__."/../lib/SeatSelector.php";

class PlanerController extends PluginController
{

    static protected $widgets = null;

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("admin")) {
            throw new AccessDeniedException();
        }
        if (method_exists("PageLayout", "allowFullscreenMode")) {
            PageLayout::allowFullscreenMode();
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
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/daygrid/main.js");
        PageLayout::addStylesheet($this->plugin->getPluginURL() . "/assets/fullcalendar/packages/daygrid/main.css");

        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/study-area-tree.js");
        PageLayout::addScript($this->plugin->getPluginURL() . "/assets/mvvfilters.js");

        $this->vpfilters = Veranstaltungsplanung::getFilters();
    }

    public function print_action()
    {
        $this->index_action();
        $this->print = true;
        PageLayout::addStylesheet($this->plugin->getPluginURL()."/assets/print.css");
        $this->render_action("index");
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

        $this->vpcolorizers = Veranstaltungsplanung::getColorizers();
        if ($GLOBALS['user']->cfg->getValue("VERANSTALTUNGSPLANUNG_COLORIZE_" . strtoupper($object_type))) {
            list($colorizerclass, $colorizerindex) = explode("__", $GLOBALS['user']->cfg->getValue("VERANSTALTUNGSPLANUNG_COLORIZE_" . strtoupper($object_type)));
            $colorizer = $this->vpcolorizers[$colorizerclass];
        }
        if (!$colorizer) {
            $colorizer = $this->vpcolorizers["VPStandardColorizer"];
            $colorizerindex = "standard";
        }

        foreach ($query->fetchAll("CourseDate") as $termin) {
            $title = (string) $termin->course['name'];
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
                'backgroundColor' => $colorizer->getColor($colorizerindex, $termin),
                'classNames' => array(
                    "course_".$termin['range_id'],
                    $termin['metadate_id'] ? "dateseries" : "singledate"
                )
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
                    'editable' => false,
                    'backgroundColor' => $colorizer->getColor($colorizerindex, $termin),
                    'classNames' => array("event_data")
                );
            }
        }
        if ($object_type === "resources") {
            $query = new \Veranstaltungsplanung\SQLQuery(
                "termine",
                "open_room_requests"
            );
            $query->join(
                "resource_request_appointments",
                "resource_request_appointments",
                "`termine`.`termin_id` = `resource_request_appointments`.`appointment_id`"
            );
            $query->join(
                "resource_requests",
                "resource_requests",
                "(`termine`.`termin_id` = `resource_requests`.`termin_id`)
                    OR (`resource_request_appointments`.`request_id` = `resource_requests`.`id`)
                    OR (`termine`.`metadate_id` = `resource_requests`.`metadate_id`)"
            );
            $query->where("start", "`termine`.`end_time` >= :start", array(
                'start' => $start
            ));
            $query->where("end", "`termine`.`date` <= :end", array(
                'end' => $end
            ));
            $query->groupBy("`termine`.`termin_id`");
            foreach ($this->vpfilters['resources'] as $filter) {
                $filter->applyFilter($query);
            }

            foreach ($query->fetchAll("CourseDate") as $termin) {
                $title = (string) $termin->course['name'];
                $statement = DBManager::get()->prepare("
                    SELECT resources.*
                    FROM resources
                        INNER JOIN resource_requests ON (resource_requests.resource_id = resources.id)
                        LEFT JOIN resource_request_appointments ON (`resource_request_appointments`.`request_id` = `resource_requests`.`id`)
                    WHERE `resource_requests`.`metadate_id` = :metadate_id
                        OR `resource_request_appointments`.`appointment_id` = :termin_id
                        OR `resource_requests`.`termin_id` = :termin_id
                ");
                $statement->execute([
                    'termin_id' => $termin->getId(),
                    'metadate_id' => $termin['metadate_id'] ?: "nix"
                ]);
                $resource = Resource::buildExisting($statement->fetch(PDO::FETCH_ASSOC));
                if ($resource) {
                    $title = $resource['name'].": ".$title;
                }
                $title = _("Raumanfrage")." ".$title;
                $termine[] = array(
                    'id' => "termine_".$termin->getId(),
                    'title' => $title,
                    'start' => date("c", $termin['date']),
                    'end' => date("c", $termin['end_time']),
                    'backgroundColor' => $colorizer->getColor($colorizerindex, $termin),
                    'classNames' => array(
                        "course_".$termin['range_id'],
                        $termin['metadate_id'] ? "dateseries" : "singledate",
                        "open_request"
                    )
                );
            }
        }

        //manage background colors:
        $backgrounds = [];
        foreach ($termine as $key => $termin) {
            if ($backgrounds[$termin['backgroundColor']]) {
                $backgrounds[$termin['backgroundColor']]++;
            } else {
                $backgrounds[$termin['backgroundColor']] = 1;
            }
        }
        $commoncolors = [
            "#e7ebf1", //content-color-20
            "#e2efcf", //green-20
            "#dfabcb", //violet-40
            "#f26e00",
            "#129c94",
            "#682c8b",
            "#d60000",
            "#a85d45",
            "#ffbd33",
            "#28497c"
        ];

        foreach ($termine as $key => $termin) {
            if (is_numeric($termin['backgroundColor'])
                && $termin['backgroundColor'] < count($commoncolors)) {
                $termine[$key]['backgroundColor'] = $commoncolors[$termin['backgroundColor']];
            }
        }

        foreach ($termine as $key => $termin) {
            $background_hsl = Color::hsl($termin['backgroundColor']);

            //now analyze the ligting of the background and make the font white if the background is dark:
            preg_match("/hsl\(\s*(\d+\.?\d*),\s*(\d+\.?\d*)\%,\s*(\d+\.?\d*)\%\s*\)/", $background_hsl, $matches);
            array_shift($matches);
            $light = $matches[2];
            if ($light < 0.6) {
                $termine[$key]['textColor'] = "#ffffff";
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
        }
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
        if ($termin->room_booking) {
            $statement = DBManager::get()->prepare("
                SELECT resource_booking_intervals.*, resource_bookings.range_id as termin_id
                FROM resource_booking_intervals
                    INNER JOIN resource_bookings ON (resource_bookings.id = resource_booking_intervals.booking_id)
                WHERE resource_bookings.resource_id = :resource_id
                    AND resource_bookings.range_id NOT IN (:termin_ids)
                    AND resource_booking_intervals.`begin` <= :end
                    AND resource_booking_intervals.`end` >= :start
            ");
            $statement->execute(array(
                'termin_ids' => $termin_ids,
                'start' => $start,
                'end' => $end,
                'resource_id' => $termin->room_booking['resource_id']
            ));
            $termine_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($termine_data as $data) {
                $termin_ids[] = $data['termin_id'];
                $events[] = array(
                    'id' => $data['interval_id'],
                    'start' => date("c", $data['begin']),
                    'end' => date("c", $data['end']),
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
            $colorizers = Request::getArray("colorizer");
            foreach (['courses', 'persons', 'resources'] as $type) {
                $GLOBALS['user']->cfg->store(
                    'VERANSTALTUNGSPLANUNG_COLORIZE_'.strtoupper($type),
                    $colorizers[$type]
                );
            }
            PageLayout::postSuccess(_("Einstellungen wurden gespeichert."));
            $this->redirect("planer/index");
        }
    }





    public function get_dozenten_action($seminar_id)
    {
        $this->dozenten = Course::find($seminar_id)->members->filter(function ($m) { return $m['status'] === "dozent"; });
    }

    public function set_default_view_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_DEFAULTVIEW', Request::get("default_view"));
        }
        $this->render_nothing();
    }
}
