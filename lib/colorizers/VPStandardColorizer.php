<?php

class VPStandardColorizer implements VPColorizer
{

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Standardfarben");
    }

    public function getColor($termin)
    {
        if (is_a($termin, "CourseDate")) {
            return $termin['metadate_id'] ? "1" : "0";
        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
