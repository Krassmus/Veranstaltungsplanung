<?
$statusgruppen_ids = $date->statusgruppen->pluck('statusgruppe_id');
?>
<label>
    <?= _("Teilnehmergruppen") ?>
    <div>
        <select multiple class="statusgruppen_select" name="statusgruppen[]">
            <? foreach ($statusgruppen as $statusgruppe) : ?>
                <option value="<?= htmlReady($statusgruppe->getId()) ?>"<?= in_array($statusgruppe->getId(), $statusgruppen_ids) ? ' selected' : '' ?>>
                    <?= htmlReady($statusgruppe['name']) ?>
                </option>
            <? endforeach ?>
        </select>
    </div>
</label>
