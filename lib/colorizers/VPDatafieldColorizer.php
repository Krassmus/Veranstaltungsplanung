<?php

class VPDatafieldColorizer implements VPColorizer
{

    public function getFilterIndexes()
    {
        $datafields = DataField::getDataFields("sem");
        $indexes = [];
        foreach ($datafields as $datafield) {
            $indexes[$datafield->getId()] = sprintf(_("Farbe nach VA-Datenfeld %s"), $datafield['name']);
        }
        return $indexes;
    }

    public function getColor($datafield_id, $termin)
    {
        $datafield_entry = DatafieldEntryModel::findByModel(
            $termin->course,
            $datafield_id
        );
        if ($datafield_entry[0] && $datafield_entry[0]['content']) {
            if ($datafield_entry[0]->datafield['type'] === "bool") {
                return $datafield_entry[0]['content'] ? "1" : "0";
            }
            return Veranstaltungsplanung::stringToColorCode(
                (string) $datafield_entry[0]['content']
            );
        } else {
            return "0";
        }
    }
}
