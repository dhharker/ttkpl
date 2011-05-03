<?php


/* 
 * Contains interfaces and classes for operating on datatypes
 *
 */

class histogram {

    public $x = array ();
    public $numBins = -1;
    public $rehash = TRUE;
    public $binWidth = 1;
    public $range = 0;
    public $bins = array ();     // labels and bins have indexen in synch.
    public $labels = array ();  //
    const roundDp = 6;

    public function addPoint ($x) {
        $x = (array) $x;
        $this->x = array_values ($x);
        $this->rehash = TRUE;
    }
    public function setBins ($numBins, $uBound = 500, $lBound = 250) {
        if ($numBins == -1 || !(is_numeric ($numBins) && $numBins > 0)) {
            $p = $this->countPoints ();
            $n = 2 * sqrt($p);
            $n = round ($n);
            $n = ($n < $lBound) ? $lBound : $n;
            $n = ($n > $uBound) ? $uBound : $n;
            $this->numBins = $n;
        }
        elseif (is_numeric ($numBins) && $numBins > 0)
            $this->numBins = $numBins;
        else
            $this->numBins = 100;
        $this->rehash = TRUE;
    }
    public function getBinCounts ($xText = 0) {
        switch ($xText) {
            default:
            case 0: // midpoint
                $tf = function ($min, $max) {
                    return round (cal::mean ($min, $max), histogram::roundDp). '';
                };
                break;
            case 1: // range
                $tf = function ($min, $max) {
                    return round ($min, histogram::roundDp) . " - " . round ($max, histogram::roundDp);
                };
                break;
            case 2: // range (useful even?)
                $tf = function ($min, $max) {
                    return round ($max - $min, histogram::roundDp) . '';
                };
                break;

        }

        if ($this->rehash == TRUE) {
            $this->setBins ($this->numBins);
            $this->bins = array ();
            $this->max = max ($this->x);
            $this->min = min ($this->x);
            $this->range = $this->max - $this->min;
            $this->numPoints = count ($this->x);
            $this->binWidth = (($this->range > 0) ? $this->range : 1E-9) / ($this->numBins - 1);
            $this->rehash = FALSE;
        }

        for ($bin = 0; $bin < $this->numBins; $bin++) {
            $min = $bin * $this->binWidth + $this->min;
            $max = ($bin + .9999999999999999) * $this->binWidth + $this->min;
            $this->labels[$bin] = ($tf ($min, $max));
            $this->bins[$bin] = 0;
        }

        foreach ($this->x as $xi => $x) {
            $bin = round (($x - $this->min) / $this->binWidth);
            $this->bins[$bin]++;
        }
    }
    function countPoints () {
        return count ($this->x);
    }
    public function getBinWidth () {
        return $this->binWidth;
    }


}


?>
