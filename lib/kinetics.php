<?php
/* 
 *
 */


class dnaHistogram extends histogram {

    function numBonds () {
        $this->numBonds = 0;
        $this->bondsInBin = array();
        foreach ($this->bins as $numBonds => $numFragments) {
            $this->bondsInBin[$numBonds] = $numBonds * $numFragments;
            $this->numBonds += $this->bondsInBin[$numBonds];
        }
        return $this->numBonds;
    }

}



?>
