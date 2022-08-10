<?php

include("functions.php");


// BEWONERS

$sparql = "
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>

SELECT * WHERE {
  <" . $_GET['uri'] . "> rdfs:label ?loclabel .
  ?locobs geo:geoWithin <" . $_GET['uri'] . "> .
  ?locobs rdfs:label ?locobslabel .
  ?locobs roar:documentedIn ?doc .
  ?doc rdfs:label ?doclabel .
  ?locobs roar:hasPerson ?p .
  optional { ?p sem:hasLatestBeginTimeStamp ?start . }
  optional { ?p sem:hasEarliestEndTimeStamp ?end . }
  ?p rdf:value ?po .
  optional { 
    ?po pnv:hasName/pnv:literalName ?litname .
  }
  optional { 
    ?pr prov:wasDerivedFrom ?po .
  }
} LIMIT 100
";

$endpoint = 'https://api.druid.datalegend.net/datasets/menno/roar/services/roar/sparql';

$json = getSparqlResults($endpoint,$sparql);
$bewonerdata = json_decode($json,true);

$loclabel = "";
if(isset($bewonerdata['results']['bindings'][0]['loclabel']['value'])){
	$loclabel = $bewonerdata['results']['bindings'][0]['loclabel']['value'];
}


//print_r($data);

//die;

// GEBOORTE EN STERFPLAATS

$sparql = "
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>
PREFIX bio: <http://purl.org/vocab/bio/0.1/> 

SELECT * WHERE {
  <" . $_GET['uri'] . "> rdfs:label ?loclabel .
  ?bioevent bio:place/rdf:value <" . $_GET['uri'] . "> .
  ?bioevent a ?eventtype .
  optional {
  	?bioevent sem:hasTimeStamp ?time .
  }
  ?po ?p ?bioevent .
  ?po pnv:hasName/pnv:literalName ?litname .
  ?po roar:documentedIn ?doc .
  OPTIONAL{
    ?pr prov:wasDerivedFrom ?po .
  }	
  ?doc rdfs:label ?doclabel .
} LIMIT 100
";

$json = getSparqlResults($endpoint,$sparql);
$eventdata = json_decode($json,true);

if(isset($eventdata['results']['bindings'][0]['loclabel']['value'])){
	$loclabel = $eventdata['results']['bindings'][0]['loclabel']['value'];
}



?>

<h1><?= $loclabel ?></h1>

<a target="_blank" href="<?= $_GET['uri'] ?>"><?= $_GET['uri'] ?></a>

<h3>a roar:Location</h3>

<ul>
<?php

foreach ($eventdata['results']['bindings'] as $k => $v) {
	echo "<li>";
	if(preg_match("/Birth/",$v['eventtype']['value'])){
		echo " birthplace of ";
	}
	if(preg_match("/Death/",$v['eventtype']['value'])){
		echo " place of death of ";
	}
	echo $v['litname']['value'] . "";
	if(isset($v['pr']['value'])){
		echo " <a href=\"" . $v['pr']['value'] . "\">[r]</a>";
	}
	echo "\n<br /><em>a roar:PersonObservation documented in '" . $v['doclabel']['value'] . "'</em> <a href=\"" . $v['doc']['value'] . "\">[d]</a>";
	echo "</li>";
}



foreach ($bewonerdata['results']['bindings'] as $k => $v) {
	echo "<li>";
	echo $v['locobslabel']['value'];
	echo ", residential address of<br />";
	echo $v['litname']['value'] . "";
	if(isset($v['pr']['value'])){
		echo " <a href=\"" . $v['pr']['value'] . "\">[r]</a>";
	}
	if(isset($v['start']['value'])){
		echo " from " . $v['start']['value'] . "";
	}
	if(isset($v['end']['value'])){
		echo " until " . $v['end']['value'] . "";
	}
	echo "\n<br /><em>a roar:LocationObservation linked to this location, documented in '" . $v['doclabel']['value'] . "'</em> <a href=\"" . $v['doc']['value'] . "\">[d]</a>";
	echo "</li>";
}



?>
</ul>