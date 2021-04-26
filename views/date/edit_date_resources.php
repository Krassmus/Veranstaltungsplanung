<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "date/save") ?>"
      data-dialog>

    <input type="hidden" name="start" value="<?= htmlReady($start) ?>">
    <input type="hidden" name="end" value="<?= htmlReady($end) ?>">

    <label>
        <?= _("Ressource auswählen") ?>
        <select name="resource_id" required>
            <option value=""> - </option>
            <? foreach ($resources as $resource_data) : ?>
                <option value="<?= htmlReady($resource_data['resource_id']) ?>">
                    <?= htmlReady($resource_data['name']) ?>
                </option>
            <? endforeach ?>
        </select>
    </label>

    <div class="jquery_tabs">
        <ul>
            <li>
                <a href="#add_course">
                    <?= _("Veranstaltung") ?>
                </a>
            </li>
            <li>
                <a href="#add_person">
                    <?= _("Person") ?>
                </a>
            </li>
            <li>
                <a href="#add_free">
                    <?= _("Freie Buchung") ?>
                </a>
            </li>
        </ul>
        <div id="add_course">
            <label>
                <?= _("Veranstaltung auswählen") ?>
                <input type="text">
            </label>
        </div>
        <div id="add_person">
            <label>
                <?= _("Person eintragen") ?>
                <input type="text">
            </label>
        </div>
        <div id="add_free">
            <label>
                <?= _("Freie Eintragung") ?>
                <input type="text">
            </label>
        </div>
    </div>

    <script>
        jQuery(function () {
            jQuery(".jquery_tabs").tabs();
        });
    </script>





    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>
