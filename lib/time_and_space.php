<?php
/* 
 * 
 */


class temporalDatum extends datum {

    // palaeoTime object when this is
    public $palaeoTime = NULL;

    function __construct ($palaeoTime = NULL, $value = NULL) {
        $this->palaeoTime = palaeoTime::_bootstrap ($palaeoTime);
        $this->setValue ($value);

    }

}

class spatialDatum extends datum {

    // latLon object where this is
    public $latLon = NULL;

    function __construct ($latLon = NULL, $value = NULL) {
        $this->latLon = latLon::_bootstrap ($latLon);
        $this->setValue ($value);
    }
}

?>
