<form class="filterconflicts">
    <label>
        <input type="checkbox"
               name="conflicts"
               onChange="$('#course_conflicts').val(this.checked ? '1' : '');"
               value="1"<?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_CONFLICTS ? 'checked="checked"' : '' ?>>
        <?= Icon::create('checkbox-checked', Icon::ROLE_CLICKABLE)->asImg(20, ['class' => 'text-bottom']) ?>
        <?= Icon::create('checkbox-unchecked', Icon::ROLE_CLICKABLE)->asImg(20, ['class' => 'text-bottom']) ?>
        <?= _('Nur Termine mit Überschneidungen') ?>
    </label>
</form>
