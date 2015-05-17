<?php
error_reporting( error_reporting() & ~E_NOTICE );

function adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO) {

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
                    return ($Revrec_Net + $Revrec_Net*$Non_DR/$DR);
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
    
    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }
    $Non_DR = array("CREDIT", "CUSTOM", "DR-1", "DS", "IMMEDIATE", "RETURNS", "POS", "NS", "unknown");
    $money = NULL;
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
	    $Revrec_Type = str_replace($Non_DR, "NON-DR", $list[3]);
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
    
    $Non_DR = array("CREDIT", "CUSTOM", "DR-1", "DS", "IMMEDIATE", "RETURNS", "POS", "NS", "unknown");
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
	    $Revrec_Type = str_replace($Non_DR, "NON-DR", $list[15]);
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
    
    generate_money_pivot_new($res);

    $NewRevenue=0;
    $OldRevenue=0;
    
    foreach ($res as $list) {
	$Category = $list[4];
	$SO_Channel_Code = $list[2];
	if ($list[15] == "DR") {
	    $Revrec_Type = $list[15];
	} else {
	    $Revrec_Type = str_replace($Non_DR, "NON-DR", $list[15]);
	}
	$P4_Reporting_Ship_to_GEO = $list[3];
	$Revrec_Net = floatval(str_replace(',', '', $list[12]));
	$Segment = $list[5];
	$Product_Family=$list[9];
	$OldRevenue += $Revrec_Net;
	$NewRevenue += adjustRevenue($Revrec_Net, $Category, $Revrec_Type, $SO_Channel_Code, $Product_Family, $Segment, $P4_Reporting_Ship_to_GEO);
    }
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


function merge_ssot() {

    global $res;
    
    $files = array("Product.csv", "Service.csv");
    
    foreach ($files as $filename) {
	if ($filename == "Product.csv"){
	    $product = 1;
	}else {
	    $product = 0;
	}

	$handle = @fopen($filename, "r");
	
	if (!$handle) {
	    print "Unable to open file\n";
	    exit;
	}

	if ($product == 1){
	    $header= array("Parent Enduser Group","Rev_Rec_QTR","SO Channel Code","P4 Reporting Ship to GEO","Category","Segment","Vertical","Sub Vertical","Reportable Business","Product Family","Product Line Code","Product Line Desc","Revrec Net$","Revrec Cost$","VertRev_Final","Revrec Type");
	    
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
	    }
	    //print implode(',', $header)."\n";
	    $i=0;
	    $res[$i]=$header;
	}
	while (($buffer = fgets($handle, 4096)) !== false) {
	    $i++;
	    $list = str_getcsv($buffer, ",", '"');
	    foreach ($header as $hdr_str) {
		if ($index[$hdr_str] == -1){
		    if ($hdr_str == "Category"){
			if ($product == 1) {
			    $res[$i][] = "Product";
			}else {
			    $res[$i][] = "Service";
			}
		    }else {
			$res[$i][] = "To be filled";
		    }
		}else {
		    $res[$i][] = $list[$index[$hdr_str]];
		}   
	    }
	}    
    }
}
merge_ssot("Service.csv", 0);
verify_revenue_adjustment_new($res);
?>
