<form action="<?= PluginEngine::getLink($plugin, [], "date/edit") ?>"
      class="default"
      data-dialog
      method="get">

    <input type="hidden"
           name="data[date]"
           value="<?= date("d.m.Y H:i", $date['date']) ?>">
    <input type="hidden"
           name="data[end_time]"
           value="<?= date("d.m.Y H:i", $date['end_time']) ?>">
    <input type="hidden" name="object_type" value="<?= htmlReady(Request::get("object_type")) ?>">

    <label>
        <?= _("Ressource auswählen") ?>
        <select name="for_resource_id"
                required>
            <? foreach ($resources as $resource) : ?>
                <option value="<?= htmlReady($resource->getId()) ?>">
                    <?= htmlReady($resource['name']) ?>
                </option>
            <? endforeach ?>
        </select>
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Auswählen")) ?>
    </div>
</form>
