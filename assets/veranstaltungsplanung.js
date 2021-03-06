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
        jQuery('.durchfuehrende_dozenten')
            .load(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/get_dozenten/' + this.value),
                function () {
                    jQuery('select[name=durchfuehrende_dozenten]').select2();
                }
            );
    } else {
        jQuery('.durchfuehrende_dozenten').html('');
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
            $(info.el).attr("title", info.event._def.title);
        },
        eventClick: function (info) {
            var termin_id = info.event.id;
            STUDIP.Dialog.fromURL(
                STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/date/edit/' + termin_id),
                {
                    "data": STUDIP.Veranstaltungsplanung.getCurrentParameters()
                }
            );
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
});
