<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "planer/create_date") ?>"
      data-dialog>

    <input type="hidden" name="start" value="<?= htmlReady($start) ?>">
    <input type="hidden" name="end" value="<?= htmlReady($end) ?>">

    <label>
        <?= _("Kontext auswählen") ?>
        <? if (Request::get("object_type") === "courses") : ?>
            <select name="course_id" required onChange="STUDIP.Veranstaltungsplanung.getDozenten.call(this);">
                <option value=""> - </option>
                <? foreach ($courses as $course) : ?>
                    <option value="<?= htmlReady($course->getId()) ?>">
                        <?= htmlReady($course->getFullName()) ?>
                    </option>
                <? endforeach ?>
            </select>
        <? elseif(Request::get("object_type") === "persons") : ?>
            <select name="user_id" required>
                <option value=""> - </option>
                <? foreach ($persons as $user) : ?>
                    <option value="<?= htmlReady($user->getId()) ?>">
                        <?= htmlReady($user->getFullName()) ?>
                    </option>
                <? endforeach ?>
            </select>
        <? else : ?>
            <select name="resource_id" required>
                <option value=""> - </option>
                <? foreach ($resources as $resource_data) : ?>
                    <option value="<?= htmlReady($resource_data['resource_id']) ?>">
                        <?= htmlReady($resource_data['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        <? endif ?>
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