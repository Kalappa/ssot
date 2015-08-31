<?php
error_reporting( error_reporting() & ~E_NOTICE );
$money = NULL;
$peug = NULL;
$eu_peug = NULL;
$eu_peug_CAPS = NULL;
$glcode_map = NULL;
$pf_map = NULL;
$bu_map = NULL;
$mdf = NULL;
$header = NULL;
$DEBUG=1;
$MOAT = NULL;
$MO_MOAT = NULL;
$fileCount = 0;

$Non_DR_Str = array("CREDIT", "CUSTOM", "DS", "IMMEDIATE", "RETURNS", "POS", "NS", "unknown");

function genMDF($quarter="Q115", $mdfFile = "MDF.csv", $outputFile = "final.csv"){

    global $mdf;

    $handle = @fopen($mdfFile, "r");

    $handleWriter = @fopen($outputFile, "a+");

    if (! $handle) {
        print " Unable to Open File $mdfFile \n";
        return;
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
			//print "PARTNER: ".$str[14]."\n";
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

function genMDFAggregate($quarter="Q115", $mdfFile = "MDF.csv", $outputFile = "final.csv", $product = 0){

    global $mdf;

    $handle = @fopen($mdfFile, "r");

    $handleWriter = @fopen($outputFile, "a+");

    if (! $handle) {
        print " Unable to Open File: $outputFile \n";
        return;
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
    if ($product == 0) {
	$str= array("MDF_PartAdv_Adj",$quarter,"MDF_PartAdv_Adj",4,"Product","MDF_PartAdv_Adj",4,4,4,"MDF_PartAdv_Adj","MDF_PartAdv_Adj","MDF_PartAdv_Adj",4,"MDF_PartAdv_Adj");
	$vertIndex = 12;
    }else {
	$str= array("MDF_PartAdv_Adj",$quarter,"MDF_PartAdv_Adj",4,"Product","MDF_PartAdv_Adj",4,4,4,"MDF_PartAdv_Adj","MDF_PartAdv_Adj","MDF_PartAdv_Adj","MDF_PartAdv_Adj","MDF_PartAdv_Adj",4,"MDF_PartAdv_Adj");
	$vertIndex = 14;
    }
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
			$str[$vertIndex] = -1 * $partAdjust[$region." - ".$bu]*1000000;
			//print "PARTNER: ".$str[$vertIndex]."\n";
		    } else { 
			$str[$vertIndex] = $value*1000000;
		    }
		    $sum += $str[$vertIndex];
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
function generate_money_pivot_new($res) {

    global $money;
    global $Non_DR_Str;

    $money = NULL;
    foreach ($res as $index => $list) {
	$Category = $list[4];
	if ($list[15] == "DR" || $list[15] == "DR-1") {
	    $Revrec_Type = "DR";
	    if ($list[2] == ""){
		$list[2] = "INDIRECT-DISTI";
	    }
	} else {
	    $Revrec_Type = str_replace($Non_DR_Str, "NON-DR", $list[15]);
	}
	if ($list[2] != "INDIRECT-DISTI"){
	    continue;
	}
	if ($Category != "Product"){
	    continue;
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
    global $MO_MOAT;

    generate_money_pivot_new($res);
    
    $handle = @fopen($outputFile, "w+");
  
    $NewRevenue=0;
    $OldRevenue=0;
    $i=0;
    foreach ($res as $list) {
	$Category = $list[4];
	$SO_Channel_Code = $list[2];
	if ($list[15] == "DR" || $list[15] == "DR-1") {
	    $Revrec_Type = "DR";
	    if ($list[2] == ""){
		$SO_Channel_Code = "INDIRECT-DISTI";
		$list[2] = "INDIRECT-DISTI";
	    }
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
	$Product_Line_Code = $list[10];
	$Product_Line_Desc = $list[11];
	$P4_Reporting_Country = $list[17];
	$Theater = $list[18];
	$Revrec_Cost = $list[13];
	$OldRevenue += $Revrec_Net;
	$adjRev =  adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO);
	$NewRevenue += $adjRev;
	if ($i != 0 ){
	    $list[14] = $adjRev;
	}

	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][0] += $Revrec_Net;
	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][1] += $Revrec_Cost;
	$MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][2] += $adjRev;

	$MO_MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][$Product_Line_Desc][$Product_Line_Code][0] += $Revrec_Net;
	$MO_MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][$Product_Line_Desc][$Product_Line_Code][1] += $Revrec_Cost;
	$MO_MOAT[$Parent_Enduser_Group][$Revrec_Qtr][$So_Channel_Code][$Theater][$Category][$Segment][$Vertical][$Sub_Vertical][$Reportable_Business][$Product_Family][$Product_Line_Desc][$Product_Line_Code][2] += $adjRev;

	fputcsv($handle,$list);
	if ($list[3] == "North America" || $list[3] == "Latin America"){ 
	    $Region = "AMER";
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

function gl_code($filename = "GLCode.csv"){
    
    global $glcode_map;


    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file $filename \n";
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

function pfgroup_map($filename = "ssot_all/PFGrouping.csv"){
    
    global $pf_map;
    global $bu_map;

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file $filename\n";
	exit;
    }
    $buffer = fgets($handle, 4096);
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$oldProdFamily = $list[0];
	$oldProdDesc = $list[1];
	$newProdDesc = $list[2];
	$newProdFamily = $list[3];
	$newBu = $list[4];
	# Index 0 is the new product line desc
	$pf_map[$oldProdFamily][$oldProdDesc][0]=$newProdDesc;
	# Index 1 is the new product family
	$pf_map[$oldProdFamily][$oldProdDesc][1]=$newProdFamily;
	# Index 1 is the new product family
	$bu_map[$oldProdFamily]=$newBu;
    }
#    print_r($pf_map);
}

function eu_peug($filename = "EU_PEUG.csv"){
    
    global $eu_peug;
    global $eu_peug_CAPS;

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file $filename \n";
	exit;
    }
    while (($buffer = fgets($handle, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	$eu_peug[$list[0]] = $list[1];
    }
#    print_r($eu_peug);
}

function peug_vert($filename = "PEUG_VERT.csv"){

    global $peug;

    $handle = @fopen($filename, "r");
    
    if (!$handle) {
	print "Unable to open file $filename \n";
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
    global $pf_map;
    global $bu_map;
    $peg = NULL;

    #we assume "product.csv comes first :(

    $handleException = @fopen("exception.csv", "w");
    
    if (!$handleException) {
	print "Unable to open file exception.csv \n";
	exit;
    }

    $header= array("Parent Enduser Group","Rev_Rec_QTR","SO Channel Code","P4 Reporting Ship to GEO","Category","Segment","Vertical","Sub Vertical","Reportable Business","Product Family","Product Line Code","Product Line Desc","Revrec Net\$","Revrec Cost\$","VertRev_Final","Revrec Type", "Pulse", "P4 Reporting Ship to Country", "Theater");
    
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
	    print "Unable to open file $filename\n";
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
	    # We do not want to use the PEG values from the SSOT, but redefine separately for FP&A
	    $index["Parent Enduser Group"] = -1;
	    # Product family mapping for Security Legacy
	    $index["Product Family"] = -1;
	    $index["Product Line Desc"] = -1;
	    $index["Reportable Business"] = -1;
	}
	while (($buffer = fgets($handle, 4096)) !== false) {
	    $i++;
	    $list = str_getcsv($buffer, ",", '"');
	    if (empty(array_filter($list))){
		continue;
	    }
	    $glcode = $list[1];
	    $glcode_segment = $list[23];
	    $prodLineDesc = $list[25];
	    $prodFamily = $list[29];
	    $country = $list[20];
	    $Geo = $list[14];
	    foreach ($header as $hdr_str) {
		if ($index[$hdr_str] == -1){
		    switch ($hdr_str) {
			case "Reportable Business":
			    if (ISSET($bu_map[$prodFamily])){
				$res[$i][] = $bu_map[$prodFamily];
			    } else {
			    	$res[$i][] = $list[26];
			    }
			break;
			case "Product Line Desc":
			    if (ISSET($pf_map[$prodFamily][$prodLineDesc][0])){
				$res[$i][] = $pf_map[$prodFamily][$prodLineDesc][0];
			    } else {
			    	$res[$i][] = $list[25];
			    }
			break;
			case "Product Family":
			    if ($list[29] == "PTX"){
				$res[$i][] = "PTX Series";
			    } else if (ISSET($pf_map[$prodFamily][$prodLineDesc][0])){
				$res[$i][] = $pf_map[$prodFamily][$prodLineDesc][1];
			    } else {
			    	$res[$i][] = $list[29];
			    }
			break;
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
			    if (ISSET($glcode_map[$glcode][$glcode_segment][0])){
				if (ISSET($eu_peug[$glcode_map[$glcode][$glcode_segment][0]]) || (ISSET($eu_peug[strtoupper($glcode_map[$glcode][$glcode_segment][0])]))) {
				    $peg[$i] = $eu_peug[strtoupper($glcode_map[$glcode][$glcode_segment][0])];
				} else {
				    $peg[$i] = strtoupper($glcode_map[$glcode][$glcode_segment][0]);
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
			    if (preg_match("/pulse/i", $buffer)){
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
		      	case "Theater": {
			    if ($Geo == "North America" || $Geo == "Latin America"){
				$res[$i][] = "AMER";
			    }else {
				$res[$i][] = $Geo;
			    }
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
    global $MO_MOAT;
    global $fileCount;

    if ($fileCount == 0){
	$handle = @fopen($outputAggregateFile, "w+");
    }else {
	$handle = @fopen($outputAggregateFile, "a+");
    }
    if (! $handle) {
        print " Unable to Open File $outputAggregateFile \n";
        exit;
    }

    $handleStandalone = @fopen($outputStandaloneFile, "w");

    if (! $handle) {
        print " Unable to Open File $outputStandaloneFile \n";
        exit;
    }

    $header= array("Parent_Enduser_Group","Revrec_Qtr","SO_Channel_Code","Theater","Category","Segment","Vertical","Sub_Vertical","Reportable_Business","Product_Family","OrigRevrec_Net\$","Revrec_Cost\$","Vert_Rev_Final\$", "Pulse");
    if ($fileCount == 0) {
	fputcsv($handle, $header);
    }
    fputcsv($handleStandalone, $header);
    
    foreach ($MOAT as $Parent_Enduser_Group => $list1){
	foreach ($list1 as $Revrec_Qtr => $list2){
	    foreach ($list2 as $So_Channel_Code => $list3){
		foreach ($list3 as $Theater => $list4){
		    foreach ($list4 as $Category => $list5){
			foreach ($list5 as $Segment => $list6){
			    foreach ($list6 as $Vertical => $list7) {
				foreach ($list7 as $Sub_Vertical => $list8){
				    foreach ($list8 as $Reportable_Business => $list9){
					foreach ($list9 as $Product_Family => $list10){
					    if (preg_match("/pulse/i", $Product_Family)){
						$pulse = "Y";
					    } else {
						$pulse = "N";
					    }
					    if (strcmp($Parent_Enduser_Group, "Parent_Enduser_Group") != 0) { 
						fputcsv($handle, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $Theater, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $list10[0], $list10[1], $list10[2], $pulse));
						fputcsv($handleStandalone, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $Theater, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $list10[0], $list10[1], $list10[2], $pulse));
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


function genAggregatedTableProduct($outputAggregateFile = "Aggregate.csv", $outputStandaloneFile){

    global $MOAT;
    global $MO_MOAT;
    global $fileCount;

    if ($fileCount == 0){
	$handle = @fopen($outputAggregateFile, "w+");
    }else {
	$handle = @fopen($outputAggregateFile, "a+");
    }
    if (! $handle) {
        print " Unable to Open File $outputAggregateFile \n";
        exit;
    }

    $handleStandalone = @fopen($outputStandaloneFile, "w");

    if (! $handle) {
        print " Unable to Open File $outputStandaloneFile \n";
        exit;
    }

    $header= array("Parent_Enduser_Group","Revrec_Qtr","SO_Channel_Code","Theater","Category","Segment","Vertical","Sub_Vertical","Reportable_Business","Product_Family","Product_Line_Desc", "Product_Line_Code", "OrigRevrec_Net\$","Revrec_Cost\$","Vert_Rev_Final\$", "Pulse");
    if ($fileCount == 0) {
	fputcsv($handle, $header);
    }
    fputcsv($handleStandalone, $header);
    
    foreach ($MO_MOAT as $Parent_Enduser_Group => $list1){
	foreach ($list1 as $Revrec_Qtr => $list2){
	    foreach ($list2 as $So_Channel_Code => $list3){
		foreach ($list3 as $Theater => $list4){
		    foreach ($list4 as $Category => $list5){
			foreach ($list5 as $Segment => $list6){
			    foreach ($list6 as $Vertical => $list7) {
				foreach ($list7 as $Sub_Vertical => $list8){
				    foreach ($list8 as $Reportable_Business => $list9){
					foreach ($list9 as $Product_Family => $list10){
					    foreach ($list10 as $Product_Line_Desc => $list11){
						foreach ($list11 as $Product_Line_Code => $list12){
						    if (preg_match("/pulse/i", $Product_Family)){
							$pulse = "Y";
						    } else {
							$pulse = "N";
						    }
						    if (strcmp($Parent_Enduser_Group, "Parent_Enduser_Group") != 0) { 
							fputcsv($handle, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $Theater, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $Product_Line_Desc, $Product_Line_Code, $list12[0], $list12[1], $list12[2], $pulse));
							fputcsv($handleStandalone, array($Parent_Enduser_Group, $Revrec_Qtr, $So_Channel_Code, $Theater, $Category, $Segment, $Vertical, $Sub_Vertical, $Reportable_Business, $Product_Family, $Product_Line_Desc, $Product_Line_Code, $list12[0], $list12[1], $list12[2], $pulse));
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
	}
    }
    fclose($handle);
    fclose($handleStandalone);
}

function addPulseData($outputAggregateFile = "Aggregate.csv", $qtr, $shift=0){

    global $MOAT;
    global $MO_MOAT;
    $handle = @fopen($outputAggregateFile, "a+");
    $handlePulse = @fopen("ssot_all/".$qtr."_ServicePulse.csv", "r");

    if (! $handle) {
        print " Unable to Open Aggregated File $outputAggregateFile\n";
        exit;
    }

    if (! $handlePulse) {
        print " Unable to Open File PulseService \n";
	return;
    }
    $buffer = fgets($handlePulse, 4096);
    $filler = array("Pulse","Pulse");
    while (($buffer = fgets($handlePulse, 4096)) !== false) {
	$list = str_getcsv($buffer, ",", '"');
	if ($shift) {
	    array_splice($list, 10, 0, $filler); 
	}
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
	    $fileList[$matches[1]][$matches[2].$matches[1]][]=realpath($entry);
	}
    }
}
#print_r($fileList);
print "Genrating Mapings for Unknown Entries in SSOT ... \n";
gl_code("GLCode.csv");
pfgroup_map("ssot_all/PFGrouping.csv");
print "Ingest the latest Parent Enguser Group Mappings ... \n";
eu_peug("EU_PEUG.csv");
print "Ingest the latest Parent Enguser Group to Vertical and Subvertical Mappings ... \n";
peug_vert("PEUG_VERT.csv");
print "Generating the final SSOT, w/ Adjuted Revenue Computation ... \n";

foreach ($fileList as $year => $list1) {
    $outputProduct = "results/VerticalRevenue_ProductLine_20$year.csv";
    $output = "results/VerticalRevenue_20$year.csv";
    foreach ($list1 as $qtr => $files) {
	print "Working on $qtr .. \n";
	$outputFinal = "results/".$qtr."_Final.csv";
	$outputAggregatedStandalone = "results/".$qtr."_VerticalRevenue.csv";
	$outputAggregatedStandaloneProduct = "results/".$qtr."_VerticalRevenue_Product.csv";
	$mdfFile = "ssot_all/".$qtr."_MDF.csv";
	print "$qtr: Merge SSOTs, Collect all quarter data ... \n";
	merge_ssot($qtr, $files);
	print "$qtr: Applying Revenue Adjustment and Verifying the Total Revenue per Quarter ... \n";
	verify_revenue_adjustment_new($res, $outputFinal);
	print "$qtr: Generating MDF Adjustments ... \n";
	genMDF($qtr,$mdfFile,$outputFinal);
	print "$qtr: Generating Aggregate Table  ... \n";
	genAggregatedTable($output, $outputAggregatedStandalone);
	print "$qtr: Generating Aggregate Table w/ Product  ... \n";
	genAggregatedTableProduct($outputProduct, $outputAggregatedStandaloneProduct);
	print "$qtr: Adding Pulse Data  ... \n";
	addPulseData($output, $qtr);
	print "$qtr: Adding Pulse Data w/ Product ... \n";
	addPulseData($outputProduct, $qtr, 1);
	print "$qtr: Generating MDF Adjustment for Aggregate  ... \n";
	genMDFAggregate($qtr,$mdfFile,$output, 0);
	print "$qtr: Generating MDF Adjustment for Aggregate w/ Product ... \n";
	genMDFAggregate($qtr,$mdfFile,$outputProduct, 1);
	$fileCount++;
	$res = NULL;
	$money = NULL;
	$mdf = NULL;
	$MOAT = NULL;    
	$MO_MOAT = NULL;
    }
}


?>
