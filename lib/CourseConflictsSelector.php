<?php

class CourseConflictsSelector extends SidebarWidget
{

    public function __construct()
    {
        $this->layout = "sidebar/widget-layout.php";
        $this->title = _("Überschneidungsfreiheit");
    }

    public function render($variables = [])
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views/');
        $this->template = $factory->open('filterconflicts/widget.php');

        if ($this->layout) {
            $layout = $GLOBALS['template_factory']->open($this->layout);
            $layout->base_class = "sidebar";
            $layout->title = $this->title;
            $layout->layout_css_classes = (array) $this->layout_css_classes;
            $layout->additional_attributes = [];
            $this->template->set_layout($layout);
        }
        return $this->template->render();
    }
}
