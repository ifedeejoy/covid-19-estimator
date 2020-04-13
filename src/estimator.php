<?php
function covid19ImpactEstimator($data)
{
	$input = $data;
	$reportedCases = $input['reportedCases'];
	$periodType = $input['periodType'];
	$timeElapsed = $input['timeToElapse'];
	$totalBeds = $input['totalHospitalBeds'];
	$avgIncome = $input['region']['avgDailyIncomeInUSD'];
	$impact = impact($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome);
	$severe = severe($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome);
	$data = array("data" => $input, "impact" => $impact, "severeImpact" => $severe);
  	return $data;
}

function impact($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome)
{
	$impactCI = currentlyInfected($reportedCases, 10);
	$infectionsByTime = infectionsByTime($impactCI, $periodType, $timeElapsed);
	$severeByTime = $infectionsByTime * 0.15;
	$bedsByTime = bedsByTime($severeByTime, $totalBeds);
	$icuCases = $infectionsByTime * 0.05;
	$ventilatorCases = $infectionsByTime * 0.02;
	$dollarsInFlight = floor(floor($infectionsByTime * 0.65 * $avgIncome)/30);

	$impact = array(
		"currentlyInfected" => $impactCI,
		"infectionsByRequestedTime" => floor($infectionsByTime),
		"severeCasesByRequestedTime" => floor($severeByTime),
		"hospitalBedsByRequestedTime" => floor($bedsByTime),
		"casesForICUByRequestedTime" => floor($icuCases),
		"casesForVentilatorsByRequestedTime" => floor($ventilatorCases),
		"dollarsInFlight" => floor($dollarsInFlight)
    );
    return $impact;
}

function severe($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome)
{
	$severeCI = currentlyInfected($reportedCases, 50);
	$infectionsByTime = infectionsByTime($severeCI, $periodType, $timeElapsed);
	$severeByTime = $infectionsByTime * 0.15;
	$bedsByTime = bedsByTime($severeByTime, $totalBeds);
	$icuCases = $infectionsByTime * 0.05;
	$ventilatorCases = $infectionsByTime * 0.02;
	$dollarsInFlight = floor(floor($infectionsByTime * 0.65 * $avgIncome)/30);

	$severeImpact = array(
		"currentlyInfected" => $severeCI,
		"infectionsByRequestedTime" => floor($infectionsByTime),
		"severeCasesByRequestedTime" => floor($severeByTime),
		"hospitalBedsByRequestedTime" => floor($bedsByTime),
		"casesForICUByRequestedTime" => floor($icuCases),
		"casesForVentilatorsByRequestedTime" => floor($ventilatorCases),
		"dollarsInFlight" => floor($dollarsInFlight)
    );
    return $severeImpact;
}

function timeElapsed($periodType, $timeElapsed)
{
	if($periodType == "days"):
		$period = $timeElapsed;
	elseif($periodType == "weeks"):
		$period = $timeElapsed * 7;
	elseif ($periodType == "months"):
		$period = $timeElapsed * 30;
	endif;
	return $period;
}

function currentlyInfected($reportedCases, $multiplier)
{
	$currentlyInfected = $reportedCases * $multiplier;
	return $currentlyInfected;
}

function infectionsByTime($currentlyInfected, $periodType, $timetoElapse)
{
	$factor = floor(timeElapsed($periodType, $timetoElapse) / 3);
	$result = $currentlyInfected * pow(2, $factor);
	return $result;
}


function bedsByTime($severeByTime, $totalBeds)
{
	$availableBeds = $totalBeds * 0.35;
	$bedsByTime = $severeByTime - $availableBeds;
	return $bedsByTime;
}

