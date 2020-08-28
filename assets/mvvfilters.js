jQuery(function () {
    jQuery(document).on("change", ".sidebar-widget.courses select[name=institut_id]", function () {
        let institut_id = jQuery(this).val();
        window.setTimeout(function () {
            $(".sidebar-widget.courses.mvvfilters .sidebar-widget-content form select").load(
                STUDIP.URLHelper.getURL("plugins.php/veranstaltungsplanung/mvvfilters/get_mvv_stgteil") + " form select option",
                function () {
                    $(".sidebar-widget.courses.mvvfilters .sidebar-widget-content form select").trigger("change");
                }
            );
        }, 200);
    });
});
