<?php
error_reporting( error_reporting() & ~E_NOTICE );
$money = NULL;
$peug = NULL;
$eu_peug = NULL;
$eu_peug_CAPS = NULL;
$glcode_map = NULL;
$mdf = NULL;
$header = NULL;
$DEBUG=1;
$MOAT = NULL;
$fileCount = 0;

$Non_DR_Str = array("CREDIT", "CUSTOM", "DR-1", "DS", "IMMEDIATE", "RETURNS", "POS", "NS", "unknown");

function genMDF($quarter="Q115", $mdfFile = "MDF.csv", $outputFile = "final.csv"){

    global $mdf;

    $handle = @fopen($mdfFile, "r");

    $handleWriter = @fopen($outputFile, "a+");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }

    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$mdfInput[$list[0]]=$list[1];
    }
#    print_r($mdfInput);
    foreach ($mdf as $region => $sublist1){
	foreach($sublist1 as $bu => $sublist2){
	    foreach ($sublist2 as $vertical => $sublist3){
		foreach ($sublist3 as $subvertical => $value) {
		    $sum[$region." - ".$bu] += $value;
		}
	    }
	}
    }
#    print_r($mdf);
    foreach ($mdf as $region => $sublist1){
    	foreach($sublist1 as $bu => $sublist2){
    	    foreach ($sublist2 as $vertical => $sublist3){
    		foreach ($sublist3 as $subvertical => $value) {
		    $reg_bu = $region." - ".$bu;
		    $adj = $mdf[$region][$bu][$vertical][$subvertical]*$mdfInput[$reg_bu]/($sum[$reg_bu]-$mdfInput[$reg_bu]);
    		    $mdf_adj[$region][$bu][$vertical][$subvertical] = $adj;
		    if ($vertical != "PARTNER DEPENDENT"){
			$partAdjust[$reg_bu] += $adj;
		    }
    		}
    	    }
    	}
    }
#    print_r($mdf_adj);
    $sum=0;
    $str= array("MDF_PartAdv_Adj",$quarter,"MDF_PartAdv_Adj",4,"Product","MDF_PartAdv_Adj",4,4,4,"MDF_PartAdv_Adj","MDF_PartAdv_Adj","MDF_PartAdv_Adj",0,"MDF_PartAdv_Adj",4,"MDF_PartAdv_Adj", "MDF_PartAdv_Adj");
    foreach ($mdf_adj as $region => $sublist1){
    	foreach($sublist1 as $bu => $sublist2){
    	    foreach ($sublist2 as $vertical => $sublist3){
    		foreach ($sublist3 as $subvertical => $value) {
		    $str[3] = $region;
		    $str[6] = $vertical;
		    $str[7] = $subvertical;
		    $str[8] = $bu;
		    if ($vertical == "PARTNER DEPENDENT"){
			if ($subvertical != "OTHER"){
			    print "Raise Hell\n";
			    exit;
			}
			$str[14] = -1 * $partAdjust[$region." - ".$bu]*1000000;
			print "PARTNER: ".$str[14]."\n";
		    } else { 
			$str[14] = $value*1000000;
		    }
		    $sum += $str[14];
		    fputcsv($handleWriter,$str);		   
    		}
    	    }
    	}
    }
    print "MDF Sum = ".intval($sum)."\n";
    fclose($handle);
    fclose($handleWriter);
}

function adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO) {
    
    global $money;

    $Non_DR = $money[$Product_Family][$Segment][$P4_Reporting_Ship_to_GEO]["NON-DR"];

    $DR = $money[$Product_Family][$Segment][$P4_Reporting_Ship_to_GEO]["DR"];
    if ($Revrec_Net == 0){
            return 0;
    }
    if ($Category == "Product" && $SO_Channel_Code == "INDIRECT-DISTI" && $Non_DR == 0){
            return $Revrec_Net;
    } else {
        if ($Category == "Product"){
            if ($SO_Channel_Code == "INDIRECT-DISTI") {
                if ($Revrec_Type == "DR") {
                    return 0;
                } else {
                    return ($Revrec_Net + $Revrec_Net*$DR/$Non_DR);
                }
            } else {
                return $Revrec_Net;
            }
        } else {
            return $Revrec_Net;
        }
    }
}
                            
function generate_money_pivot($filename = "test1.csv") {
    
    global $money;
    global $Non_DR_Str;

    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }

    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$Category = $list[0];
	if ($list[18] != "INDIRECT-DISTI"){
	    continue;
	}
	if ($Category != "Product"){
	    continue;
	}
	if ($list[3] == "DR") {
	    $Revrec_Type = $list[3];
	} else {
	    $Revrec_Type = str_replace($Non_DR_Str, "NON-DR", $list[3]);
	}
	$P4_Reporting_Ship_to_GEO = $list[17];
	$Revrec_Net = $list[24];
	$Segment = $list[26];
	$Product_Family=$list[32];
	$money[$Product_Family][$Segment][$P4_Reporting_Ship_to_GEO][$Revrec_Type] += floatval(str_replace(',', '', $Revrec_Net));
    }
    //    print_r($money);
}

function generate_money_pivot_new($res) {

    global $money;
    global $Non_DR_Str;

    $money = NULL;
    foreach ($res as $index => $list) {
	$Category = $list[4];
	if ($list[2] != "INDIRECT-DISTI"){
	    continue;
	}
	if ($Category != "Product"){
	    continue;
	}
	if ($list[15] == "DR") {
	    $Revrec_Type = $list[15];
	} else {
	    $Revrec_Type = str_replace($Non_DR_Str, "NON-DR", $list[15]);
	}
	$P4_Reporting_Ship_to_GEO = $list[3];
	$Revrec_Net = $list[12];
	$Segment = $list[5];
	$Product_Family=$list[9];
	$money[$Product_Family][$Segment][$P4_Reporting_Ship_to_GEO][$Revrec_Type] += floatval(str_replace(',', '', $Revrec_Net));
    }
    //    print_r($money);
}

function verify_revenue_adjustment_new($res, $outputFile = "final.csv"){

    global $Non_DR_Str;
    global $mdf;
    global $MOAT;

    generate_money_pivot_new($res);
    
    $handle = @fopen($outputFile, "a+");
  
    $NewRevenue=0;
    $OldRevenue=0;
    $i=0;
    foreach ($res as $list) {
	$Category = $list[4];
	$SO_Channel_Code = $list[2];
	if ($list[15] == "DR") {
	    $Revrec_Type = $list[15];
	} else {
	    $Revrec_Type = str_replace($Non_DR_Str, "NON-DR", $list[15]);
	}
	$Parent_Enduser_Group = $list[0];
	$Revrec_Qtr = $list[1];
	$So_Channel_Code = $list[2];
	$P4_Reporting_Ship_to_GEO = $list[3];
	$Category = $list[4];
	$Segment = $list[5];
	$Vertical = $list[6];
	$Sub_Vertical = $list[7];
	$Reportable_Business = $list[8];
	$Revrec_Net = floatval(str_replace(',', '', $list[12]));
	$Product_Family=$list[9];
	$OldRevenue += $Revrec_Net;
	$adjRev =  adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO);
	$NewRevenue += $adjRev;
	if ($i != 0 ){
	    $list[14] = $adjRev;
	}
	# if ($Revrec_Net != $adjRev){
	#     print " Revenue mismatch: ".implode(',', $list)."\n";
	# }
	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$P4_Reporting_Ship_to_GEO][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][0] += $Revrec_Net;
	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$P4_Reporting_Ship_to_GEO][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][1] += 1;
	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$P4_Reporting_Ship_to_GEO][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][2] += $adjRev;
	fputcsv($handle,$list);
	if ($list[3] == "North America" || $list[3] == "Latin America"){ 
	    $Region = "North America";
	}else {
	    $Region = $list[3];
	}
	$vertical = $list[6];
	$subvertical = $list[7];
	$bu = $list[8];
	if ($i != 0 ){
	    $mdf[$Region][$bu][$vertical][$subvertical] += $adjRev/1000000;
	}
	$i = 1;
    }
    fclose($handle);
    print "Old Revenue = ". round($OldRevenue,0)."\n";
    print "New Revenue = ". round($NewRevenue,0)."\n";
    return ($OldRevenue == $NewRevenue);
}


function verify_revenue_adjustment($filename = "test1.csv"){
    
    generate_money_pivot($filename);

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
        print "Unable to open file\n";
        exit;
    }
    $NewRevenue=0;
    $OldRevenue=0;
    
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$Category = $list[0];
	$SO_Channel_Code = $list[18];
	if ($list[3] == "DR") {
	    $Revrec_Type = $list[3];
	} else {
	    $Revrec_Type = str_replace($Non_DR, "NON-DR", $list[3]);
	}
	$P4_Reporting_Ship_to_GEO = $list[17];
	$Revrec_Net = floatval(str_replace(',', '', $list[24]));
	$Segment = $list[26];
	$Product_Family=$list[32];
	$OldRevenue += $Revrec_Net;
	$NewRevenue += adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO);
    }
    print "Old Revenue = ". $OldRevenue."\n";
    print "New Revenue = ". $NewRevenue."\n";
    return ($OldRevenue == $NewRevenue);
}

function gl_code($filename = "GLCode.csv"){
    
    global $glcode_map;


    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file\n";
	exit;
    }
    $buffer = fgets($handle, 4096);
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	if ($list[2] <> "??"){
	    $glcode_map[$list[0]][$list[1]][0] = $list[2];
	} else {
	    $glcode_map[$list[0]][$list[1]][0] = $list[3];
	}
    }
#    print_r($glcode_map);
}

function eu_peug($filename = "EU_PEUG.csv"){
    
    global $eu_peug;
    global $eu_peug_CAPS;

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file\n";
	exit;
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$eu_peug[$list[0]] = $list[1];
    }
}

function peug_vert($filename = "PEUG_VERT.csv"){

    global $peug;

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file\n";
	exit;
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$peug[$list[0]][0]=$list[1];
	$peug[$list[0]][1]=$list[2];
    }
}   

function merge_ssot($quarter="Q115", $files) {

    global $res;
    global $peug;
    global $eu_peug;
    global $eu_peug_CAPS;
    global $glcode_map;
    global $header;
    $peg = NULL;

    #we assume "product.csv comes first :(

    $handleException = @fopen("exception.csv", "w");
    
    if (!$handleException) {
	print "Unable to open file\n";
	exit;
    }

    $header= array("Parent Enduser Group","Rev_Rec_QTR","SO Channel Code","P4 Reporting Ship to GEO","Category","Segment","Vertical","Sub Vertical","Reportable Business","Product Family","Product Line Code","Product Line Desc","Revrec Net\$","Revrec Cost\$","VertRev_Final","Revrec Type", "Pulse", "P4 Reporting Ship to Country");
    
    foreach ($files as $filename) {
	if (strpos($filename,'Product') !== false) {
	    $product = 1;
	    $i=0;
	    $res[$i]=$header;
	}else {
	    $product = 0;
	}

	$handle = @fopen($filename, "r");
	
	if (!$handle) {
	    print "Unable to open file\n";
	    exit;
	}
	# Build index based on header of the current csv file
	$index = NULL;
	if (($buffer = fgets($handle, 4096)) !== false) {
	    $list = str_getcsv($buffer, ",", '"');
	    foreach ($header as $hdr_str) {
		$idx = array_search($hdr_str, $list);
		if ($idx) {
		    $index[$hdr_str] = $idx;
		} else {
		    $index[$hdr_str] = -1;
		}
	    }
	    // We do not want to use the PEG values from the SSOT, but redefine separately for FP&A
		$index["Parent Enduser Group"] = -1;
	}
	while (($buffer = fgets($handle, 4096)) !== false) {
	    $i++;
	    $list = str_getcsv($buffer, ",", '"');
	    if (empty(array_filter($list))){
		continue;
	    }
	    $glcode = $list[1];
	    $prodLineDesc = $list[23];
	    $country = $list[18];
	    foreach ($header as $hdr_str) {
		if ($index[$hdr_str] == -1){
		    switch ($hdr_str) {
			case "Category":
			    if ($product == 1) {
				$res[$i][] = "Product";
			    }else {	
				$res[$i][] = "Service";
			    }
			break;
		        case "Parent Enduser Group":
			    $PEUG = $list[6];
			    $PEUG_CAPS = strtoupper($list[6]);
			    $EU = $list[4];
			    $EU_CAPS = strtoupper($list[4]);
			    $PEU = $list[3];
			    $PEU_CAPS = strtoupper($list[3]);
			    # Check if the PEG is present in mapping, then use the corresponding group
			    # Check
			    if (ISSET($eu_peug[$PEUG_CAPS])){
				$peg[$i] = $eu_peug[$PEUG_CAPS];
			    } else if (ISSET($eu_peug[$PEUG])){
				$peg[$i] = $eu_peug[$PEUG];
			    } else if (ISSET($eu_peug[$PEU_CAPS]) && (strcasecmp($PEU_CAPS, "unknown") <> 0)){
				$peg[$i] = $eu_peug[$PEU_CAPS];
			    } else if (ISSET($eu_peug[$PEU]) && (strcasecmp($PEU, "unknown") <> 0)){
				$peg[$i] = $eu_peug[$PEU];
			    } else if (ISSET($eu_peug[$EU_CAPS]) && (strcasecmp($EU_CAPS, "unknown") <> 0)){
				$peg[$i] = $eu_peug[$EU_CAPS];
	    		    } else if (ISSET($eu_peug[$EU]) && (strcasecmp($EU, "unknown") <> 0)){
				$peg[$i] = $eu_peug[$EU];
	    		    } else {
				$peg[$i] = $PEUG_CAPS;
			    }
			    if (ISSET($glcode_map[$glcode][$prodLineDesc][0])){
				if (ISSET($eu_peug[$glcode_map[$glcode][$prodLineDesc][0]]) || (ISSET($eu_peug[strtoupper($glcode_map[$glcode][$prodLineDesc][0])]))) {
				    $peg[$i] = $eu_peug[strtoupper($glcode_map[$glcode][$prodLineDesc][0])];
				} else {
				    $peg[$i] = strtoupper($glcode_map[$glcode][$prodLineDesc][0]);
				}
			    } 
			    if (empty($PEUG) && (strcasecmp($PEU, "unknown") == 0)) {
				if (!empty($EU)){
                                    # Exception 1: PEG is Empty, EUG is unknown
				    if ($DEBUG){
					fwrite($handleException, "Exception1,".$buffer);
				    }
				    $peg[$i] = $eu_peug[$EU_CAPS];
				} else {
				    # Exception 2: PEG is Empty, EUG is unknown, EU is Empty
				    if ($DEBUG){
					fwrite($handleException, "Exception2,".$buffer);
				    }
				}
			    }
			    # if (!ISSET($eu_peug[$PEUG_CAPS]) && !ISSET($eu_peug[$PEUG]) && (strcasecmp($PEU, "unknown") == 0)) {				
			    # 	if (!empty($EU)){
                            #         # Exception 3: PEG is Not Found in Mapping, EUG is unknown
			    # 	    print "Exception 3,".$buffer."\n";
			    # 	    $peg[$i] = $eu_peug[$EU_CAPS];
			    # 	} else {
			    # 	    # Exception 4: PEG is Not Found in Mapping, EUG is unknown, EU is Empty
			    # 	    print "Exception 4,".$buffer."\n";
			    # 	}
			    # }
			    $res[$i][] = $peg[$i];
			break;
		        case "Vertical":
			    if (ISSET($peug[$peg[$i]])) {
				$res[$i][] = $peug[$peg[$i]][0];
			    } else {
				$res[$i][] = "PARTNER DEPENDENT";
			    }
			break;
		        case "Sub Vertical":
			    if (ISSET($peug[$peg[$i]])) {
				$res[$i][] = $peug[$peg[$i]][1];
			    } else {
				$res[$i][] = "OTHER";
			    }
			break;
		        case "Rev_Rec_QTR":
			    $res[$i][] = $quarter;
			break;
		        case "Revrec Type":
			    $res[$i][] = $list[0];
			break;
		      	case "Pulse": {
			    if (stripos($buffer, "pulse")){
				$res[$i][] = "Y";
			    } else {
				$res[$i][] = "N";
			    }
			}
			break;
		      	case "P4 Reporting Ship to Country": {
			    $res[$i][] = $country;
			    break;
			}
		      default:
			$res[$i][] = "To be filled";
		    }
		}else {
		    $res[$i][] = $list[$index[$hdr_str]];
		}   
	    }
	}    
    }
    fclose($handleException);
}
function genAggregatedTable($outputAggregateFile = "Aggregate.csv", $outputStandaloneFile){

    global $MOAT;
    global $fileCount;

    if ($fileCount == 0){
	$handle = @fopen($outputAggregateFile, "w+");
    }else {
	$handle = @fopen($outputAggregateFile, "a+");
    }
    if (! $handle) {
        print " Unable to Open File";
        exit;
    }

    $handleStandalone = @fopen($outputStandaloneFile, "w");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }

    if ($fileCount == 0) {
	$header= array("Parent Enduser Group","Rev_Rec_QTR","SO Channel Code","P4 Reporting Ship to GEO","Category","Segment","Vertical","Sub Vertical","Reportable Business","Product Family","Revrec Net\$","Revrec Cost\$","VertRev_Final");
	fputcsv($handle, $header);
	fputcsv($handleStandalone, $header);
    }
    foreach ($MOAT as $Parent_Enduser_Group => $list1){
	foreach ($list1 as $Revrec_Qtr => $list2){
	    foreach ($list2 as $So_Channel_Code => $list3){
		foreach ($list3 as $P4_Reporting_Ship_to_GEO => $list4){
		    foreach ($list4 as $Category => $list5){
			foreach ($list5 as $Segment => $list6){
			    foreach ($list6 as $Vertical => $list7) {
				foreach ($list7 as $Sub_Vertical => $list8){
				    foreach ($list8 as $Reportable_Business => $list9){
					foreach ($list9 as $Product_Family => $list10){
					    if (strcmp($Parent_Enduser_Group, "Parent Enduser Group") != 0) { 
						fputcsv($handle, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $P4_Reporting_Ship_to_GEO, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $list10[0], $list10[1], $list10[2]));
						fputcsv($handleStandalone, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $P4_Reporting_Ship_to_GEO, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $list10[0], $list10[1], $list10[2]));
					    }
					}
				    }
				}
			    }
			}
		    }
		}
	    }
	}
    }
    fclose($handle);
    fclose($handleStandalone);
}

function addPulseData($outputAggregateFile = "Aggregate.csv"){

    global $MOAT;
    $handle = @fopen($outputAggregateFile, "a+");
    $handlePulse = @fopen("PulseService.csv", "r");

    if (! $handle) {
        print " Unable to Open Aggregated File";
        exit;
    }

    if (! $handlePulse) {
        print " Unable to Open File PulseService";
        exit;
    }
    $buffer = fgets($handlePulse, 4096);
    while (($buffer = fgets($handlePulse, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	fputcsv($handle, $list);
    }
    fclose($handle);
    fclose($handlePulse);
}

$scrip = array_shift($argv);
foreach ($argv as $entry) {
    if ($entry != "." && $entry != "..") {
	$matches = NULL;
	preg_match('/\d\d(\d\d)\_(.*?)\_(.*?)\.csv/', $entry, $matches);
#	print_r($matches);
	if ($matches){
	    $fileList[$matches[2].$matches[1]][]=realpath($entry);
	}
    }
}
#print_r($fileList);
print "Genrating Mapings for Unknown Entries in SSOT ... \n";
gl_code("GLCode.csv");
print "Ingest the latest Parent Enguser Group Mappings ... \n";
eu_peug("EU_PEUG.csv");
print "Ingest the latest Parent Enguser Group to Vertical and Subvertical Mappings ... \n";
peug_vert("PEUG_VERT.csv");
print "Generating the final SSOT, w/ Adjuted Revenue Computation ... \n";

foreach ($fileList as $qtr => $files) {
    $output = "results/Aggregated_Q113_Q115.csv";
    $outputFinal = "results/".$qtr."_Final.csv";
    $outputAggregated = "results/".$qtr."_Aggregated.csv";
    merge_ssot($qtr, $files);
    print "$qtr: Applying Revenue Adjustment and Verifying the Total Revenue per Quarter ... \n";
    verify_revenue_adjustment_new($res, $outputFinal);
    genMDF($qtr,"MDF.csv",$outputFinal);
    genAggregatedTable($output, $outputAggregated);
    $fileCount++;
    $res = NULL;
    $money = NULL;
    $mdf = NULL;
    $MOAT = NULL;    
}

?>
