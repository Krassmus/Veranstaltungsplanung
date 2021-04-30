<? if ($editable && count($courses) > 0) : ?>
    <div>
        <?= _("Veranstaltung auswählen") ?>
    </div>
    <select name="data[range_id]"
            required
        <?= ($editable ? "" : "readonly") ?>
            style="display: inline-block;"
            onChange="STUDIP.Veranstaltungsplanung.getDozenten.call(this); $(this).closest('label').find('.planer_course_link').attr('href', this.value ? STUDIP.URLHelper.getURL($(this).closest('label').find('.planer_course_link').data('base-url'), {'cid': this.value}) : '');">
        <option value=""> - </option>
        <? foreach ($courses as $course) : ?>
            <option value="<?= htmlReady($course->getId()) ?>"<?= $course->getId() === $date['range_id'] ? " selected" : "" ?>>
                <?= htmlReady($course->getFullName()) ?>
            </option>
        <? endforeach ?>
    </select>
<? elseif(count($courses) > 0) : ?>
    <div>
        <?= _("Veranstaltung") ?>
    </div>
    <input type="text" readonly value="<?
    foreach ($courses as $course) {
        if ($course->getId() === $date['range_id']) {
            echo htmlReady($course->getFullName());
            break;
        }
    }
    ?>">
<? else : ?>
    <div>
        <?= _("Veranstaltung") ?>
    </div>
    <input type="text"
           readonly
           value="<?= htmlReady(Course::find($date['range_id'])->getFullName()) ?>">
<? endif ?>

<a href="<?= $date['range_id'] ? URLHelper::getLink("dispatch.php/course/timesrooms", ['cid' => $date['range_id']]) : "" ?>"
   data-base-url="dispatch.php/course/timesrooms"
   target="_blank"
   title="<?= _("Zur Veranstaltung springen") ?>"
   class="planer_course_link">
    <?= Icon::create("seminar+move_right", "clickable")->asImg(25, ['class' => "text.bottom"]) ?>
</a>
