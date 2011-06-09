#!/usr/bin/php
<?php


/**
 * CLI script for processing values in a spreadsheet
 *
 * @author david
 */

require_once 'lib/ttkpl.php';

echo "\nTTKPL CLI David Harker 2011\n";

// predict.php -f input.csv [-o output.csv] -v (verbose) -h (help)
$opts = getopt ("f:o::vh");

if (isset ($opts['h']) || !isset ($opts['f'])) {
    echo "Usage: " . __FILE__ . " -f input.csv [-o output.csv] -v (verbose) -h (help)\n";
    exit ();
}


$verbose = isset ($opts['h']);

if (!file_exists ($opts['f']))
    die ("Couldn't find input file {$opts['f']}\n");
else
    $csvInFile = $opts['f'];



?>
