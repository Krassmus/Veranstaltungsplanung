<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "planer/create_date") ?>"
      data-dialog>

    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">
    <input type="hidden" name="start" value="<?= htmlReady($start) ?>">
    <input type="hidden" name="end" value="<?= htmlReady($end) ?>">

    <label>
        <?= _("Veranstaltung auswählen") ?>
            <select name="course_id" required onChange="STUDIP.Veranstaltungsplanung.getDozenten.call(this);">
                <option value=""> - </option>
                <? foreach ($courses as $course) : ?>
                    <option value="<?= htmlReady($course->getId()) ?>">
                        <?= htmlReady($course->getFullName()) ?>
                    </option>
                <? endforeach ?>
            </select>
    </label>

    <label>
        <?= _("Art") ?>
        <select name="dateType">
            <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                <option <?= Request::get('dateType') == $key ? 'selected' : '' ?>
                        value="<?= $key ?>"><?= htmlReady($val['name']) ?></option>
            <? endforeach ?>
        </select>
    </label>

    <? if ($in_semester) : ?>
        <label>
            <input type="checkbox" name="metadate" value="1">
            <?= _("Regelmäßiger Termin") ?>
        </label>
    <? endif ?>

    <? if (Config::get()->RESOURCES_ENABLE) : ?>
        <label>
            <?= _('Raum') ?>
            <select name="resource_id">
                <option value="nothing"><?= _('<em>Keinen</em> Raum buchen') ?></option>
                <? if ($resList->numberOfRooms()) : ?>
                    <? foreach ($resList->getRooms() as $room_id => $room) : ?>
                        <option value="<?= $room_id ?>"
                            <?= Request::option('room') == $room_id ? 'selected' : '' ?>>
                            <?= htmlReady($room->getName()) ?>
                            <? if ($room->getSeats() > 1) : ?>
                                <?= sprintf(_('(%d Sitzplätze)'), $room->getSeats()) ?>
                            <? endif ?>
                        </option>
                    <? endforeach ?>
                <? endif ?>
            </select>
        </label>
    <? endif ?>

    <label>
        <?= _('Freie Ortsangabe') ?>
        <input type="text"
               name="freeRoomText"
               maxlength="255">
        <? if (Config::get()->RESOURCES_ENABLE) : ?>
            <small style="display: block"><?= _('(führt <em>nicht</em> zu einer Raumbuchung)') ?></small>
        <? endif ?>
    </label>

    <div class="durchfuehrende_dozenten">

    </div>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Erstellen")) ?>
    </div>
</form>