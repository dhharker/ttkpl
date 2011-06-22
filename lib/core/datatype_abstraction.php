<?php
/* 
 * 
 */

class latLon extends facet {

    public $lat = 0;
    function setLat ($lat) {
        return ($this->_isLat ($lat)) ? ($this->lat = $lat + 0.0) : FALSE;
    }
    function getLat () {
        return $this->lat;
    }

    public $lon = 0;
    function setLon ($lon) {
        return ($this->_isLon ($lon)) ? ($this->lon = $lon + 0.0) : FALSE;
    }
    function getLon () {
        return $this->lon;
    }

    function __toString () {
        $rdp = 3;
        $a = round ($this->lat, $rdp);
        $o = round ($this->lon, $rdp);
        return "{$a}N,{$o}E";
    }

    public function distanceTo (facet $to) {
        return (get_class ($to) == 'latLon') ? self::haversine ($this, $to) : FALSE;
    }


    function __construct ($latA = NULL, $lonA = NULL) {
        return $this->setLatLon ($latA, $lonA);
    }

    // internal
    static function haversine (latLon $from, latLon $to) {
        // adapted from http://www.movable-type.co.uk/scripts/latlong.html
        $R = 6371; // km mean earth radius (ellipsoidal model reduces error by up to .3% or something but who has time...)
        //$R = 6378.160; // from http://www.sunearthtools.com/dp/tools/pos_earth.php#accuracy
        $dLat = deg2rad ($to->getLat () - $from->getLat ());
        $dLon = deg2rad ($to->getLon () - $from->getLon ());
        $a = sin($dLat/2) * sin($dLat/2) +
                cos (deg2rad ($from->getLat ())) * cos (deg2rad ($from->getLat ())) *
                sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $dkm = $R * $c;
        return scalarFactory::makeKilometres ($dkm);
    }
    static function _bootstrap ($arrArg) {
        $o = new latLon ();
        if (is_object ($arrArg) && get_class ($arrArg) == 'latLon')
            return $arrArg;
        return @(($o->setLatLon ($arrArg['lat'], $arrArg['lon'])) || ($o->setLatLon ($arrArg[0], $arrArg[1])) || ($o->setLatLon ($arrArg[1], $arrArg[0]))) ? $o : FALSE;
    }
    function _isLat ($lat) {
        return (is_numeric ($lat) && $lat <= 90 && $lat >= -90) ? TRUE : FALSE;
    }
    function _isLon ($lon) {
        return (is_numeric ($lon) && $lon <= 180 && $lon >= -180) ? TRUE : FALSE;
    }
    function _wrapLat ($lat) {
        return $this->_wrap ($lat, 180);
    }
    function _wrapLon ($lon) {
        return $this->_wrap ($lon, 360);
    }


    // convenience
    function setLatLon ($lat, $lon) {
        return $this->setLat ($lat) && $this->setLon ($lon);
    }

}

require_once 'palaeotime.class.php';
?>
