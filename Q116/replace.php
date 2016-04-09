<?php

$handle = @fopen("Lookup.csv", "r");

if ($handle) {

    if (($buffer = fgets($handle, 4096)) !== false) {
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	$map[$line[1]]=$line[0];
    }
}

if (!function_exists('str_putcsv')) {
    function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
        $fp = fopen('php://temp', 'r+b');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = rtrim(stream_get_contents($fp), "\n");
        fclose($fp);
        return $data;
    }
}
#print_r($map);

$handle = @fopen("Q415.csv", "r");
$output = @fopen("Q415_Res.csv", "w");
if ($handle) {

    if (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	$line[14] = "New PEG";
	fputcsv($output, $line);
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
#	print_r($line);
	if (array_key_exists($line[0], $map)){
	    print "Found Match\n";
	    $line[14]=$map[$line[0]];
	} else {
	    $line[14]=$line[0];
	}
	print "After Append \n";
#	print_r($line);
	fputcsv($output, $line);
    }
}

?>
