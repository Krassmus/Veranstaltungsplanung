if (typeof STUDIP.Veranstaltungsplanung === "undefined") {
    STUDIP.Veranstaltungsplanung = {};
}

jQuery(function () {
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
        calendar.refetchEvents();
    });

    var calendarEl = document.getElementById('calendar');

    console.log(STUDIP.Veranstaltungsplanung.hidden_days);
    var calendar = new FullCalendar.Calendar(calendarEl, {
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
        eventDragStart: function (info) {
            var termin_id = info.event.id;
            calendar.addEvent({
                'title': "Geblockt",
                'start': "2019-03-26 14:15",
                'end': "2019-03-26 16:00",
                'rendering': "background",
                'backgroundColor': "darkred",
                'color': "white",
                'classNames': "blocked",
                'editable': false
            });
        },
        eventDragStop: function () {
            var events = calendar.getEvents();
            for (var i in events) {
                for (var k in events[i].classNames) {
                    if (events[i].classNames[k] == "blocked") {
                        events[i].remove();
                    }
                }
            }
        },
        select: function(arg) {
            var title = prompt('Event Title:');
            if (title) {
                calendar.addEvent({
                    title: title,
                    start: arg.start,
                    end: arg.end,
                    allDay: arg.allDay,
                    textColor: 'black'
                })
            }
            calendar.unselect()
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

    calendar.render();
});