<?php

require_once __DIR__."/lib/SQLQuery.class.php";
require_once __DIR__."/lib/filters/VPFilter.interface.php";

require_once __DIR__."/lib/filters/VPCourseSearchFilter.php";
require_once __DIR__."/lib/filters/VPSemesterFilter.php";
require_once __DIR__."/lib/filters/VPCourseInstituteFilter.php";
require_once __DIR__."/lib/filters/VPCourseStudyareaFilter.php";
require_once __DIR__."/lib/filters/VPCourseModulFilter.php";
require_once __DIR__."/lib/filters/VPCourseVisibilityFilter.php";
require_once __DIR__."/lib/filters/VPCourseDatafieldsFilter.php";

require_once __DIR__."/lib/filters/VPUserUserFilter.php";
require_once __DIR__."/lib/filters/VPUserInstituteFilter.php";
require_once __DIR__."/lib/filters/VPUserRoleFilter.php";
require_once __DIR__."/lib/filters/VPUserDatafieldsFilter.php";

require_once __DIR__."/lib/filters/VPResourceTreeFilter.php";
require_once __DIR__."/lib/filters/VPResourceSeatFilter.php";

require_once __DIR__."/lib/colorizers/VPColorizer.interface.php";
require_once __DIR__."/lib/colorizers/VPStandardColorizer.php";
require_once __DIR__."/lib/colorizers/VPInstituteColorizer.php";
require_once __DIR__."/lib/colorizers/VPStudyareaColorizer.php";
require_once __DIR__."/lib/colorizers/VPPlanningcolorsColorizer.php";
require_once __DIR__."/lib/colorizers/VPDatafieldColorizer.php";

class Veranstaltungsplanung extends StudIPPlugin implements SystemPlugin
{

    static public function getFilters()
    {
        $vpfilters = [];
        foreach (get_declared_classes() as $class) {
            if (in_array('VPFilter', class_implements($class))) {
                $vpfilters[$class::context()][] = new $class();
            }
        }
        return $vpfilters;
    }

    static public function getColorizers()
    {
        $vpcolorizers = [];
        foreach (get_declared_classes() as $class) {
            if (in_array('VPColorizer', class_implements($class))) {
                $vpcolorizers[$class] = new $class();
            }
        }
        return $vpcolorizers;
    }

    static public function isReadOnly()
    {
        $status = Config::get()->VPLANER_READONLY;
        if (!$status) {
            return false;
        }
        if ($status === "autor") {
            return !$GLOBALS['perm']->have_perm("tutor");
        }
        if ($status === "tutor") {
            return !$GLOBALS['perm']->have_perm("dozent");
        }
        if ($status === "dozent") {
            return !$GLOBALS['perm']->have_perm("admin");
        }
        if ($status === "admin") {
            return !$GLOBALS['perm']->have_perm("root");
        }
        return false;
    }

    static public function stringToColorCode($str)
    {
        $code = dechex(crc32($str));
        $code = substr($code, 0, 6);
        return "#".$code;
    }

    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("dozent")) {
            $nav = new Navigation(
                _("Planung"),
                PluginEngine::getURL($this, [], "planer/index")
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
