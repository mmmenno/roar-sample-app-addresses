<?php

include("functions.php");


?><!DOCTYPE html>
<html>
<head>
  
<title>mapping lives</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>

  <link rel="stylesheet" href="styles.css" />

  <style type="text/css">
    body{
      background-color: #000;
    }
  </style>
  
</head>
<body>


<div id="bigmap"></div>


<div id="intro">
  
  <h1><a style="color:#000;" href="/">Family map</a></h1>

  <p>
    This map shows addresses and places of birth &amp; death of - amongst others - some members of my family.
  </p>

  <p>
    All data is 'document-based', which means I only added data found in online documents I could link to.
  </p> 

  <p>
    Using only these observations, I 'reconstructed' the lives of people like <a onclick="loadReconstruction('http://gendata.denengelse.nl/pr1')" href="#r=http://gendata.denengelse.nl/pr1">my grandmother</a>, who came from Austria to work as a maid in Amsterdam and <a onclick="loadReconstruction('http://gendata.denengelse.nl/pr2')"  href="#r=http://gendata.denengelse.nl/pr2">Izaäk Lob</a>, one of her employers.
  </p>

  <p>
    Click any marker to see the observations linked to that location.
  </p>
</div>



<div id="content">
  <h1>The roar ontology</h1>

  <p>
    When describing persons and locations, it's important to distinguish observations (as found in a document) from reconstructions (combined data from multiple observations you consider to be about the same person).
  </p>

  <p>
    The <a href="https://w3id.org/roar/">roar ontology</a> was developed to do just that. Here we showcase how address data, birth- and deathdates found in archival documents can be used to create personreconstructions, visualised as lines on the map.
  </p> 

  <p>
    Read more on the data, modelling and the way reconstructions were made on the <a href="https://github.com/mmmenno/roar-samples">github data repo</a>.
  </p>
</div>






<script>
  $(document).ready(function() {

    $('form select').change(function(){
      $("form").submit();
    });

    createMap();
    refreshMap();

    var hash = window.location.hash;
    console.log(hash)

    if(hash.match(/^#l/)) {
      var href = hash.substring(3);
      loadLocation(href);
    }

    if(hash.match(/^#r/)) {
      var href = hash.substring(3);
      loadReconstruction(href);
    }

    if(hash.match(/^#d/)) {
      var href = hash.substring(3);
      loadDocument(href);
    }
  });

  function createMap(){
    center = [52.357213, 4.893606];
    zoomlevel = 13;
    
    map = L.map('bigmap', {
          center: center,
          zoom: zoomlevel,
          minZoom: 1,
          maxZoom: 19,
          scrollWheelZoom: true,
          zoomControl: false
      });

    L.control.zoom({
        position: 'bottomleft'
    }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map);
  }

  function refreshMap(){

    $.ajax({
          type: 'GET',
          url: 'geojson-places.php',
          dataType: 'json',
          success: function(jsonData) {
            if (typeof streets !== 'undefined') {
              map.removeLayer(streets);
            }

            streets = L.geoJson(null, {
              pointToLayer: function (feature, latlng) {                    
                  return new L.CircleMarker(latlng, {
                      color: '#de0000',
                      radius:8,
                      weight: 0,
                      opacity: 0.8,
                      fillOpacity: 0.8
                  });
              },
              style: function(feature) {
                return {
                    clickable: true
                };
              },
              onEachFeature: function(feature, layer) {
                layer.on({
                    click: whenClicked
                  });
                }
            }).addTo(map);

            streets.addData(jsonData).bringToFront();

            

          },
          error: function() {
              console.log('Error loading data');
          }
      });
  }

function whenClicked(){
  $("#content").show();

  var props = $(this)[0].feature.properties;
  
  $("#content").load('location.php?uri=' + props['uri'],function(){
    updContentBehaviour();
  });

  
 
}

function updContentBehaviour(){
  
  $('#content a').click(function(e) {
    
    var href = $(this).attr('href');
    var txt = $(this).text();

    if(txt == "[r]"){
      e.preventDefault();
      loadReconstruction(href);
      window.location.hash = "r=" + href;
    }else if(txt == "[l]"){
      e.preventDefault();
      loadLocation(href);
      window.location.hash = "l=" + href;
    }else if(txt == "[d]"){
      e.preventDefault();
      loadDocument(href);
      window.location.hash = "d=" + href;
    }else{
      console.log(href);
    }

  });

}

function loadReconstruction(href){

  $("#content").load('reconstruction.php?uri=' + href,function(){
    updContentBehaviour();
  });

  console.log('geojson-reconstruction.php?uri=' + href);

  $.ajax({
      type: 'GET',
      url: 'geojson-reconstruction.php?uri=' + href,
      dataType: 'json',
      success: function(jsonData) {
        if (typeof lifeline !== 'undefined') {
          map.removeLayer(lifeline);
        }

        lifeline = L.geoJson(null, {
          style: function(feature) {
            return {
                radius: 6,
                clickable: false
            };
          },
          onEachFeature: function (feature, layer) {
            if (layer instanceof L.Polyline) {
              layer.setStyle({
                color: "rgb(" + feature.properties.color + ")",
                weight: 5
              });
            }
          }
        }).addTo(map);

        lifeline.addData(jsonData).bringToBack();

        map.fitBounds(lifeline.getBounds());
      },
      error: function() {
          console.log('Error loading data');
      }
  });
}

function loadLocation(href){

  $("#content").load('location.php?uri=' + href,function(){
    updContentBehaviour();
  });
}

function loadDocument(href){

  if (typeof lifeline !== 'undefined') {
    map.removeLayer(lifeline);
  }


  $("#content").load('document.php?uri=' + href,function(){
    updContentBehaviour();
  });
}

</script>



</body>
</html>
