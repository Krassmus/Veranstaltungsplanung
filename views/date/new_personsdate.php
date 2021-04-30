<form action="<?= PluginEngine::getLink($plugin, [], "date/edit") ?>"
      class="default"
      data-dialog
      method="get">

    <input type="hidden"
           name="data[date]"
           value="<?= date("d.m.Y H:i", $date['date']) ?>">
    <input type="hidden"
           name="data[end_time]"
           value="<?= date("d.m.Y H:i", $date['end_time']) ?>">
    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">

    <label>
        <?= _("Person auswählen") ?>
        <select name="for_user_id"
                required>
            <? foreach ($persons as $user) : ?>
                <? if (!$GLOBALS['perm']->have_perm("admin", $user->getId()) && $GLOBALS['perm']->have_perm("dozent", $user->getId())) : ?>
                    <option value="<?= htmlReady($user->getId()) ?>">
                        <?= htmlReady($user->getFullName()) ?>
                    </option>
                <? endif ?>
            <? endforeach ?>
        </select>
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Auswählen")) ?>
    </div>
</form>
