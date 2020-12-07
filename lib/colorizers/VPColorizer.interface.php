<?php

interface VPColorizer
{
    /**
     * Name of the colorizer displayed in the configuration window.
     * @return string
     */
    public function getName();

    /**
     * @param CourseDate|EventData $termin
     * @return string : color of the date.
     */
    public function getColor($termin);
}
