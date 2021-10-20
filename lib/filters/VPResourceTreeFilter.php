<?php

class VPResourceTreeFilter implements VPFilter
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
        return ["resource_ids" => _("Ressourcenbaum")];
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget($index)
    {
        $resource_tree = new ResourceSelector();
        $resource_tree->addLayoutCSSClass("resources");
        return $resource_tree;
    }

    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_RESOURCES', Request::get("resource_ids"));
        $resource_ids = explode(",", Request::get("resource_ids"));
        if (Request::get("resource_ids") && $resource_ids && count($resource_ids)) {
            //possibly add all sub-items
            $query->join(
                "resources2",
                "resources",
                "`resources2`.`id` = `resources`.`parent_id`"
            );
            /*$query->join(
                "resources3",
                "resources",
                "`resources3`.`id` = `resources2`.`parent_id`"
            );
            $query->where("resource_ids", "(`resources`.`id` IN (:resource_ids) OR `resources2`.`id` IN (:resource_ids) OR `resources3`.`id` IN (:resource_ids))", [
                'resource_ids' => $resource_ids
            ]);*/
        }
    }
}
