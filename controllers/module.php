<?php

class ModuleController extends PluginController
{

    public function show_action($parent_id = null)
    {
        $this->selected    = null;
        if ($parent_id === "root") {
            $studiengaenge = Studiengang::findBySQL("`stat` = 'genehmigt' ORDER BY name ASC");
            $areas = [];
            foreach ($studiengaenge as $studiengang) {
                $areas[] = [
                    'id' => "studiengang_".$studiengang->id,
                    'name' => $studiengang['name'],
                    'children' => count($studiengang->studiengangteile)
                ];
            }
            $this->areas = $areas;
            return;
        }
        list($type, $id) = explode("_", $parent_id);
        switch ($type) {
            case "studiengang":
                $GLOBALS['user']->cfg->store('VERANSTALTUNGSPLANUNG_MODULES_LAST_STG_ID', $id);
                $parent = Studiengang::find($id);
                $this->parent = [
                    'id' => $parent_id,
                    'name' => $parent->getDisplayName(),
                    'parent_id' => 'root'
                ];
                $areas = [];
                foreach ($parent->studiengangteile as $studiengangteil) {
                    $areas[] = [
                        'id' => "studiengangteil_".$studiengangteil->id,
                        'name' => $studiengangteil->getDisplayName(),
                        'children' => count($studiengangteil->versionen)
                    ];
                }
                $this->areas = $areas;
                break;
            case "studiengangteil":
                $parent = StudiengangTeil::find($id);
                $grandparent = null;
                foreach ($parent->studiengang as $s) {
                    if ($s->id === $GLOBALS['user']->cfg->VERANSTALTUNGSPLANUNG_MODULES_LAST_STG_ID) {
                        $grandparent = $s;
                        break;
                    }
                }
                if (!$grandparent) {
                    $grandparent = $parent->studiengang[0];
                }

                $this->parent = [
                    'id' => $parent_id,
                    'name' => $parent->getDisplayName(),
                    'parent_id' => 'studiengang_'.$grandparent->id
                ];
                $areas = [];
                foreach ($parent->versionen as $studiengangteilversion) {
                    foreach ($studiengangteilversion->abschnitte as $abschnitt) {
                        $areas[] = [
                            'id' => "studiengangteilabschnitt_" . $abschnitt->id,
                            'name' => $abschnitt->getDisplayName(),
                            'children' => count($abschnitt->module)
                        ];
                    }
                }
                $this->areas = $areas;
                break;
            case "studiengangteilabschnitt":
                $parent = StgteilAbschnitt::find($id);
                $this->parent = [
                    'id' => $parent_id,
                    'name' => $parent->getDisplayName(),
                    'parent_id' => 'studiengangteil_'.$parent->version->studiengangteil->id
                ];
                $areas = [];
                foreach ($parent->module as $module) {
                    $areas[] = [
                        'id' => "modul_".$module->id,
                        'name' => $module->getDisplayName(),
                        'children' => 0
                    ];
                }
                $this->areas = $areas;
                break;
            case "modul":
                break;
        }
    }
}
