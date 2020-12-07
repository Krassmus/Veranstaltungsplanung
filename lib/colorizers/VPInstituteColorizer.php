<?php

class VPInstituteColorizer implements VPColorizer
{

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Farben nach Einrichtungen");
    }

    public function getColor($termin)
    {
        if (is_a($termin, "CourseDate")) {
            return Veranstaltungsplanung::stringToColorCode($termin->course->institut_id);
        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
