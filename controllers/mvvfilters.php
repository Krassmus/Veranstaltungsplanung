<?php

class MvvfiltersController extends PluginController
{
    public function get_mvv_stgteil_action()
    {
        $filter = new VPCourseStgTeilFilter();
        $widget = $filter->getSidebarWidget();
        if ($widget) {
            $this->render_text($widget->render());
        } else {
            $this->render_text("error");
        }
    }

    public function get_mvv_modul_action()
    {
        $filter = new VPCourseModulFilter();
        $widget = $filter->getSidebarWidget();
        if ($widget) {
            $this->render_text($widget->render());
        } else {
            $this->render_text("error");
        }
    }
}
