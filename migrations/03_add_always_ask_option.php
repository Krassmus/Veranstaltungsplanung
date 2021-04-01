<?php
class AddAlwaysAskOption extends Migration
{
    function up()
    {
        Config::get()->create("VERANSTALTUNGSPLANUNG_ALWAYS_ASK", array(
            'value' => "1",
            'type' => "boolean",
            'range' => "user",
            'section' => "Veranstaltungsplaner",
            'description' => "Should the user always be asked before he/she changes a date?"
        ));
    }

    public function down()
    {
        Config::get()->delete("VERANSTALTUNGSPLANUNG_ALWAYS_ASK");
    }
}
