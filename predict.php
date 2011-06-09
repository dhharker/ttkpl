<?php


/**
 * CLI script for processing values in a spreadsheet
 *
 * @author david
 */


// predict.php -f input.csv [-o output.csv] -v (verbose) -h (help)
$opts = getopts ("f:o::vh");

if (isset ($opts['h'])) {
    echo __FILE__ . " -f input.csv [-o output.csv] -v (verbose) -h (help)\n";
    exit ();
}

$verbose = isset ($opts['h']);

if (!file_exists ($opts['f'])) {
    die ("Couldn't find input file {$opts['f']}\n");
}


?>
