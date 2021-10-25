<? foreach ($themen as $thema) : ?>
    <?
    $selected = false;
    foreach ($date->topics as $t) {
        if ($t->title === $thema->title) {
            $selected = true;
            break;
        }
    }
    ?>
    <option value="<?= htmlReady($thema->getId()) ?>"<?= $selected ? ' selected' : '' ?>><?= htmlReady($thema['title']) ?></option>
<? endforeach ?>
