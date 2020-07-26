<form class="default">
    <input type="hidden" name="seats" value="">

    <label class="min">
        <span style="display: inline-block; width: 40px;"><?= _("Min") ?></span>
        <input type="range"
               min="0"
               max="<?= htmlReady($max_seats_of_all) ?>"
               step="5"
               value="<?= htmlReady($min_seats ?: 0) ?>"
               onchange="$(this).closest('form').find('[name=seats]').val($(this).closest('form').find('.max input').val() + this.value > 0 ? this.value + ',' + $(this).closest('form').find('.max input').val() : ''); $(this).closest('form').find('[name=seats]').trigger('change');"
               oninput="$(this).closest('label').find('.seats').text(this.value);">
        <span class="seats"><?= htmlReady($min_seats ?: 0) ?></span>
    </label>

    <label class="max">
        <span style="display: inline-block; width: 40px;"><?= _("Max") ?></span>
        <input type="range"
               min="0"
               max="<?= htmlReady($max_seats_of_all) ?>"
               step="5"
               value="<?= htmlReady($max_seats ?: 0) ?>"
               onchange="$(this).closest('form').find('[name=seats]').val($(this).closest('form').find('.min input').val() + this.value > 0 ? $(this).closest('form').find('.min input').val() + ',' + this.value : ''); $(this).closest('form').find('[name=seats]').trigger('change');"
               oninput="$(this).closest('label').find('.seats').text(this.value);">
        <span class="seats"><?= htmlReady($max_seats ?: 0) ?></span>
    </label>

    <a href="#" onclick="$(this).closest('form').find('input').val('0'); $(this).closest('form').find('input').trigger('change'); $(this).closest('form').find('.seats').text(0); return false;">
        <?= _("Filter zurücksetzen") ?>
    </a>
</form>
