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
     * Name of the parameter that is sent by the sidebar widget. We need to know this to tunnel
     * it to the URL and fetch dates with it.
     * @return string
     */
    public function getParameterName();

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @return SidebarWidget
     */
    public function getSidebarWidget();

    /**
     * Method executed to change the SQLQuery-object.
     * @param \Veranstaltungsplanung\SQLQuery $query
     * @return void
     */
    public function applyFilter(\Veranstaltungsplanung\SQLQuery $query);
}