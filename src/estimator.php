<?php
function covid19ImpactEstimator($data)
{
	$input = $data;
	$reportedCases = $input['reportedCases'];
	$periodType = $input['periodType'];
	$timeElapsed = $input['timeToElapse'];
	$totalBeds = $input['totalHospitalBeds'];
	$avgIncome = $input['region']['avgDailyIncomeInUSD'];
	$avgPopulation = $input['region']['avgDailyIncomePopulation'];
	$impact = impact($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome, $avgPopulation);
	$severe = severe($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome, $avgPopulation);
	$data = array("data" => $input, "impact" => $impact, "severeImpact" => $severe);
  	return $data;
}

function impact($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome, $avgPopulation)
{
	$impactCI = currentlyInfected($reportedCases, 10);
	$infectionsByTime = infectionsByTime($impactCI, $periodType, $timeElapsed);
	$severeByTime = $infectionsByTime * 0.15;
	$bedsByTime = bedsByTime($severeByTime, $totalBeds);
	$icuCases = $infectionsByTime * 0.05;
	$ventilatorCases = $infectionsByTime * 0.02;
	$dollarsInFlight = dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeElapsed, $periodType);

	$impact = array(
		"currentlyInfected" => $impactCI,
		"infectionsByRequestedTime" => (int) $infectionsByTime,
		"severeCasesByRequestedTime" => (int) $severeByTime,
		"hospitalBedsByRequestedTime" => (int) $bedsByTime,
		"casesForICUByRequestedTime" => (int) $icuCases,
		"casesForVentilatorsByRequestedTime" => (int) $ventilatorCases,
		"dollarsInFlight" => (int) $dollarsInFlight
    );
    return $impact;
}

function severe($reportedCases, $periodType, $timeElapsed, $totalBeds, $avgIncome, $avgPopulation)
{
	$severeCI = currentlyInfected($reportedCases, 50);
	$infectionsByTime = infectionsByTime($severeCI, $periodType, $timeElapsed);
	$severeByTime = $infectionsByTime * 0.15;
	$bedsByTime = bedsByTime($severeByTime, $totalBeds);
	$icuCases = $infectionsByTime * 0.05;
	$ventilatorCases = $infectionsByTime * 0.02;
	$dollarsInFlight = dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeElapsed, $periodType);

	$severeImpact = array(
		"currentlyInfected" => $severeCI,
		"infectionsByRequestedTime" => (int) $infectionsByTime,
		"severeCasesByRequestedTime" => (int) $severeByTime,
		"hospitalBedsByRequestedTime" => (int) $bedsByTime,
		"casesForICUByRequestedTime" => (int) $icuCases,
		"casesForVentilatorsByRequestedTime" => (int) $ventilatorCases,
		"dollarsInFlight" => (int) $dollarsInFlight
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
	$factor = (int)(timeElapsed($periodType, $timetoElapse) / 3);
	$result = $currentlyInfected * pow(2, $factor);
	return $result;
}


function bedsByTime($severeByTime, $totalBeds)
{
	$availableBeds = $totalBeds * 0.35;
	$bedsByTime = $availableBeds - $severeByTime;
	return $bedsByTime;
}

function dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeElapsed, $periodType)
{
	$period = timeElapsed($periodType, $timeElapsed);
	$result = (($infectionsByTime * $avgPopulation * $avgIncome)/$period);
	return $result;
}

