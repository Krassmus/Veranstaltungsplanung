<?php

require_once __DIR__."/lib/SQLQuery.class.php";
require_once __DIR__."/lib/VeranstaltungsplanungFilter.interface.php";

class Veranstaltungsplanung extends StudIPPlugin implements SystemPlugin
{
    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("admin")) {
            $nav = new Navigation(
                _("Planung"),
                PluginEngine::getURL($this, array(), "planer/index")
            );
            Navigation::addItem("/browse/va_planer", $nav);
        }
    }

    public function perform($unconsumed_path)
    {
        $this->addStylesheet("assets/veranstaltungsplanung.less");
        $this->addStylesheet("assets/study-area-tree.less");
        parent::perform($unconsumed_path); // TODO: Change the autogenerated stub
    }
}