<form action="<?= PluginEngine::getLink($plugin, [], "planer/settings") ?>"
      method="post"
      class="default">

    <fieldset>
        <legend>
            <?= _("Generelle Ansicht") ?>
        </legend>

        <label>
            <?= _("Orientierungslinie (x Uhr)") ?>
            <input type="number"
                   min="0"
                   max="23"
                   name="line"
                   value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE ?: "12") ?>">
        </label>

        <label>
            <?= _("Orientierungslinie 2 (x Uhr)") ?>
            <input type="number"
                   min="0"
                   max="23"
                   name="line2"
                   value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE2 ?: "12") ?>">
        </label>

        <div class="hgroup">
            <label>
                <?= _("Beginn des Tages") ?>
                <input type="time"
                       name="mintime"
                       value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_MINTIME ?: "00:00") ?>">
            </label>

            <label>
                <?= _("Ende des Tages") ?>
                <input type="time"
                       name="maxtime"
                       value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_MAXTIME ?: "23:59") ?>">
            </label>
        </div>

        <label>
            <?= _("Tage verstecken") ?>
            <div>
                <? $days = $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_HIDDENDAYS ? json_decode($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_HIDDENDAYS, true) : [] ?>
                <select class="multiselect" multiple name="hidden_days[]" data-multiple>
                    <option value="1"<?= in_array(1, $days) ? " selected" : "" ?>><?= _("Montag") ?></option>
                    <option value="2"<?= in_array(2, $days) ? " selected" : "" ?>><?= _("Dienstag") ?></option>
                    <option value="3"<?= in_array(3, $days) ? " selected" : "" ?>><?= _("Mittwoch") ?></option>
                    <option value="4"<?= in_array(4, $days) ? " selected" : "" ?>><?= _("Donnerstag") ?></option>
                    <option value="5"<?= in_array(5, $days) ? " selected" : "" ?>><?= _("Freitag") ?></option>
                    <option value="6"<?= in_array(6, $days) ? " selected" : "" ?>><?= _("Samstag") ?></option>
                    <option value="0"<?= in_array(0, $days) ? " selected" : "" ?>><?= _("Sonntag") ?></option>
                </select>
            </div>
        </label>
        <script>
            jQuery(function () {
                jQuery("select[multiple].multiselect").select2();
            });
        </script>

        <label>
            <input type="checkbox"
                   name="always_ask"
                   <?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_ALWAYS_ASK ? 'checked' : '' ?>
                   value="1">
            <?= _("Immer fragen, bevor ein Termin verschoben wird.") ?>
        </label>
    </fieldset>

    <? $filter_names = $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DISABLED_FILTER ? json_decode($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DISABLED_FILTER, true) : [] ?>
    <? foreach (['courses' => _("Veranstaltungen"), 'persons' => _("Personen"), 'resources' => _("Ressourcen")] as $type => $title) : ?>
        <fieldset>
            <legend>
                <?= sprintf(_("Filter für %s"), $title) ?>
            </legend>

            <? foreach ((array) $filters[$type] as $filter) : ?>
                <? foreach ($filter->getNames() as $index => $name) : ?>
                    <label>
                        <input type="checkbox"
                               name="filter[]"
                               value="<?= htmlReady($index) ?>"<?= !in_array($index, $filter_names) ? "checked" : "" ?>>
                        <?= htmlReady($name) ?>
                    </label>
                <? endforeach ?>
            <? endforeach ?>
        </fieldset>
    <? endforeach ?>

    <fieldset>
        <legend>
            <?= _("Terminfarben") ?>
        </legend>

        <? foreach (['courses' => _("Farben für Veranstaltungstermine"), 'persons' => _("Farben für Persontermine"), 'resources' => _("Farben für Ressourcenbuchungen")] as $type => $title) : ?>
        <label>
            <?= $title ?>
            <select name="colorizer[<?= htmlReady($type) ?>]">
                <? foreach ((array) Veranstaltungsplanung::getColorizers() as $colorizer) : ?>
                    <? foreach ($colorizer->getFilterIndexes() as $index => $name) : ?>
                        <option value="<?= htmlReady(get_class($colorizer)."__".$index) ?>"<?= $GLOBALS['user']->cfg->getValue("VERANSTALTUNGSPLANUNG_COLORIZE_" . strtoupper($type)) === get_class($colorizer)."__".$index ? " selected" : "" ?>>
                            <?= htmlReady($name) ?>
                        </option>
                    <? endforeach ?>
                <? endforeach ?>
            </select>
        </label>
        <? endforeach ?>
    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>
