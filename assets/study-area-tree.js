/*jslint browser: true */
/*global jQuery, STUDIP */
STUDIP.StudyareaTree = {
    remove: function () {
        var id = jQuery(this).closest("li").attr("class");
        var ids = jQuery("#study_area_tree input[name=study_area_ids]").val().split(",");
        ids = _.without(ids, id);
        console.log(ids);
        jQuery("#study_area_tree input[name=study_area_ids]").val(ids.join(","));
        jQuery("#study_area_tree input[name=study_area_ids]").trigger("change");
        jQuery(this).closest('li').remove();
    }
};

(function ($, STUDIP) {
    'use strict';

    function processAreaTree(move_up) {
        return function () {
            var id  = move_up ? $(this).data().id : $(this).closest('li').data().id,
                url = STUDIP.URLHelper.getURL('plugins.php/veranstaltungsplanung/study_area_tree/show/' + id);
            $.get(url).done(function (response) {
                var node   = $('<div>').html(response),
                    parent = $('#study_area_tree');
                $('.children', parent).hide('slide', {'direction': move_up ? 'right' : 'left'}, function () {
                    $('.up', parent).replaceWith(node.find('.up'));
                    $('.children', parent)
                        .replaceWith(node.find('.children'))
                        .show('slide', {'direction': move_up ? 'left' : 'right'});
                });
            });

            return false;
        };
    }

    function select() {
        var id = $(this).closest('li').data().id,
            li = $('#study_area_tree .selected > li.template').clone();
        li.hide()
            .removeClass("template")
            .find("input")
            .val(id);

        li.find(".name")
            .text($('#study_area_tree .children li[data-id=' + id + "]").text());
        li.appendTo('#study_area_tree .selected');
        li.fadeIn(300);

        var ids = jQuery("#study_area_tree input[name=study_area_ids]").val().split(",");
        ids.push(id);
        ids = _.without(_.uniq(ids), "");
        jQuery("#study_area_tree input[name=study_area_ids]").val(ids.join(","));
        jQuery("#study_area_tree input[name=study_area_ids]").trigger("change");
        return false;
    }

    $(document)
        .on('click', '#study_area_tree .up a', processAreaTree(true))
        .on('click', '#study_area_tree .children a.navigator', processAreaTree(false))
        .on('click', '#study_area_tree .children a.selector', select);

}(jQuery, STUDIP));