<?php

ini_set('display_error',1);
$host = 'localhost';
$port = '5432';
$dbname = 'gis';
$user = 'postgres';
$password = '';

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

echo $conn;

if (!$conn){
    echo "Not connected : " . pg_error();
    exit;
}

if (isset($_POST['action'])) {

    switch ($_POST['action']) {
        case 'get_limit':
            get_limit();
            break;
        case 'get_near_route':
            get_near_route();
            break;
        case 'get_near_me':
            get_near_me();
            break;
        case 'density':
            density();
            break;
    }
}

function get_limit() {

    if (!empty($_POST['limit']))
        $limit = $_POST['limit'];
    else
        $limit = 20;

    if (!empty($_POST['amenities'])) {
        $amenities = $_POST['amenities'];
        $amenity_string = '\'' . $_POST['amenities'][0] . '\',';

        foreach ($amenities as $amenity) {
            $amenity_string .= '\'' . $amenity . '\',';
        }
        $amenity_string = substr($amenity_string, 0, strlen($amenity_string) - 1);
    } else
        exit;

    $query = "    select name, ST_AsGeoJSON(ST_Transform(way,4326)), amenity, null
                  from planet_osm_point 
                  where amenity in (" . $amenity_string . ")
                  and name is not null
                  limit " . $limit . ";";

    if (!$response = pg_query($GLOBALS['conn'], $query)){
        echo "Query error";
        exit;
    }
    while ($row = pg_fetch_assoc($response)){
        foreach($row as $i => $attr){
            echo $attr.", ";
        }
        echo ";";
    }
    exit;
}

function get_near_route() {

    if (!empty($_POST['amenities'])) {
        $amenities = $_POST['amenities'];
        $amenity_string = '\'' . $_POST['amenities'][0] . '\',';

        foreach ($amenities as $amenity) {
            $amenity_string .= '\'' . $amenity . '\',';
        }
        $amenity_string = substr($amenity_string, 0, strlen($amenity_string) - 1);
    } else
        exit;

    if (!empty($_POST['dist']))
        $dist = $_POST['dist'];

    if (!empty($_POST['bus']))
        $bus = $_POST['bus'];


    $query = "SELECT poi.name, ST_AsGeoJSON(ST_Transform(poi.way,4326)), poi.amenity, null
              FROM planet_osm_line as road, planet_osm_point as poi 
              WHERE road.osm_id = (select min(osm_id) from planet_osm_line where 1=1 and ref = " . '\'' . $bus . '\'' . ") 
              AND ST_DWithin(
                        ST_Transform(road.way::geometry, 3857),  
                        ST_Transform(poi.way::geometry, 3857), " . $dist . ") and poi.amenity in (" . $amenity_string . ")";


    if (!$response = pg_query($GLOBALS['conn'], $query)){
        echo $query;
        exit;
    }

    while ($row = pg_fetch_assoc($response)){
        foreach($row as $i => $attr){
            echo $attr.", ";
        }
        echo ";";
    }

    $query = "select ref, ST_AsGeoJSON(ST_Transform(way,4326)), null, null from planet_osm_line where 1=1 and ref = " . '\'' . $bus . '\'';
    if (!$response = pg_query($GLOBALS['conn'], $query)){
        echo "Query error";
        exit;
    }
    while ($row = pg_fetch_row($response)){
        foreach($row as $i => $attr){
            echo $attr.", ";
        }
        echo ";";
    }
    exit;
}

function get_near_me() {
    if (!empty($_POST['amenities'])) {
        $amenities = $_POST['amenities'];
        $amenity_string = '\'' . $_POST['amenities'][0] . '\',';

        foreach ($amenities as $amenity) {
            $amenity_string .= '\'' . $amenity . '\',';
        }
        $amenity_string = substr($amenity_string, 0, strlen($amenity_string) - 1);
    } else
        exit;

    if (!empty($_POST['dist']))
        $dist = $_POST['dist'];

    if (!empty($_POST['gps_lat']) && !empty($_POST['gps_lon']))
        $gps = $_POST['gps_lon'] . ' ' . $_POST['gps_lat'];


    $query = "SELECT poi.name, ST_AsGeoJSON(ST_Transform(poi.way,4326)), poi.amenity, null 
              FROM planet_osm_point as poi 
              WHERE 1=1 
              AND ST_DWithin(
                        ST_Transform(" . '\'' . "SRID=4326;POINT(" . $gps . ")" . '\'' . "::geometry, 3857),  
                        ST_Transform(poi.way::geometry, 3857), " . $dist . ") and poi.amenity in (" . $amenity_string . ")";


    if (!$response = pg_query($GLOBALS['conn'], $query)){
        echo $query;
        exit;
    }

    while ($row = pg_fetch_row($response)){
        foreach($row as $i => $attr){
            echo $attr.", ";
        }
        echo ";";
    }

    exit;
}

function density(){
    if (!empty($_POST['amenities'])) {
        $amenities = $_POST['amenities'];
        $amenity_string = '\'' . $_POST['amenities'][0] . '\',';

        foreach ($amenities as $amenity) {
            $amenity_string .= '\'' . $amenity . '\',';
        }
        $amenity_string = substr($amenity_string, 0, strlen($amenity_string) - 1);
    } else
        exit;


    $query = "  select pol.name, ST_AsGeoJSON(ST_Transform(pol.way,4326)), null, count(poi.name)
                from planet_osm_polygon pol
                cross join planet_osm_point poi
                where pol.admin_level = '10' and st_contains(ST_Transform(pol.way,4326), ST_Transform(poi.way,4326))
                and poi.amenity in (" . $amenity_string . ")
                and poi.name is not null
                group by pol.name, pol.way";

    if (!$response = pg_query($GLOBALS['conn'], $query)){
        echo "Query error";
        exit;
    }

    while ($row = pg_fetch_assoc($response)){
        foreach($row as $i => $attr){
            echo $attr.", ";
        }
        echo ";";
    }
    exit;
}

?>