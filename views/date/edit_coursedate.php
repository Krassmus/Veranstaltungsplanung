<form class="default vplaner_edit_date"
      method="post"
      action="<?= PluginEngine::getLink($plugin, [], "date/save/".$date->getId()) ?>"
      data-date_id="<?= htmlReady($date->getId()) ?>"
      data-dialog>

    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">

    <fieldset>
        <legend><?= _('Organisation') ?></legend>

        <label>
            <?= $this->render_partial("date/_select_course") ?>
        </label>

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

        <? if ($in_semester && !Config::get()->VPLANER_DISABLE_METADATES) : ?>
            <label>
                <? if ($editable) : ?>
                    <input type="checkbox"
                           <?= ($editable ? "" : "readonly") ?>
                           name="metadate"
                           onchange="$('#vplaner_editdate').toggleClass('metadate').toggleClass('singledate')"
                           value="1"<?= $date['metadate_id'] ? " checked" : "" ?>>
                <? else : ?>
                    <?= Icon::create("checkbox-" .($date['metadate_id'] ? "" : "un"). "checked", "info")->asImg(20, ['class' => "text-bottom"]) ?>
                <? endif ?>
                <?= _("Regelmäßiger Termin") ?>
            </label>
        <? elseif (!Config::get()->VPLANER_DISABLE_METADATES) : ?>
            <input type="hidden" name="metadate" value="<?= $date['metadate_id'] ? 1 : 0 ?>">
        <? endif ?>

    </fieldset>
    <fieldset id="vplaner_editdate" class="<?= $date['metadate_id'] ? 'metadate' : 'singledate' ?>">
        <legend><?= _('Termin(e) bearbeiten') ?></legend>

        <table class="multi_edit_table">
            <thead>
                <tr>
                    <th width="50%"><?= _('Eigenschaft des Einzeltermins') ?></th>
                    <th width="50%"><?= _('Eigenschaft aller Termine') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <label>
                            <?= _("Termintyp") ?>
                            <? if ($editable) : ?>
                                <select id="vplaner_date_type"
                                        name="data[date_typ]"
                                        onchange="$('#vplaner_date_type_cycledate').val('')"
                                        <?= ($editable ? "" : "readonly") ?>>

                                    <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                                        <option <?= ($date['date_typ'] == $key || ($date->isNew() && $GLOBALS['user']->cfg->VPLANER_DEFAULT_DATETYPE == $key)) ? 'selected' : '' ?>
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
                    </td>
                    <td>
                        <label>
                            <?= _("Termintyp aller Termine") ?>
                            <?
                            $difference = false;
                            if ($date->cycle->dates) {
                                foreach ((array)$date->cycle->dates->toArray() as $otherdate) {
                                    if ($otherdate['date_typ'] !== $date['date_typ']) {
                                        $difference = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <select name="date_typ_cycledate"
                                    id="vplaner_date_type_cycledate"
                                    onchange="if ($(this).val()) {$('#vplaner_date_type').val($(this).val()); }"
                                    <?= ($editable ? "" : "readonly") ?>>
                                <option value=""><?= $difference ? _('Unterschiedliche Werte') : '' ?></option>
                                <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                                    <option <?= !$difference && ($key == $date['date_typ'] || ($date->isNew() && $GLOBALS['user']->cfg->VPLANER_DEFAULT_DATETYPE == $key)) ? 'selected' : '' ?>
                                        value="<?= $key ?>"><?= htmlReady($val['name']) ?></option>
                                <? endforeach ?>
                            </select>
                        </label>
                    </td>
                </tr>
                <? if (Config::get()->RESOURCES_ENABLE
                    && ($selectable_rooms || $room_search)): ?>
                <tr>
                    <td>
                        <label>
                            <?= _('Raum') ?>
                            <? if ($room_search): ?>
                                <?= $room_search->render() ?>
                            <? else: ?>
                                <?
                                $begin = new DateTime();
                                $begin->setTimestamp($date['date']);
                                $end = new DateTime();
                                $end->setTimestamp($date['end_time']);
                                ?>
                                <select name="resource_id"
                                        onchange="$('#vplaner_resource_id_cycledate').val('')"
                                        id="vplaner_resource_id"
                                    <?= ($editable ? "" : "readonly") ?> style="width: calc(100% - 23px);">
                                    <option value="no"><?= _('<em>Keinen</em> Raum buchen') ?></option>
                                    <? foreach ($selectable_rooms as $room): ?>
                                        <option value="<?= htmlReady($room->id) ?>"
                                                <?= $date->room_booking && ($date->room_booking['resource_id'] === $room->id) ? " selected" : "" ?>
                                                <?= $room->isAssigned($begin, $end, [$date->room_booking ? $date->room_booking->id : 'no']) ? 'disabled' : ''?>>
                                            <?= htmlReady($room->name) ?>
                                            <? if ($room->seats > 1) : ?>
                                                <?= sprintf(_('(%d Sitzplätze)'), $room->seats) ?>
                                            <? endif ?>
                                        </option>
                                    <? endforeach ?>
                                </select>
                            <? endif ?>
                        </label>
                    </td>
                    <td>
                        <label>
                            <?
                            $difference = false;
                            if ($date->cycle->dates) {
                                foreach ((array)$date->cycle->dates->toArray() as $otherdate) {
                                    $otherdate = CourseDate::buildExisting($otherdate);
                                    if ($otherdate->room_booking['resource_id'] !== $date->room_booking['resource_id']) {
                                        $difference = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?= _('Raum aller Termine') ?>
                            <? if ($room_search): ?>
                                <?= $room_search->render() ?>
                            <? else: ?>
                                <select name="resource_id_cycledate" <?= ($editable ? "" : "readonly") ?>
                                        id="vplaner_resource_id_cycledate"
                                        onchange="if ($(this).val()) {$('#vplaner_resource_id').val($(this).val()); }"
                                        style="width: calc(100% - 23px);">
                                    <option value=""><?= $difference ? _('Unterschiedliche Werte') : '' ?></option>
                                    <option value="no"><?= _('<em>Keinen</em> Raum buchen') ?></option>
                                    <? foreach ($selectable_rooms as $room): ?>
                                        <option value="<?= htmlReady($room->id) ?>"
                                                <?= !$difference && $date->room_booking && ($date->room_booking['resource_id'] === $room->id) ? " selected" : "" ?>
                                            <?= $room->isAssigned($begin, $end, [$date->room_booking ? $date->room_booking->id : 'no']) ? 'disabled' : ''?>>
                                            <?= htmlReady($room->name) ?>
                                            <? if ($room->seats > 1) : ?>
                                                <?= sprintf(_('(%d Sitzplätze)'), $room->seats) ?>
                                            <? endif ?>
                                        </option>
                                    <? endforeach ?>
                                </select>
                            <? endif ?>
                        </label>
                    </td>
                </tr>
                <? endif ?>
                <tr>
                    <td>
                        <label>
                            <?= _('Freie Ortsangabe') ?>
                            <? if (Config::get()->RESOURCES_ENABLE) : ?>
                                (<?= _('keine Raumbuchung') ?>)
                            <? endif ?>
                            <input type="text"
                                   onchange="if (this.value !== $('#vplaner_raum_cycledate').val()) {$('#vplaner_raum_cycledate').val('Unterschiedliche Werte');}"
                                   id="vplaner_raum"
                                   name="data[raum]"
                                <?= ($editable ? "" : "readonly") ?>
                                   value="<?= htmlReady($date['raum']) ?>"
                                   maxlength="255">

                        </label>
                    </td>
                    <td>
                        <label>
                            <?
                            $difference = false;
                            if ($date->cycle->dates) {
                                foreach ((array) $date->cycle->dates->toArray() as $otherdate) {
                                    $otherdate = CourseDate::buildExisting($otherdate);
                                    if ($date['raum'] !== $otherdate['raum']) {
                                        $difference = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?= _('Freie Ortsangabe aller Termine') ?>
                            <? if (Config::get()->RESOURCES_ENABLE) : ?>
                                (<?= _('keine Raumbuchung') ?>)
                            <? endif ?>
                            <input type="text"
                                   id="vplaner_raum_cycledate"
                                   name="raum_cycledate"
                                   onchange="if (this.value !== 'Unterschiedliche Werte') { $('#vplaner_raum').val(this.value); }"
                                <?= ($editable ? "" : "readonly") ?>
                                   value="<?= htmlReady($difference ? 'Unterschiedliche Werte' : $date['raum']) ?>"
                                   maxlength="255">
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="durchfuehrende_dozenten">
                            <? if ($date->course) : ?>
                                <?
                                $dozenten = $date->course->members->filter(function ($m) {
                                    return $m['status'] === "dozent";
                                });
                                $teacher_ids = [];
                                foreach ($date->dozenten as $user) {
                                    $teacher_ids[] = $user->getId();
                                }
                                ?>
                                <label>
                                    <?= _("Durchführende Lehrende") ?>
                                    <div>
                                        <select
                                            multiple
                                            id="vplaner_dozenten"
                                            onchange="if ($('#vplaner_dozenten_cycledate').val().join() != $(this).val().join()) { $('#vplaner_dozenten_cycledate').val(['diff']).trigger('change'); }"
                                            class="durchfuehrende_dozenten_select"
                                            name="durchfuehrende_dozenten[]">
                                            <? foreach ($dozenten as $dozent) : ?>
                                                <option value="<?= htmlReady($dozent['user_id']) ?>"<?= in_array($dozent['user_id'], $teacher_ids) ? ' selected' : '' ?>>
                                                    <?= htmlReady($dozent['title_front']." ".$dozent['vorname']." ".$dozent['nachname']) ?>
                                                </option>
                                            <? endforeach ?>
                                        </select>
                                    </div>
                                </label>
                            <? endif ?>
                        </div>
                    </td>
                    <td>
                        <div class="durchfuehrende_dozenten">
                            <? if ($date->course) : ?>
                            <label>
                                <?= _("Durchführende Lehrende aller Termine") ?>
                                <?
                                $dozenten = $date->course->members->filter(function ($m) {
                                    return $m['status'] === "dozent";
                                });
                                $difference = false;
                                $teacher_ids_hash = null;
                                if ($date->cycle->dates) {
                                    foreach ((array) $date->cycle->dates->toArray() as $otherdate) {
                                        $otherdate = CourseDate::buildExisting($otherdate);
                                        $teacher_ids = $otherdate->dozenten->pluck('user_id');
                                        sort($teacher_ids);
                                        $teacher_ids = implode("_", $teacher_ids);
                                        if ($teacher_ids_hash === null) {
                                            $teacher_ids_hash = $teacher_ids;
                                        }
                                        if ($teacher_ids_hash !== $teacher_ids) {
                                            $difference = true;
                                            break;
                                        }
                                    }
                                }
                                $teacher_ids = [];
                                foreach ($date->dozenten as $user) {
                                    $teacher_ids[] = $user->getId();
                                }
                                ?>
                                <div>
                                    <select multiple
                                            id="vplaner_dozenten_cycledate"
                                            class="durchfuehrende_dozenten_select"
                                            onchange="if ($(this).val().indexOf('diff') == -1) { $('#vplaner_dozenten').val($(this).val()).trigger('change'); }"
                                            name="durchfuehrende_dozenten_cycledate[]">
                                        <option value="diff"<?= $difference ? ' selected' : '' ?>><?= $difference ? _('Unterschiedliche Werte') : _('Keine Änderungen') ?></option>
                                        <? foreach ($dozenten as $dozent) : ?>
                                            <option value="<?= htmlReady($dozent['user_id']) ?>"<?= !$difference && in_array($dozent['user_id'], $teacher_ids) ? ' selected' : '' ?>>
                                                <?= htmlReady($dozent['title_front']." ".$dozent['vorname']." ".$dozent['nachname']) ?>
                                            </option>
                                        <? endforeach ?>
                                    </select>
                                </div>
                            </label>
                            <? endif ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="statusgruppen">
                            <? if ($date->course) : ?>
                                <label>
                                    <?= _("Teilnehmergruppen") ?>
                                    <div>
                                        <?
                                        $statusgruppen_ids = $date->statusgruppen->pluck('statusgruppe_id');
                                        ?>
                                        <select multiple
                                                class="statusgruppen_select"
                                                id="vplaner_statusgruppen"
                                                onchange="if ($('#vplaner_statusgruppen_cycledate').val().join() != $(this).val().join()) { $('#vplaner_statusgruppen_cycledate').val(['diff']).trigger('change'); }"
                                                name="statusgruppen[]">
                                            <? foreach ($date->course->statusgruppen as $statusgruppe) : ?>
                                                <option value="<?= htmlReady($statusgruppe->getId()) ?>"<?= in_array($statusgruppe->getId(), $statusgruppen_ids) ? ' selected' : '' ?>>
                                                    <?= htmlReady($statusgruppe['name']) ?>
                                                </option>
                                            <? endforeach ?>
                                        </select>
                                    </div>
                                </label>
                            <? endif ?>
                        </div>
                    </td>
                    <td>
                        <div class="statusgruppen">
                            <? if ($date->course) : ?>
                                <?
                                $difference = false;
                                $statusgruppen_ids_hash = null;
                                if ($date->cycle->dates) {
                                    foreach ((array) $date->cycle->dates->toArray() as $otherdate) {
                                        $otherdate = CourseDate::buildExisting($otherdate);
                                        $statusgruppen_ids = $otherdate->statusgruppen->pluck('statusgruppe_id');
                                        sort($statusgruppen_ids);
                                        $statusgruppen_ids = implode("_", $statusgruppen_ids);
                                        if ($statusgruppen_ids_hash === null) {
                                            $statusgruppen_ids_hash = $statusgruppen_ids;
                                        }
                                        if ($statusgruppen_ids_hash !== $statusgruppen_ids) {
                                            $difference = true;
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <label>
                                    <?= _("Teilnehmergruppen aller Termine") ?>
                                    <div>
                                        <?
                                        $statusgruppen_ids = $date->statusgruppen->pluck('statusgruppe_id');
                                        ?>
                                        <select multiple
                                                class="statusgruppen_select"
                                                id="vplaner_statusgruppen_cycledate"
                                                onchange="if ($(this).val().indexOf('diff') == -1) { $('#vplaner_statusgruppen').val($(this).val()).trigger('change'); }"
                                                name="statusgruppen_cycledate[]">
                                            <option value="diff"<?= $difference ? ' selected' : '' ?>><?= $difference ? _('Unterschiedliche Werte') : _('Keine Änderungen') ?></option>
                                            <? foreach ($date->course->statusgruppen as $statusgruppe) : ?>
                                                <option value="<?= htmlReady($statusgruppe->getId()) ?>"<?= !$difference && in_array($statusgruppe->getId(), $statusgruppen_ids) ? ' selected' : '' ?>>
                                                    <?= htmlReady($statusgruppe['name']) ?>
                                                </option>
                                            <? endforeach ?>
                                        </select>
                                    </div>
                                </label>
                            <? endif ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <?= _('Themen') ?>
                            <div>
                                <select name="topics[]" multiple class="relevante_themen">
                                    <? if ($date->course) : ?>
                                        <?= $this->render_partial('planer/get_themen', ['date' => $date, 'themen' => $date->course->topics]) ?>
                                    <? endif ?>
                                </select>
                            </div>
                        </label>

                        <div>
                            <input type="text"
                                   id="add_topic"
                                   placeholder="<?= _('neues Thema eingeben ...') ?>"
                                   style="max-width: 200px;"
                                   onkeydown="if (event.key == 'Enter') { STUDIP.Veranstaltungsplanung.addThema.call(this); return false; }">
                            <?= \Studip\LinkButton::create(_('Thema hinzufügen'), '#', ['onclick' => "STUDIP.Veranstaltungsplanung.addThema.call(window.document.getElementById('add_topic'));"]) ?>
                        </div>
                    </td>
                    <td>

                    </td>
                </tr>
            </tbody>
        </table>

    </fieldset>

    <script>
        $(function () {
            $('.durchfuehrende_dozenten_select, .statusgruppen_select, .relevante_themen').select2();
        });
    </script>

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
