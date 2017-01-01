<?php
error_reporting( error_reporting() & ~E_NOTICE );


function map_SKUtoPF($filename = "SKUtoPF.csv"){

    global $SKUtoPF;
    global $headerList;
    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }
    $buffer = fgets($handle, 4096);
    $header = trim($buffer);
    $headerList = str_getcsv($buffer, ",", '"');
    while (($buffer = fgets($handle, 4096)) !== false) {
        $list = str_getcsv($buffer, ",", '"');
	$SKUtoPF[$list[0]][] = $list[1];
	$SKUtoPF[$list[0]][] = $list[2];
    }
}

function map_SKUtoCategory($filename = "SKUtoCategory.csv"){

    global $SKUtoCategory;
    global $headerList;
    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }
    $buffer = fgets($handle, 4096);
    $header = trim($buffer);
    $headerList = str_getcsv($buffer, ",", '"');
    while (($buffer = fgets($handle, 4096)) !== false) {
        $list = str_getcsv($buffer, ",", '"');
	$SKUtoCategory[$list[0]][] = $list[1];
    }
}

function map_SKUtoSubCategory($filename = "SKUtoSubCategory.csv"){

    global $SKUtoSubCategory;
    global $headerList;
    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }
    $buffer = fgets($handle, 4096);
    $header = trim($buffer);
    $headerList = str_getcsv($buffer, ",", '"');
    while (($buffer = fgets($handle, 4096)) !== false) {
        $list = str_getcsv($buffer, ",", '"');
	$SKUtoSubCategory[$list[0]][] = $list[1];
    }
}

function generate_prevQtr($filename = "Data.csv", $customerData = 0) {

    global $map;
    global $header;
    global $headerList;
    global $pqShipmentsNetValue;
    global $cqShipmentsNetValue;
    global $pqShipmentsCost;
    $handle = @fopen($filename, "r");

    if (! $handle) {
        print " Unable to Open File";
        exit;
    }
    $buffer = fgets($handle, 4096);
    $header = trim($buffer);
    $headerList = str_getcsv($buffer, ",", '"');
    while (($buffer = fgets($handle, 4096)) !== false) {
        $list1 = str_getcsv($buffer, ",", '"');
	if ($customerData){
	    $customerName = $list1[0];
	    $list = array_slice($list1, 1);
	} else {
	    $list = $list1;
	    $customerName = "INTERNAL";
	}
	$partNum = $list[0];
	$techView = $list[1];
	$NPIProgram = $list[2];
	$ItemCatGroupKey = $list[3];
	$Quarter = $list[4];
	$shipNetValue = $list[5]; #floatval(str_replace(',', '', $list[5]));
	$shipCost = $list[6]; #floatval(str_replace(',', '', $list[6]));
	$orderQty = $list[7]; #floatval(str_replace(',', '', $list[7]));
        if ($shipCost > 0 && (($shipNetValue == "0") || !strcmp($shipNetValue, ""))){
	     $shipNetValue = 0.01;
	}
	$map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][0] = $shipNetValue;
	$map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][1] = $shipCost;
	$map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][2] = $orderQty;
	if (ISSET($map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1])) {
	    $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][3] = $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1][0];
	    $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][4] = $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1][1];
	    $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][5] = $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1][2];
	    $pqShipmentsNetValue += $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1][0];
	    $cqShipmentsNetValue += $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][0];
	    $pqShipmentsCost += $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter - 1][1];
	    $cqShipmentsCost += $map[$customerName][$partNum][$techView][$NPIProgram][$ItemCatGroupKey][$Quarter][1];
	    
	}
    }
}

function generate_results($filename = "results.csv", $customerData = 0){

    global $pqShipmentsNetValue;
    global $cqShipmentsNetValue;
    global $pqShipmentsCost;
    global $map;
    global $qtr;
    global $headerList;
    global $Customer_PQ_ASP;
    global $SKUtoPF;
    global $SKUtoCategory;
    global $SKUtoSubCategory;

    $f1 = $pqShipmentsNetValue;
    $g1 = $pqShipmentsCost;
    if ($f1 != 0){
	$w1 = 1 - $g1 / floatval($f1);
    }
    print "w1 = $w1, $g1 = g1, f1 = $f1\n";
    
    $handle = @fopen($filename, "w");
    if ($customerData == 0){
	array_unshift($headerList, "Customer");
    }
    fputcsv($handle, $headerList);
    fwrite($handle,",PqShipNetValue, PqShipCost, PqOrderQty, CQ_ASP, CQ_ACP, CQ_Margin, PQ_ASP, PQ_ACP, PQ_Margin, ProductMix_With_PQHistory, ProductMix_Without_PQHistory, ProductMix_No_CQ_Shipment, zProductMix, Pricing, Volume_Pricing, Pricing_Margin, Cost_Savings, Volume, zVolume, Product Line, Profit Center, Category, Sub Category\n");
    foreach ($map as $customerName => $list0){
	foreach ($list0 as $partNum => $list1){
	    foreach ($list1 as $techView => $list2){
		foreach ($list2 as $NPIProgram => $list3){
		    foreach ($list3 as $ItemCatGroupKey => $list4){
			foreach ($list4 as $Quarter => $list5){
			    if ($Quarter != ($qtr -1)){
				$i = $list5[0];
				$j = $list5[1];
				$k = $list5[2];
				$f = $list5[3];
				$g = $list5[4];
				$h = $list5[5];
				if ($k != "" && $k != 0){
				    $r = $CQ_ASP = $i / floatval($k);
				    $s = $CQ_ACP = $j / floatval($k);
				}else {
				    $r = "";
				    $s = "";
				}
				if ($i != 0){
				    $t = $CQ_MArgin = (1 - $j / floatval($i));
				}else {
				    $t = "";
				}
				if ($h !=  0 && $f != ""){
				    $u = $PQ_ASP = $f / floatval($h);
				    $v = $PQ_ACP = $g / floatval($h);
				    $Customer_PQ_ASP[$partNum][0] += $PQ_ASP;
				    $Customer_PQ_ASP[$partNum][1] += 1;
				}else {
				    $u = "";
				    $v = "";
				}
				if ($f != 0){
				    $w = $PQ_Margin = (1 - $g / floatval($f));
				}else {
				    $w = "";
				}
				$PQ_MarginSum += $PQ_Margin;
				if (($w != "" && $i != "")){
				    $ProductMix_With_PQHistory = ($k * $u - $f) * ($w - $w1);
				}
				$ProductMix_With_PQHistorySum += $ProductMix_With_PQHistory;
				if ($f == "" && $t != ""){
				    $ProductMix_Without_PQHistory = ($i * ($t - $w1));
				}
				$ProductMix_Without_PQHistorySum += $ProductMix_Without_PQHistory;
				if ($i == "" && $j == "" && $f != "" && $f != 0){
				    $ProductMix_No_CQ_Shipment = -1 * $f * ($w - $w1);
				}
				$ProductMix_No_CQ_ShipmentSum += $ProductMix_No_CQ_Shipment;
				if (strcmp($r,"") && strcmp($u, "")){			    
				    $ac = $Pricing = ($r - $u) * $k;
				    $PricingSum += $Pricing;
				} else {
				    $ac = "";
				}
				$Volume_Pricing = $ac * $w1;
				$Volume_PricingSum += $Volume_Pricing;
				$Pricing_Margin = $ac * (1 - $w1);
				$Pricing_MarginSum += $Pricing_Margin;			
				if ($v != "" && $s != ""){
				    $Cost_Savings = ($v - $s) * $k;
				}else {
				    $Cost_Savings = "";
				}
				$Cost_SavingsSum += $Cost_Savings;
				if ($h != ""){
				    $Volume = ($k - $h) * $u * $w1;
				} else {
				    $Volume = $i * $w1;
				}
				$VolumeSum += $Volume;
				if (!strcmp($ItemCatGroupKey, "0002") ||  !strcmp($ItemCatGroupKey,"ZADV") || !strcmp($ItemCatGroupKey,"ZAPP") || $ItemCatGroupKey == 2){
				    $category = "Chassis";
				} else if (ISSET($SKUtoCategory[$partNum])){
				    $category = $SKUtoCategory[$partNum][0];
				}else {
				    $category = "other";
				}				
				$zProductMix = $ProductMix_With_PQHistory + $ProductMix_Without_PQHistory + $ProductMix_No_CQ_Shipment;
				$zVolume = $Volume + $Volume_Pricing;
				fwrite($handle, "\"$customerName\",$partNum,$techView,$NPIProgram,$ItemCatGroupKey,$Quarter,$list5[0],$list5[1],$list5[2],$list5[3],$list5[4],$list5[5],$r,$s,$t,$u,$v,$w,$ProductMix_With_PQHistory,$ProductMix_Without_PQHistory,$ProductMix_No_CQ_Shipment,$zProductMix,$Pricing,$Volume_Pricing,$Pricing_Margin,$Cost_Savings,$Volume,$zVolume");
				fwrite($handle, ",".$SKUtoPF[$partNum][0].",".$SKUtoPF[$partNum][1]);
				fwrite($handle, ",$category");
				if (ISSET($SKUtoSubCategory[$partNum])){
				    $subCategory = $SKUtoSubCategory[$partNum][0];
				}else {
				    $subCategory = "other";
				}
				fwrite($handle, ",$subCategory\n");				
				unset($partNum);
				unset($techView);
				unset($NPIProgram);
				unset($ItemCatGroupKey);
				unset($Quarter);
				unset($list5[0]);
				unset($list5[1]);
				unset($list5[2]);
				unset($list5[3]);
				unset($list5[4]);
				unset($list5[5]);
				unset($r);
				unset($s);
				unset($t);
				unset($u);
				unset($v);
				unset($w);
				unset($ProductMix_With_PQHistory);
				unset($ProductMix_Without_PQHistory);
				unset($ProductMix_No_CQ_Shipment);
				unset($Pricing);
				unset($Volume_Pricing);
				unset($Pricing_Margin);
				unset($Cost_Savings);
				unset($Volume);
				unset($subCategory);
			    }
			}
		    }
		}
	    }
	}
    }
    print "Sum: PQ_Margin = $PQ_MarginSum, ProductMix_With_PQHistory = $ProductMix_With_PQHistorySum, ProductMix_Without_PQHistory = $ProductMix_Without_PQHistorySum, ProductMix_No_CQ_ShipmentSum = $ProductMix_No_CQ_ShipmentSum, PricingSum = $PricingSum, Pricing_MarginSum = $Pricing_MarginSum, Cost_SavingsSum = $Cost_SavingsSum, Volume_PricingSum = $Volume_PricingSum, VolumeSum = $VolumeSum\n";
}

function generate_Customerresults($filename = "Customerresults.csv", $customerData = 1){

    global $pqShipmentsNetValue;
    global $cqShipmentsNetValue;
    global $pqShipmentsCost;
    global $map;
    global $qtr;
    global $headerList;
    global $Customer_PQ_Avg_ASP;
    global $Customer_PQ_ASP;
    global $SKUtoPF;
    global $SKUtoCategory;
    global $SKUtoSubCategory;

    $f1 = $pqShipmentsNetValue;
    $g1 = $pqShipmentsCost;
    if ($f1 != 0){
	$w1 = 1 - $g1 / floatval($f1);
    }
    print "w1 = $w1, $g1 = g1, f1 = $f1\n";

    foreach ($Customer_PQ_ASP as $partNum => $list){
	$Customer_PQ_Avg_ASP[$partNum] = $list[0]/$list[1];
    }
    $handle = @fopen($filename, "w");
    if ($customerData == 0){
	array_unshift($headerList, "Customer");
    }
    fputcsv($handle, $headerList);
    fwrite($handle,",PqShipNetValue, PqShipCost, PqOrderQty, Profit Center, Product Line - Medium Text, Category, SubCategory, PQ Customer ASP, PQ Average ASP, CQ ASP, Pricing Pressure, Customer Mix, Pricing Pressure ($), Customer Mix ($)\n");
    foreach ($map as $customerName => $list0){
	foreach ($list0 as $partNum => $list1){
	    foreach ($list1 as $techView => $list2){
		foreach ($list2 as $NPIProgram => $list3){
		    foreach ($list3 as $ItemCatGroupKey => $list4){
			foreach ($list4 as $Quarter => $list5){
			    if ($Quarter != ($qtr -1)){
				$j = $list5[0];
				$k = $list5[1];
				$l = $list5[2];
				$g = $list5[3];
				$h = $list5[4];
				$i = $list5[5];

				if (strcmp($g, "") && $i != 0){
				    $p = $PQ_Customer_ASP = $g / $i;
				    $PQ_Customer_ASP_Sum += $PQ_Customer_ASP;
				}else {
				    $p = $PQ_Customer_ASP = "";				   
				}
				
				$q = $PQ_Avg_ASP = $Customer_PQ_Avg_ASP[$partNum];
				
				if (strcmp($j, "") && $l != 0){
				    $r = $CQ_Asp = $j / $l;
				    $CQ_Asp_Sum += $CQ_Asp;
				}else {
				    $r = $CQ_Asp = "";
				}
				
				if (strcmp($r, "") && strcmp($p, "")){
				    $s = $Pricing_Pressure = ($r - $p) * $l;
				    $Pricing_Pressure_Sum += $Pricing_Pressure;
				}else {
				    $s = $Pricing_Pressure = "";
				}
				 
				if (strcmp($q, "")){
				    $t = $Customer_Mix = ( floatval($r) - floatval($q) ) * $l - floatval ($s);
				    $Customer_Mix_Sum += $Customer_Mix;
				}else {
				    $t = $Customer_Mix = "";
				}

				$Pricing_Pressure_Dollar = floatval($s) * ( 1 - $w1);
				$Pricing_Pressure_Dollar_Sum += $Pricing_Pressure_Dollar;

				$Customer_Mix_Dollar = floatval ($t) * (1 - $w1);
				$Customer_Mix_Dollar_Sum += $Customer_Mix_Dollar;
				if (!strcmp($ItemCatGroupKey, "0002") ||  !strcmp($ItemCatGroupKey,"ZADV") || !strcmp($ItemCatGroupKey,"ZAPP") || $ItemCatGroupKey == 2){
				    $category = "Chassis";
				} else if (ISSET($SKUtoCategory[$partNum])){
				    $category = $SKUtoCategory[$partNum][0];
				}else {
				    $category = "other";
				}
				if (!ISSET($SKUtoSubCategory[$partNum])){
				    $subCategory = "other";
				}else {
				    $subCategory = $SKUtoSubCategory[$partNum][0];
				}
				fwrite($handle, "\"$customerName\",$partNum,$techView,$NPIProgram,$ItemCatGroupKey,$Quarter,$list5[0],$list5[1],$list5[2],$list5[3],$list5[4],$list5[5],".$SKUtoPF[$partNum][0].",".$SKUtoPF[$partNum][1].",$category,$subCategory,$p,$q,$r,$s,$t,$Pricing_Pressure_Dollar,$Customer_Mix_Dollar\n");
				unset($partNum);
				unset($techView);
				unset($NPIProgram);
				unset($ItemCatGroupKey);
				unset($Quarter);
				unset($list5[0]);
				unset($list5[1]);
				unset($list5[2]);
				unset($list5[3]);
				unset($list5[4]);
				unset($list5[5]);
				unset($p);
				unset($q);
				unset($r);
				unset($s);
				unset($t);
				unset($PQ_Customer_ASP);
				unset($PQ_Avg_ASP);
				unset($CQ_Asp);
				unset($Pricing_Pressure);
				unset($Customer_Mix);
				unset($Pricing_Pressure_Dollar);
				unset($Customer_Mix_Dollar);
				unset($subCategory);
			    }
			}
		    }
		}
	    }
	}
    }
    print "Sum: PQ_Customer_Asp = $PQ_Customer_ASP_Sum, CQ_Asp = $CQ_Asp_Sum, Pricing_Pressure = $Pricing_Pressure_Sum, Customer_Mix = $Customer_Mix_Sum, Pricing_Pressure_Dollar = $Pricing_Pressure_Dollar_Sum, Customer_Mix_Dollar = $Customer_Mix_Dollar_Sum \n";
}

if (count($argv) != 4){
    print "Usage: php margins.php <current quearter number, 3 or 4> <data file>.csv <customer-data file>.csv\n";
    exit();
}
$qtr = $argv[1];

map_SKUtoPF();
map_SKUtoCategory();
map_SKUtoSubCategory();
$routingDataFile = $argv[2];
$routingCustomerDataFile = $argv[3];
generate_prevQtr($routingDataFile, 0);
generate_results("Results_".$routingDataFile, 0);
unset($map);
generate_prevQtr($routingCustomerDataFile, 1);
generate_Customerresults("CustomerResults_".$routingCustomerDataFile, 1);
