<?php
class AddReadonlyOption extends Migration
{
    function up()
    {
        Config::get()->create("VPLANER_READONLY", array(
            'value' => "dozent",
            'type' => "string",
            'range' => "global",
            'section' => "Veranstaltungsplaner",
            'description' => "Until which roles (dozent, admin, etc.) the VPlaner should be set to readonly mode?"
        ));
    }

    public function down()
    {
        Config::get()->delete("VPLANER_READONLY");
    }
}
