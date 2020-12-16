<?php

class VPPlanningcolorsColorizer implements VPColorizer
{

    public function getFilterIndexes()
    {
        return [
            'semesterplan' => _("Planungsfarben für regelmäßige Termine")
        ];
    }

    public function getColor($index, $termin)
    {
        if (is_a($termin, "CourseDate")) {
            if ($termin->metadate_id) {
                $event_colors = InstituteCalendarHelper::getCourseEventcolors($termin->course);
                $color = $event_colors[$termin->metadate_id][$GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT];


                $min_time = explode(':', Config::get()->INSTITUTE_COURSE_PLAN_START_HOUR);
                $minbigtime = (int) $min_time[0];
                $max_time = explode(':', Config::get()->INSTITUTE_COURSE_PLAN_END_HOUR);
                $maxbigtime = (int) $max_time[0];

                $start_time = explode(':', $termin->cycle['start_time']);
                $bigtime = (int) $start_time[0];
                if (!($bigtime > $maxbigtime || $bigtime < $minbigtime) && ($bigtime % 2)) {
                    $bigtime--;
                }
                $start_time = $bigtime . ':00:00';

                $end_time = explode(':', $start_time);
                $end_time[0] += 2;
                $end_time = implode(':', $end_time);

                if ($start_time != $termin->cycle['start_time'] && $end_time != $termin->cycle['end_time']) {
                    //diese Termine passen nicht in das vorgegebene 2-Stunden-Raster und werden daher grau dargestellt:
                    $color = '#6c737a';
                }
                if (!$color) {
                    $semtype = $termin->course->getSemType();
                    $institut = Institute::find($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT);
                    $inst_default_colors = InstituteCalendarHelper::getInstituteDefaultEventcolors($institut);
                    $color = array_key_exists($semtype['name'], $inst_default_colors)
                        ? $inst_default_colors[$semtype['name']]
                        : InstituteCalendarHelper::DEFAULT_EVENT_COLOR;
                }
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
