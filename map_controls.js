var map = L.map('map').setView([42.35, -71.08], 13);
map.setView(new L.LatLng(48.1486, 17.1077), 13);


var x = document.getElementById("coordinates");
var pos;

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition);
    } else {
        x.innerHTML = "Geolocation is not supported by this browser.";
    }
}

function showPosition(position) {
    x.innerHTML = "Latitude: " + Math.round(position.coords.latitude * 10000) / 10000 +
        "<br>Longitude: " + Math.round(position.coords.longitude * 10000) / 10000;

    pos = position.coords;
}

function getColor(d) {
    if(d.density === null)
        switch (d.type)
        {
            case 'pub':
                return "#CC9900";
            case 'biergarten':
                return "#cc1114";
            case 'cafe':
                return "#000000";
            case 'restaurant':
                return "#0fef00";
            case 'bar':
                return "#5359cc";
        }
    else {
        d = d.density;

        return d > 150 ? '#5a001d' :
            d > 100 ? '#BD0026' :
                d > 80 ? '#E31A1C' :
                    d > 60 ? '#FC4E2A' :
                        d > 40 ? '#FD8D3C' :
                            d > 20 ? '#FEB24C' :
                                d > 10 ? '#FED976' :
                                    '#FFEDA0';
    }
}

// load a tile layer
L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 20,
        minZoom: 7
    }).addTo(map);

function checkAll(buttonClicked, data)
{
    if (!data.amenities.length)
    {
        alert("You must select at least one amenity !");
        return false;
    }

    switch (data.action) {
        case 'get_near_route':
            if (data.bus === "" || data.dist === "")
            {
                alert("You must define bus line and distance to search for amenities!");
                return false;
            }
            break;
        case 'get_near_me':
            if (data.dist === "")
            {
                alert("You must define distance to search for amenities!");
                return false;
            }
            break;
        default:
            return true;
    }

    return true;
}

getLocation();

// load GeoJSON from an external file
$(document).ready(function(){
    console.log(pos);

    $('.mybutton').click(function(){
        var buttonClicked = this.getAttribute('name');
        var ajaxurl = 'query_module.php';
        var limit = document.getElementById("limit").value;
        var bus = document.getElementById("bus").value;
        var dist = document.getElementById("dist").value;

        var amenities = [];
        $.each($("input[name='amenities']:checked"), function(){
            amenities.push($(this).val());
        });

        data =  {'action': buttonClicked,
            'limit' : limit,
            'bus' : bus,
            'amenities' : amenities,
            'dist' : dist,
            'gps_lat': pos.latitude,
            'gps_lon': pos.longitude
        };

        console.log(data);

        if(!checkAll(buttonClicked, data))
        {
            console.log('error');
            return;
        }

        $.post(ajaxurl, data, function (response) {
            // Response div goes here.
            //alert(response);
            map.eachLayer(function(layer){
                if (typeof layer._url === "undefined"){
                    map.removeLayer(layer);
                }
            });
            var geojson = {
                "type": "FeaturesCollection",
                "features": []
            };
            var dataArray = response.split(", ;");
            dataArray.pop();
            dataArray.forEach (function (d){
                d = d.split(", ");
                var feature = {
                    "type": "Feature",
                    "properties": {
                        name: d[0].toString().replace("Resource id #4", ""),
                        type: d[2] ? d[2].toString() : null,
                        density: d[3] ? d[3].toString () : null
                    },
                    "geometry": JSON.parse(d[1])
                };
                geojson.features.push(feature);
            });

            var mapDataLayer = L.geoJson(geojson, {
                style: function(feature)
                {
                    return {
                        fillColor: getColor(feature.properties),
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.7
                    };
                },

                pointToLayer: function (feature, latlng) {
                    var markerStyle = {
                        radius: 8,
                        color: "white"
                    };
                    return L.circleMarker(latlng, markerStyle);
                },



                onEachFeature: function (feature, layer) {
                    var html = feature.properties.name + " (";

                    if (feature.properties.density !== null)
                        html += feature.properties.density + ")";
                    else
                        html += feature.properties.type + ")";

                    layer.bindPopup(html);
                }
            }).addTo(map);
        });
    });

});

