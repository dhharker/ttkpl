<?php namespace ttkpl;

class bil_import {

    const BIL_DATA_EXT = 'bil';
    const BIL_HDR_EXT = 'hdr';

    private $dbroot = ''; // here be folders containing data and header files
    private $files = array(); // here be a list of files/folders in dbroot

    function __construct ($path) {
        $this->dbroot = TTKPL_PATH . "data/$path/";
        exec ("ls " . $this->dbroot, $this->files);
    }

    function _readHdr () {

    }

}

?>