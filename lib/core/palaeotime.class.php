<?php

/**
 * Description of palaeotime
 *
 * @author david
 */


class palaeoTime extends facet {


    public $timeScalar = NULL;

    function getYearsBp () {
        return $this->timeScalar->getValue ();
    }
    function setYearsBp ($ybp) {
        $this->init ($ybp);
        return $this->timeScalar->setValue ($ybp);
    }

    public function distanceTo (facet $to) {
        return (is_a ($to, 'palaeoTime')) ? cal::dif ($to->getYearsBp (), $this->getYearsBp ()) : FALSE;
    }

    public function init ($yearsBp = NULL, dataSet &$ds = NULL) {
        if (!is_object ($this->timeScalar) || !is_a ($this->timeScalar, 'scalar'))
            $this->timeScalar = scalarFactory::makeYearsBp ($yearsBp, $ds);
        elseif (is_object ($yearsBp) && is_a ($yearsBp, 'scalar'))
            $this->timeScalar = clone ($yearsBp);
        elseif ($yearsBp !== NULL && is_numeric($yearsBp))
            $this->timeScalar->setValue ($yearsBp);
        else
            return FALSE;
        return TRUE;
    }
    public function __construct ($yearsBp = NULL, dataSet &$ds = NULL) {
        return $this->init ($yearsBp, $ds);
    }

    //internal
    static function _bootstrap ($arrArg) {
        if (is_object ($arrArg) && is_a ($arrArg, 'palaeoTime'))
            return $arrArg;
        elseif (is_numeric ($arrArg))
            $v = $arrArg;
        elseif (is_array ($arrArg) && isset ($arrArg['ybp']) && is_numeric ($arrArg['ybp']))
            $v = $arrArg['ybp'];
        else
            return FALSE;
        return new palaeoTime ($v);

    }
}



?>
