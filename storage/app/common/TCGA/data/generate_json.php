<?php

$studiesFile = 'tcga_studies.tsv';
$outputFile = 'tcga_clinical_variables.json';

if (!file_exists($studiesFile)) {
    fwrite(STDERR, "File not found: $studiesFile\n");
    exit(1);
}

$handle = fopen($studiesFile, 'r');
if (!$handle) {
    fwrite(STDERR, "Cannot open $studiesFile\n");
    exit(1);
}

$cohortIds = [];
while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    if (count($row) >= 2) {
        $cohortIds[] = $row[1];
    }
}
fclose($handle);

$result = [];

foreach ($cohortIds as $cohortId) {
    $pattern = "*{$cohortId}.csv";
    $files = glob($pattern);
    if (empty($files)) {
        continue;
    }
    $csvFile = $files[0];
    $csvHandle = fopen($csvFile, 'r');
    if (!$csvHandle) {
        continue;
    }
    $header = fgetcsv($csvHandle);
    $varIdx = array_search('variable', $header);
    $catIdx = array_search('category', $header);
    if ($varIdx === false || $catIdx === false) {
        fclose($csvHandle);
        continue;
    }
    while (($data = fgetcsv($csvHandle)) !== false) {
        $variable = $data[$varIdx];
        $category = $data[$catIdx];
        if (!isset($result[$cohortId][$variable])) {
            $result[$cohortId][$variable] = [];
        }
        if (!in_array($category, $result[$cohortId][$variable], true)) {
            $result[$cohortId][$variable][] = $category;
        }
    }
    fclose($csvHandle);
}

file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Exported to $outputFile\n";
