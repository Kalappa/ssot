<?php
error_reporting( error_reporting() & ~E_NOTICE );
$money = NULL;
$peug = NULL;
$eu_peug = NULL;
$eu_peug_CAPS = NULL;

$Non_DR_Str = array("CREDIT", "CUSTOM", "DR-1", "DS", "IMMEDIATE", "RETURNS", "POS", "NS", "unknown");

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

function verify_revenue_adjustment_new($res){

    global $Non_DR_Str;

    generate_money_pivot_new($res);
    
    $handle = @fopen("final.csv", "w+");
  
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
	$P4_Reporting_Ship_to_GEO = $list[3];
	$Revrec_Net = floatval(str_replace(',', '', $list[12]));
	$Segment = $list[5];
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
	$i = 1;
	fputcsv($handle,$list);
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

function merge_ssot($quarter="Q115") {

    global $res;
    global $peug;
    global $eu_peug;
    global $eu_peug_CAPS;
    $peg = NULL;

    #we assume "product.csv comes first :(

    $files = array("Product.csv", "Service.csv");

    $header= array("Parent Enduser Group","Rev_Rec_QTR","SO Channel Code","P4 Reporting Ship to GEO","Category","Segment","Vertical","Sub Vertical","Reportable Business","Product Family","Product Line Code","Product Line Desc","Revrec Net\$","Revrec Cost\$","VertRev_Final","Revrec Type");
    
    foreach ($files as $filename) {
	if ($filename == "Product.csv"){
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
			    if (empty($PEUG) && (strcasecmp($PEU, "unknown") == 0)) {
				if (!empty($EU)){
                                    # Exception 1: PEG is Empty, EUG is unknown
				    print "Exception 1,".$buffer."\n";
				    $peg[$i] = $eu_peug[$EU_CAPS];
				} else {
				    # Exception 2: PEG is Empty, EUG is unknown, EU is Empty
				    print "Exception 2,".$buffer."\n";
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
		      default:
			$res[$i][] = "To be filled";
		    }
		}else {
		    $res[$i][] = $list[$index[$hdr_str]];
		}   
	    }
	}    
    }
}
eu_peug();
peug_vert();
merge_ssot();
verify_revenue_adjustment_new($res);
?>
