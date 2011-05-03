<?php


// For getting geolocal air temperature maxima and minima from PMIP2 atmosphere datasets at 6ka and 21ka



class PMIP2 {
    
    const EXTRACT_COMMAND = "/usr/bin/ncks -s \"%%f\\n\" -C -h -d lon,%d.0 -d lat,%d.0 -v %s %s";
    const T_LGM_21KA = "21k";
    const T_MID_HOLOCENE_6KA = "6k";
    const T_PRE_INDUSTRIAL_0KA = "0k";
    const TMIN_VAR = 'tasmin';
    const TMAX_VAR = 'tasmax';
    const ALT_VAR = 'orog';
    const MODEL_HADCM3M2 = 'HadCM3M2';
    const NETCDF_DATA_EXT = 'nc';
    const NETCDF_CTRL_EXT = 'ctrl';
    
    private $files = array ();
    
    function getTemps ($lat, $lon) {
        $times = array (self::T_LGM_21KA, self::T_MID_HOLOCENE_6KA, self::T_PRE_INDUSTRIAL_0KA);
        $vars = array (self::TMAX_VAR, self::TMIN_VAR);
        $temps = array ();
        foreach ($times as $t)
            foreach ($vars as $v)
                $temps[$t.'a'][$v] = $this->_getMaxMin ($this->_extractTemps ($lat, $lon, $v, $t, self::MODEL_HADCM3M2), $v);
        return $temps;
        
    }
    
    function _getMaxMin ($tempArr, $v = self::TMAX_VAR) {
        sort ($tempArr, SORT_NUMERIC);
        return ($v == self::TMAX_VAR) ? $tempArr[11] : $tempArr[0];
    }
    
    function __construct () {
        $this->dbroot = dirname (__FILE__) . '/';
        exec ("ls " . $this->dbroot, $this->files);
    }
    
    function _extractTemps ($lat, $lon, $varname, $timename, $modelname) {
        $file = $this->_genDataFileName ($varname, $timename, $modelname);
        $cmd = sprintf (self::EXTRACT_COMMAND, $lon, $lat, $varname, $this->dbroot . $file);
//         die ($cmd . "\n");
         exec ($cmd, $r);
        $r = implode("\n", $r);
        //echo ($cmd);
        $ts = $this->_getTempsFromOutput  ($r);
        
        return $ts;
    }
    
    function _getTempsFromOutput ($strin) {
        $expr = "/\s(\d+\.\d+\s+){12}/";
        if (preg_match ($expr, $strin, $matches) == 0)
            return false;
        preg_match_all ("(\d+\.\d+)", $matches[0], $matches);
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



?>