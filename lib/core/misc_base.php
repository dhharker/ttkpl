<?php
/* 
 *
 */

abstract class taUtils {
    function __construct () {
        //parent::__construct ();

    }

    /* For calculating the mean of values weighted by the inverse of their weighting as a proportion
     * of the total of weighting values. invWeights can be thought of the "distance to" the real value
     * e.g. if it is 10°C 10m away and 20°C 2km away; values(10, 20) invweights(10, 2000)
     * @todo rewrite
     * @param array values contains values
     * @param array invWeights contains weights to be inverted
     * @return float mean value by inverse weights
     */
    function invWeightedMean ($values, $invWeights) {
        // normalise weights to 1 and invert
        $ws = array_sum ($invWeights);
//        foreach ($invWeights as &$w)
//            $w = 1 - ($w / $ws);
        // sum products & rtn
        $sum = 0;
        foreach ($values as $vi => $v)
            $sum += $v * $invWeights[$vi];
        return $sum / $ws;
    }
    /*
     * Calculates weighted mean of values
     * @param array values to be averaged
     * @param weights to apply to values
     * @return float weighted mean
     */
    function weightedMean ($values, $weights) {
        $sum = 0;
        foreach ($values as $vi => $v)
            $sum += $v * $weights[$vi];
        return $sum / array_sum ($weights);
    }
    function OLDinvWeightedMean ($values, $invWeights) {
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

