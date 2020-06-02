<form class="default vplaner_edit_date"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "date/edit/".$date->getId()) ?>"
      data-dialog>

    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">

    <div class="hgroup">
        <label>
            <?= _("Beginn") ?>
            <input type="text"
                   name="data[date]"
                   data-datetime-picker
                   value="<?= date("d.m.Y H:i", $date['date']) ?>">
        </label>
        <label>
            <?= _("Ende") ?>
            <input type="text"
                   name="data[end_time]"
                   data-datetime-picker
                   value="<?= date("d.m.Y H:i", $date['end_time']) ?>">
        </label>
    </div>

    <label>
        <div>
            <?= _("Veranstaltung auswählen") ?>
        </div>
        <select name="data[range_id]"
                required
                style="display: inline-block;"
                onChange="STUDIP.Veranstaltungsplanung.getDozenten.call(this); $(this).closest('label').find('.planer_course_link').attr('href', this.value ? STUDIP.URLHelper.getURL($(this).closest('label').find('.planer_course_link').data('base-url'), {'cid': this.value}) : '');">
            <option value=""> - </option>
            <? foreach ($courses as $course) : ?>
                <option value="<?= htmlReady($course->getId()) ?>"<?= $course->getId() === $date['range_id'] ? " selected" : "" ?>>
                    <?= htmlReady($course->getFullName()) ?>
                </option>
            <? endforeach ?>
        </select>

        <a href="<?= $date['range_id'] ? URLHelper::getLink("dispatch.php/course/timesrooms", ['cid' => $date['range_id']]) : "" ?>"
           data-base-url="dispatch.php/course/timesrooms"
           target="_blank"
           title="<?= _("Zur Veranstaltung springen") ?>"
           class="planer_course_link">
            <?= Icon::create("seminar+move_right", "clickable")->asImg(25, ['class' => "text.bottom"]) ?>
        </a>
    </label>

    <label>
        <?= _("Art") ?>
        <select name="data[date_typ]">
            <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                <option <?= $date['date_typ'] == $key ? 'selected' : '' ?>
                        value="<?= $key ?>"><?= htmlReady($val['name']) ?></option>
            <? endforeach ?>
        </select>
    </label>

    <? if ($in_semester) : ?>
        <label>
            <input type="checkbox" name="metadate" value="1"<?= $date['metadate_id'] ? " checked" : "" ?>>
            <?= _("Regelmäßiger Termin") ?>
        </label>
    <? endif ?>

    <? if (Config::get()->RESOURCES_ENABLE
        && ($selectable_rooms || $room_search)): ?>
        <label>
            <?= _('Raum') ?>
            <? if ($room_search): ?>
                <?= $room_search->render() ?>
            <? else: ?>
                <select name="resource_id" style="width: calc(100% - 23px);">
                    <option value=""><?= _('<em>Keinen</em> Raum buchen') ?></option>
                    <? foreach ($selectable_rooms as $room): ?>
                        <option value="<?= htmlReady($room->id) ?>"<?= $date->room_booking && ($date->room_booking['resource_id'] === $room->id) ? " selected" : "" ?>>
                            <?= htmlReady($room->name) ?>
                            <? if ($room->seats > 1) : ?>
                                <?= sprintf(_('(%d Sitzplätze)'), $room->seats) ?>
                            <? endif ?>
                        </option>
                    <? endforeach ?>
                </select>
            <? endif ?>
        </label>
    <? endif ?>

    <label>
        <?= _('Freie Ortsangabe') ?>
        <input type="text"
               name="data[raum]"
               value="<?= htmlReady($date['raum']) ?>"
               maxlength="255">
        <? if (Config::get()->RESOURCES_ENABLE) : ?>
            <small style="display: block"><?= _('(führt <em>nicht</em> zu einer Raumbuchung)') ?></small>
        <? endif ?>
    </label>

    <div class="durchfuehrende_dozenten">

    </div>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
        <? if (!$date->isNew()) : ?>
            <? if ($date->cycle) : ?>
                <?= \Studip\Button::create(_("Ausfallen lassen"), "ex_date", ['data-confirm' => _("Wirklich diesen Termin ausfallen lassen?")]) ?>
            <? endif ?>
            <?= \Studip\Button::create(_("Löschen"), "delete_date", ['data-confirm' => $date->cycle ? _("Wirklich diesen Termin und alle Wiederholungen löschen?") : _("Wirklich diesen Termin löschen?")]) ?>

        <? endif ?>
    </div>
</form>
