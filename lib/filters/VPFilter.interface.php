<?php

/**
 * Interface VPFilter
 *
 * A VPFilter is an element in the sidebar that has lots of functionality in once. Most
 * important is to know that each VPFilter has a distinct context: either "courses",
 * "persons" or "resources". The static method VPFilter::context() returns exactly one
 * of these strings to indicate the context it is responsible for.
 *
 * Next it has a name returned by the method getName, which is only important to enable
 * or disable it.
 *
 * The method getSidebarWidget returns an element of type SidebarWidget. Remind that this
 * element has to have a CSS-class exactly like the name of the context. Usually you add
 * this CSS class through the method $mywidget->addLayoutCSSClass($context);
 *
 * Further more we need to know what the parameter name of the VPFilter is. Most of the
 * time your widget has only one input or select element. But once it comes to fancy
 * widgets like in VPResourceTreeFilter where you are able to select multiple resources
 * you might want to declare exactly what of the parameters is relevant for the filter.
 * You return that parameter-name with the method getParameterName().
 *
 * The method applyFilter(\Veranstaltungsplanung\SQLQuery $query) should add your filter
 * to the query. You can use Request::get($parameter_name) to get the parameter you are
 * looking for. It will always be available. But the $query itself is not just one $query
 * object but could be multiple different $queries. Sometimes it is a query object to
 * select the courses or sometimes it is a query to select course-dates. You should only
 * rely on the table that is specific for your context: auth_user_md5 for "persons",
 * seminare for "courses" and resources_objects for "resources".
 *
 */
interface VPFilter
{
    /**
     * Either "courses", "persons" or "resources". Indicates for which selected context this filter should be
     * available.
     * @return string
     */
    static public function context();

    /**
     * Returns an associative array of names with their indexes like array('param1' => _("Freie Suche")).
     * The name is a readable string for the configuration window. And the index will be used in
     * the other methods of this class as $index. $index must be unique over all VPFilters.
     * @return associative array : like array('param1' => _("Freie Suche"))
     */
    public function getNames();

    /**
     * Returns a widget (or null) that gets attached to the sidebar.
     * @param string $index : index of the parameter
     * @return SidebarWidget
     */
    public function getSidebarWidget($index);

    /**
     * Method executed to change the SQLQuery-object. The parameter above will be in
     * the Request. Usually the filter stores this parameter in a userconfig
     * and then uses it to add contraints to $query.
     * @param string $index : index of the parameter
     * @param \Veranstaltungsplanung\SQLQuery $query
     * @return void
     */
    public function applyFilter($index, \Veranstaltungsplanung\SQLQuery $query);
}
