var map;
var page = {	
	init: function(){
		var OSM_layer, dataLayer, mapOptions, constant;
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
			DEFAULT_LNG: -3.533899,
			DEFAULT_LAT: 50.718412,
			// MIN_LAT: 6411819.9396445,
			// MIN_LON: -891534.32014409,
			// MAX_LAT: 8427311.5011069,
			// MAX_LON: 1065253.6036058,
			OVERALL_MAX_ZOOM: 18,
			DEFAULT_ZOOM: 11,
			MAP_TILES: new OpenLayers.Layer.OSM()		
		},
		control = new OpenLayers.Control()
		map = new OpenLayers.Map("map", mapOptions);
		map.addLayers([constant.MAP_TILES, page.dataLayer]);
		map.zoomToMaxExtent();
		map.setCenter(new OpenLayers.LonLat(constant.DEFAULT_LNG, constant.DEFAULT_LAT).transform(map.displayProjection, map.projection), constant.DEFAULT_ZOOM);
		map.addControl(control);
		page.getData();
		$(document).bind('mousemove', function(e){
			$('#tooltip').css({
			   left: e.pageX -100,
			   top: e.pageY -15
			});
		});
		hoverControl = new OpenLayers.Control.SelectFeature(page.dataLayer, {
			hover: true,
			highlightOnly: true,
			renderIntent: "temporary",
			eventListeners: {
				beforefeaturehighlighted: page.show_tooltip,
				featurehighlighted: page.show_tooltip,
				featureunhighlighted: page.hide_tooltip
			}
		});
		map.addControl(hoverControl);
		hoverControl.activate();
		page.registerEvents();
	},
	dataLayer: new OpenLayers.Layer.Vector("Data Layer"),
	data: [],
	getData: function () {
		$.ajax({
			url: "dummyData.json",
			dataType: "json",
			success: function (response) {
				page.data = response.data;
				page.loadMarkers();
			}
		});
	},
	loadMarkers: function () {
		page.dataLayer.removeAllFeatures();
		var locations = page.data.locations, i, location, attributes, markerStyle, marker, data = page.data;
		for (i = 0; i < locations.length; i++){
			location = locations[i];
			attributes = {name: location.name, data: location.values};
			markerStyle = {
				externalGraphic: "images/icon.png",
				graphicWidth: 25, 
				graphicHeight: 29,
				name:location.values.temperature
			};
			marker = new OpenLayers.Geometry.Point(location.lon, location.lat);
			marker.transform(map.displayProjection, map.projection);
			page.dataLayer.addFeatures([new OpenLayers.Feature.Vector(marker, attributes, markerStyle)]);
		}
	},
	show_tooltip: function(polygon) {
		$('#tooltip').html("<h1>" + polygon.feature.attributes.name + "</h1><p>Temperature: "+ polygon.feature.attributes.data.temperature + "&deg; C<br>Humidity: "+ polygon.feature.attributes.data.humidity + "%</p>").show();
	},
	hide_tooltip: function(polygon) {
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
	}
}

$(document).ready(function () {
	page.init();
});    