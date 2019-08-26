<label><?= _('Ressource auswählen') ?></label>

<form>
    <div id="resource_tree"
         class="courses object_tree"
         data-url="plugins.php/veranstaltungsplanung/resource_tree/show/">
        <?= $this->render_partial('resource_tree/show.php') ?>
    </div>
</form>