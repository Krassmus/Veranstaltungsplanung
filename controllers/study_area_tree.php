<?php

class StudyAreaTreeController extends PluginController
{
    public function show_action($parent_id = null)
    {
        $this->selected    = null;
        $this->parent      = StudipStudyArea::find($parent_id);
        $this->study_areas = StudipStudyArea::findBySQL(
            "parent_id = ? ORDER BY priority",
            [$this->parent ? $this->parent->id : 'root']
        );
    }
}
