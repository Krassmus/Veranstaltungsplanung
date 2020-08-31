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

    public function get_mvv_modulteil_action()
    {
        $filter = new VPCourseModulteilFilter();
        $widget = $filter->getSidebarWidget();
        if ($widget) {
            $this->render_text($widget->render());
        } else {
            $this->render_text("error");
        }
    }
}
