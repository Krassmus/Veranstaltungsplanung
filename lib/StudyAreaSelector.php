<?php

class StudyAreaSelector extends SidebarWidget
{

    public function __construct()
    {
        $this->layout = "sidebar/widget-layout.php";
        $this->title = _("Studienbereiche");
    }

    public function render($variables = [])
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views/');
        $this->template = $factory->open('study_area_tree/widget.php');

        $selected_areas = StudipStudyArea::findMany(explode(",", $GLOBALS['user']->cfg->ADMIN_COURSES_STUDYAREAS));

        if (count($selected_areas) === 1) {
            $parent = StudipStudyArea::find($selected_areas[0]->parent_id);
        } else {
            $parent = StudipStudyArea::find('root');
        }
        $study_areas = StudipStudyArea::findBySQL(
            "parent_id = ? ORDER BY priority",
            [$parent ? $parent->id : 'root']
        );

        $this->template->study_areas = $study_areas;
        $this->template->parent      = $parent;
        $this->template->selected    = $selected_areas;

        //$this->addTemplateElement($template);

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
