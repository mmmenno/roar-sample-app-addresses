<?php

include("functions.php");



$sparql = "
PREFIX schema: <http://schema.org/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>

SELECT * WHERE {
  <" . $_GET['uri'] . "> pnv:hasName/pnv:literalName ?litname .
  <" . $_GET['uri'] . "> roar:hasLocation ?loc .
  ?loc rdfs:label ?loclabel .
  optional{ ?loc sem:hasLatestBeginTimeStamp ?start . }
  optional { ?loc sem:hasEarliestEndTimeStamp ?end . }
  ?loc schema:position ?pos .
  ?loc geo:geoWithin ?locwithuri .
  ?locwithuri geo:hasGeometry/geo:asWKT ?wkt .
} 
order by ?pos";

$endpoint = 'https://api.druid.datalegend.net/datasets/menno/roar/services/roar/sparql';

$json = getSparqlResults($endpoint,$sparql);
$data = json_decode($json,true);

$reclabel = $data['results']['bindings'][0]['litname']['value'];


//print_r($data);

//die;
$points = array();

foreach ($data['results']['bindings'] as $k => $v) {

	$coords = str_replace(array("POINT(",")"), "", $v['wkt']['value']);
	$latlon = explode(" ", $coords);
	$points[] = array((double)$latlon[0],(double)$latlon[1]);

}

$sparql = "
PREFIX schema: <http://schema.org/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>
PREFIX bio: <http://purl.org/vocab/bio/0.1/> 
SELECT * WHERE {
	optional {
  	<" . $_GET['uri'] . "> bio:birth ?birth .
  	?birth sem:hasTimeStamp ?dob .
  	optional {
	  	?birth bio:place ?bplace .
	    ?bplace rdfs:label ?bplacelabel .
	    ?bplace geo:hasGeometry/geo:asWKT ?bwkt .
	  }
	}
  optional {
  	<" . $_GET['uri'] . "> bio:death ?death .
  	?death sem:hasTimeStamp ?dod .
  	optional {
	  	?death bio:place ?dplace .
	    ?dplace rdfs:label ?dplacelabel .
	    ?dplace geo:hasGeometry/geo:asWKT ?dwkt .
	  }
  }} 
";


$json = getSparqlResults($endpoint,$sparql);
$bddata = json_decode($json,true);

foreach ($bddata['results']['bindings'] as $k => $v) {
	if(isset($v['bplace']['value'])){
		$coords = str_replace(array("POINT(",")"), "", $v['bwkt']['value']);
		$latlon = explode(" ", $coords);
		$birthpoint = array((double)$latlon[0],(double)$latlon[1]);
		array_unshift($points , $birthpoint);
	}
	if(isset($v['dplace']['value'])){
		$coords = str_replace(array("POINT(",")"), "", $v['dwkt']['value']);
		$latlon = explode(" ", $coords);
		$points[] = array((double)$latlon[0],(double)$latlon[1]);
	}
}

//print_r($points);


$fc = array("type"=>"FeatureCollection", "features"=>array());

$i = 0;
foreach ($points as $v) {

	$next = $i+1;

	if($next > (count($points)-1)){
		continue;
	}

	$loc = array("type"=>"Feature");
	$loc['geometry'] = array(
		"type"=>"LineString",
		"coordinates" => array($points[$i],$points[$next])
	);

	if($i == 0){
		$other = 255;
	}else{
		$step = round(255 / (count($points)-1));
		$other = 255 - ($i * $step);
	}
	
	$props = array(
		"color" => "150," . $other . "," . $other
		//"color" => $other . "," . $other . "," . $other
	);
	
	
	
	$loc['properties'] = $props;
	$fc['features'][] = $loc;

	$i++;
}

$json = json_encode($fc);

//file_put_contents("geojson-" . $scape . "/" . $qcountry . '.geojson', $json);


echo $json;




?>

