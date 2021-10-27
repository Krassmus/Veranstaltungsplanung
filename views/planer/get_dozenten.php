<?
$teacher_ids = [];
foreach ($date->dozenten as $user) {
    $teacher_ids[] = $user->getId();
}
?>
<label>
    <?= _("Durchführende Lehrende") ?>
    <div>
        <select multiple class="durchfuehrende_dozenten_select" name="durchfuehrende_dozenten[]">
            <? foreach ($dozenten as $dozent) : ?>
            <option value="<?= htmlReady($dozent['user_id']) ?>"<?= in_array($dozent['user_id'], $teacher_ids) ? ' selected' : '' ?>>
                <?= htmlReady($dozent['title_front']." ".$dozent['vorname']." ".$dozent['nachname']) ?>
            </option>
            <? endforeach ?>
        </select>
    </div>
</label>
