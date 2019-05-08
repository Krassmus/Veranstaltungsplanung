<form class="default" method="post" action="<?= PluginEngine::getLink($plugin, array(), "planer/create_date") ?>" data-dialog>

    <input type="hidden" name="start" value="<?= htmlReady($start) ?>">
    <input type="hidden" name="end" value="<?= htmlReady($end) ?>">

    <label>
        <?= _("Kontext auswählen") ?>
        <select name="course_id" required>
            <option value=""> - </option>
            <? foreach ($courses as $course) : ?>
                <option value="<?= htmlReady($course->getId()) ?>">
                    <?= htmlReady($course->getFullName()) ?>
                </option>
            <? endforeach ?>
        </select>
    </label>
</form>