<?php

class SeatSelector extends SidebarWidget
{

    public function __construct()
    {
        $this->layout = "sidebar/widget-layout.php";
        $this->title = _("Sitzplätze");
    }

    public function render($variables = [])
    {
        $factory  = new Flexi_TemplateFactory(__DIR__ . '/../views/');
        $this->template = $factory->open('seatselector/widget.php');

        $seats = explode(",", $GLOBALS['user']->cfg->ADMIN_RESOURCES_SEATS);
        $this->template->min_seats = $seats[0];
        $this->template->max_seats = $seats[1];

        $statement = DBManager::get()->prepare("
            SELECT MAX(resource_properties.state)
            FROM resource_properties
                INNER JOIN resource_property_definitions ON (resource_property_definitions.property_id = resource_properties.property_id)
            WHERE resource_property_definitions.name = 'seats'
            GROUP BY resource_property_definitions.property_id
        ");
        $statement->execute();
        $this->template->max_seats_of_all = $statement->fetch(PDO::FETCH_COLUMN, 0) ?: 10;

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
