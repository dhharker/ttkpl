<?php namespace ttkpl;

class bil_import extends RawImporter {

    const BIL_DATA_EXT = 'bil';
    const BIL_HDR_EXT = 'hdr';
    

    private $dbroot = ''; // here be folders containing data and header files
    public $files = array(); // here be a list of files/folders in dbroot
    private $resolution = null;

    function __construct ($path, $resolution) {
        $this->resolution = $resolution;
        $this->dbroot = TTKPL_PATH . "data/$path/";
        exec ("ls -r" . $this->dbroot, $this->files);
        print_r ($this->files);
    }

    function _readHdr () {

    }
    
    // copied from pmip2

    function _genDataFileName ($varname, $timename, $res = null, $ext = null) {
        if ($res === null) $res = $this->resolution;
        if ($ext === null) $ext = self::BIL_DATA_EXT;
        $expr = "/^\/$timename\/{$varname}_{$res}_bil\/{$varname}\.{$ext}$/";

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