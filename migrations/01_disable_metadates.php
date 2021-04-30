<?php
class DisableMetadates extends Migration
{
    function up()
    {
        Config::get()->create("VPLANER_DISABLE_METADATES", [
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "Veranstaltungsplaner",
            'description' => "Should cycle-dates/metadates be hidden from editing?"
        ]);
    }

    public function down()
    {
        Config::get()->delete("VPLANER_DISABLE_METADATES");
    }
}
