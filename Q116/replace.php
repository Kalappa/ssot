<?php

if (sizeof($argv) < 4) {
    print "usage: php replace.php Lookup.csv <second input> <result file>\n";
    exit();
}

$handle = @fopen($argv[1], "r");

if ($handle) {

    if (($buffer = fgets($handle, 4096)) !== false) {
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	$map[$line[1]][0]=$line[0];
	$map[$line[1]][1]=$line[2];
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

$handle = @fopen($argv[2], "r");
$output = @fopen($argv[3], "w");
if ($handle) {

    if (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	$count = sizeof($line);
	$line[$count+1] = "Parent Enduser Group";
	$line[$count+2] = "Vertical";
	fputcsv($output, $line);
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
#	print_r($line);
	if (array_key_exists($line[0], $map)){
#	    print "Found Match\n";
	    $line[$count+1]=$map[$line[0]][0];
	    $line[$count+2]=$map[$line[0]][1];
	} else {
	    $line[$count+1]=$line[0];
	    if (strcmp($line[6], "PARTNER DEPENDENT") == 0){
		$line[$count+2] = "OTHER VERTICALS";
	    }else {
		$line[$count+2]=$line[6];
	    }
	}
#	print "After Append \n";
#	print_r($line);
	fputcsv($output, $line);
    }
}

?>
