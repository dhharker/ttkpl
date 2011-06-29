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
