<?php

interface VPFilter
{
    /**
     * Either "courses", "persons" or "resources". Indicates for which selected context this filter should be
     * available.
     * @return string
     */
    static public function context();

    /**
     * Name of the filter displayed in the configuration window.
     * @return string
     */
    public function getName();

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget();

    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query);
}