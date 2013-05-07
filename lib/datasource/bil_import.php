<?php namespace ttkpl;

class bil_import extends RawImporter {

    const BIL_DATA_EXT = 'bil';
    const BIL_HDR_EXT = 'hdr';

    const YOFF_WTF = .4839999;
    const XOFF_WTF = 0;//.9999999;
        
    const INTL_ROUND = 7; // num decimal places to round to when calculating offsets etc. when XDIM or YDIM are irrational

    private $dbroot = ''; // here be folders containing data and header files
    public $files = array(); // here be a list of files/folders in dbroot
    public $currentHeader = array (); // key=>val array of .hdr file for use computing byte offsets etc.
    private $resolution = null;
    private $headerCache = array ();
    public $error = array ();

    function __construct ($path, $resolution) {
        $this->resolution = $resolution;
        $this->dbroot = TTKPL_PATH . "data/$path/";
        exec ("find " . $this->dbroot . " -type f", $this->files);
    }
    
    function loadDB ($varname, $timename, $res = null, $ext = null, $monthNo = '') {

        if (!isset ($this->headerCache[$varname])) $this->headerCache[$varname] = array ();
        if (!isset ($this->headerCache[$varname][$timename])) $this->headerCache[$varname][$timename] = array ();
        if (isset ($this->headerCache[$varname][$timename][$res])) return $this->headerCache[$varname][$timename][$res];
        
        $hdrfile = $this->_genDataFileName ($varname, $timename, $res = null, self::BIL_HDR_EXT, $monthNo = '');
        $datafile = $this->_genDataFileName ($varname, $timename, $res = null, self::BIL_DATA_EXT, $monthNo = '');
        $hdrlines = \explode("\n", file_get_contents ($hdrfile));
        $header = array ();
        
        foreach ($hdrlines as $hl)
            if (preg_match ('/^(\w+)\s+([\w.+-]+)$/ims', trim($hl), $matches) > 0)
                $header[$matches[1]] = $matches[2];
        
        //print_r ($header);

        $ch = count ($header);
        if (isset  ($header['LAYOUT']) && $header['LAYOUT'] != 'BIL') {
            $this->error[] = "Only BIL format is supported at this time (BIP and BSQ to come later if needed).";
        }
        elseif ($ch > 0) {
            $header["DATA_FILE"] = $datafile;
            $headerCache[$varname][$timename][$res] = $header;
            $this->currentHeader = $header;
            return $ch;
        }
        return false;
    }
    function _isRealLat ($lat) {


        $zero = $this->currentHeader['ULYMAP'];
        $base = $lat - $zero;
        $a = round (fmod ($base, $this->currentHeader['YDIM']), self::INTL_ROUND);
        $b = ($lat >= $this->currentHeader['MinY'] && $lat <= $this->currentHeader['MaxY']);
        //debug ("isRealLat($lat) err=$a");
        
        if ($a == 0 && $b) return true;
        return false;
    }
    function _isRealLon ($lon) {
        $zero = $this->currentHeader['ULXMAP'];
        $base = $lon - $zero;
        $a = round (fmod ($base, $this->currentHeader['XDIM']), self::INTL_ROUND);
        $b = ($lon >= $this->currentHeader['MinX'] && $lon <= $this->currentHeader['MaxX']);
        //debug ("isRealLon($lon) err=$a");

        if ($a == 0 && $b) return true;
        return false;
    }
    function _getBILByteOffset($lat, $lon, $bandNo = 0) {
        if (!$this->_isRealLat($lat) || !$this->_isRealLon($lon)) return false;
        return $this->_latToBILDPO ($lat)  +
               $this->_lonToBILDPO ($lon, $bandNo);
    }
    /**
     * Convert latitude to a Y axis row start byte offset (top left 0)
     * @param <type> $lat decimal degrees (must be real data point)
     */
    // @FIXME!
    function _latToBILDPO ($lat) {
        //print_r ($this->currentHeader);
        $a = (round ( ($this->currentHeader['ULYMAP'] - $lat) / $this->currentHeader['YDIM'] ) )
               * $this->currentHeader['TOTALROWBYTES'];
        $max = ($this->currentHeader['TOTALROWBYTES'] * $this->currentHeader['NROWS']) - ($this->currentHeader['NBITS'] / 8);
        if ($a < 0) {
            $this->error[] = "Insane offset $a from lat: $lat, using 0.";
            $a = 0;
        }
        elseif ($a > $max) {
            $this->error[] = "Insane offset $a from lat: $lat, using $max.";
            $a = $max;
        }
        return $a;
    }
    // @FIXME!
    function _lonToBILDPO ($lon, $bandNo = 0) {
        $a = (
            (round ( ($lon - $this->currentHeader['ULXMAP']) / $this->currentHeader['XDIM'] ) -1) *
            ($this->currentHeader['NBITS']/8)
        ) + ($bandNo * $this->currentHeader['BANDROWBYTES']);
        return $a;
    }
    function _readNumFromFile ($filename, $offset, $bytes = null) {
        if ($bytes === null) $bytes = $this->currentHeader['NBITS'] / 8;
        $fh = fopen ($filename, 'r');
        fseek ($fh, $offset);
        $val = $this->_decodeBinary(fread($fh, $bytes));
        fclose ($fh);
        return $val;
    }
    /**
     * Decodes 1, 2 or 4 bytes of data into an int
     * @param string $str binary data to decode
     * @param bool $signed or unsigned (worldclim is signed)
     * @return int
     */
    function _decodeBinary ($str, $signed = true) {
        switch (strlen ($str)) {
            case 1:
                $p = ($signed) ? 'c' : 'C'; break;
            case 2:
                $p = ($signed) ? 's' : 'S'; break;
            case 4:
                $p = ($signed) ? 'l' : 'L'; break;
        }
        //return array_shift (unpack ("s", $str));
        return array_shift (unpack ($p, $str));
    }
    function read ($lat, $lon) {
        return $this->_readNumFromFile ($this->currentHeader['DATA_FILE'], $this->_getBILByteOffset($lat, $lon));
    }

    /**
     *
     * @param <type> $tempArr array of monthly temperatures
     * @param <type> $v self::T(MIN|MAX|MEAN)_VAR determines processing of temps array
     * @return <type> the max, min or mean of values in the temps array according to the source var name or the whole array if unknown varname.
     */
    // @TODO refactor this into worldclim_i_n as this class shouldn't be aware of var names etc.
    function _getMaxMinMeanByVarName ($tempArr, $v = self::TMAX_VAR) {
        $tempArr = (array) $tempArr;
        sort ($tempArr, SORT_NUMERIC);
        switch ($v) {
            case worldclim::TMAX_VAR:
                return $tempArr[11];
            case worldclim::TMIN_VAR:
                return $tempArr[0];
            case worldclim::TMEAN_VAR:
                return array_sum($tempArr) / count ($tempArr);
        }
        return $tempArr[0];
    }
    
    
    /**
     * Finds the nearest two latitudes which are real datapoints, only the original lat if it is real.
     * @param float $lat 
     * @return array() of 1 or 2 lat vals
     */
    function _nearestRealLats ($lat) {
        
        if ($this->_isRealLat($lat)) return array ($lat);

        $ll = floor ((($lat - $this->currentHeader['ULYMAP']) / $this->currentHeader['YDIM']) * $this->currentHeader['YDIM']) + $this->currentHeader['ULYMAP'];
        $lh = ceil ((($lat - $this->currentHeader['ULYMAP']) / $this->currentHeader['YDIM']) * $this->currentHeader['YDIM']) + $this->currentHeader['ULYMAP'];

        if (!$this->_isRealLat($ll)) {
            debug ("nearest real low lat to $lat is not $ll");
        }
        if (!$this->_isRealLat($ll)) {
            debug ("nearest real high lat to $lat is not $lh");
        }

        return array ($ll, $lh);
    }
    function _nearestRealLons ($lon) {
        if ($this->_isRealLon($lon)) return array ($lon);

        $ll = (floor (($lon + $this->currentHeader['ULXMAP']) / $this->currentHeader['XDIM']) * $this->currentHeader['XDIM']) - $this->currentHeader['ULXMAP'];
        $lh = (ceil (($lon + $this->currentHeader['ULXMAP']) / $this->currentHeader['XDIM']) * $this->currentHeader['XDIM']) - $this->currentHeader['ULXMAP'];

        if (!$this->_isRealLon($ll)) {
            //debug ("nearest real low lon to $lon is not $ll");
        }
        if (!$this->_isRealLon($lh)) {
            //debug ("nearest real high lon to $lon is not $lh");
        }
        //debug ("$ll > $lon > $lh");
        return array ($ll, $lh);
    }
    function nearestLatLons ($lat, $lon) {
        $ys = $this->_nearestRealLats($lat);
        $xs = $this->_nearestRealLons($lon);
        $o = array ();
        foreach ($ys as $y) foreach ($xs as $x)
            $o[] = array ('lat' => $y, 'lon' => $x);
        return $o;
    }

    // copied from pmip2

    function _genDataFileName ($varname, $timename, $res = null, $ext = null, $monthNo = '') {
        if ($res === null) $res = $this->resolution;
        if ($ext === null) $ext = self::BIL_DATA_EXT;

        $expr = "/^.*?\/$timename\/{$varname}_{$res}_bil\/{$varname}{$monthNo}\.{$ext}$/";
        
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

}

?>