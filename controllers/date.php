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
            $data = Request::getArray("data");
            if ($data['date']) {
                $data['date'] = strtotime($data['date']);
            }
            if ($data['end_time']) {
                $data['end_time'] = strtotime($data['end_time']);
            }
            $this->date->setData($data);
        }

        if (!$this->date->isNew() && !$GLOBALS['perm']->have_studip_perm("tutor", $this->date['range_id'])) {
            throw new AccessDeniedException();
        }

        if (Request::isPost()) {
            if (Request::submitted("delete_date")) {
                if ($this->date->cycle) {
                    $this->date->cycle->delete();
                } else {
                    $this->date->delete();
                }
                $this->response->add_header("X-Dialog-Execute", "STUDIP.Veranstaltungsplanung.reloadCalendar");
                $this->response->add_header("X-Dialog-Close", 1);
                $this->render_nothing();
                return;
            }
            if (Request::submitted("ex_date")) {
                if ($this->date->cycle) {
                    $this->date->cancelDate();
                } else {
                    $this->date->delete();
                }
                $this->response->add_header("X-Dialog-Execute", "STUDIP.Veranstaltungsplanung.reloadCalendar");
                $this->response->add_header("X-Dialog-Close", 1);
                $this->render_nothing();
                return;
            }
            //Add course date or booking of resource or personal event?
            if (Request::option("metadate")) {
                if ($this->date['metadate_id']) {
                    $cycledate = $this->date->cycle;
                } else {
                    $cycledate = new SeminarCycleDate();
                }

                $cycledate['seminar_id'] = $this->date['range_id'];
                $cycledate['start_time'] = date("H:i:s", $this->date['date']);
                $cycledate['end_time'] = date("H:i:s", $this->date['end_time']);
                $cycledate['weekday'] = date("w", $this->date['date']) > 0 ? date("w", $this->date['date']) : 7;
                $cycledate['cycle'] = 0;
                $cycledate->store();
                $dozenten = Course::find($this->date['range_id'])->members->filter(function ($m) { return $m['status'] === "dozent"; });
                foreach ($cycledate->getAllDates() as $date) {
                    $date['date_typ'] = $this->date['date_typ'];
                    if (!Request::option("resource_id")) {
                        $date['raum'] = $this->date['raum'];
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
                        $booking = $date->room_booking;
                        if (!$booking) {
                            $booking = new ResourceBooking();
                            $booking['range_id'] = $date->getId();
                        }
                        $booking['resource_id'] = Request::option("resource_id");
                        $booking['begin'] = $date['date'];
                        $booking['end'] = $date['end_time'];
                        $booking['repeat_end'] = $date['end_time'];
                        $booking['repeat_quantity'] = 0;
                        $booking['repetition_interval'] = 0;
                        $booking['booking_user_id'] = $GLOBALS['user']->id;
                        $booking['booking_type'] = 0;
                        $booking['preparation_time'] = 0;
                        $booking->store();
                    } elseif ($date->room_booking) {
                        $date->room_booking->delete();
                    }
                }
            } else {
                //Unregelmäßiger Termin
                if (!$this->date->isNew() && $this->date['metadate_id']) {
                    //Regelmäßigen Termin löschen:
                    $data = $this->date->toRawArray();
                    $this->date->cycle->delete();
                    unset($data['metadate_id']);
                    $this->date = new CourseDate();
                    $this->date->setData($data);
                }
                $this->date['autor_id'] = $GLOBALS['user']->id;
                if (Request::option("resource_id")) {
                    $this->date['raum'] = null;
                }
                $this->date->store();

                $dozenten = $this->date->course->members->filter(function ($m) { return $m['status'] === "dozent"; });
                if (Request::getArray("durchfuehrende_dozenten") && count(Request::getArray("durchfuehrende_dozenten")) !== count($dozenten)) {
                    $statement = DBManager::get()->prepare("
                        INSERT IGNORE INTO termin_related_persons
                        SET user_id = :user_id,
                            range_id = :termin_id
                    ");
                    foreach (Request::getArray("durchfuehrende_dozenten") as $user_id) {
                        $statement->execute(array(
                            'user_id' => $user_id,
                            'termin_id' => $this->date->getId()
                        ));
                    }
                }
                if (Request::option("resource_id")) {
                    $booking = $this->date->room_booking;
                    if (!$booking) {
                        $booking = new ResourceBooking();
                        $booking['range_id'] = $this->date->getId();
                    }
                    $booking['resource_id'] = Request::option("resource_id");
                    $booking['begin'] = $this->date['date'];
                    $booking['end'] = $this->date['end_time'];
                    $booking['repeat_end'] = $this->date['end_time'];
                    $booking['repeat_quantity'] = 0;
                    $booking['repetition_interval'] = 0;
                    $booking['booking_user_id'] = $GLOBALS['user']->id;
                    $booking['booking_type'] = 0;
                    $booking['preparation_time'] = 0;
                    $booking->store();
                } elseif ($this->date->room_booking) {
                    $this->date->room_booking->delete();
                }

            }
            //Dialog schließen und Fullcalendar neu laden:
            $this->response->add_header("X-Dialog-Execute", "STUDIP.Veranstaltungsplanung.reloadCalendar");
            $this->response->add_header("X-Dialog-Close", 1);
            $this->render_nothing();
            return;
        }

        if ($this->date->isNew()) {
            PageLayout::setTitle(sprintf(_("Termin erstellen %s - %s"), date("d.m.Y H:i", $this->date['date']), date((floor($this->date['date'] / 86400) == floor($this->date['end_time'] / 86400) ? "H:i" : "d.m.Y H:i "), $this->date['end_time'])));
        } else {
            PageLayout::setTitle(sprintf(_("Termin bearbeiten %s - %s"), date("d.m.Y H:i", $this->date['date']), date((floor($this->date['date'] / 86400) == floor($this->date['end_time'] / 86400) ? "H:i" : "d.m.Y H:i "), $this->date['end_time'])));
        }
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
