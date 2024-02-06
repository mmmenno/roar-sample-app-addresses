<?php

include("functions.php");

$sparql = "
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>

SELECT * WHERE {
  ?loc a roar:Location .
  ?loc geo:hasGeometry/geo:asWKT ?wkt .
} 
LIMIT 1000
";

$endpoint = 'https://api.druid.datalegend.net/datasets/menno/roar/services/roar/sparql';

$json = getSparqlResults($endpoint,$sparql);
$data = json_decode($json,true);

//print_r($data);

//die;


$fc = array("type"=>"FeatureCollection", "features"=>array());

foreach ($data['results']['bindings'] as $k => $v) {

	$loc = array("type"=>"Feature");
	$props = array(
		"uri" => $v['loc']['value'],
		"label" => "not yet labeled",
		"cnt" => 0
	);
	
	
	$coords = str_replace(array("POINT(",")"), "", $v['wkt']['value']);
	$latlon = explode(" ", $coords);
	$loc['geometry'] = array("type"=>"Point","coordinates"=>array((double)$latlon[0],(double)$latlon[1]));
	
	$loc['properties'] = $props;
	$fc['features'][] = $loc;

}

$json = json_encode($fc);

//file_put_contents("geojson-" . $scape . "/" . $qcountry . '.geojson', $json);

header('Content-Type: application/json; charset=utf-8');
echo $json;












