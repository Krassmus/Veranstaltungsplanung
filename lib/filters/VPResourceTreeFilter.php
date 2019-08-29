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
    public function getName()
    {
        return _("Ressourcenbaum");
    }

    /**
     * Name of the Parameter in URL and in widget.
     * @return string "course_search"
     */
    public function getParameterName()
    {
        return "resource_ids";
    }

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget()
    {
        $resource_tree = new ResourceSelector();
        $resource_tree->addLayoutCSSClass("resources");
        return $resource_tree;
    }

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query)
    {
        $GLOBALS['user']->cfg->store('ADMIN_RESOURCES', Request::get("resource_ids"));
        $resource_ids = explode(",", Request::get("resource_ids"));
        if (Request::get("resource_ids") && $resource_ids && count($resource_ids)) {
            //possibly add all sub-items
            $query->where("resource_ids", "`resources_assign`.`resource_id` IN (:resource_ids)", array(
                'resource_ids' => $resource_ids
            ));
        }
    }
}