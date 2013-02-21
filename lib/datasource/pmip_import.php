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
    
    const NCKS_ARGS = " -s \"%%f\\n\" -C -h -d lon,%d.0 -d lat,%d.0 -v %s %s";
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

    public $error = array ();
    
    
    
    function __construct () {
        $this->ncksPath = '/usr/bin/ncks';
        if (!file_exists($this->ncksPath))
            die ("Couldn't find ncks. On Debian/Ubuntu derivatives you can try installing the 'nco' package.");
    }
    
    
       /**
     *
     * @param <type> $tempArr array of monthly temperatures
     * @param <type> $v self::T(MIN|MAX|MEAN)_VAR determines processing of temps array
     * @return <type> the max, min or mean of values in the temps array according to the source var name or the whole array if unknown varname.
     */
    function _getMaxMinMeanByVarName ($tempArr, $v = self::TMAX_VAR) {
        $tempArr = (array) $tempArr;
        sort ($tempArr, SORT_NUMERIC);
        switch ($v) {
            case self::TMAX_VAR:
                return $tempArr[11];
            case self::TMIN_VAR:
                return $tempArr[0];
            case self::TMEAN_VAR:
                return array_sum($tempArr) / count ($tempArr);
        }
        return $tempArr[0];
    }


    function _getDayMinOffset ($tempArr, $v = self::TMIN_VAR) {
        $min = $this->_getMaxMinMeanByVarName ($tempArr, $v);
        $mmin = array_filter ($tempArr, function ($v) use (&$min) {
            return ($v == $min);
        });
        $mmin = (count ($mmin) == 1 && $mmin = array_keys($mmin)) ? $mmin[0] : 0;
        $dmin = round (((365/12) * ($mmin + 1)) + 365/24);
        return $dmin;

    }

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
    
    function _genDataFileName ($varname, $timename, $modelname) {
        $expr = "/^$varname.*?$timename.*?$modelname.*?\." . self::NETCDF_DATA_EXT . "$/";

        foreach ($this->files as $f)
            if (preg_match ($expr, $f) > 0) {
                $this->filename = $f;
                return $f;
            }
    }


    function _extractElevation ($lat, $lon, $varname, $timename, $modelname) {

        // This static cache thing is a bit dodgy, but it does speed things up!
        static $cache = array ();

        $ak = "$lat#$lon#$varname#$timename#$modelname";
        if (!empty ($cache[$ak])) return $cache[$ak];

        $file = $this->_genDataFileName ($varname, $timename, $modelname);

        $cmd = sprintf ($this->ncksPath . self::NCKS_ARGS, $lon, $lat, $varname, $this->dbroot . $file);

        exec ($cmd, $r);
        $r = implode("\n", $r);

        $ev = $this->_getElevationFromOutput ($r);
        $cache[$ak] = $ev;

        return $ev;

    }

    function _extractTemps ($lat, $lon, $varname, $timename, $modelname) {

        // This static cache thing is a bit dodgy, but it does speed things up!
        static $cache = array ();

        $ak = "$lat#$lon#$varname#$timename#$modelname";
        if (!empty ($cache[$ak])) return $cache[$ak];

        $file = $this->_genDataFileName ($varname, $timename, $modelname);

        $cmd = sprintf ($this->ncksPath . self::NCKS_ARGS, $lon, $lat, $varname, $this->dbroot . $file);

        exec ($cmd, $r);
        $r = implode("\n", $r);
        if ($varname == self::ALT_VAR)
            $ts = $this->_getElevationFromOutput ($r);
        else
            $ts = $this->_getTempsFromOutput ($r);
        //debug ($ts);
        $cache[$ak] = $ts;

        return $ts;
    }


}



?>