<?php

$client_ip = $_SERVER['REMOTE_ADDR']; // IP du client
if ($client_ip != "127.0.0.1") {
    $geo_api_url = "https://ipapi.co/$client_ip/xml/";
} else {
    $geo_api_url = "https://ipapi.co/xml/";
}

$geo_curl = curl_init();
curl_setopt($geo_curl, CURLOPT_URL, $geo_api_url);
curl_setopt($geo_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($geo_curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($geo_curl, CURLOPT_PROXY, 'www-cache:3128');
curl_setopt($geo_curl, CURLOPT_FOLLOWLOCATION, true);
$geo_response = curl_exec($geo_curl);
curl_close($geo_curl);

if ($geo_response === false) {
    die('Erreur lors de la récupération des données de géolocalisation.');
}

$geo_data = simplexml_load_string($geo_response);
$latitude = (string)$geo_data->latitude ?? null;
$longitude = (string)$geo_data->longitude ?? null;
$city = (string)$geo_data->city ?? "Inconnue";
$region = (string)$geo_data->region ?? "Inconnue";

$meteo_api_url = "https://www.infoclimat.fr/public-api/gfs/xml?_ll=$latitude,$longitude&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";

$meteo_curl = curl_init();
curl_setopt($meteo_curl, CURLOPT_URL, $meteo_api_url);
curl_setopt($meteo_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($meteo_curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($meteo_curl, CURLOPT_PROXY, 'www-cache:3128');
curl_setopt($meteo_curl, CURLOPT_FOLLOWLOCATION, true);
$meteo_response = curl_exec($meteo_curl);
curl_close($meteo_curl);

if ($meteo_response === false) {
    die('Erreur lors de la récupération des données météo.');
}

$air_quality_url = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&outFields=*&returnGeometry=true&f=pjson";

$init_air = curl_init();
curl_setopt($init_air, CURLOPT_URL, $air_quality_url);
curl_setopt($init_air, CURLOPT_RETURNTRANSFER, true);
curl_setopt($init_air, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($init_air, CURLOPT_PROXY, 'www-cache:3128');
curl_setopt($init_air, CURLOPT_FOLLOWLOCATION, true);
$air_response = curl_exec($init_air);
curl_close($init_air);

$air_data = json_decode($air_response, true);
$air_quality = $air_data['features'][0]['attributes']['lib_qual'] ?? 'Non disponible';

// Récupération des données de traffic
$traffic_api = "https://carto.g-ny.org/data/cifs/cifs_waze_v2.json";
$init_traffic = curl_init();
curl_setopt($init_traffic, CURLOPT_URL, $traffic_api);
curl_setopt($init_traffic, CURLOPT_RETURNTRANSFER, true);
curl_setopt($init_traffic, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($init_traffic, CURLOPT_PROXY, "www-cache");
curl_setopt($init_traffic, CURLOPT_PROXYPORT, 3128);
$traffic_response = curl_exec($init_traffic);
if ($traffic_response === false) {
    echo "Erreur lors de la récupération des données de géolocalisation.";
    curl_close($init_traffic);
    exit;
}
curl_close($init_traffic);

$xsl_file = 'atmosphere.xsl';
$xml_weather = new DOMDocument;
if (!$xml_weather->loadXML($meteo_response)) {
    die('Erreur dans le chargement du XML météo.');
}

$xsl_doc = new DOMDocument;
$xsl_doc->load($xsl_file);

$xslt_processor = new XSLTProcessor;
$xslt_processor->importStyleSheet($xsl_doc);
$html_weather = $xslt_processor->transformToXML($xml_weather);

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Prévisions Météo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div id="map" style="height: 400px;"></div>
<h2 style="text-align: center;">Qualité de l'air : <?= htmlspecialchars($air_quality) ?></h2>
<?= $html_weather ?>
<script>

    // icons pour les marqueurs
    var adressIcon = L.icon({
        iconUrl: 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678111-map-marker-512.png',
        iconSize: [38, 38],
        iconAnchor: [22, 94],
        popupAnchor: [-3, -76],
    });

    var trafficIcon = L.icon({
        iconUrl: 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678111-map-marker-512.png',
        iconSize: [38, 38],
        iconAnchor: [22, 94],
        popupAnchor: [-3, -76],
    });

    var map = L.map('map').setView([<?= $latitude ?>, <?= $longitude ?>], 15);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    var marker = L.marker([<?= $latitude ?>, <?= $longitude ?>]).addTo(map);
    marker.bindPopup("<b><?= htmlspecialchars($city) ?></b><br><?= htmlspecialchars($region) ?>").openPopup();

    // fonction pour ajouter les marqueurs de traffic
    function ajtTrafficMarkers(trafficData) {
        if (trafficData && trafficData.incidents) {
            trafficData.incidents.forEach(function (element) {
                var coords = element.location.polyline.split(' ');
                var marker = L.marker([coords[0], coords[1]], { icon: trafficIcon }).addTo(map);
                marker.bindPopup(`<b>${element.description}</b><br>Début : ${element.starttime}<br>Fin : ${element.endtime}`);
            });
        } else {
            console.error('Erreur lors de la récupération des données de circulation.');
        }
    }

    // traffic ajouter
    var traffic = <?= $traffic_response ?>;
    ajtTrafficMarkers(traffic);

    // adresse
    var adress = "https://api-adresse.data.gouv.fr/search/?q=iut+charlemagne+nancy";
    fetch(adress)
        .then(response => response.json())
        .then(data => {
            adressCoords = [data.features[0].geometry.coordinates[1], data.features[0].geometry.coordinates[0]];
            var marker = L.marker(adressCoords, { icon: adressIcon }).addTo(map);
            marker.bindPopup("<b>Adresse</b><br>Boulevard Charlemagne, Nancy");
        })
        .catch(error => console.error('Erreur lors de la récupération des données d\'adresse.', error));
</script>
<p>
    Lien utilisé pour la récupération des données :
    <ul>
        <li><a href="https://ipapi.co/">https://ipapi.co/</a></li>
        <li><a href="https://www.infoclimat.fr/public-api/gfs/xml">infoclimat</a></li>
        <li><a href="https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&outFields=*&returnGeometry=true&f=pjson">service3</a></li>
        <li><a href="https://carto.g-ny.org/data/cifs/cifs_waze_v2.json">carto</a></li>
        <li><a href="https://api-adresse.data.gouv.fr/search/?q=iut+charlemagne+nancy">api-adresse</a></li>
    </ul>
</body>
</html>