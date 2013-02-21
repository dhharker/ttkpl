<?php namespace ttkpl;

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
 * Contains interfaces and classes for operating on datatypes
 * @author David Harker david.harker@york.ac.uk
 */


class histogram {

    public $x = null;
    public $numPoints = 0; // stores the number of values in x
    public $numBins = -1;
    public $rehash = TRUE;
    public $binWidth = 1;
    public $range = 0;
    public $bins = array ();     // labels and bins have indexen in synch.
    public $labels = array ();  //
    const roundDp = 6;
    
    private $ptr = 0;
    
    public function __construct() {
        $this->x = new \SplFixedArray($this->numPoints);
    }
    public function setNumPoints ($np) {
        $this->numPoints = $np;
        $this->x->setSize ($np);
    }
    public function addPoint ($x) {
        $x = (array) $x;
        foreach ($x as $v) {
            if ($this->x->getSize() == $this->ptr)
                $this->x->setSize ($this->x->getSize ()+1);
            $this->x[$this->ptr] = $v;
            
            $this->ptr++;
        }
        
        $this->rehash = TRUE;
    }
    public function setBins ($numBins, $uBound = 200, $lBound = 100) {
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
        
        $xx = $this->x->toArray();
        
        if ($this->rehash == TRUE) {
            $this->setBins ($this->numBins);
            $this->bins = array ();
            $this->max = max ($xx) + 0.0;
            $this->min = min ($xx) + 0.0;
            $this->range = $this->max - $this->min + 0.0;
            $this->numPoints = count ($xx);
            $this->binWidth = (($this->range > 0) ? $this->range : 1E-9) / ($this->numBins - 1);
            $this->rehash = FALSE;
        }
        
        for ($bin = 0; $bin < $this->numBins; $bin++) {
            $min = $bin * $this->binWidth + $this->min;
            $max = ($bin + .9999999999999999) * $this->binWidth + $this->min;
            $this->labels[$bin] = ($tf ($min, $max));
            $this->bins[$bin] = 0;
        }

        foreach ($xx as $x) {
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

abstract class correction {
    abstract public function correct ($ftVal);
    public $desc = "Undescribed!";
    public $source = NULL; // where have the coefficient values for this correction come from?
    public function describe ($t = NULL) {
        return ($t === NULL) ? $this->desc : ($this->desc = $t);
    }
}

class offsetCorrection extends correction {
    public $a = 0;

    public function __construct ($val) {
        $this->a = (float) $val;
    }
    public function correct ($ftVal) {
        return (float) $ftVal + (float) $this->a;
    }

}

/*
(?)'first order' polynomial;
y = ax + b

$pc = new proportionalCorrection ($a, $b);
$x = 100;
$y = $pc->correct ($x);

*/
class deg1Polynomial extends correction {
    public $a = 0;
    public $offset = NULL;
    public function __construct ($val, $offset) {
        $this->offset = new offsetCorrection ($offset);
        $this->a = (float) $val;
    }
    public function correct ($ftVal) {
        if (is_object ($ftVal) && get_class ($ftVal) == 'scalar')
            $v = $ftVal->getScalar()->getValue();
        elseif (is_object ($ftVal))
            $v = $ftVal->getValue();
        else
            $v = $ftVal;
        return ((float) $v * (float) $this->a) + $this->offset->a;
    }
}
class deg2Polynomial extends correction {
    public $a = 0;
    public $deg1 = NULL;
    public function __construct ($m2, $m, $offset) {
        $this->deg1 = new deg1Polynomial ($m, $offset);
        $this->a = (float) $m2;
    }
    public function correct ($ftVal) {
        return (pow ((float) $ftVal, 2) * (float) $this->a) + $this->deg1->correct ($ftVal);
    }
}


/* abstract class chainable {
//
//     public $value;
//
//     public function getValue () {
//         return (is_object ($this->value)) ? $this->value->getValue () : $this->value;
//     }
//
//     public $corrections = array ();
//
//     public function addCorrection (correction $cor) {
//         $this->corrections[] = $cor;
//     }
//     public function correctValue ($v = NULL) {
//         $v = ($v === NULL) ? $this->getValue () : $v;
//         foreach ($this->corrections as $cor)
//             $v = $cor->correct ($v);
//         return $v;
//     }
//
// } */


// modified from original PrediCtoR codebase

class cal {
    // Return sum of values in an array
    public static function sum ($values) {
        //print_r ($values);
        $values = (array) $values;
        $sum = 0;
        foreach ($values as $value)
            $sum += $value;
        return $sum;
    }
    // Return mean of input (array) values
    public static function mean ($values) {
        if (count ($values))
            return self::sum ($values) / count ($values);
        else
            return 0;
    }
    public static function dif ($a,$b) {
        // Return absolute difference between a and b
        return ($a >= $b) ? ($a - $b) : ($b - $a);
    }
    // Take array of values and calculate standard deviation
    public function stddev ($values) {
        // Get the mean of the values
        $mean = self::mean ($values);
        // Find difference from mean of each value
        $mdiffs = array ();
        foreach ($values as $value) {
            $mdiffs[] = self::dif ($value,$mean);
        }
        $sqdiffsum = 0;
        // Square and sum the differences
        $sqmdiffs = 0;
        foreach ($mdiffs as $mdiff) {
            $sqmdiffs += pow ($mdiff, 2);
        }
        // Get variance (mean of sqdiffs)
        $variance = $sqmdiffs / (count ($values) - 1);
        // return the standard deviation
        return sqrt ($variance);
    }
}

class deg2Regression {

    public $regr = NULL;
    public $sqregr = NULL;


    // btw this doesn't work at all :-)
    function __construct ($x, $y) {
        // linear regression first
        $this->regr = new linearRegression ($x, $y);
        // now correct x for this
        $cr_l = $this->regr->getCorrection ();
        $xc_l = array_map (function ($x) use ($cr_l) { return $cr_l->correct ($x); }, $x);

        // Now square the corrected exes
        $xsq = array_map (function ($x) { return pow ($x, 2); }, $xc_l);
        // An do another linear regression on them
        $this->sqregr = new linearRegression ($xsq, $y);
        // Add the offsets (hrmm..!?)
        $offset = $this->regr->bfB() + $this->sqregr->bfB();
        //$xsqm = array_map (function ($x) use ($c) { return $x * $c; }, $xsq);
        $this->a = $this->sqregr->bfA (); // a * x ^ 2 +
        $this->b = $this->regr->bfA(); // b * x +
        $this->c = $offset; // + c = y

    }

    function getCorrection () {
        return new deg2Polynomial ($this->a, $this->b, $this->c);
    }
}

class linearRegression {

    protected $vals = array ();
    protected $x = array ();
    protected $y = array ();
    protected $mean_x = 0;
    protected $mean_y = 0;

    function getCorrection () {
        return new deg1Polynomial ($this->bfA (), $this->bfB ());
    }

    public function __construct ($arr_x, $y) {
        $this->loadData ($arr_x, $y);
    }
    public function loadData ($arr_x, $y) {
        $this->vals = array_combine ($arr_x, $y);
        //$this->x = $arr_x;
        //$this->y = $y;

        foreach ($this->vals as $x => $y) {
            if (is_numeric ($x) && is_numeric ($y)) {
                $this->x[] = $x;
                $this->y[] = $y;
            }
        }

        $this->mean_x = cal::mean ($this->x);
        $this->mean_y = cal::mean ($this->y);

    }

    // These get a and b values for a line of best fit (y= ax + b)
    public function bfB () { // intercept (b)
        return ($this->mean_y - ($this->bfA() * $this->mean_x));
    }
    public function bfA () { // slope (a)
        $topsum = 0;$botsum = 0;
        foreach ($this->vals as $x => $y) {
            $var_x = ($x - $this->mean_x);
            $var_y = ($y - $this->mean_y);
            $topsum += ($var_x * $var_y);
            $botsum += (pow ($var_x, 2));
        }
        return $topsum / $botsum;
    }

    // correlation coefficient (r)
    public function regR () {
        $topsum = 0; $botsum_x = 0; $botsum_y = 0;
        foreach ($this->vals as $x => $y) {
            $var_x = ($x - $this->mean_x);
            $var_y = ($y - $this->mean_y);
            $topsum += ($var_x * $var_y);
            $botsum_x += (pow ( $var_x, 2));
            $botsum_y += (pow ( $var_y, 2));
            $bot = sqrt ( ($botsum_x * $botsum_y) );
        }
        return $topsum / $bot;
    }
    // coefficient of determination (r^2)
    public function regRSq () {
        return pow ($this->regR (), 2);
    }
    public function regRSqPc () {
        return ($this->regRSq() * 100);
    }
    public function mean_x () { return $this->mean_x; }
    public function mean_y () { return $this->mean_y; }


}



