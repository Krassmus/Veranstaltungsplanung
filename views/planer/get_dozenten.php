<label>
    <?= _("Durchführende Lehrende") ?>
    <select multiple name="durchfuehrende_dozenten[]">
        <? foreach ($dozenten as $dozent) : ?>
        <option value="<?= htmlReady($dozent['user_id']) ?>">
            <?= htmlReady($dozent['title_front']." ".$dozent['vorname']." ".$dozent['nachname']) ?>
        </option>
        <? endforeach ?>
    </select>
</label>