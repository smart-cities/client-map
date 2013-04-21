var map;
var page = {	
	init: function(){
		var mapOptions, constant, control, hoverControl;
		mapOptions = {
			theme: null, // Prevent /theme/style.css from being requested
			projection: new OpenLayers.Projection("EPSG:900913"),
			displayProjection: new OpenLayers.Projection("EPSG:4326"),
			//units: "m",
			//maxResolution: 156543.0339,
			//maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34),
			controls: [
				new OpenLayers.Control.Navigation({
					dragPanOptions: {
						enableKinetic: true
					}
				}),
				new OpenLayers.Control.Attribution(),
				new OpenLayers.Control.Zoom({
					zoomInId: "customZoomIn",
					zoomOutId: "customZoomOut"
				})
			]
		},
		constant = {
			/*exeter*/
			//DEFAULT_LNG: -3.533899,
			//DEFAULT_LAT: 50.718412,
			/*birmingham */
			DEFAULT_LNG: -1.887245,
			DEFAULT_LAT: 52.480689,
			// MIN_LAT: 6411819.9396445,
			// MIN_LON: -891534.32014409,
			// MAX_LAT: 8427311.5011069,
			// MAX_LON: 1065253.6036058,
			OVERALL_MAX_ZOOM: 18,
			DEFAULT_ZOOM: 11,
			MAP_TILES: new OpenLayers.Layer.OSM()		
		},
		control = new OpenLayers.Control();
		map = new OpenLayers.Map("map", mapOptions);
		map.addLayers([constant.MAP_TILES, page.dataLayer, page.overlayLayer]);
		map.zoomToMaxExtent();
		map.setCenter(new OpenLayers.LonLat(constant.DEFAULT_LNG, constant.DEFAULT_LAT).transform(map.displayProjection, map.projection), constant.DEFAULT_ZOOM);
		map.addControl(control);
		page.getData();
		$(document).bind('mousemove', function(e){
			$('#tooltip').css({
				left: e.pageX +15,
				top: e.pageY -15
			});
		});
		hoverControl = new OpenLayers.Control.SelectFeature(page.dataLayer, {
			hover: true,
			highlightOnly: true,
			renderIntent: "temporary",
			eventListeners: {
				//beforefeaturehighlighted: page.show_tooltip, //pointless calls it twice!
				featurehighlighted: page.show_tooltip,
				featureunhighlighted: page.hide_tooltip
			}
		});
		map.addControl(hoverControl);
		hoverControl.activate();
		page.registerEvents();
	},
	dataLayer: new OpenLayers.Layer.Vector("Data Layer"),
	overlayLayer: new OpenLayers.Layer.WMS(
		"smartcities:example - Tiled", "http://smartcities.switchsystems.co.uk:8080/geoserver/smartcities/wms",
		{
			LAYERS: 'smartcities:example',
			STYLES: '',
			format: 'image/png',
			tiled: true,
			transparent:true
		},
		{
			buffer: 0,
			displayOutsideMaxExtent: true,
			isBaseLayer: false,
			yx : {'EPSG:900913' : false}
		} 
	),
	data: [],
	getData: function () {
		$.ajax({
			url: "http://www.smartcities.switchsystems.co.uk/api/reading/data",
			data: "options={\"sensors\":[\"TEMP\",\"RH\",\"LIGHT\"],\"startLat\":52.5960,\"endLat\":52.3960,\"startLng\":-2.003,\"endLng\":-1.766,\"mode\":\"real\"}",
			dataType: "json",
			success: function (response) {
				page.data = response;
				page.loadMarkers();
			}
		});
	},
	loadMarkers: function () {
		page.dataLayer.removeAllFeatures();
		var locations = page.data.readings, i, location, attributes, markerStyle, marker;
		for (i = 0; i < locations.length; i++){
			location = locations[i];
			attributes = {name: location._device_id, data: location.sensorValue};
			markerStyle = {
				externalGraphic: "images/icon.png",
				graphicWidth: 25, 
				graphicHeight: 29,
				name:location.sensorValue
			};
			marker = new OpenLayers.Geometry.Point(location.device_lng, location.device_lat);
			marker.transform(map.displayProjection, map.projection);
			page.dataLayer.addFeatures([new OpenLayers.Feature.Vector(marker, attributes, markerStyle)]);
		}
	},
	show_tooltip: function(polygon) {
		// this is a bit nasty, should probably build the elements properly...
		$('#tooltip').html("<h1>Sensor: " + polygon.feature.attributes.name + "</h1><p>Temperature: "+ polygon.feature.attributes.data + "&deg; C</p>").show();
	},
	hide_tooltip: function() {
		$('#tooltip').html('').hide();
	},
	registerEvents: function(){
		$('#homeTab').addClass('active');
		$('.content').hide();
		$('#home').show();
		$('nav li a').unbind().click(function(){
			//console.log($(this).attr('id'), $(this).attr('data-page'));
			$('.content').hide();
			$('nav li a').removeClass('active');
			$('#' + $(this).attr('id')).addClass('active');
			$('#' + $(this).attr('data-page')).show();
		});
	},
	loadBuildingData: function(){
		
		$.ajax({
			url: "http://www.smartcities.switchsystems.co.uk/api/device/data/14145%2C%2014141%2C14144%2C14143%2C12630%2C14142",
			dataType: "json",
			cache: false,
			success: function (response) {
				for (i=0; i< response.deviceReadings.length; i++){
					var reading = response.deviceReadings[i].device.readings[0].dataFloat;
					$('#sensor' + (i + 1) + ' .value').html(reading + '&deg; C');
				}
			}
		});
		
		
	}
};

$(document).ready(function () {
	page.init();
	page.loadBuildingData();
	//this is horrible... but...
	window.setTimeout(function(){page.loadBuildingData()}, 120000);
});    