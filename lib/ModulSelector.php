<?php

class ModulSelector extends SidebarWidget
{

    public function __construct()
    {
        $this->layout = "sidebar/widget-layout.php";
        $this->title = _("Modul");
    }

    public function render($variables = [])
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views/');
        $this->template = $factory->open('module/widget.php');
        $areas = [];

        $selected_areas = explode(",", $GLOBALS['user']->cfg->ADMIN_COURSES_MODULE);

        $studiengaenge = Studiengang::findBySQL("`stat` = 'genehmigt' ORDER BY name ASC");

        foreach ($studiengaenge as $studiengang) {
            $areas[] = [
                'id' => "studiengang_".$studiengang->id,
                'name' => $studiengang['name'],
                'children' => count($studiengang->studiengangteile)
            ];
        }
        $this->template->areas       = $areas;
        $this->template->parent      = null;
        $this->template->selected    = array_filter(array_map(function ($selected_id) {
            list($type, $id) = explode("_", $selected_id);
            switch ($type) {
                case "studiengang":
                    $studiengang = Studiengang::find($id);
                    if ($studiengang) {
                        return [
                            'id' => $selected_id,
                            'name' => $studiengang['name']
                        ];
                    }
                    break;
            }
        }, $selected_areas));

        if ($this->layout) {
            $layout = $GLOBALS['template_factory']->open($this->layout);
            $layout->base_class = "sidebar";
            $layout->title = $this->title;
            $layout->layout_css_classes = $this->layout_css_classes;
            $this->template->set_layout($layout);
        }
        return $this->template->render();
    }
}
