<?php

namespace Cora\Converter;

use Cora\Utils\Printer;

use Cora\Domain\Petrinet\PetrinetInterface as Petrinet;
use Cora\Domain\Petrinet\Marking\MarkingInterface as Marking;
use Cora\Domain\Petrinet\Marking\Tokens\IntegerTokenCount;

class PetrinetToDot extends Converter {
    protected $petrinet;
    protected $marking;
    protected $printer;

    public function __construct(Petrinet $net, ?Marking $marking=NULL) {
        $this->petrinet = $net;
        $this->marking  = $marking;
        $this->printer  = new Printer;
    }

    public function convert() {
        $placeStrings   = $this->placesToArray();
        $transStrings   = $this->transitionsToArray();
        $flowStrings    = $this->flowsToArray();
        $markingStrings = $this->markingToArray();
        $options = [
            'graph [fontname="monospace", fontsize="14"]',
            'node [fontname="monospace", fontsize="14"]',
            'edge [fontname="monospace", fontsize="10"]'
        ];
        $s = "digraph G {";
        $s .= "\n\t";
        $s .= implode("\n\t", $options);
        $s .= "\n\t";
        $s .= implode("\n\t", $placeStrings);
        $s .= "\n\t";
        $s .= implode("\n\t", $transStrings);
        $s .= "\n\t";
        $s .= implode("\n\t", $flowStrings);
        if (!is_null($this->marking)) {
            $s .= "\n\t";
            $s .= implode("\n\t", $markingStrings);
        }
        $s .= "\n";
        $s .= "}";
        return $s;
    }

    protected function placesToArray() {
        $places = $this->petrinet->getPlaces();
        $ids = [];
        $makeupArray = [];
        foreach ($places as $place) {
            $id = $place->getID();
            $ids[] = $id;
            $name = $place->getLabel() ?? $id;
            // Coordinates may or may not be null, so this bit checks if they are and creates a $makeup value
            $coordinates = $place->getCoordinates();
            if(!is_null($coordinates)){
                $makeup = $id . ' [xlabel="' . $name . '", xcoordinates="' . implode(',', $coordinates) . '"]';
            }else{
                $makeup = $id . ' [xlabel="' . $name . '"]';
            }
            $makeupArray[] = $makeup;
        }
        //Transform $ids into a string with ', ' in between the array values, and add the format options of the places 
        $ids = implode(", ", $ids);
        $ids .= '[shape="ellipse", width=0.75, height=0.75, label=""]';
        $this->printer->terminalLog(implode(", ",array_merge([$ids],$makeupArray)));
        return array_merge([$ids],$makeupArray);
    }
    
    protected function transitionsToArray() {
        $transitions = $this->petrinet->getTransitions();
        $ids = [];
        $makeupArray = [];
        foreach ($transitions as $transition) {
            $id = $transition->getID();
            $ids[] = $id;
            $name = $transition->getLabel() ?? $id;
            // Coordinates may or may not be null, so this bit checks if they are and creates a $makeup value accordingly
            $coordinates = $transition->getCoordinates();
            if(!is_null($coordinates)){
                $makeup = $id . ' [xlabel="' . $name . '", xcoordinates="' . implode(',', $coordinates) . '"]';
            }else{
                $makeup = $id . ' [xlabel="' . $name . '"]';
            }
            $makeupArray[] = $makeup;
        }
        //Transform $ids into a string with ', ' in between the array values, and add the format options of the places 
        $ids = implode(", ", $ids);
        $ids .= '[shape="box", style="filled", fillcolor="#2ECC71", width=0.75, height=0.75]';
        $this->printer->terminalLog(implode(", ",array_merge([$ids],$makeupArray)));
        return array_merge([$ids],$makeupArray);
    }

    protected function flowsToArray() {
        $flows = $this->petrinet->getFlows();
        $res = [];
        foreach($flows as $flow => $weight) {
            $row = sprintf("%s -> %s", $flow->getFrom(), $flow->getTo());
            if ($weight > 1)
                $row .= sprintf("[label=%d]", $weight);
            $row .= ";";
            array_push($res, $row);
        }
        return $res;
    }

    protected function markingToArray() {
        $result = [];
        if (is_null($this->marking))
            return $result;
        foreach($this->marking as $place => $tokens) {
            if ($tokens instanceof IntegerTokenCount &&
                $tokens->getValue() <= 0)
                continue;
            $l = sprintf('%s [label="%s"];', $place, $tokens);
            array_push($result, $l);
        }
        return $result;
    }
}
