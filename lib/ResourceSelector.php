<?php

class ResourceSelector extends SidebarWidget
{

    public function __construct()
    {
        $this->layout = "sidebar/widget-layout.php";
        $this->title = _("Ressourcenbaum");
    }

    public function render($variables = [])
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views/');
        $this->template = $factory->open('resource_tree/widget.php');

        $selected_resources = Resource::findMany(explode(",", $GLOBALS['user']->cfg->ADMIN_RESOURCES));

        if (count($selected_resources) === 1) {
            $parent = Resource::find($selected_resources[0]->parent_id);
            $resources = Resource::findBySQL(
                "parent_id = ? ORDER BY name",
                [$parent ? $parent->id : '0']
            );
        } else {
            $resources = Location::findAll();
            $parent = null;
        }


        $this->template->resources  = $resources;
        $this->template->parent      = $parent;
        $this->template->selected    = $selected_resources;

        if ($this->layout) {
            $layout = $GLOBALS['template_factory']->open($this->layout);
            $layout->base_class = "sidebar";
            $layout->title = $this->title;
            $layout->layout_css_classes = $this->layout_css_classes;
            $layout->additional_attributes = [];
            $this->template->set_layout($layout);
        }
        return $this->template->render();
    }
}
