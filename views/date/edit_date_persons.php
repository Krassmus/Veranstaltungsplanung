<form class="default edit_date_persons"
      method="post"
      action="<?= PluginEngine::getLink($plugin, [], "date/save/".($date->getId() ? "termine_".$date->getId() : "")) ?>"
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
        <?= _("Person auswählen") ?>
        <select name="user_ids[]"
                required
                <?= !$editable ? "readonly" : "" ?>
                multiple
                class="select2">
            <? foreach ($persons as $user) : ?>
                <? if (!$GLOBALS['perm']->have_perm("admin", $user->getId()) && $GLOBALS['perm']->have_perm("dozent", $user->getId())) : ?>
                    <option value="<?= htmlReady($user->getId()) ?>"<?= (!$date->isNew() && in_array($user->getId(), $selected_persons) ? " selected" : "") ?>>
                        <?= htmlReady($user->getFullName()) ?>
                    </option>
                <? endif ?>
            <? endforeach ?>
        </select>
    </label>
    <script>
        jQuery(function () {
            jQuery(".edit_date_persons .select2").select2({
                "closeOnSelect": false,
                "width": 'resolve'
            });
        });
    </script>

    <label class="select_course">
        <?= $this->render_partial("date/_select_course") ?>
    </label>


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
