<label><?= _('Modul auswählen') ?></label>

<form>
    <div id="study_area_tree"
         class="courses object_tree"
         data-url="plugins.php/veranstaltungsplanung/module/show/">
        <?= $this->render_partial('module/show.php') ?>
    </div>
</form>
