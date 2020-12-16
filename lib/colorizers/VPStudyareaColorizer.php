<?php

class VPStudyareaColorizer implements VPColorizer
{

    public function getFilterIndexes()
    {
        return [
            'semtree_id' => _("Farben nach Studienbereichen")
        ];
    }

    public function getColor($index, $termin)
    {
        if (is_a($termin, "CourseDate")) {
            if (count($termin->course->study_areas) > 1) {
                return "#000000";
            } elseif(count($termin->course->study_areas) === 1) {
                return Veranstaltungsplanung::stringToColorCode($termin->course->study_areas[0]->getId());
            } else {
                return "0";
            }

        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
