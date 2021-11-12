<?php
class AddContextmenuOption extends Migration
{
    function up()
    {
        Config::get()->create("VERANSTALTUNGSPLANUNG_CONTEXTMENU", [
            'value' => "1",
            'type' => "boolean",
            'range' => "user",
            'section' => "Veranstaltungsplaner",
            'description' => "Should a custom context-menu appear by right-clicking on the dates?"
        ]);
    }

    public function down()
    {
        Config::get()->delete("VERANSTALTUNGSPLANUNG_CONTEXTMENU");
    }
}
