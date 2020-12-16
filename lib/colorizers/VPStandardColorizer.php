<?php

class VPStandardColorizer implements VPColorizer
{

    public function getFilterIndexes()
    {
        return [
            'standard' => _("Standardfarben")
        ];
    }

    public function getColor($index, $termin)
    {
        if (is_a($termin, "CourseDate")) {
            return $termin['metadate_id'] ? "1" : "0";
        }
        if (is_a($termin, "EventData")) {
            return "2";
        }
    }
}
