#!/usr/bin/php
<?php
include ("drrc.php");
include ("util/drrcReport.php");
include ("util/drrcAppSpec.php");



$das = new drrcAppSpec ();
$csvIn = "homininfossils.csv";
$csv = new csvData ($csvIn);

echo "\n\nCrunching $csvIn...\n";

do {
    $row = $csv->current ();
    $result = $das->homininFossils (
        $row[$csv->getColumn ('lat')],
        $row[$csv->getColumn ('lon')],
        $row[$csv->getColumn ('name')],
        $row[$csv->getColumn ('found_ad')],
        $row[$csv->getColumn ('analysed_ad')],
        $row[$csv->getColumn ('age_bp')],
        $row[$csv->getColumn ('storage_temp_c')],
        $row[$csv->getColumn ('alt_ams')]
    );
    foreach ($result['values'] as $c => $v)
        $csv->setColumn ($c, $v);
        
    file_put_contents ($result['values']['reportFilename'], $result['report']);
    
    echo "Done " . $row[$csv->getColumn ('name')] . " & wrote " . $result['values']['reportFilename'] . " in " . $result['modelRunTimeSec'] . "s.\n";
    
}
while ($csv->next ());

$csvOut = "homininfossils_processed.csv";
$csv->export ($csvOut);

?>