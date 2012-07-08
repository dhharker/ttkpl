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

require_once ("bil_import.php");

class worldclim extends dataSet {
    public $importer = NULL;

    // These contain values of constants from the base PMIP2 importer class
    private $varname;
    private $timename;
    private $modelname;

    //const T_LGM_21KA = "21k";
    //const T_MID_HOLOCENE_6KA = "6k";
    const T_PRE_INDUSTRIAL_0KA = "0k";
    const TMIN_VAR = 'tmin';
    const TMAX_VAR = 'tmax';
    //const TMEAN_VAR = 'tas';
    const ALT_VAR = 'alt';

    function __construct ($varname = NULL, $timename = NULL) {
        if ($varname === NULL)
            $varname = worldclim::TMIN_VAR;
        if ($timename === NULL)
            $timename = worldclim::T_PRE_INDUSTRIAL_0KA;

        $this->varname = $varname;
        $this->timename = $timename;

        $this->importer = new bil_import ();
    }
}