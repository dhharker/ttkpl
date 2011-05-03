<?php



class csvData implements Iterator {
    
    public $filename = NULL;
    public $data = array ();
    public $indexedData = array ();
    public $titlesRow1 = TRUE;
    public $titles = array (NULL);
    
    function __construct ($filename, $tr1 = TRUE) {
        return $this->_init ($filename, $tr1);
    }
    function _init ($filename, $tr1) {
        
        $this->titlesRow1 = ($tr1) ? TRUE : FALSE;
        $this->filename = $filename;
        $csv = FALSE;
        if (!file_exists ($filename))
            throw new exception ("Couldn't find file: " . $filename);
        elseif (!$fh = fopen ($filename, 'r'))
            throw new exception ("Couldn't open " . $filename);
        else {
            
            while (($row = fgetcsv ($fh)) !== FALSE) {
                $this->data[] = $row;
                $this->indexedData[$row[0]] = $row[1];
                
            }
            
            if ($this->titlesRow1) {
                $this->titles = array_shift ($this->data);
                $this->rewind ();
            }
            
            $this->rewind ();
            
            
            
            return true;
        }
        
        return false;
        
    }
    
    function export ($filename) {
        if (file_exists ($filename) && !unlink ($filename))
            throw new exception ("Couldn't remove existing output file: " . $filename . " (check permissions).");
        elseif (!$fh = fopen ($filename, 'w'))
            throw new exception ("Couldn't open " . $filename . " for writing.");
        else {
            if ($this->titlesRow1) {
                fputcsv ($fh, $this->titles);
            }
            $this->rewind();
            do {
                fputcsv ($fh, $this->current());
            } while ($this->next ());
            fclose ($fh);
            $this->rewind();
        }
    }
    
    function addColumn ($name) {
        if ($this->getColumn ($name) === FALSE) {
            $nci = max (array_keys ($this->titles)) + 1;
            $this->titles[$nci] = $name;
            return $nci;
        }
        else
            return FALSE;
    }
    function getColumn ($name) {
        if (!isset ($this->colInd[$name])) {
            $i = array_search ($name, $this->titles);
            if ($i !== FALSE)
                $this->colInd[$name] = $i;
            else
                return FALSE;
        }
        return $this->colInd[$name];
    }
    function setColumn ($name, $value) {
        if ($this->getColumn ($name) === FALSE)
            $this->addColumn ($name);
        $this->data[$this->key()][$this->getColumn ($name)] = $value;
    }
    
    function rewind () {
        reset ($this->data);
    }
    function current () {
        return current ($this->data);
    }
    function key () {
        return key ($this->data);
    }
    function next () {
        return next ($this->data);
    }
    function valid () {
        return !in_array ($this->current (), array (NULL, FALSE));
    }
    
}



?>