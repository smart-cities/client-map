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
		$(document).bind('mousemove', function(e){
			$('#tooltip').css({
			   left: e.pageX + 15,
			   top: e.pageY + 15
			});
		});
		/*hoverControl = new OpenLayers.Control.SelectFeature(page.dataLayer, {
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
		hoverControl.activate();*/
		page.registerEvents();
	},
	dataLayer: new OpenLayers.Layer.Vector("KML", {
		strategies: [new OpenLayers.Strategy.Fixed()],
		protocol: new OpenLayers.Protocol.HTTP({
			url: "temp/d3e763bf-c11a-4a1b-b995-03ccfc754778.kml",
			format: new OpenLayers.Format.KML({
				extractStyles: true, 
				extractAttributes: true,
				maxDepth: 2
			})
		})
	}),
	data: [],
	get_data: function () {
		/*$.ajax({
			url: "fileadmin/data/latestForecast.json",
			dataType: "json",
			success: function (data) {
				page.data = data;
				page.loadMarkers('1');
			}
		});*/
	},
	loadMarkers: function (period_id) {
		page.dataLayer.removeAllFeatures();
		var locations = [], a, b, c, data = page.data;
		for (a = 0; a < data.locations.length; a++){
			//console.log(data.locations[a]);
			var location = data.locations[a];
			locationName = location.name;
			lat = location.lat;
			lon = location.lon;
			for (b = 0; b < location.periods.length; b++){
				period = location.periods[b];
				if (period.id === period_id){
					for (c = 0; c < period.detail.length; c++){
						detail = period.detail[c];
						text = detail.text + " <br>Max: " + detail.maxTemp + "&degc Min: " + detail.minTemp + "&degc <br>Wind: " + detail.windSpeed + " " + detail.windDirection;
						attributes = {name: locationName, desc: text};
						var markerStyle = {
							externalGraphic: "typo3conf/ext/forecastBackend/img/" + detail.icon, 
							graphicWidth: 48, 
							graphicHeight: 48, 
							graphicYOffset: -24,//-16, 
							graphicOpacity: 0//0.7
						};
						marker = new OpenLayers.Geometry.Point(lon, lat);
						marker.transform(map.displayProjection, map.projection);
						//var imgTitle = 'Lat '+coordinate.lat+', long '+coordinate.lon;
						page.dataLayer.addFeatures([new OpenLayers.Feature.Vector(marker, attributes, markerStyle)]);
					}
				}
			}
		}
	},
	show_tooltip: function(polygon) {
		//console.log(polygon.feature);
		$('#tooltip').html("<h1>" + polygon.feature.attributes.name + "</h1><p>"+ polygon.feature.attributes.desc + "</p>").show();
	},
	hide_tooltip: function(polygon) {
		$('#tooltip').html('').hide();
	},
	showTab: function(tabId, sectionId){
		
	},
	registerEvents: function(){
		$('#homeTab').addClass('active');
		$('.content').hide();
		$('#home').show();
		$('nav li a').unbind().click(function(){
			console.log($(this).attr('id'), $(this).attr('data-page'));
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