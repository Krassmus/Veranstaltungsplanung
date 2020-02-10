<script>
    if (typeof STUDIP.Veranstaltungsplanung === "undefined") {
        STUDIP.Veranstaltungsplanung = {};
    }
    STUDIP.Veranstaltungsplanung.hidden_days = <?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_HIDDENDAYS ?: "[]" ?>;
</script>

<div id="calendar"
     data-default_date="<?= date("r", $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DEFAULTDATE ?: time()) ?>"></div>

<input type="hidden" class="date_fetch_params" id="object_type" value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_OBJECT_TYPE) ?>">
<? foreach ($vpfilters as $object_type => $filterset) : ?>
    <? foreach ($filterset as $filter) : ?>
        <input type="hidden"
               data-object_type="<?= htmlReady($object_type) ?>"
               class="date_fetch_params"
               id="<?= htmlReady($filter->getParameterName()) ?>"
               value="">
    <? endforeach ?>
<? endforeach ?>

<style>
    #calendar tr[data-time="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE ?: "12") ?>:00:00"] > td {
        border-top: 1px solid #d60000;
    }
</style>

<?

$select = new SelectWidget(
    _("Objekt-Typ"),
    PluginEngine::getURL($this->plugin, array(), "planer/change_type"),
    "object_type"
);
$select->class = "change_type";
$select->addElement(new SelectElement(
    "courses",
    _("Veranstaltungen"),
    $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_OBJECT_TYPE === "courses"),
    'select-courses'
);
$select->addElement(new SelectElement(
    "persons",
    _("Personen"),
    $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_OBJECT_TYPE === "persons"),
    'select-teacher'
);
$select->addElement(new SelectElement(
    "resources",
    _("Ressourcen"),
    $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_OBJECT_TYPE === "resources"),
    'select-resources'
);
Sidebar::Get()->addWidget($select);

$disabled_filters = json_decode($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DISABLED_FILTER, true) ?: array();

foreach ($vpfilters as $object_type => $filterset) {
    foreach ($filterset as $filter) {
        if (!in_array(get_class($filter), $disabled_filters)) {
            Sidebar::Get()->addWidget($filter->getSidebarWidget(), get_class($filter));
        }
    }
}

$actions = new ActionsWidget();
$actions->addLink(
    _("Planer konfigurieren"),
    PluginEngine::getURL($plugin, array(), "planer/settings"),
    Icon::create("admin", "clickable"),
    array('data-dialog' => 1)
);
Sidebar::Get()->addWidget($actions);
