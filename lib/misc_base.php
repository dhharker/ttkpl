<?php
/* 
 *
 */

abstract class taUtils {
    function __construct () {
        //parent::__construct ();

    }

    function invWeightedMean ($values, $invWeights) {
        //print_r (array ($values, $invWeights));
//         $get = function (&$v) {
//             print_r ($v);
//             if (!is_object ($v))
//                 return $v;
//             switch (get_class ($v)) {
//                 case "datum":
//                 case "temporalDatum":
//                 case "spatialDatum":
//                     return $v->getScalar()->getValue();
//                     break;
//                 case "scalar":
//                     return $v->getValue();
//                     break;
//             }
//         };
//
//         $values = array_map ($get, $dvalues);
//         $invWeights = array_map ($get, $dinvWeights);
//         print_r ($values);
        $sum = 0;
        $iws = array_sum ($invWeights);

        if ($iws == 0) {
            return cal::mean ($values);
            die (print_r ($invWeights, TRUE));
        }
        $ws = 0;
        foreach ($values as $vi => $vv) {
            $w = (1 / ($invWeights[$vi] / $iws));
            $sum += $vv * $w;
            $ws += $w;
        }
        return $sum / $ws;
    }

    static function filenameFromCrap ($str) {
        return preg_replace (array ('/[^a-z0-9_-]/i', '/(([_-])\2+)/'), array ('_', '_'), strtolower($str));
    }

}


?>
