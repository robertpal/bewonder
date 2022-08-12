<?php 
function get_first_valid_postcode($sector){
    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, 'https://api.postcodes.io/postcodes/'.$sector.'/autocomplete');
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    $jsonData = json_decode(curl_exec($curlSession));
    curl_close($curlSession);
    return $jsonData->result[0];
}
function process_csv( $csvData ) {
    global $map_data;
    $map_data = array();   
    array_shift($csvData);
    foreach ( $csvData as $key => $csvDataLine ) {
        
        $map_data[] = array(
            'first_valid_postcode' =>  get_first_valid_postcode(preg_replace("/\s+/", "", $csvDataLine[5])),
            'sector' => $csvDataLine[5], 
            'households' =>$csvDataLine[7] 
        );

    }    
}
process_csv( array_map( 'str_getcsv', file('Camberley_RF.csv' ) ) );
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Postcode Map</title>
    <style>
        html, body, #map {
            height: 100vh;
            width: 100%;
            margin: 0px;
            padding: 0px
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDhO-UNM8tj8w3X5Cwk-_RO53msKVB8dN8"></script>
</head>
<body>
    <div id="map">Loading map</div>
<script>
document.addEventListener("DOMContentLoaded", function(){
        var locations = <?php echo json_encode($map_data);?>;
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 9,
            center: new google.maps.LatLng(53.381021, -2.608138),
            mapTypeId: google.maps.MapTypeId.TERRAIN

        });
        var infowindow = new google.maps.InfoWindow();
        var geocoder = new google.maps.Geocoder();
        var bounds = new google.maps.LatLngBounds();
        var marker, i;
        for (let sector of locations) {
            codeAddress(sector);
        }
        function codeAddress(sector) {            
            geocoder.geocode({
                'address': sector['first_valid_postcode']
            }, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    map.setCenter(results[0].geometry.location);
                    var marker = new google.maps.Marker({
                        map: map,
                        title: sector['sector'],
                        position: results[0].geometry.location
                    });
                    bounds.extend(marker.getPosition());
                    map.fitBounds(bounds);
                    google.maps.event.addListener(marker, 'click', (function (marker, location) {
                        return function () {
                            infowindow.setContent(sector['sector'] + '<br>' + sector['households']);
                            infowindow.open(map, marker);
                        };
                    })(marker, location));
                } else {
                    console.log("Geocode was not successful for the following reason: " + status);
                }
            });
        }
});
</script>
</body>
</html>

