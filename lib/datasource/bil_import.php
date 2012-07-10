<?php namespace ttkpl;

class bil_import extends RawImporter {

    const BIL_DATA_EXT = 'bil';
    const BIL_HDR_EXT = 'hdr';
    

    private $dbroot = ''; // here be folders containing data and header files
    public $files = array(); // here be a list of files/folders in dbroot
    public $currentHeader = array (); // key=>val array of .hdr file for use computing byte offsets etc.
    private $resolution = null;
    private $headerCache = array ();

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
        if ($ch > 0) {
            $header["DATA_FILE"] = $datafile;
            $headerCache[$varname][$timename][$res] = $header;
            $this->currentHeader = $header;
            return $ch;
        }
        return false;
    }
    function _isRealLat ($lat) {
        return ( fmod (($lat +  90 - (0.5 * $this->currentHeader['YDIM'])), floatval($this->currentHeader['YDIM'])) == 0 &&
                 ($lat >= $this->currentHeader['MinY'] && $lat <= $this->currentHeader['MaxY'])         ) ? TRUE : FALSE;
    }
    function _isRealLon ($lon) {
        return ( fmod (($lon +  180 - (0.5 * $this->currentHeader['XDIM'])), floatval ($this->currentHeader['XDIM'])) == 0 &&
                 ($lon >= $this->currentHeader['MinX'] && $lon <= $this->currentHeader['MaxX'])         ) ? TRUE : FALSE;
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
    function _latToBILDPO ($lat) {
        print_r ($this->currentHeader);
        return  (($lat +  90 - (.5 * $this->currentHeader['YDIM'])) / $this->currentHeader['YDIM']) * $this->currentHeader['TOTALROWBYTES'];
    }
    function _lonToBILDPO ($lon, $bandNo = 0) {
        return ((($lon + 180 - (.5 * $this->currentHeader['XDIM'])) / $this->currentHeader['XDIM']) * ($this->currentHeader['NBITS']/8)) + ($bandNo * $this->currentHeader['BANDROWBYTES']);
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
     *
     * @param string $str binary data to decode
     * @param bool $signed or unsigned
     * @return int
     */
    function _decodeBinary ($str, $signed = true) {
        switch (strlen ($str)) {
            case 1:
                $p = ($signed) ? 'c' : 'C'; break;
            case 2:
                $p = ($signed) ? 's' : 'S'; break;

        }
        return array_shift (unpack ("s", $str));
    }
    function read ($lat, $lon) {
        return $this->_readNumFromFile ($this->currentHeader['DATA_FILE'], $this->_getBILByteOffset($lat, $lon));
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