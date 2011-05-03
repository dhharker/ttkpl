#!/usr/bin/php
<?php
include ("drrc.php");
include ("util/drrcReport.php");
include ("util/drrcAppSpec.php");



$das = new drrcAppSpec ();
$csvIn = "europe.csv";
$csv = new csvData ($csvIn);

echo "\n\nCrunching $csvIn...\n";

do {
    $row = $csv->current ();
    $result = $das->europeSamples (
        $row[$csv->getColumn ('lat_dec')],
        $row[$csv->getColumn ('lon_dec')],
        $row[$csv->getColumn ('Individual')]  . '_' . $row[$csv->getColumn ('Location')],
        2000, // yr found
        2009, // yr analysed
        //$row[$csv->getColumn ('found_ad')],
        //$row[$csv->getColumn ('analysed_ad')],
        $row[$csv->getColumn ('age_bp')],
        16, // storage temp/C
        //$row[$csv->getColumn ('storage_temp_c')],
        $row[$csv->getColumn ('alt_ams')]
    );
    foreach ($result['values'] as $c => $v)
        $csv->setColumn ($c, $v);
        
    file_put_contents ($result['values']['reportFilename'], $result['report']);
    
    echo "Done " . $row[$csv->getColumn ('name')] . " & wrote " . $result['values']['reportFilename'] . " in " . $result['modelRunTimeSec'] . "s.\n";
    
}
while ($csv->next ());

$csvOut = "europe_processed.csv";
$csv->export ($csvOut);

?>