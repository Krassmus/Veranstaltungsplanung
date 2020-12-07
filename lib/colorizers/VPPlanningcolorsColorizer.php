<?php

class VPPlanningcolorsColorizer implements VPColorizer
{

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Planungsfarben für regelmäßige Termine");
    }

    public function getColor($termin)
    {
        if (is_a($termin, "CourseDate")) {
            if ($termin->metadate_id) {
                $event_colors = InstituteCalendarHelper::getCourseEventcolors($termin->course);
                $color = $event_colors[$termin->metadate_id][$GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT];
                return $color ?: "#ffffff";
            } else {
                return "0";
            }

        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
