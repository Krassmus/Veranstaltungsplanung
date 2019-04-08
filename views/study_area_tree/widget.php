<label><?= _('Studienbereich auswählen') ?></label>

<form>
    <div id="study_area_tree" class="courses">
        <?= $this->render_partial('study_area_tree/show.php') ?>
    </div>
</form>