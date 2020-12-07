<?php

class VPStudyareaColorizer implements VPColorizer
{

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName()
    {
        return _("Farben nach Studienbereichen");
    }

    public function getColor($termin)
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
