<?php

interface VPColorizer
{

    /**
     * returns an associative array of indexes and names of the filters like
     * return ['test' => "Meine Testfarben"] . The index of the array will be
     * used in the getColor-method.
     * @return array
     */
    public function getFilterIndexes();

    /**
     * @param String $index
     * @param CourseDate|EventData $termin
     * @return string : color of the date.
     */
    public function getColor($index, $termin);
}
