<div class="up">
    <? if ($parent && $parent->id && ($parent->id != '0')): ?>
        <a href="#" data-id="<?= htmlReady($parent->parent_id) ?>">
            <?= Icon::create('arr_1left')->asImg(['class' => 'text-bottom']) ?>
            <?= htmlReady($parent['name']) ?>
        </a>
    <? else: ?>
        <span>
            <?= Icon::create('home', 'info')->asImg(['class' => 'text-bottom']) ?>
            <?= _('System') ?>
        </span>
    <? endif; ?>
    <label class="unsetter" title="<?= _('Keinen Studienbereich auswählen') ?>">
        <input type="radio" name="resource_id" value=""
            <? if (!$selected) echo 'checked'; ?>>

        <!--
        <?= Icon::create('decline')->asImg(['class' => 'text-bottom']) ?>
        <?= Icon::create('decline', 'info')->asImg(['class' => 'text-bottom']) ?>
        -->
    </label>
</div>

<ol class="children">
    <? foreach ($resources as $resource): ?>
        <li data-id="<?= $resource->id ?>">
            <? if (count($resource->children) > 0): ?>
                <a href="#" class="navigator">
                    <?= Icon::create('arr_1right')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($resource['name']) ?>
                </a>
            <? else: ?>
                <span>
                    <?= Icon::create('arr_1right', 'info')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($resource['name']) ?>
                </span>
            <? endif; ?>
            <a href="#"
               class="selector">
                <?= Icon::create("arr_1down", "clickable")->asImg(['class' => "text-bottom", 'title' => _("Studienbereich auswählen")]) ?>
            </a>
        </li>
    <? endforeach; ?>
</ol>

<ul class="clean selected">
    <li style="display: none;" class="template">
        <div style="float: right; cursor: pointer;">
            <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom remove_tree_object", 'title' => _("Studienbereich abwählen"))) ?>
        </div>
        <?= Icon::create("checkbox-checked", "info")->asImg(20, array('class' => "text-bottom")) ?>
        <input type="hidden" name="resource_id[]" value="">
        <span class="name"></span>
    </li>
    <? foreach ($selected as $resource) : ?>
        <li data-id="<?= $resource->id ?>">
            <div style="float: right; cursor: pointer;">
                <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom remove_tree_object", 'title' => _("Objekt abwählen"))) ?>
            </div>
            <?= Icon::create("checkbox-checked", "info")->asImg(20, array('class' => "text-bottom")) ?>
            <input type="hidden" name="sem_tree_id[]" value="<?= htmlReady($resource->id) ?>">
            <span class="name"><?= htmlReady($resource['name']) ?></span>
        </li>
    <? endforeach ?>
</ul>
<input type="hidden"
       class="ids"
       name="resource_ids"
       value="<?= implode(",", array_map(function ($r) { return $r->id; }, $selected)) ?>">
