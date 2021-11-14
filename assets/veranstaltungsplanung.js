if (typeof STUDIP.Veranstaltungsplanung === "undefined") {
    STUDIP.Veranstaltungsplanung = {};
}

STUDIP.Veranstaltungsplanung.dragging = false;
STUDIP.Veranstaltungsplanung.changeEventStart = function (info) {
    var termin_id = info.event.id;
    STUDIP.Veranstaltungsplanung.dragging = true;
    //Display blocked areas:
    jQuery.ajax({
        "url": STUDIP.URLHelper.getURL("plugins.php/veranstaltungsplanung/planer/get_collisions"),
        "data": {
            "termin_id": info.event.id,
            "start": STUDIP.Veranstaltungsplanung.calendar.view.currentStart.toUTCString(),
            "end": STUDIP.Veranstaltungsplanung.calendar.view.currentEnd.toUTCString()
        },
        "dataType": "json",
        "success": function (output) {
            if (STUDIP.Veranstaltungsplanung.dragging) {
                for (var i in output.events) {
                    STUDIP.Veranstaltungsplanung.calendar.addEvent({
                        'start': output.events[i].start,
                        'end': output.events[i].end,
                        'rendering': "background",
                        'backgroundColor': output.events[i].conflict === "original"
                            ? "yellow"
                            : "darkred",
                        'color': "white",
                        'classNames': "blocked",
                        'editable': false
                    });
                }
            }
        }
    });
};
STUDIP.Veranstaltungsplanung.changeEventEnd = function (info) {
    var termin_id = info.event.id;
    STUDIP.Veranstaltungsplanung.dragging = false;
    var events = STUDIP.Veranstaltungsplanung.calendar.getEvents();
    //remove yellow and black blocked dates:
    for (var i in events) {
        for (var k in events[i].classNames) {
            if (events[i].classNames[k] === "blocked") {
                events[i].remove();
            }
        }
    }
};
STUDIP.Veranstaltungsplanung.dropEvent = function (info) {
    let termin_id = info.event.id;
    let start = parseInt(info.event.start.getTime() / 1000, 10).toFixed(0);
    let end = parseInt(info.event.end.getTime() / 1000, 10).toFixed(0);

    let revert = function () {
        info.revert();
    };
    let makeChange = function () {
        //AJAX to change; if there is a collision open a dialog and ask what to do
        jQuery.ajax({
            "url": STUDIP.URLHelper.getURL("plugins.php/veranstaltungsplanung/date/change_event"),
            "data": {
                "termin_id": info.event.id,
                "start": start,
                "end": end
            },
            "dataType": "json",
            "success": function (output) {
                if (output.alert) {
                    if (typeof STUDIP.Report !== "undefined") {
                        STUDIP.Report.info("Hinweis".toLocaleString(), output.alert);
                    } else {
                        window.alert(output.alert);
                    }
                    STUDIP.Veranstaltungsplanung.calendar.refetchEvents();
                }
                if (output.rejected) {
                    info.revert();
                }
            },
            "error": function () {
                info.revert();
            }
        });
    };

    if ($("#always_ask").val() !== "0") {
        STUDIP.Dialog.confirm("Soll der Termin verschoben werden?", makeChange, revert);
    } else {
        makeChange();
    }
};

STUDIP.Veranstaltungsplanung.appendFragment = function () {
    var fragment = [];
    var params = STUDIP.Veranstaltungsplanung.getCurrentParameters();
    for (var i in params) {
        fragment.push(encodeURIComponent(i) + "=" + encodeURIComponent(params[i]));
    }
    fragment = fragment.join("&");
    window.location.hash = fragment;
    return fragment;
};

STUDIP.Veranstaltungsplanung.rearrangeSidebar = function () {
    var object_type = jQuery(".change_type").val();
    if (object_type !== "courses") {
        jQuery(".sidebar-widget.courses").hide();
    }
    if (object_type !== "persons") {
        jQuery(".sidebar-widget.persons").hide();
    }
    if (object_type !== "resources") {
        jQuery(".sidebar-widget.resources").hide();
    }
    jQuery(".sidebar-widget." + object_type).show();
};

STUDIP.Veranstaltungsplanung.reloadCalendar = function () {
    STUDIP.Veranstaltungsplanung.calendar.refetchEvents();
};

STUDIP.Veranstaltungsplanung.getCurrentParameters = function () {
    var object_type = jQuery("#object_type").val();
    var params = {
        "object_type": object_type
    };
    jQuery(".date_fetch_params").each(function () {
        if (jQuery(this).val()
                && jQuery(this).val() !== "all"
                && (jQuery(this).attr("id") !== "object_type")
                && (jQuery(this).data("object_type") === object_type)) {
            params[jQuery(this).attr("id")] = jQuery(this).val();
        }
    });
    return params;
};

STUDIP.Veranstaltungsplanung.getDozenten = function () {
    if (this.value) {
        let date_id = $(this).closest('form').data('date_id');
        jQuery('.durchfuehrende_dozenten')
            .load(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/get_dozenten/' + this.value + '/' + date_id),
                function () {
                    jQuery('.durchfuehrende_dozenten_select').select2();
                }
            );
    } else {
        jQuery('.durchfuehrende_dozenten').html('');
    }
};
STUDIP.Veranstaltungsplanung.getStatusgruppen = function () {
    if (this.value) {
        let date_id = $(this).closest('form').data('date_id');
        jQuery('.statusgruppen')
            .load(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/get_statusgruppen/' + this.value + '/' + date_id),
                function () {
                    jQuery('.statusgruppen_select').select2();
                }
            );
    } else {
        jQuery('.durchfuehrende_dozenten').html('');
    }
};
STUDIP.Veranstaltungsplanung.getThemen = function () {
    if (this.value) {
        let date_id = $(this).closest('form').data('date_id');
        jQuery('.relevante_themen')
            .load(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/get_themen/' + this.value + '/' + date_id),
                function () {
                    jQuery('select[name=relevante_themen]').select2();
                }
            );
    } else {
        jQuery('.relevante_themen').html('');
    }
};
STUDIP.Veranstaltungsplanung.addThema = function () {
    if (this.value) {
        let option = $('<option selected></option>');
        option.attr('value', this.value);
        option.text(this.value);
        jQuery('.relevante_themen').append(option);
        $(this).val('');
    }
};

jQuery(function () {
    //extract fragment:
    var fragment = window.location.hash.substr(1).split("&");
    for (var i in fragment) {
        var params = fragment[i].split("=");
        if (params[0]) {
            jQuery("#" + params[0]).val(decodeURIComponent(params[1]));
            jQuery(".sidebar select[name=" + params[0] + "], .sidebar input[name=" + params[0] + "]").val(decodeURIComponent(params[1]));
            jQuery(".sidebar select[name=" + params[0] + "], .sidebar input[name=" + params[0] + "]").trigger("change");
        }
    }

    jQuery(".sidebar form").on("submit", function () {
        return false;
    });
    jQuery(".change_type").on("change", STUDIP.Veranstaltungsplanung.rearrangeSidebar);
    jQuery(".sidebar select, .sidebar input[name]").on("change", function () {
        var name = jQuery(this).attr("name").replace(/\[\]/, "");
        var val = jQuery(this).val();
        if (val && typeof val === "object") {
            val = JSON.stringify(val.filter(i => !!i));
        }
        jQuery("#" + name).val(val);
        STUDIP.Veranstaltungsplanung.appendFragment();
        if (typeof STUDIP.Veranstaltungsplanung.calendar !== "undefined") {
            STUDIP.Veranstaltungsplanung.calendar.refetchEvents();
        }
    }).trigger("change");







    var calendarEl = document.getElementById('calendar');

    STUDIP.Veranstaltungsplanung.calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ 'interaction', 'timeGrid', 'dayGrid' ],
        defaultView: STUDIP.Veranstaltungsplanung.defaultView, // dayGridMonth timeGridWeek
        allDaySlot: false,
        customButtons: {
            datepicker: {
                text: 'Datum wählen'.toLocaleString(),
                click: function() {
                    if ($("#hiddenDate").length === 0) {
                        var $btnCustom = $('.fc-datepicker-button'); // name of custom button in the generated code
                        $btnCustom.after('<input type="hidden" id="hiddenDate" class="datepicker">');

                        $("#hiddenDate").css("opacity", 0).datepicker({
                            showOn: "both",
                            dateFormat:"yy-mm-dd",
                            onSelect: function (dateText, inst) {
                                STUDIP.Veranstaltungsplanung.calendar.gotoDate(dateText);
                            },
                        }).datepicker("show");
                    } else {
                        $("#hiddenDate").datepicker("show");
                    }
                }
            },
            viewchanger: {
                text: 'Ansicht',
                click: function() {
                    if (STUDIP.Veranstaltungsplanung.calendar.view.type === "timeGridWeek") {
                        STUDIP.Veranstaltungsplanung.calendar.changeView("dayGridMonth");
                    } else {
                        STUDIP.Veranstaltungsplanung.calendar.changeView("timeGridWeek");
                    }
                    jQuery.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/set_default_view'), {
                        'default_view': STUDIP.Veranstaltungsplanung.calendar.view.type
                    });
                }
            }
        },
        header: {
            left: 'prev next datepicker',
            center: 'title',
            right: 'viewchanger'
        },
        weekNumbers: true,
        firstDay: 1,
        hiddenDays: STUDIP.Veranstaltungsplanung.hidden_days,
        height: '80vh',
        snapDuration: '00:05:00',
        weekends: true,
        minTime: $("#mintime").val() || '00:00:00',
        maxTime: $("#maxtime").val() || '24:00:00',
        scrollTime: '07:30:00',
        selectable: jQuery("#editable").val() ? true : false, //optionally prevent creating new dates
        selectMirror: true,
        timezone: 'local',
        eventRender: function(info) {
            $(info.el)
                .attr("title", info.event._def.title)
                .data('id', info.event._def.publicId);
        },
        eventClick: function (info) {
            var termin_id = info.event.id;
            if (!$(info.el).hasClass('event_data')) {
                STUDIP.Dialog.fromURL(
                    STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/edit/' + termin_id),
                    {
                        "data": STUDIP.Veranstaltungsplanung.getCurrentParameters()
                    }
                );
            }
        },
        eventResizeStart: STUDIP.Veranstaltungsplanung.changeEventStart,
        eventResizeStop: STUDIP.Veranstaltungsplanung.changeEventEnd,
        eventDragStart: STUDIP.Veranstaltungsplanung.changeEventStart,
        eventDragStop: STUDIP.Veranstaltungsplanung.changeEventEnd,
        eventDrop: STUDIP.Veranstaltungsplanung.dropEvent,
        eventResize: STUDIP.Veranstaltungsplanung.dropEvent,
        select: function (arg) {
            //neuer Termin:
            var data = STUDIP.Veranstaltungsplanung.getCurrentParameters();
            data["start"] = arg.start.getTime() / 1000;
            data["end"] = arg.end.getTime() / 1000;
            STUDIP.Dialog.fromURL(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/edit', data)
            );
        },
        editable: jQuery("#editable").val() ? true : false,
        defaultDate: jQuery("#calendar").data("default_date") ? jQuery("#calendar").data("default_date") : "now",
        locale: "de",
        events: {
            url: STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/fetch_dates'),
            method: 'POST',
            extraParams: STUDIP.Veranstaltungsplanung.getCurrentParameters,
            failure: function() {
                alert('there was an error while fetching events!');
            },
            success: function (events) {
                if ($('#print').val()) {
                    //calculate minTime and maxTime depending on the displayed events:
                    let mintime = 8 * 60 * 60;
                    let maxtime = 16 * 60 * 60;
                    let start = null;
                    let end = null;
                    for (let event of events) {
                        start = new Date(event.start);
                        mintime = Math.min(mintime, start.getSeconds() + 60 * start.getMinutes() + 60 * 60 * start.getHours());
                        end = new Date(event.end);
                        maxtime = Math.max(maxtime, end.getSeconds() + 60 * end.getMinutes() + 60 * 60 * end.getHours());
                    }

                    mintime = (Math.floor(mintime / 3600) < 10 ? "0" : "")
                        + Math.floor(mintime / 3600)
                        + ":"
                        + (Math.floor((mintime / 60) % 60) < 10 ? "0" : "")
                        + Math.floor((mintime / 60) % 60)
                        + ":"
                        + (mintime % 3600 < 10 ? "0" : "")
                        + mintime % 3600;
                    maxtime = (Math.floor(maxtime / 3600) < 10 ? "0" : "")
                        + Math.floor(maxtime / 3600)
                        + ":"
                        + (Math.floor((maxtime / 60) % 60) < 10 ? "0" : "")
                        + Math.floor((maxtime / 60) % 60)
                        + ":"
                        + (maxtime % 3600 < 10 ? "0" : "")
                        + maxtime % 3600;
                    if (mintime != STUDIP.Veranstaltungsplanung.calendar.getOption('minTime')) {
                        STUDIP.Veranstaltungsplanung.calendar.setOption('minTime', mintime);
                    }
                    if (maxtime != STUDIP.Veranstaltungsplanung.calendar.getOption('maxTime')) {
                        STUDIP.Veranstaltungsplanung.calendar.setOption('maxTime', maxtime);
                    }
                }
            },
            textColor: 'black' // a non-ajax option
        }
    });

    STUDIP.Veranstaltungsplanung.calendar.render();

    STUDIP.Veranstaltungsplanung.rearrangeSidebar();

    if ($('#context_menu').val()) {

        let loadContents = function (termin_id, event_type) {
            let topics_promise = jQuery.Deferred();
            let groups_promise = jQuery.Deferred();
            let teachers_promise = jQuery.Deferred();
            $.ajax({
                "url": STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/get_contextmenu_items/' + termin_id),
                'dataType': 'json',
                'success': function (output) {
                    let topics = output.topics;
                    let subitems = {};
                    for (let i in topics) {
                        subitems['topic_' + topics[i].issue_id] = {
                            'name': topics[i].title,
                            'type': 'checkbox',
                            'selected': topics[i].active,
                            'value': topics[i].issue_id,
                            'events': {
                                change: function () {
                                    let issue_id = $(this).val();
                                    $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/toggle_topic/' + termin_id), {
                                        'issue_id': issue_id
                                    });
                                }
                            }
                        };
                    }
                    if (Object.keys(subitems).length === 0) {
                        subitems.topic_notopic = {
                            'name': 'Keine Themen',
                            'disabled': true
                        };
                    }
                    subitems['topic_new'] = {
                        'name': 'Thema anlegen',
                        'type': 'text',
                        'events': {
                            keyup: function (ev) {
                                if (ev.originalEvent.key === 'Enter') {
                                    let topic = $(this).val();
                                    $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/add_topic/' + termin_id), {
                                        'topic_title': topic
                                    });
                                    $(this).val('');
                                    $.contextMenu('update');
                                }
                            }
                        }
                    };
                    topics_promise.resolve(subitems);

                    //teachers:
                    subitems = {};
                    if (output.teachers.length > 1) {
                        for (let i in output.teachers) {
                            subitems['teacher_' + output.teachers[i].user_id] = {
                                'name': output.teachers[i].name,
                                'type': 'checkbox',
                                'selected': output.teachers[i].active,
                                'value': output.teachers[i].user_id,
                                'events': {
                                    change: function () {
                                        let user_id = $(this).val();
                                        $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/toggle_teacher/' + termin_id), {
                                            'user_id': user_id,
                                            'active': $(this).is(':checked') ? 1 : 0
                                        });
                                    }
                                }
                            };
                        }
                    } else if (output.teachers.length > 0) {
                        subitems.topic_notopic = {
                            'name': output.teachers[0].name,
                            'disabled': true
                        };
                    }
                    teachers_promise.resolve(subitems);

                    subitems = {};
                    for (let i in output.groups) {
                        subitems['statusgruppe_' + output.groups[i].statusgruppe_id] = {
                            'name': output.groups[i].name,
                            'type': 'checkbox',
                            'selected': output.groups[i].active,
                            'value': output.groups[i].statusgruppe_id,
                            'events': {
                                change: function () {
                                    let statusgruppe_id = $(this).val();
                                    $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/toggle_statusgruppe/' + termin_id), {
                                        'statusgruppe_id': statusgruppe_id,
                                        'active': $(this).is(':checked') ? 1 : 0
                                    });
                                }
                            }
                        };
                    }
                    groups_promise.resolve(subitems);
                }
            });
            return {
                'topics': topics_promise.promise(),
                'groups': groups_promise.promise(),
                'teachers': teachers_promise.promise()
            };
        };

        $.contextMenu({
            selector: '.fc-event:not(.event_data)',
            build: function ($trigger, e) {
                let id = $(e.target).closest('.fc-event').data('id').split('_');
                let termin_id = id[1];
                let is_dateseries = $(e.target).closest('.fc-event').hasClass('dateseries');
                let is_personaldate = $(e.target).closest('.fc-event').hasClass('event_data');
                let items = {};
                items.edit = {
                    name: "Bearbeiten",
                    icon: "edit",
                    callback: function () {
                        STUDIP.Dialog.fromURL(
                            STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/edit/' + termin_id),
                            {
                                "data": STUDIP.Veranstaltungsplanung.getCurrentParameters()
                            }
                        );
                    }
                };
                if (is_dateseries) {
                    items.cancel = {
                        name: "Ausfallen lassen",
                        icon: "cancel",
                        callback: function () {
                            STUDIP.Dialog.confirm('Wirklich ausfallen lassen?', function () {
                                $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/save/' + termin_id), {
                                    'ex_date': 1
                                }).then(STUDIP.Veranstaltungsplanung.reloadCalendar);
                            });
                        }
                    };
                }
                items.delete = {
                    name: "Löschen",
                    icon: "delete",
                    callback: function () {
                        STUDIP.Dialog.confirm('Wirklich löschen?', function () {
                            $.post(STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/save/' + termin_id), {
                                'delete_date': 1
                            }).then(STUDIP.Veranstaltungsplanung.reloadCalendar);
                        });
                    }
                };
                let contents = loadContents(termin_id, id[0]);
                if (!is_personaldate) {
                    items.sep1 = "---------";
                    items.teachers = {
                        name: 'Durchführend',
                        icon: function () {
                            return 'context-menu-icon context-menu-icon-teacher';
                        },
                        items: contents.teachers
                    };
                    items.groups = {
                        name: 'Gruppen',
                        icon: function () {
                            return 'context-menu-icon context-menu-icon-groups';
                        },
                        items: contents.groups
                    };
                    items.topics = {
                        name: "Themen",
                        icon: function () {
                            return 'context-menu-icon context-menu-icon-topic';
                        },
                        items: contents.topics
                    };
                }
                return {
                    items: items
                };
            }
        });
    }
});
