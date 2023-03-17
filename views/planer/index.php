<script>
    if (typeof STUDIP.Veranstaltungsplanung === "undefined") {
        STUDIP.Veranstaltungsplanung = {};
    }
    STUDIP.Veranstaltungsplanung.hidden_days = <?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_HIDDENDAYS ?: "[]" ?>;
    STUDIP.Veranstaltungsplanung.defaultView = '<?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DEFAULTVIEW ?: "timeGridWeek" ?>';
    STUDIP.Veranstaltungsplanung.timegrid = '<?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_TIMEGRID ?: '5' ?>';
    <? if ($print) : ?>
    window.setTimeout(function () {
        window.print();
    }, 1000);
    <? endif ?>
</script>

<div id="calendar"
     data-default_date="<?= date("r", $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DEFAULTDATE ?: time()) ?>"></div>

<input type="hidden" id="mintime" value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_MINTIME ?: "00:00") ?>:00">
<input type="hidden" id="maxtime" value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_MAXTIME ?: "24:00") ?>:00">
<input type="hidden" id="editable" value="<?= Veranstaltungsplanung::isReadOnly() ? "0" : "1" ?>">
<input type="hidden" id="always_ask" value="<?= $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_ALWAYS_ASK ? "1" : "0" ?>">

<input type="hidden" class="date_fetch_params" id="object_type" value="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_OBJECT_TYPE) ?>">
<? foreach ($vpfilters as $object_type => $filterset) : ?>
    <? foreach ($filterset as $filter) : ?>
        <? foreach ($filter->getNames() as $index => $name) : ?>
        <input type="hidden"
               data-object_type="<?= htmlReady($object_type) ?>"
               class="date_fetch_params"
               id="<?= htmlReady($index) ?>"
               value="">
        <? endforeach ?>
    <? endforeach ?>
<? endforeach ?>

<style>
    #calendar tr[data-time="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE ? ($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE < 10 ? "0" : "").$GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE : "12") ?>:00:00"] > td {
        border-top: 1px solid #d60000;
    }
    #calendar tr[data-time="<?= htmlReady($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE2 ? ($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE2 < 10 ? "0" : "").$GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_LINE2 : "12") ?>:00:00"] > td {
        border-top: 1px solid #d60000;
    }
    #calendar .ui-datepicker-trigger {
        opacity: 0;
        max-width: 0px;
        max-height: 0px;
        margin: 0px;
        padding: 0px;
        border: 0px;
    }
</style>

<? if ($print) : ?>
    <input type="hidden" id="print" value="1">
<? endif ?>

<? if ($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_CONTEXTMENU) : ?>
    <input type="hidden" id="context_menu" value="1">
<? endif ?>

<?

$select = new SelectWidget(
    _("Objekt-Typ"),
    PluginEngine::getURL($this->plugin, [], "planer/change_type"),
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

$disabled_filters = json_decode($GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_DISABLED_FILTER, true) ?: [];

foreach ($vpfilters as $object_type => $filterset) {
    foreach ($filterset as $filter) {
        foreach ($filter->getNames() as $index => $filtername) {
            if (!in_array($index, $disabled_filters)) {
                $widget = $filter->getSidebarWidget($index);
                if ($widget) {
                    Sidebar::Get()->addWidget($widget, $index);
                }
            }
        }
    }
}

$actions = new ActionsWidget();
$actions->addLink(
    _("Planer konfigurieren"),
    PluginEngine::getURL($plugin, [], "planer/settings"),
    Icon::create("admin", "clickable"),
    ['data-dialog' => 1]
);
$actions->addLink(
    _("Drucken"),
    PluginEngine::getURL($plugin, [], "planer/print"),
    Icon::create("print", "clickable"),
    ['target' => "_blank"]
);
Sidebar::Get()->addWidget($actions);
