<?php

require_once __DIR__."/lib/SQLQuery.class.php";
require_once __DIR__."/lib/filters/VPFilter.interface.php";

require_once __DIR__."/lib/filters/VPCourseSearchFilter.php";
require_once __DIR__."/lib/filters/VPSemesterFilter.php";
require_once __DIR__."/lib/filters/VPCourseInstituteFilter.php";
require_once __DIR__."/lib/filters/VPCourseStudyareaFilter.php";
require_once __DIR__."/lib/filters/VPCourseVisibilityFilter.php";

require_once __DIR__."/lib/filters/VPUserInstituteFilter.php";
require_once __DIR__."/lib/filters/VPUserRoleFilter.php";

require_once __DIR__."/lib/filters/VPResourceTreeFilter.php";
require_once __DIR__."/lib/filters/VPResourceSeatFilter.php";

class Veranstaltungsplanung extends StudIPPlugin implements SystemPlugin
{

    static public function getFilters()
    {
        $vpfilters = array();
        foreach (get_declared_classes() as $class) {
            if (in_array('VPFilter', class_implements($class))) {
                $vpfilters[$class::context()][] = new $class();
            }
        }
        return $vpfilters;
    }

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
        parent::perform($unconsumed_path);
    }
}
