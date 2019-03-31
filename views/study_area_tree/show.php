<div class="up">
    <? if ($parent && $parent->sem_tree_id && ($parent->sem_tree_id !== 'root')): ?>
        <a href="#" data-id="<?= htmlReady($parent->parent_id) ?>">
            <?= Icon::create('arr_1left')->asImg(['class' => 'text-bottom']) ?>
            <?= htmlReady($parent->getName()) ?>
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
    <? foreach ($study_areas as $study_area): ?>
        <li data-id="<?= $study_area->id ?>">
            <? if (count($study_area->_children) > 0): ?>
                <a href="#" class="navigator">
                    <?= Icon::create('arr_1right')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($study_area->getName()) ?>
                </a>
            <? else: ?>
                <span>
                    <?= Icon::create('arr_1right', 'info')->asImg(['class' => 'text-bottom']) ?>
                    <?= htmlReady($study_area->getName()) ?>
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
            <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom", 'onClick' => "STUDIP.StudyareaTree.remove.call(this);", 'title' => _("Studienbereich abwählen"))) ?>
        </div>
        <?= Icon::create("checkbox-checked", "info")->asImg(20, array('class' => "text-bottom")) ?>
        <input type="hidden" name="sem_tree_id[]" value="">
        <span class="name"></span>
    </li>
    <? foreach ($selected as $study_area) : ?>
        <li class="<?= $study_area->id ?>">
            <div style="float: right; cursor: pointer;">
                <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom", 'onClick' => "STUDIP.StudyareaTree.remove.call(this);", 'title' => _("Studienbereich abwählen"))) ?>
            </div>
            <?= Icon::create("checkbox-checked", "info")->asImg(20, array('class' => "text-bottom")) ?>
            <input type="hidden" name="sem_tree_id[]" value="<?= htmlReady($study_area->id) ?>">
            <span class="name"><?= htmlReady($study_area->getName()) ?></span>
        </li>
    <? endforeach ?>
</ul>
<input type="hidden" name="study_area_ids" value="<?= implode(",", array_map(function ($studyarea) { return $studyarea->id; }, $selected)) ?>">