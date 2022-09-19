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
// birth, death, sameas
$sparql = "
PREFIX schema: <http://schema.org/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>
PREFIX bio: <http://purl.org/vocab/bio/0.1/> 
PREFIX owl:	<http://www.w3.org/2002/07/owl#>
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
  }
  optional {
  	<" . $_GET['uri'] . "> owl:sameAs ?sameas .
  }
} 
";


$json = getSparqlResults($endpoint,$sparql);
$bddata = json_decode($json,true);


// Documents 

$sparql = "
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
SELECT ?doc ?doclabel WHERE {
 	<" . $_GET['uri'] . "> prov:wasDerivedFrom ?obs .
    ?obs roar:documentedIn ?doc .
  	?doc rdfs:label ?doclabel .
} 
group by ?doc ?doclabel
limit 25
";


$json = getSparqlResults($endpoint,$sparql);
$docdata = json_decode($json,true);


// shared addresses
$sparql = "
PREFIX schema: <http://schema.org/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX roar: <https://w3id.org/roar#>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX pnv: <https://w3id.org/pnv#>
SELECT ?withinloc ?person2 ?litname2 ?withinloclabel WHERE {
  <" . $_GET['uri'] . "> roar:hasLocation ?loc .
  ?loc rdfs:label ?loclabel .
  ?loc geo:geoWithin ?withinloc .
  ?person2 roar:hasLocation ?loc2 .
  ?person2 pnv:hasName/pnv:literalName ?litname2 .
  ?loc2 geo:geoWithin ?withinloc .
  ?withinloc rdfs:label ?withinloclabel .
  FILTER(?person2 != <" . $_GET['uri'] . ">)
}
GROUP BY ?withinloc ?person2 ?litname2 ?withinloclabel
";

//echo $sparql;

$json = getSparqlResults($endpoint,$sparql);
$sharesdata = json_decode($json,true);


?>

<h1><?= $reclabel ?></h1>

<?= str_replace("http://gendata.denengelse.nl/",":",$_GET['uri']) ?>

<?php
foreach ($bddata['results']['bindings'] as $k => $v) {
	if(isset($v['sameas']['value'])){
		echo "<br /><br />sameAs <a target=\"_blank\" href=\"" . $v['sameas']['value'] . "\">" . $v['sameas']['value'] . "</a><br />";
	}
}
?>

<h3>a roar:PersonReconstruction</h3>


<ul>
<?php

foreach ($bddata['results']['bindings'] as $k => $v) {
	echo "<li>";
	if(isset($v['dob']['value'])){
		echo "Born on " . $v['dob']['value'] . "";
	}
	if(isset($v['bplace']['value'])){
		echo " in <strong>" . $v['bplacelabel']['value'] . "</strong>";
		echo " <a href=\"" . $v['bplace']['value'] . "\">L</a>";
	}
	echo "</li>";

}

?>



<?php

foreach ($data['results']['bindings'] as $k => $v) {
	echo "<li>address";
	if(isset($v['start']['value'])){
		echo " from " . $v['start']['value'] . "";
	}
	if(isset($v['end']['value'])){
		echo " to " . $v['end']['value'] . "";
	}
	echo ":<br /><strong>" . $v['loclabel']['value'] . "</strong>";
	echo " <a href=\"" . $v['locwithuri']['value'] . "\">L</a>";
	echo "</li>";
}

?>




<?php

foreach ($bddata['results']['bindings'] as $k => $v) {
	echo "<li>";
	if(isset($v['dod']['value'])){
		echo "Dies on " . $v['dod']['value'] . "";
	}
	if(isset($v['dplace']['value'])){
		echo " in <strong>" . $v['dplacelabel']['value'] . "</strong>";
		echo " <a href=\"" . $v['dplace']['value'] . "\">L</a>";
	}
	echo "</li>";

}

?>
</ul>


<h5>Extracted from these documents:</h5>


<?php

foreach ($docdata['results']['bindings'] as $k => $v) {
	//echo $v['doclabel']['value'] . "";
	echo " <a target=\"_blank\" href=\"" . $v['doc']['value'] . "\">" . $v['doclabel']['value'] . "</a>";
	echo " <a class=\"btn\" href=\"" . $v['doc']['value'] . "\">D</a>";
	echo "<br />";

}

?>


<h5>Shares locations with:</h5>


<?php

foreach ($sharesdata['results']['bindings'] as $k => $v) {
	//echo $v['doclabel']['value'] . "";
	echo $v['withinloclabel']['value'] . " ";
	echo " <a class=\"btn\" href=\"" . $v['withinloc']['value'] . "\">L</a> with ";
	echo $v['litname2']['value'] . " ";
	echo " <a class=\"btn\" href=\"" . $v['person2']['value'] . "\">R</a>";
	echo "<br />";

}

?>


