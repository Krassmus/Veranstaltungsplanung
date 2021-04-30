<?php

class InfoController extends PluginController
{
    public function date_action($date_id) {
        list($type, $termin_id) = explode("_", $date_id, 2);
        if ($type === "$type") {
            $this->termin = CourseDate::find($termin_id);
            if (!$this->termin || !$GLOBALS['perm']->have_studip_perm("user", $this->termin['range_id'])) {
                throw new AccessDeniedException();
            }
            $this->redirect(URLHelper::getURL("dispatch.php/course/timesrooms/editDate/".$termin_id, ['cid' => $this->termin['range_id']]));

            /*PageLayout::setTitle($this->termin->course['name'] . ": " . date("d.m.Y H.i", $this->termin['date']));
            $this->seminar = new Seminar($this->termin['range_id']);
            $this->dozenten = $this->seminar->getMembers("dozent");
            $this->render_template("info/coursedate");*/
        } else {

        }
    }
}
