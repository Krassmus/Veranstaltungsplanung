<script>
    if (typeof STUDIP.Veranstaltungsplanung === "undefined") {
        STUDIP.Veranstaltungsplanung = {};
    }
    STUDIP.Veranstaltungsplanung.hidden_days = <?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_HIDDENDAYS ?: array() ?>;
</script>

<div id="calendar"
     data-default_date="<?= date("r", $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DEFAULTDATE) ?>"></div>

<? foreach ($filters as $name => $filter) : ?>
    <input type="hidden" class="date_fetch_params" id="<?= htmlReady($name) ?>" value="<?= htmlReady($filter['value']) ?>">
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
    false),
    'select-courses'
);
$select->addElement(new SelectElement(
    "teachers",
    _("Lehrende"),
    false),
    'select-teacher'
);
$select->addElement(new SelectElement(
    "resources",
    _("Ressourcen"),
    false),
    'select-resources'
);
Sidebar::Get()->addWidget($select);

foreach ($filters as $filter) {
    Sidebar::Get()->addWidget($filter['widget']);
}

$actions = new ActionsWidget();
$actions->addLink(
    _("Planer konfigurieren"),
    PluginEngine::getURL($plugin, array(), "planer/settings"),
    Icon::create("admin", "clickable"),
    array('data-dialog' => 1)
);
Sidebar::Get()->addWidget($actions);