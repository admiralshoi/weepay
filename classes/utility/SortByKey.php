<?php

namespace classes\utility;

class SortByKey {


    private array $sorting = array(
        "replacement" => array(),
        "splitReplacement" => array(),
        "key" => "",
        "key_2" => "",
        "ascending" => false
    );

    function run(&$arr,$key = "", $ascending = false, array $specialReplacement = array(), array $splitReplace = array(), $key2 = ""): void {
        if(empty($arr)) return;

        $this->sorting["ascending"] = $ascending; $this->sorting["key"] = $key; $this->sorting["key_2"] = $key2;
        $this->sorting["replacement"] = $specialReplacement; $this->sorting["splitReplacement"] = $splitReplace;
        usort($arr, array($this,"sortAscDescByKey"));
    }

    private function sortAscDescByKey($a, $b): int {
        $useKey = !empty($this->sorting["key"]);
        $useKey2 = !empty($this->sorting["key_2"]);
        if($useKey && is_array($a) && is_array($b) &&
            (!array_key_exists($this->sorting["key"],$a) || !array_key_exists($this->sorting["key"],$b))) return 0;

        $valueA = $useKey ? $a[$this->sorting["key"]] : $a;
        $valueB = $useKey ? $b[$this->sorting["key"]] : $b;

        $valueA = $useKey2 ? $valueA[$this->sorting["key_2"]] : $valueA;
        $valueB = $useKey2 ? $valueB[$this->sorting["key_2"]] : $valueB;

        if(!empty($this->sorting["replacement"]) && count($this->sorting["replacement"]) === 2) {
            $valueA = str_replace($this->sorting["replacement"][0],$this->sorting["replacement"][1],$valueA);
            $valueB = str_replace($this->sorting["replacement"][0],$this->sorting["replacement"][1],$valueB);
        }
        elseif(!empty($this->sorting["splitReplacement"]) && count($this->sorting["splitReplacement"]) === 2) {
            $valueA = (explode($this->sorting["splitReplacement"][0], $valueA))[ ($this->sorting["splitReplacement"][1]) ];
            $valueB = (explode($this->sorting["splitReplacement"][0], $valueB))[ ($this->sorting["splitReplacement"][1]) ];
        }

        if(is_numeric($valueA) || is_numeric($valueB)) {
            $valueA = (float)$valueA;
            $valueB = (float)$valueB;
        }

        if ($valueA === $valueB) return 0;
        return ($valueA > $valueB) ? ($this->sorting["ascending"] ? 1 : -1) : ($this->sorting["ascending"] ? -1 : 1);
    }
}