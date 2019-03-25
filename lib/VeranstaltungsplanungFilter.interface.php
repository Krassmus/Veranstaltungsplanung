<?php

interface VeranstaltungsplanungFilter
{
    public function getVeranstaltungsplanungFilter();

    public function applyVeranstaltungsplanungFilter(
        \Veranstaltungsplanung\SQLQuery $query,
        $filter_id,
        $value,
        $object_type
    );
}