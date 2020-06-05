/*jslint browser: true */
/*global jQuery, STUDIP */
STUDIP.StudyareaTree = {
    remove: function () {
        var id = jQuery(this).closest("li").data("id");
        var object_tree = $(this).closest('.object_tree');
        jQuery(this).closest('li').remove();
        var ids = $(object_tree).find("input.ids").val().split(",");
        ids = _.without(ids, id);
        $(object_tree).find("input.ids").val(ids.join(","));
        $(object_tree).find("input.ids").trigger("change");

    }
};

(function ($, STUDIP) {
    'use strict';

    function processAreaTree(move_up) {
        return function () {
            var id  = move_up ? $(this).data().id : $(this).closest('li').data().id,
                object_tree = $(this).closest('.object_tree'),
                url = STUDIP.URLHelper.getURL($(object_tree).data("url") + id);
            $.get(url).done(function (response) {
                var node   = $('<div>').html(response),
                    parent = object_tree;
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
            object_tree = $(this).closest('.object_tree'),
            li = $(object_tree).find('.selected > li.template').clone();
        li.hide()
            .removeClass("template")
            .data("id", id)
            .find("input")
            .val(id);

        li.find(".name")
            .text($(object_tree).find('.children li[data-id=' + id + "]").text());
        li.appendTo($(object_tree).find('.selected'));
        li.fadeIn(300);

        var ids = $(object_tree).find("input.ids").val().split(",");
        ids.push(id);
        ids = _.without(_.uniq(ids), "");
        $(object_tree).find("input.ids").val(ids.join(","));
        $(object_tree).find("input.ids").trigger("change");
        return false;
    }

    $(document)
        .on('click', '.object_tree .up a', processAreaTree(true))
        .on('click', '.object_tree .children a.navigator', processAreaTree(false))
        .on('click', '.object_tree .children a.selector', select)
        .on('click', ".object_tree .remove_tree_object", STUDIP.StudyareaTree.remove);

}(jQuery, STUDIP));
