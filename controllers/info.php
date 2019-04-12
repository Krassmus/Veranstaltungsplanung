<?php

class InfoController extends PluginController
{
    public function date_action($date_id) {
        list($type, $termin_id) = explode("_", $date_id, 2);
        $this->termin = CourseDate::find($termin_id);
        if (!$this->termin || !$GLOBALS['perm']->have_studip_perm("user", $this->termin['range_id'])) {
            throw new AccessDeniedException();
        }
        PageLayout::setTitle($this->termin->course['name'].": ".date("d.m.Y H.i", $this->termin['date']));
        $this->render_text($termin_id);
    }
}