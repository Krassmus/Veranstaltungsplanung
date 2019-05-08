<form class="default">
    <fieldset>
        <label>
            <?= _("Zugewiesene Lehrende") ?>
            <? $dozenten_ids = $termin->dozenten->pluck("user_id") ?>
            <select multiple name="dozenten">
                <? foreach ($dozenten as $dozent) : ?>
                <option value="<?= htmlReady($dozent['user_id']) ?>"<?= in_array($dozent['user_id'], $dozenten_ids) ? " selected" : "" ?>>
                    <?= htmlReady($dozent['fullname']) ?>
                </option>
                <? endforeach ?>
            </select>
        </label>
    </fieldset>
</form>