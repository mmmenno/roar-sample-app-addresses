<?php

include("functions.php");



$sparql = "
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX pnv: <https://w3id.org/pnv#>
PREFIX bio: <http://purl.org/vocab/bio/0.1/> 
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>

SELECT * WHERE {
  <" . $_GET['uri'] . "> rdfs:label ?doclabel .
  ?obs roar:documentedIn <" . $_GET['uri'] . "> .
  ?obs a ?type .
  optional{
    ?obs rdfs:label ?loclabel .
  }
  optional{
    ?obs pnv:hasName/pnv:literalName ?plabel .
    optional{
   		?pr prov:wasDerivedFrom ?obs . 
  	}
  }
  optional{
    ?obs bio:birth/sem:hasTimeStamp ?dob .
  }
  optional{
    ?obs bio:death/sem:hasTimeStamp ?dod .
  }
  optional{
    ?obs geo:geoWithin ?geowithin .
  }
} LIMIT 100

";

$endpoint = 'https://api.druid.datalegend.net/datasets/menno/roar/services/roar/sparql';

$json = getSparqlResults($endpoint,$sparql);
$data = json_decode($json,true);

if(isset($data['results']['bindings'][0]['doclabel']['value'])){
	$doclabel = $data['results']['bindings'][0]['doclabel']['value'];
}



?>

<h1><?= $doclabel ?></h1>

<a target="_blank" href="<?= $_GET['uri'] ?>"><?= $_GET['uri'] ?></a>

<h3>a roar:Document</h3>

<ul>
<?php

foreach ($data['results']['bindings'] as $k => $v) {
	echo "<li>";
	if(isset($v['plabel'])){
		echo $v['plabel']['value'] . " ";
	}
	if(isset($v['pr']['value'])){
		echo " <a href=\"" . $v['pr']['value'] . "\">R</a>";
	}
	if(isset($v['plabel'])){
		echo "<br />";
	}
	if(isset($v['dob'])){
		echo "born " . $v['dob']['value'] . " ";
	}
	if(isset($v['dod'])){
		echo "died " . $v['dod']['value'] . " ";
	}


	if(isset($v['loclabel'])){
		echo $v['loclabel']['value'] . "";
	}
	if(isset($v['geowithin']['value'])){
		echo " <a href=\"" . $v['geowithin']['value'] . "\">L</a>";
	}
	
	echo "<br /><em>a " . str_replace("https://w3id.org/roar#","roar:",$v['type']['value']) . "</em>";
	echo "</li>";
}






?>
</ul>