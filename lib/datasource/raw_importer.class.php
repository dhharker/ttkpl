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

/**
 * Base class to import temperature and altitude (or whatever) data from various file-based geo-data
 * formats.
 *
 * This moved out of PMIP2 class as it is mostly generic.
 */

class RawImporter {

    public $files = array ();

     

    function __construct () {
        $this->dbroot = TTKPL_PATH . 'data/pmip2/';
        exec ("ls " . $this->dbroot, $this->files);
    }

    function _getTempsFromOutput ($strin) {
        $expr = "/\s(\d+\.\d+\s+){1,12}/";
        if (preg_match ($expr, $strin, $matches) == 0)
            return false;
        preg_match_all ("(\d+\.\d+)", $matches[0], $matches);
        return $matches[0];
    }

    function _getElevationFromOutput ($strin) {
        $expr = '/.*?(\d+\.\d+\s+)\s*$/';
        if (preg_match ($expr, $strin, $matches) == 0)
            return false;
        return $matches[0];
    }

    

}