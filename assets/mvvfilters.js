jQuery(function () {
    jQuery(document).on("change", ".sidebar-widget.courses select[name=institut_id]", function () {
        let institut_id = jQuery(this).val();
        window.setTimeout(function () {
            $(".sidebar-widget.courses.stgteilfilter .sidebar-widget-content form select").load(
                STUDIP.URLHelper.getURL("plugins.php/veranstaltungsplanung/mvvfilters/get_mvv_stgteil") + " form select option",
                function () {
                    $(".sidebar-widget.courses.stgteilfilter .sidebar-widget-content form select").trigger("change");
                }
            );
        }, 200);
    });
    jQuery(document).on("change", ".sidebar-widget.courses select[name=stgteil_id]", function () {
        let stgteil_id = jQuery(this).val();
        window.setTimeout(function () {
            $(".sidebar-widget.courses.modulfilter .sidebar-widget-content form select").load(
                STUDIP.URLHelper.getURL("plugins.php/veranstaltungsplanung/mvvfilters/get_mvv_modul") + " form select option",
                {
                    "stgteil_id": stgteil_id,
                    "modul_id": ""
                },
                function () {
                    $(".sidebar-widget.courses.modulfilter .sidebar-widget-content form select").val('');
                    $(".sidebar-widget.courses.modulfilter .sidebar-widget-content form select").trigger('change');
                }
            );
        }, 400);
    });
});