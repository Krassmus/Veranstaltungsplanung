<?php

class DateController extends PluginController
{
    public function edit_action($date_id = null)
    {
        if (strpos($date_id, "termine_") === 0) {
            $date_id = substr($date_id, 8);
        }
        $this->date = new CourseDate($date_id);
        $this->editable = $this->date->isNew()
            || (!Veranstaltungsplanung::isReadOnly()
                && $GLOBALS['perm']->have_studip_perm("tutor", $this->date['range_id'])
                && !LockRules::Check($this->date['range_id'], 'room_time'));
        if ($this->editable) {
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
        }

        if ($this->date->isNew()) {
            PageLayout::setTitle(sprintf(_("Termin erstellen %s - %s"), date("d.m.Y H:i", $this->date['date']), date((floor($this->date['date'] / 86400) == floor($this->date['end_time'] / 86400) ? "H:i" : "d.m.Y H:i "), $this->date['end_time'])));
        } else {
            if ($this->editable) {
                PageLayout::setTitle(sprintf(_("Termin bearbeiten %s - %s"), date("d.m.Y H:i", $this->date['date']), date((floor($this->date['date'] / 86400) == floor($this->date['end_time'] / 86400) ? "H:i" : "d.m.Y H:i "), $this->date['end_time'])));
            } else {
                PageLayout::setTitle(sprintf(_("Termin %s - %s"), date("d.m.Y H:i", $this->date['date']), date((floor($this->date['date'] / 86400) == floor($this->date['end_time'] / 86400) ? "H:i" : "d.m.Y H:i "), $this->date['end_time'])));
            }
        }
        switch (Request::get("object_type")) {
            case "courses":
                $query = new \Veranstaltungsplanung\SQLQuery(
                    "seminare",
                    "veranstaltungsplanung_courses"
                );
                $query->join(
                    "termine",
                    "`seminare`.`Seminar_id` = `termine`.`range_id`",
                    'LEFT JOIN'
                );
                $query->groupBy("`seminare`.`Seminar_id`");
                $query->orderBy("`seminare`.name ASC");

                $this->vpfilters = Veranstaltungsplanung::getFilters();
                foreach ($this->vpfilters[Request::get("object_type")] as $filter) {
                    foreach ($filter->getNames() as $index => $name) {
                        $filter->applyFilter($index, $query);
                    }
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
                    foreach ($filter->getNames() as $index => $name) {
                        $filter->applyFilter($index, $query);
                    }
                }
                $this->persons = $query->fetchAll("User");

                $this->selected_persons = [];
                if (count($this->date->dozenten) > 0) {
                    foreach ($this->date->dozenten as $dozent) {
                        $this->selected_persons[] = $dozent->getId();
                    }
                } elseif($this->date && $this->date->course) {
                    foreach ($this->date->course->getMembersWithStatus("dozent") as $dozent) {
                        $this->selected_persons[] = $dozent['user_id'];
                    }
                }
                if (Request::option("for_user_id")) {
                    $query = new \Veranstaltungsplanung\SQLQuery(
                        "seminare",
                        "veranstaltungsplanung_user_courses"
                    );
                    $query->join("seminar_user", "seminar_user", "seminare.Seminar_id = seminar_user.Seminar_id AND seminar_user.status = 'dozent'");
                    $query->where(
                        "dozent",
                        "seminar_user.user_id = :dozent_id ",
                        ['dozent_id' => Request::option("for_user_id")]
                    );
                    $query->groupBy("`seminare`.`Seminar_id`");
                    $query->orderBy("`seminare`.name ASC");

                    $this->courses = $query->fetchAll("Course");
                } else {
                    $this->courses = [];
                }


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
                    foreach ($filter->getNames() as $index => $name) {
                        $filter->applyFilter($index, $query);
                    }
                }
                $this->resources = $query->fetchAll("Resource");
                break;
        }

        $this->setAvailableRooms();
        $this->semester = Semester::findByTimestamp($this->date['date']);
        $this->in_semester = $this->semester && ($this->semester['vorles_beginn'] <= $this->date['date'] && $this->semester['vorles_ende'] >= $this->date['end_time']);

        if ($this->date->isNew() && (Request::get("object_type") === "persons") && !Request::option("for_user_id")) {
            $this->render_template("date/new_personsdate");
        } elseif ($this->date->isNew() && Request::get("object_type") === "resources" && !Request::option("for_resource_id")) {
            $this->render_template("date/new_resourcesdate");
        } else {
            $this->render_template("date/edit_coursedate");
        }

    }

    public function getCourseTeachingload(Course $course)
    {
        $seconds = 0;
        foreach ($course->dates as $date) {
            $seconds += $date['end_time'] - $date['date'];
        }
        return $seconds;
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

    public function change_event_action()
    {
        list($type, $termin_id) = explode("_", Request::option("termin_id"), 2);
        $this->date = CourseDate::find($termin_id);
        $start = (int) (Request::get("start"));
        $end = (int) (Request::get("end"));
        $output = [];
        $output['type'] = $type;
        if ($type === "termine" && $GLOBALS['perm']->have_studip_perm("dozent", $this->date['range_id'])) {
            if ($this->date['metadate_id']) {
                $cycledate = $this->date->cycle;
                $cycledate['seminar_id'] = $this->date['range_id'];
                $cycledate['start_time'] = date("H:i:s", $start);
                $cycledate['end_time'] = date("H:i:s", $end);
                $cycledate['weekday'] = date("w", $start) > 0 ? date("w", $start) : 7;
                $cycledate['cycle'] = 0;
                $cycledate->store();
                if ($start < time()) {
                    $output['alert'] = _("Bei Änderungen von regelmäßigen Terminen werden nur zukünftige Termine verändert.");
                }
            } else {
                $this->date['date'] = $start;
                $this->date['end_time'] = $end;
                $this->date->store();
                if ($this->date->room_booking) {
                    $this->date->room_booking['begin'] = $start;
                    $this->date->room_booking['end'] = $end;
                    if ($this->date->room_booking['end'] > $this->date->room_booking['repeat_end']) {
                        $this->date->room_booking['repeat_end'] = $end;
                    }
                    try {
                        $this->date->room_booking->store();
                    } catch (ResourceBookingOverlapException $e) {
                        $this->date->room_booking->delete();
                        $output['alert'] = _("Die Raumbuchung wurde gelöst, weil es zu Überlappungen mit anderen Buchungen kam.");
                    } catch(Exception $e) {
                        $this->date->room_booking->delete();
                        $output['alert'] = _("Fehler (die Raumbuchung wurde aufgelöst): ").$e->getMessage();
                    }
                }
            }
            $output['test'] = 1;
        } else {
            $output['rejected'] = 1;
        }
        $this->render_json($output);
    }

    public function save_action($date_id = null)
    {
        if (strpos($date_id, "termine_") === 0) {
            $date_id = substr($date_id, 8);
        }
        $this->date = new CourseDate($date_id);
        $was_new = $this->date->isNew();
        $had_metadate = $this->date['metadate_id'];
        $this->editable = $this->date->isNew()
            || (!Veranstaltungsplanung::isReadOnly()
                && $GLOBALS['perm']->have_studip_perm("tutor", $this->date['range_id'])
                && !LockRules::Check($this->date['range_id'], 'room_time'));
        if ($this->editable) {

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
                $GLOBALS['user']->cfg->store('VPLANER_DEFAULT_DATETYPE', $data['date_typ']);
                $this->date->setData($data);
            }
        } else {
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

            $delete_termin_related_persons = DBManager::get()->prepare("
                DELETE FROM termin_related_persons
                WHERE range_id = :termin_id
            ");

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
                $cycledate['cycle'] = 0; //jede Woche
                $success = $cycledate->store();
                $dozenten = Course::find($this->date['range_id'])->members->filter(function ($m) {
                    return $m['status'] === "dozent";
                });

                foreach ($cycledate->getAllDates() as $date) {

                    if (Request::get('date_typ_cycledate')) {
                        $date['date_typ'] = Request::get('date_typ_cycledate');
                    } elseif ($was_new) {
                        $date['date_typ'] = $this->date['date_typ'];
                    }

                    if (!Request::option("resource_id_cycledate") || Request::option("resource_id_cycledate") !== 'no' ) {
                        if (Request::get('raum_cycledate') !== 'Unterschiedliche Werte') {
                            $date['raum'] = Request::get('raum_cycledate');
                        }
                    } else {
                        $date['raum'] = null;
                    }


                    $date->store();

                    if (($date['date'] == $this->date['date']) && ($date['end_time'] == $this->date['end_time']) && ($date->getId() !== $this->date->getId())) {
                        $this->date->delete();
                        $this->date = $date;
                    }


                    if (Request::getArray("durchfuehrende_dozenten_cycledate") && !in_array('diff', Request::getArray("durchfuehrende_dozenten_cycledate"))) {
                        $delete_termin_related_persons->execute(['termin_id' => $date->getId()]);
                        if (count(Request::getArray("durchfuehrende_dozenten_cycledate")) !== count($dozenten)) {
                            $statement = DBManager::get()->prepare("
                                INSERT IGNORE INTO termin_related_persons
                                SET user_id = :user_id,
                                    range_id = :termin_id
                            ");
                            foreach (Request::getArray("durchfuehrende_dozenten_cycledate") as $user_id) {
                                $statement->execute([
                                    'user_id' => $user_id,
                                    'termin_id' => $date->getId()
                                ]);
                            }
                        }
                    }

                    if (Request::option("resource_id_cycledate")) {
                        if (Request::option("resource_id_cycledate") !== 'no') {
                            $singledate = new SingleDate($date);
                            $singledate->bookRoom(
                                Request::option("resource_id")
                            );
                        } elseif($date->room_booking) {
                            $date->room_booking->delete();
                        }
                    }

                    $statusgruppen_ids = Request::getArray('statusgruppen_cycledate');
                    if (!in_array('diff', $statusgruppen_ids)) {
                        $delete = DBManager::get()->prepare('
                            DELETE FROM `termin_related_groups`
                            WHERE `termin_id` = :termin_id
                                AND `statusgruppe_id` NOT IN (:statusgruppen_ids)
                        ');
                        $delete->execute([
                            'termin_id' => $date->getId(),
                            'statusgruppen_ids' => $statusgruppen_ids
                        ]);
                        foreach ($statusgruppen_ids as $statusgruppen_id) {
                            $insert = DBManager::get()->prepare('
                                INSERT IGNORE INTO `termin_related_groups`
                                SET termin_id = :termin_id,
                                    `statusgruppe_id` = :statusgruppen_id
                            ');
                            $insert->execute([
                                'termin_id' => $date->getId(),
                                'statusgruppen_id' => $statusgruppen_id
                            ]);
                        }
                    }

                }
            }


            //Unregelmäßiger Termin
            if (!$was_new && $had_metadate && !Request::option("metadate")) {
                //Regelmäßigen Termin löschen:
                $data = $this->date->toRawArray();
                $this->date->cycle->delete();
                unset($data['metadate_id']);
                $this->date = new CourseDate();
                $this->date->setData($data);
            }

            $this->date['autor_id'] = $GLOBALS['user']->id;
            if (Request::option("resource_id") && Request::option("resource_id") !== 'no') {
                $this->date['raum'] = null;
            }
            if ($data['date_typ']) {
                $this->date['date_typ'] = $data['date_typ'];
            }

            $this->date->store();

            $dozenten = $this->date->course->members->filter(function ($m) {
                return $m['status'] === "dozent";
            });
            $delete_termin_related_persons->execute(['termin_id' => $this->date->getId()]);
            if (Request::getArray("durchfuehrende_dozenten") && count(Request::getArray("durchfuehrende_dozenten")) !== count($dozenten)) {
                $statement = DBManager::get()->prepare("
                    INSERT IGNORE INTO termin_related_persons
                    SET user_id = :user_id,
                        range_id = :termin_id
                ");
                foreach (Request::getArray("durchfuehrende_dozenten") as $user_id) {
                    $statement->execute([
                        'user_id' => $user_id,
                        'termin_id' => $this->date->getId()
                    ]);
                }
            }

            if (Request::option("resource_id")) {
                if (Request::option("resource_id") !== 'no') {
                    $singledate = new SingleDate($this->date);
                    $singledate->bookRoom(
                        Request::option("resource_id")
                    );
                } elseif ($this->date->room_booking) {
                    $this->date->room_booking->delete();
                }
            }



            $statusgruppen_ids = Request::getArray('statusgruppen');
            $delete = DBManager::get()->prepare('
                DELETE FROM `termin_related_groups`
                WHERE `termin_id` = :termin_id
                    AND `statusgruppe_id` NOT IN (:statusgruppen_ids)
            ');
            $delete->execute([
                'termin_id' => $this->date->getId(),
                'statusgruppen_ids' => $statusgruppen_ids
            ]);
            foreach ($statusgruppen_ids as $statusgruppen_id) {
                $insert = DBManager::get()->prepare('
                    INSERT IGNORE INTO `termin_related_groups`
                    SET termin_id = :termin_id,
                        `statusgruppe_id` = :statusgruppen_id
                ');
                $insert->execute([
                    'termin_id' => $this->date->getId(),
                    'statusgruppen_id' => $statusgruppen_id
                ]);
            }

            $topics = Request::getArray('topics');
            foreach ($this->date->topics as $t) {
                if (!in_array($t->getId(), $topics)) {
                    $this->date->removeTopic($t->getId());
                }
            }
            foreach ($topics as $topicdata) {
                if (preg_match('/^[a-f0-9]{32}$/', $topicdata)) {
                    //this is an md5 hash:
                    $this->date->addTopic($topicdata);
                } else {
                    $topic = CourseTopic::findOneBySQL("`seminar_id` = :course_id AND `title` = :title", [
                        'course_id' => $this->date['range_id'],
                        'title' => $topicdata
                    ]);
                    if (!$topic) {
                        $topic = new CourseTopic();
                        $topic['seminar_id'] = $this->date['range_id'];
                        $topic['title'] = $topicdata;
                        $topic->store();
                    }
                    $this->date->addTopic($topic->getId());
                }
            }
            //Dialog schließen und Fullcalendar neu laden:
            $this->response->add_header("X-Dialog-Execute", "STUDIP.Veranstaltungsplanung.reloadCalendar");
            $this->response->add_header("X-Dialog-Close", 1);
            $this->render_nothing();
            return;
        }
    }

    public function get_contextmenu_items_action(CourseDate $coursedate)
    {
        $output = [
            'topics' => [],
            'teachers' => [],
            'groups' => [],
            'rooms' => []
        ];
        foreach ($coursedate->course->topics as $topic) {
            $active = false;
            foreach ($coursedate->topics as $t) {
                if ($t->getId() === $topic->getId()) {
                    $active = true;
                    break;
                }
            }
            $output['topics'][] = [
                'issue_id' => $topic->getId(),
                'title' => $topic['title'],
                'active' => $active
            ];
        }
        foreach ($coursedate->course->statusgruppen as $gruppe) {
            $active = false;
            foreach ($coursedate->statusgruppen as $g) {
                if ($g->getId() === $gruppe->getId()) {
                    $active = true;
                    break;
                }
            }
            $output['groups'][] = [
                'statusgruppe_id' => $gruppe->getId(),
                'name' => $gruppe['name'],
                'active' => $active
            ];
        }
        foreach ($coursedate->course->members->filter(function ($m) { return $m['status'] === 'dozent'; }) as $member) {
            $active = false;
            if (count($coursedate->dozenten)) {
                foreach ($coursedate->dozenten as $u) {
                    if ($u->getId() === $member['user_id']) {
                        $active = true;
                        break;
                    }
                }
            } else {
                $active = true;
            }
            $output['teachers'][] = [
                'user_id' => $member['user_id'],
                'name' => $member->user->getFullName(),
                'active' => $active
            ];
        }
        $begin = new DateTime();
        $begin->setTimestamp($coursedate['date']);
        $end = new DateTime();
        $end->setTimestamp($coursedate['end_time']);
        $output['rooms'][] = [
            'resource_id' => 'no',
            'name' => _('Kein Raum'),
            'active' => $coursedate->room_booking ? false : true,
            'disabled' => false
        ];
        foreach (Room::findAll() as $room) {
            $output['rooms'][] = [
                'resource_id' => $room->id,
                'name' => $room['name'],
                'active' => $coursedate->room_booking && $coursedate->room_booking->resource_id === $room->id,
                'disabled' => $room->isAssigned(
                    $begin,
                    $end,
                    [$coursedate->room_booking ? $coursedate->room_booking->id : 'no']
                )
            ];
        }
        $this->render_json($output);
    }

    public function toggle_topic_action(CourseDate $coursedate)
    {
        if (Request::isPost() && Request::option('issue_id') && $GLOBALS['perm']->have_studip_perm('tutor', $coursedate['range_id'])) {
            $topic = CourseTopic::find(Request::option('issue_id'));
            if ($topic && $topic['seminar_id'] === $coursedate['range_id']) {
                $exists = false;
                foreach ($coursedate->topics as $t) {
                    if ($topic->getId() === $t->getId()) {
                        $exists = true;
                    }
                }
                if ($exists) {
                    $coursedate->removeTopic($topic);
                } else {
                    $coursedate->addTopic($topic);
                }
            }
        }
        $this->render_nothing();
    }

    public function add_topic_action(CourseDate $coursedate)
    {
        if (Request::isPost() && Request::get('topic_title') && $GLOBALS['perm']->have_studip_perm('tutor', $coursedate['range_id'])) {
            $topic = CourseTopic::findOneBySQL('`seminar_id` = :course_id AND `title` = :title', [
                'course_id' => $coursedate['range_id'],
                'title' => Request::get('topic_title')
            ]);
            if (!$topic) {
                $topic = new CourseTopic();
                $topic['seminar_id'] = $coursedate['range_id'];
                $topic['title'] = Request::get('topic_title');
                $topic->store();
            }
            $coursedate->addTopic($topic);
        }
        $this->render_nothing();
    }

    public function toggle_teacher_action(CourseDate $coursedate)
    {
        if (Request::isPost() && Request::option('user_id') && $GLOBALS['perm']->have_studip_perm('dozent', $coursedate['range_id'])) {
            $dozenten = $coursedate->course->members->filter(function ($m) { return $m['status'] === 'dozent'; });
            if (Request::bool('active')) {
                if (count($dozenten) === count($coursedate->dozenten) + 1) {
                    $statement = DBManager::get()->prepare('
                        DELETE FROM `termin_related_persons`
                        WHERE `range_id` = :termin_id
                    ');
                    $statement->execute([
                        'termin_id' => $coursedate->getId()
                    ]);
                } else {
                    $statement = DBManager::get()->prepare('
                        INSERT IGNORE INTO `termin_related_persons`
                        SET `user_id` = :user_id,
                            `range_id` = :termin_id
                    ');
                    $statement->execute([
                        'user_id' => Request::option('user_id'),
                        'termin_id' => $coursedate->getId()
                    ]);
                }
            } else {
                if (count($coursedate->dozenten) === 0) {
                    foreach ($dozenten as $doz) {
                        if ($doz['user_id'] !== Request::option('user_id')) {
                            $statement = DBManager::get()->prepare('
                                INSERT IGNORE INTO `termin_related_persons`
                                SET `user_id` = :user_id,
                                    `range_id` = :termin_id
                            ');
                            $statement->execute([
                                'user_id' => $doz['user_id'],
                                'termin_id' => $coursedate->getId()
                            ]);
                        }
                    }
                } else {
                    $statement = DBManager::get()->prepare('
                        DELETE FROM `termin_related_persons`
                        WHERE `user_id` = :user_id
                            AND `range_id` = :termin_id
                    ');
                    $statement->execute([
                        'user_id' => Request::option('user_id'),
                        'termin_id' => $coursedate->getId()
                    ]);
                }
            }
        }
        $this->render_nothing();
    }

    public function toggle_statusgruppe_action(CourseDate $coursedate)
    {
        $statusgruppe = Statusgruppen::find(Request::option('statusgruppe_id'));
        if (Request::isPost() && $statusgruppe && ($statusgruppe['range_id'] === $coursedate['range_id']) && $GLOBALS['perm']->have_studip_perm('tutor', $coursedate['range_id'])) {
            if (Request::bool('active')) {
                $statement = DBManager::get()->prepare('
                    INSERT IGNORE INTO `termin_related_groups`
                    SET `termin_id` = :termin_id,
                        `statusgruppe_id` = :statusgruppe_id
                ');
                $statement->execute([
                    'termin_id' => $coursedate->getId(),
                    'statusgruppe_id' => $statusgruppe->getId()
                ]);
            } else {
                $statement = DBManager::get()->prepare('
                    DELETE FROM `termin_related_groups`
                    WHERE `termin_id` = :termin_id
                        AND `statusgruppe_id` = :statusgruppe_id
                ');
                $statement->execute([
                    'termin_id' => $coursedate->getId(),
                    'statusgruppe_id' => $statusgruppe->getId()
                ]);
            }
        }
        $this->render_nothing();
    }

    public function use_room_action(CourseDate $coursedate)
    {
        if (Request::isPost()) {
            if (Request::option("resource_id") !== 'no') {
                $singledate = new SingleDate($coursedate);
                $singledate->bookRoom(
                    Request::option("resource_id")
                );
            } elseif ($coursedate->room_booking) {
                $coursedate->room_booking->delete();
            }
        }
        $this->render_nothing();
    }
}
