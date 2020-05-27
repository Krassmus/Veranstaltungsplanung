<?php

class DateController extends PluginController
{
    public function edit_action($date_id = null)
    {
        if (strpos($date_id, "termine_") === 0) {
            $date_id = substr($date_id, 8);
        }
        $this->date = new CourseDate($date_id);
        if (Request::int("start")) {
            $this->date['date'] = Request::int("start");
        }
        if (Request::int("end")) {
            $this->date['end_time'] = Request::int("end");
        }
        if (count(Request::getArray("data"))) {
            $this->date->setData(Request::getArray("data"));
        }

        if (Request::isPost()) {
            if (Request::submitted("delete_date")) {
                if ($this->date->cycle) {
                    $this->date->cycle->delete();
                } else {
                    $this->date->delete();
                }
            }
            //Add course date or booking of resource or personal event?
            if (Request::option("metadate")) {
                $cycledate = new SeminarCycleDate();
                $cycledate['seminar_id'] = Request::option("course_id");
                $cycledate['start_time'] = date("H:i:s", $this->date['date']);
                $cycledate['end_time'] = date("H:i:s", $this->date['end_time']);
                $cycledate['weekday'] = date("w", $this->date['date']) > 0 ? date("w", $this->date['date']) : 7;
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
                        $assignment['begin'] = $this->date['date'];
                        $assignment['end'] = $this->date['end_time'];
                        $assignment['repeat_end'] = $this->date['end_time'];
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
                $this->date = new CourseDate();
                $this->date['range_id'] = Request::option("course_id");
                $this->date['autor_id'] = $GLOBALS['user']->id;
                if (!Request::option("resource_id")) {
                    $this->date['raum'] = Request::get("freeRoomText");
                }
                $this->date->store();
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
                    $assignment['begin'] = $this->date['date'];
                    $assignment['end'] = $this->date['end_time'];
                    $assignment['repeat_end'] = $this->date['end_time'];
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

        $this->setAvailableRooms();
        $this->semester = Semester::findByTimestamp($this->date['date']);
        $this->in_semester = $this->semester && ($this->semester['vorles_beginn'] <= $this->date['date'] && $this->semester['vorles_ende'] >= $this->date['end_time']);
        $this->render_template("date/create_date_".Request::get("object_type"));
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
}
