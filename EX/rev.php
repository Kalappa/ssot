<?php

$pattern = "/EX.*/";
$matches = preg_grep($pattern, file('ex.csv'));

foreach ($matches as $line) {
    $full = str_getcsv($line);
    $res[$full[2]]=$full[3];
}

$handle = @fopen("detail.csv", "r");
$output = @fopen("result.csv", "w");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
	$line = str_getcsv($buffer);
	fputs($output, $buffer);
	if (array_key_exists($line[10], $res)){
	    $line[10] = $res[$line[10]];
	    $line[12] = "CC";
	    $line[28] = "SVC";
	    # billing date : 47
	    $line[42] = $line[47];
	    $line[43] = date('m/d/Y', strtotime("+60 months", strtotime($line[47])));
#	    $res_line = str_replace($line[10], $res[$line[10]], $buffer, $count);
#	    print ("Adding line : ".implode(', ',$line)."\n");
#	    fputs($output, implode(',',$line)."\n");	    
	    fputcsv($output, $line, ",");
	}
    }
}
?>
