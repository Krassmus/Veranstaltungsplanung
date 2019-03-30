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
    STUDIP.Veranstaltungsplanung.dragging = false;
    var events = STUDIP.Veranstaltungsplanung.calendar.getEvents();
    for (var i in events) {
        for (var k in events[i].classNames) {
            if (events[i].classNames[k] === "blocked") {
                events[i].remove();
            }
        }
    }

    //AJAX to change; if there is a collision open a dialog and ask what to do
};

STUDIP.Veranstaltungsplanung.appendFragment = function () {
    var object_type = jQuery("#object_type").val();
    var fragment = "object_type=" + encodeURIComponent(object_type);

    jQuery(".date_fetch_params").each(function () {
        if (jQuery(this).val()
                && (jQuery(this).attr("id") !== "object_type")
                && (jQuery(this).data("object_type") === object_type)) {
            fragment += "&" + jQuery(this).attr("id") + "=" + encodeURIComponent(jQuery(this).val());
        }
    });
    window.location.hash = fragment;
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
    jQuery(".change_type").on("change", function () {
        var object_type = jQuery(".change_type").val();
        if (object_type !== "courses") {
            jQuery("form .courses").closest(".sidebar-widget").hide();
        }
        if (object_type !== "teachers") {
            jQuery("form .teachers").closest(".sidebar-widget").hide();
        }
        if (object_type !== "resources") {
            jQuery("form .resources").closest(".sidebar-widget").hide();
        }
        jQuery(".sidebar-widget ." + object_type).closest(".sidebar-widget").show();

    });
    jQuery(".sidebar select, .sidebar input").on("change", function () {
        var name = jQuery(this).attr("name");
        jQuery("#" + name).val(jQuery(this).val());
        STUDIP.Veranstaltungsplanung.appendFragment();
        STUDIP.Veranstaltungsplanung.calendar.refetchEvents();
    });





    var calendarEl = document.getElementById('calendar');

    STUDIP.Veranstaltungsplanung.calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ 'interaction', 'timeGrid' ],
        defaultView: 'timeGridWeek',
        allDaySlot: false,
        header: {
            left: '',
            center: 'prev,next today',
            right: ''
        },
        weekNumbers: true,
        firstDay: 1,
        hiddenDays: STUDIP.Veranstaltungsplanung.hidden_days,
        height: '80vh',
        snapDuration: '00:05:00',
        weekends: true,
        minTime: '00:00:00',
        maxTime: '24:00:00',
        scrollTime: '07:30:00',
        selectable: true,
        selectMirror: true,
        eventResizeStart: STUDIP.Veranstaltungsplanung.changeEventStart,
        eventResizeStop: STUDIP.Veranstaltungsplanung.changeEventEnd,
        eventDragStart: STUDIP.Veranstaltungsplanung.changeEventStart,
        eventDragStop: STUDIP.Veranstaltungsplanung.changeEventEnd,
        select: function (arg) {
            var title = prompt('Event Title:');
            if (title) {
                STUDIP.Veranstaltungsplanung.addEvent({
                    title: title,
                    start: arg.start,
                    end: arg.end,
                    allDay: arg.allDay,
                    textColor: 'black'
                });
            }
            STUDIP.Veranstaltungsplanung.unselect();
        },
        editable: true,
        defaultDate: jQuery("#calendar").data("default_date") ? jQuery("#calendar").data("default_date") : null,
        locale: "de",
        events: {
            url: STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/planer/fetch_dates'),
            method: 'POST',
            extraParams: function() {
                var obj = {};
                jQuery(".date_fetch_params").each(function () {
                    obj[jQuery(this).attr("id")] = jQuery(this).val();
                });
                return obj;
            },
            failure: function() {
                alert('there was an error while fetching events!');
            },
            textColor: 'black' // a non-ajax option
        }
    });

    STUDIP.Veranstaltungsplanung.calendar.render();

    jQuery("<.sidebar select[name=object_type]").trigger("change");
});