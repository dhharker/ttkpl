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
 * @author David Harker david.harker@york.ac.uk
 */



class palaeoTime extends facet {


    public $timeScalar = NULL;
    public $datasource = NULL;
    
    function getYearsBp () {
        return $this->timeScalar->getValue ();
    }
    function setYearsBp ($ybp) {
        return $this->init ($ybp);
        //return $this->timeScalar->setValue ($ybp);
    }

    public function distanceTo (facet $to) {
        return (is_a ($to, 'palaeoTime')) ? cal::dif ($to->getYearsBp (), $this->getYearsBp ()) : FALSE;
    }

    public function init ($yearsBp = NULL, dataSet &$ds = NULL) {
        if (!is_a ($this->timeScalar, 'scalar'))
            return $this->timeScalar = scalarFactory::makeYearsBp ($yearsBp, $ds);
        elseif (is_object ($yearsBp) && is_a ($yearsBp, 'scalar'))
            $this->timeScalar = clone ($yearsBp);
        elseif ($yearsBp !== NULL)
            return $this->timeScalar->setValue ($yearsBp + 0.00);
        else
            return -345;
        return TRUE;
    }
    public function __construct ($yearsBp = NULL, dataSet &$ds = NULL) {
        $succ = $this->init ($yearsBp, $ds);
        if ($succ) {
            $this->datasource = $ds;
            return TRUE;
        }
        return FALSE;
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
