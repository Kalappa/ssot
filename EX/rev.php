<?php

$pattern = "/EX.*/";
$matches = preg_grep($pattern, file('ex.csv'));

foreach ($matches as $line) {
    $full = str_getcsv($line);
    $res[$full[2]]=$full[3];
}

$handle = @fopen($argv[1], "r");
print "Processing Input file: $argv[1]..\n";
$output = @fopen("result.csv", "w");
if ($handle) {

    if (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	$line[70] = "Contract Start Date";
	$line[71] = "Contract End Date";
	fputcsv($output, $line, ",");
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
#	fputs($output, $buffer);
	if (array_key_exists($line[10], $res)){
	    $line[10] = $res[$line[10]];
	    $line[12] = "CC";
	    $line[28] = "SVC";
	    # billing date : 51
	    $billingDate = 51;
	    $line[70] = $line[$billingDate];
	    $line[71] = date('m/d/Y', strtotime("+60 months", strtotime($line[$billingDate])));
#	    print "$line[70], $line[71]\n";
#	    $res_line = str_replace($line[10], $res[$line[10]], $buffer, $count);
#	    print ("Adding line : ".implode(', ',$line)."\n");
#	    fputs($output, implode(',',$line)."\n");	    
	    fputcsv($output, $line, ",");
	}
    }
}
?>
