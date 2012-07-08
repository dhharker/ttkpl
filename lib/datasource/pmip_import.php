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

// For getting geolocal air temperature maxima and minima from PMIP2 atmosphere datasets at 6ka and 21ka



class PMIP2 extends RawImporter {
    
    const EXTRACT_COMMAND = "/usr/bin/ncks -s \"%%f\\n\" -C -h -d lon,%d.0 -d lat,%d.0 -v %s %s";
    const T_LGM_21KA = "21k";
    const T_MID_HOLOCENE_6KA = "6k";
    const T_PRE_INDUSTRIAL_0KA = "0k";
    const TMIN_VAR = 'tasmin';
    const TMAX_VAR = 'tasmax';
    const TMEAN_VAR = 'tas';
    const ALT_VAR = 'orog';
    const MODEL_HADCM3M2 = 'HadCM3M2';
    const MODEL_CCSM = 'CCSM';
    const NETCDF_DATA_EXT = 'nc';
    const NETCDF_CTRL_EXT = 'ctrl';
    
    function getTemps ($lat, $lon) {
        $times = array (self::T_LGM_21KA, self::T_MID_HOLOCENE_6KA, self::T_PRE_INDUSTRIAL_0KA);
        $vars = array (self::TMAX_VAR, self::TMIN_VAR);
        $temps = array ();
        foreach ($times as $t)
            foreach ($vars as $v) {
                $temps[$t.'a'] = $this->_getMaxMinMeanByVarName ($this->_extractTemps ($lat, $lon, $v, $t, self::MODEL_HADCM3M2), $v);
                //$temps[$t.'a'][$v] = $this->_getMaxMin ($this->_extractTemps ($lat, $lon, $v, $t, self::MODEL_HADCM3M2), $v);
            }
        return $temps;
        
    }
    
    
}



?>