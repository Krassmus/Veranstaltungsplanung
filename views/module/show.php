<div class="up">
    <? if ($parent && $parent['id'] && ($parent['id'] !== 'root')): ?>
        <a href="#" data-id="<?= htmlReady($parent['parent_id']) ?>">
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
        <input type="radio" name="sem_tree_id" value=""
            <? if (!$selected) echo 'checked'; ?>>

        <!--
        <?= Icon::create('decline')->asImg(['class' => 'text-bottom']) ?>
        <?= Icon::create('decline', 'info')->asImg(['class' => 'text-bottom']) ?>
        -->
    </label>
</div>

<ol class="children">
    <? foreach ($areas as $area): ?>
        <li data-id="<?= htmlReady($area['id']) ?>">
            <? if ($area['children'] > 0): ?>
                <a href="#" class="navigator">
                    <?= Icon::create('arr_1right')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($area['name']) ?>
                </a>
            <? else: ?>
                <span>
                    <?= Icon::create('arr_1right', 'info')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($area['name']) ?>
                </span>
            <? endif; ?>
            <a href="#"
               class="selector">
                <?= Icon::create("arr_1down", "clickable")->asImg(['class' => "text-bottom", 'title' => _("Bereich auswählen")]) ?>
            </a>
        </li>
    <? endforeach; ?>
</ol>

<ul class="clean selected">
    <li style="display: none;" class="template">
        <div style="float: right; cursor: pointer;">
            <?= Icon::create("trash", "clickable")->asImg(20, ['class' => "text-bottom remove_tree_object", 'title' => _("Modul abwählen")]) ?>
        </div>
        <?= Icon::create("checkbox-checked", "info")->asImg(20, ['class' => "text-bottom"]) ?>
        <input type="hidden" name="modul_ids[]" value="">
        <span class="name"></span>
    </li>
    <? foreach ($selected as $area) : ?>
        <li class="<?= htmlReady($area['id']) ?>" data-id="<?= htmlReady($area['id']) ?>">
            <div style="float: right; cursor: pointer;">
                <?= Icon::create("trash", "clickable")->asImg(20, ['class' => "text-bottom remove_tree_object", 'title' => _("Modul abwählen")]) ?>
            </div>
            <?= Icon::create("checkbox-checked", "info")->asImg(20, ['class' => "text-bottom"]) ?>
            <input type="hidden" name="modul_ids[]" value="<?= htmlReady($area['id']) ?>">
            <span class="name"><?= htmlReady($area['name']) ?></span>
        </li>
    <? endforeach ?>
</ul>
<input type="hidden"
       class="ids"
       name="module_ids"
       value="<?= implode(",", array_map(function ($s) { return $s['id']; }, $selected)) ?>">
