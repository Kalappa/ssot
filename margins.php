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

function generate_prevQtr($filename = "Data.csv", $customerData = 0) {

    global $map;
    global $header;
    global $headerList;
    global $pqShipmentsNetValue;
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
        if ($shipCost > 0 && ($shipNetValue == "")){
	     $shipNetValue = 0.01;
	     print ("Fixed ship net value\n");
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
    global $pqShipmentsCost;
    global $map;
    global $qtr;
    global $headerList;

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
    fwrite($handle,",PqShipNetValue, PqShipCost, PqOrderQty, CQ_ASP, CQ_ACP, CQ_Margin, PQ_ASP, PQ_ACP, PQ_Margin, ProductMix_With_PQHistory, ProductMix_Without_PQHistory, ProductMix_No_CQ_Shipment, Pricing, Volume_Pricing, Pricing_Margin, Cost_Savings, Volume, PF1, PF2, Category\n");
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
#			print "CQ_ASP : $r, $i, $k, ".floatval($k)."\n";
				if ($i != 0){
				    $t = $CQ_MArgin = (1 - $j / floatval($i));
				}else {
				    $t = "";
				}
				if ($h !=  0 && $f != ""){
				    $u = $PQ_ASP = $f / floatval($h);
				    $v = $PQ_ACP = $g / floatval($h);
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
				if ($list[3] == "0002" ||  $list[3] == "ZADV" || $list[3] == "ZAPP"){
				    $category = "Chassis";
				} else if (ISSET($SKUtoCategory[$partNum])){
				    $category = $SKUtoCategory[$partNum][0];
				}else {
				    $category = $other;
				}
				
				fwrite($handle, "\"$customerName\", $partNum, $techView, $NPIProgram, $ItemCatGroupKey, $Quarter, $list5[0], $list5[1], $list5[2], $list5[3], $list5[4], $list5[5], $r, $s, $t, $u, $v, $w, $ProductMix_With_PQHistory, $ProductMix_Without_PQHistory, $ProductMix_No_CQ_Shipment, $Pricing, $Volume_Pricing, $Pricing_Margin, $Cost_Savings, $Volume");
				fwrite($handle, ", ".$SKUtoPF[$partNum][0].", ".$SKUtoPF[$partNum][1]);
				fwrite($handle, ", $category\n");
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
			    }
			}
		    }
		}
	    }
	}
    }
    print "Sum: PQ_Margin = $PQ_MarginSum, ProductMix_With_PQHistory = $ProductMix_With_PQHistorySum, ProductMix_Without_PQHistory = $ProductMix_Without_PQHistorySum, ProductMix_No_CQ_ShipmentSum = $ProductMix_No_CQ_ShipmentSum, PricingSum = $PricingSum, Pricing_MarginSum = $Pricing_MarginSum, Cost_SavingsSum = $Cost_SavingsSum, Volume_PricingSum = $Volume_PricingSum, VolumeSum = $VolumeSum\n";
}


$qtr = $argv[1];

map_SKUtoPF();
generate_prevQtr("Data.csv", 0);
generate_results("results.csv", 0);
unset($map);
generate_prevQtr("CustomerData.csv", 1);
generate_results("Customerresults.csv", 1);
