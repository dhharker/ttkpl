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
    $csvIn = $opts['f'];

$csv = new csvData ($csvIn);

$detailFolder = dirname(realpath ($csvIn)) . '/' . basename ($csvIn, '.csv');
if (!is_dir($detailFolder) && !mkdir ($detailFolder))
    die ("Couldn't create output directory $detailFolder\n");

$id_processed = array (); // keep track of which 'id'+'sample' col vals have been used
$seq_id = 1;

echo "Loaded $csvIn - starting crunchage...\n";

// Iterate each row and · check sanity · calculate results · log to text file · append to new CSV
do {
    $row = $csv->current ();
    $ok = TRUE; $error = '';
    // check sanity

    // unique identifier
    if (strlen ($row[$csv->getColumn ('id')]) == 0) {
        $csv->setColumn('id', $seq_id);
    }
    $sample_identifier = $row[$csv->getColumn ('sample')] . '-' . $row[$csv->getColumn ('id')];
    $sample_identifier = preg_replace ("/[^a-z0-9\s]/", '-', $sample_identifier);
    if (isset ($id_processed [$sample_identifier])) {
        $n = 0;
        while (isset ($id_processed [$sample_identifier . '-' . $n])) {
            $n++;
        }
        $sample_identifier .= '-' . $n;
    }
    echo "Processing $sample_identifier...\n";




    // do maths
    // check it worked
    // write txt
    // append to csv



    if ($row[$csv->getColumn ('lat_dec')] + $row[$csv->getColumn ('lon_dec')] > 0.0 && is_numeric ($row[$csv->getColumn ('id')])) {
        $result = $das->olSamples (
            $row[$csv->getColumn ('lat_dec')],
            $row[$csv->getColumn ('lon_dec')],
            $row[$csv->getColumn ('id')]  . '_' . $row[$csv->getColumn ('site')],
            1980, // yr found
            2012, // yr analysed
            //$row[$csv->getColumn ('found_ad')],
            //$row[$csv->getColumn ('analysed_ad')],
            $row[$csv->getColumn ('age_bp')],
            16, // storage temp/C
            //$row[$csv->getColumn ('storage_temp_c')],
            $row[$csv->getColumn ('alt_ams')],
            null,
            'midden' // $row[$csv->getColumn ('burial')]
        );
        foreach ($result['values'] as $c => $v)
            $csv->setColumn ($c, $v);

        file_put_contents ($result['values']['reportFilename'], $result['report']);

        echo "Done " . $row[$csv->getColumn ('name')] . " & wrote " . $result['values']['reportFilename'] . " in " . $result['modelRunTimeSec'] . "s.\n";
    }
    $seq_id++;
}
while ($csv->next ());

?>
