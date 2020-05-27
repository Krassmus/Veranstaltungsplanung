<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "date/edit") ?>"
      data-dialog>

    <input type="hidden" name="start" value="<?= htmlReady($start) ?>">
    <input type="hidden" name="end" value="<?= htmlReady($end) ?>">

    <label>
        <?= _("Person auswählen") ?>
        <select name="user_id" required>
            <option value=""> - </option>
            <? foreach ($persons as $user) : ?>
                <option value="<?= htmlReady($user->getId()) ?>">
                    <?= htmlReady($user->getFullName()) ?>
                </option>
            <? endforeach ?>
        </select>
    </label>



    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>
