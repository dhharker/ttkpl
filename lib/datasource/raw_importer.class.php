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

abstract class RawImporter {

    public $files = array ();

        /**
     *
     * @param <type> $tempArr array of monthly temperatures
     * @param <type> $v self::T(MIN|MAX|MEAN)_VAR determines processing of temps array
     * @return <type> the max, min or mean of values in the temps array according to the source var name or the whole array if unknown varname.
     */
    function _getMaxMinMeanByVarName ($tempArr, $v = self::TMAX_VAR) {
        sort ($tempArr, SORT_NUMERIC);
        switch ($v) {
            case self::TMAX_VAR:
                return $tempArr[11];
            case self::TMIN_VAR:
                return $tempArr[0];
            case self::TMEAN_VAR:
                return array_sum($tempArr) / count ($tempArr);
        }
        return $tempArr;
    }


    function _getDayMinOffset ($tempArr, $v = self::TMIN_VAR) {
        $min = $this->_getMaxMin ($tempArr, $v);
        $mmin = array_filter ($tempArr, function ($v) use (&$min) {
            return ($v == $min);
        });
        $mmin = (count ($mmin) == 1 && $mmin = array_keys($mmin)) ? $mmin[0] : 0;
        $dmin = round (((365/12) * ($mmin + 1)) + 365/24);
        return $dmin;

    }

    function __construct () {
        $this->dbroot = TTKPL_PATH . 'data/pmip2/';
        exec ("ls " . $this->dbroot, $this->files);
    }


    function _extractElevation ($lat, $lon, $varname, $timename, $modelname) {

        // This static cache thing is a bit dodgy, but it does speed things up!
        static $cache = array ();

        $ak = "$lat#$lon#$varname#$timename#$modelname";
        if (!empty ($cache[$ak])) return $cache[$ak];

        $file = $this->_genDataFileName ($varname, $timename, $modelname);

        $cmd = sprintf (self::EXTRACT_COMMAND, $lon, $lat, $varname, $this->dbroot . $file);

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

        $cmd = sprintf (self::EXTRACT_COMMAND, $lon, $lat, $varname, $this->dbroot . $file);

        exec ($cmd, $r);
        $r = implode("\n", $r);

        $ts = $this->_getTempsFromOutput ($r);
        $cache[$ak] = $ts;

        return $ts;
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
        //preg_match_all ("(\d+\.\d+)", $matches[0], $matches);
        return $matches[0];
    }

    function _genDataFileName ($varname, $timename, $modelname) {
        $expr = "/^$varname.*?$timename.*?$modelname.*?\." . self::NETCDF_DATA_EXT . "$/";

        foreach ($this->files as $f)
            if (preg_match ($expr, $f) > 0) {
                $this->filename = $f;
                return $f;
            }
    }

}