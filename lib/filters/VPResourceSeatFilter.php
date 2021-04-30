<?php

class VPResourceSeatFilter implements VPFilter
{
    static public function context()
    {
        return "resources";
    }

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getNames()
    {
        return ["seats" => _("Sitzplätze")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $resource_tree = new SeatSelector();
        $resource_tree->addLayoutCSSClass("resources");
        return $resource_tree;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $seats = explode(",", Request::get("seats"));
        if (($seats[1] > 0) && ($seats[1] < $seats[0])) {
            $min = $seats[1];
            $seats[1] = $seats[0];
            $seats[0] = $min;
        }
        $GLOBALS['user']->cfg->store('ADMIN_RESOURCES_SEATS', implode(",", $seats));
        if ($seats[0] || $seats[1]) {
            $statement = DBManager::get()->prepare("
                SELECT property_id
                FROM resource_property_definitions
                WHERE name = 'seats'
            ");
            $statement->execute();
            $property_id = $statement->fetch(PDO::FETCH_COLUMN, 0);
            if ($property_id) {
                $query->join(
                    "resource_properties",
                    "resource_properties",
                    "`resource_properties`.`resource_id` = `resources`.`id` AND `resource_properties`.`property_id` = '" . $property_id . "'"
                );
                if ($seats[0]) {
                    $query->where("resource_properties_seats_min", "(CAST(`resource_properties`.`state` AS UNSIGNED) >= :minseats)", [
                        'minseats' => $seats[0]
                    ]);
                }
                if ($seats[1]) {
                    $query->where("resource_properties_seats_max", "(CAST(`resource_properties`.`state` AS UNSIGNED) <= :maxseats)", [
                        'maxseats' => $seats[1]
                    ]);
                }
            }
        }
    }
}
