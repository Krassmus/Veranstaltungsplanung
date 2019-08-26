<label><?= _('Studienbereich auswählen') ?></label>

<form>
    <div id="study_area_tree"
         class="courses object_tree"
         data-url="plugins.php/veranstaltungsplanung/study_area_tree/show/">
        <?= $this->render_partial('study_area_tree/show.php') ?>
    </div>
</form>