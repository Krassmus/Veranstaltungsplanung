<?php

class VPInstituteColorizer implements VPColorizer
{

    public function getFilterIndexes()
    {
        return [
            'institut_id' => _("Farben nach Einrichtungen")
        ];
    }

    public function getColor($index, $termin)
    {
        if (is_a($termin, "CourseDate")) {
            return Veranstaltungsplanung::stringToColorCode($termin->course->institut_id);
        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
