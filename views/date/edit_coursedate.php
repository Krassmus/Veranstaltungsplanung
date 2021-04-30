<form class="default vplaner_edit_date"
      method="post"
      action="<?= PluginEngine::getLink($plugin, [], "date/save/".$date->getId()) ?>"
      data-dialog>

    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">

    <div class="hgroup">
        <label>
            <?= _("Beginn") ?>
            <input type="text"
                   <?= ($editable ? "data-datetime-picker" : "readonly") ?>
                   name="data[date]"
                   value="<?= date("d.m.Y H:i", $date['date']) ?>">
        </label>
        <label>
            <?= _("Ende") ?>
            <input type="text"
                   <?= ($editable ? "data-datetime-picker" : "readonly") ?>
                   name="data[end_time]"
                   value="<?= date("d.m.Y H:i", $date['end_time']) ?>">
        </label>
    </div>

    <label>
        <?= $this->render_partial("date/_select_course") ?>
    </label>

    <label>
        <?= _("Art") ?>
        <? if ($editable) : ?>
            <select name="data[date_typ]" <?= ($editable ? "" : "readonly") ?>>
                <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                    <option <?= $date['date_typ'] == $key ? 'selected' : '' ?>
                            value="<?= $key ?>"><?= htmlReady($val['name']) ?></option>
                <? endforeach ?>
            </select>
        <? else : ?>
            <input type="text" readonly value="<?
            foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) {
                if ($date['date_typ'] == $key) {
                    echo htmlReady($val['name']);
                    break;
                }
            }
            ?>">
        <? endif ?>
    </label>

    <? if ($in_semester && !Config::get()->VPLANER_DISABLE_METADATES) : ?>
        <label>
            <? if ($editable) : ?>
                <input type="checkbox" <?= ($editable ? "" : "readonly") ?> name="metadate" value="1"<?= $date['metadate_id'] ? " checked" : "" ?>>
            <? else : ?>
                <?= Icon::create("checkbox-" .($date['metadate_id'] ? "" : "un"). "checked", "info")->asImg(20, ['class' => "text-bottom"]) ?>
            <? endif ?>
            <?= _("Regelmäßiger Termin") ?>
        </label>
    <? elseif (!Config::get()->VPLANER_DISABLE_METADATES) : ?>
        <input type="hidden" name="metadate" value="<?= $date['metadate_id'] ? 1 : 0 ?>">
    <? endif ?>

    <? if (Config::get()->RESOURCES_ENABLE
        && ($selectable_rooms || $room_search)): ?>
        <label>
            <?= _('Raum') ?>
            <? if ($room_search): ?>
                <?= $room_search->render() ?>
            <? else: ?>
                <select name="resource_id" <?= ($editable ? "" : "readonly") ?> style="width: calc(100% - 23px);">
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
               <?= ($editable ? "" : "readonly") ?>
               value="<?= htmlReady($date['raum']) ?>"
               maxlength="255">
        <? if (Config::get()->RESOURCES_ENABLE) : ?>
            <small style="display: block"><?= _('(führt <em>nicht</em> zu einer Raumbuchung)') ?></small>
        <? endif ?>
    </label>

    <div class="durchfuehrende_dozenten">

    </div>

    <div data-dialog-button>
        <? if ($editable) : ?>
            <?= \Studip\Button::create(_("Speichern")) ?>
            <? if (!$date->isNew()) : ?>
                <? if ($date->cycle) : ?>
                    <?= \Studip\Button::create(_("Ausfallen lassen"), "ex_date", ['data-confirm' => _("Wirklich diesen Termin ausfallen lassen?")]) ?>
                <? endif ?>
                <?= \Studip\Button::create(_("Löschen"), "delete_date", ['data-confirm' => $date->cycle ? _("Wirklich diesen Termin und alle Wiederholungen löschen?") : _("Wirklich diesen Termin löschen?")]) ?>

            <? endif ?>
        <? endif ?>
    </div>
</form>
