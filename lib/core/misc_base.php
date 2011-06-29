<?php

/*
* Copyright 2008-2011 David Harker
*
* Licensed under the EUPL, Version 1.1 or – as soon they
  will be approved by the European Commission - subsequent
  versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the
  Licence.
* You may obtain a copy of the Licence at:
*
* http://ec.europa.eu/idabc/eupl
*
* Unless required by applicable law or agreed to in
  writing, software distributed under the Licence is
  distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
  express or implied.
* See the Licence for the specific language governing
  permissions and limitations under the Licence.
*/


/**
 * @author David Harker david.harker@york.ac.uk
 */

abstract class taUtils {
    function __construct () {
        //parent::__construct ();
    }

    /**
     * Calculates weighted mean of values
     * @param array values to be averaged
     * @param weights to apply to values
     * @return float weighted mean
     */
    function weightedMean ($values, $weights) {
        array_walk ($weights, function (&$v, $k) {
            $v = abs ($v);
        });
        $sum = 0;
        foreach ($values as $vi => $v)
            $sum += $v * $weights[$vi];
        return ($sum / array_sum ($weights));
    }

    /** For calculating the mean of values weighted by the inverse of their weighting as a proportion
     * of the total of weighting values. invWeights can be thought of the "distance to" the real value
     * e.g. if it is 10°C 10m away and 20°C 2km away; values(10, 20) invweights(10, 2000)
     * @param array values contains values
     * @param array invWeights contains weights to be inverted
     * @return float mean value by inverse weights
     */
    function invWeightedMean ($values, $invWeights) {
        array_walk ($invWeights, function (&$v, $k) {
            $v = abs ($v);
        });
        $sum = 0;
        $iws = array_sum ($invWeights);
        if ($iws == 0)
            return cal::mean ($values);
        $ws = 0;
        foreach ($values as $vi => $vv) {
            $w = (1 / ($invWeights[$vi] / $iws));
            $sum += $vv * $w;
            $ws += $w;
        }
        return ($sum / $ws);
    }

    /** Filters crap out of strings and produces something that can be used as a filename
     * @param string crap to be filtered
     * @return string clean string for use as/in filename
     */
    static function filenameFromCrap ($crap) {
        return preg_replace (array ('/[^a-z0-9_-]/i', '/(([_-])\2+)/'), array ('_', '_'), strtolower($crap));
    }

}

