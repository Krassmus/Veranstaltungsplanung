<?php

class ResourceTreeController extends PluginController
{
    public function show_action($parent_id = null)
    {
        $this->selected    = null;
        $this->parent      = VPResource::find($parent_id);
        $this->resources   = VPResource::findBySQL(
            "parent_id = ? ORDER BY name",
            [$this->parent ? $this->parent->id : '0']
        );
    }
}
