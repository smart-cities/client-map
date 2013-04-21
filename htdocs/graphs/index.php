<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Device Graphs</title>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
</head>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" charset="utf-8"></script>
<style>

svg {
	font: 12px sans-serif;
}

.line {
	fill: none;
	stroke: #ff7f0e ;
	stroke-width: 1.5px;
}

.line1 {
	stroke:#1f77b4;
	background-color:#1f77b4;
}
.line2 {
	stroke: #2ca02c;
	background-color:#2ca02c
}
.line3 { stroke: #d62728; background-color:#d62728; }
.line4 { stroke: #9467bd; background-color:#9467bd;}
.line5 { stroke: #8c564b; background-color:#8c564b;}
.line6 { stroke: #e377c2; background-color:#e377c2;}
.line7 { stroke: #7f7f7f; background-color:#7f7f7f;}

.axis path, .axis line {
	fill: none;
	stroke: #000;
	shape-rendering: crispEdges;
}

.legend { color:#fff; width:100px; float:left; font-weight:bold; padding:1px 3px; }

</style>
<body>
<h1>Device Graphs</h1>

<?php
$devices = array(14141 => 2,14142 => 6, 14143 => 4, 14144 => 3, 14145 => 1, 12630 => 5);

$x=0;
foreach ($devices as $deviceId => $index) {
$x++;
echo '<div class="legend line'.$x.'">Device '.$index.' <br/> #'.$deviceId.'</div>';
}

?>

<script>

var path, x,y,svg;

<?php foreach ($devices as $deviceId => $index) { ?>
d3.json(
		'/api/device/readings/<?=$deviceId;?>?period=21600',
		function (jsondata) {
			data_<?=$deviceId;?> = jsondata.deviceReadings;
			init(data_<?=$deviceId;?>);
		});
<? } ?>

var minY = 0, maxY = 50;

var margin = {top: 10, right: 10, bottom: 20, left: 40},
width = 960 - margin.left - margin.right,
height = 500 - margin.top - margin.bottom;

var yaxis;
var linestore = new Array();
var datastore = new Array();

var firstInit = false
var lineCount = 0;

function init(data) {

	data.forEach(function(d) {
		d.date = new Date(d.timestamp*1000);
	});
	datastore.push(data);

	if (firstInit==false) {

		lineCount++;
		maxY = d3.max(data.map(function(d) { return d.dataFloat*1; }));
		maxY = 50;

		y = d3.scale.linear()
		.domain([0,maxY])
		.range([height, 0]);

		x = d3.time.scale()
			.domain([new Date(data[data.length - 1].date),new Date(data[0].date)])
			.range([0, width]);

		var line = d3.svg.line()
		.x(function(d) { return x(d.date); })
		.y(function(d,i) { return y(d.dataFloat); });

		linestore.push(line);

		svg = d3.select("body").append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

		svg.append("defs").append("clipPath")
		.attr("id", "clip")
		.append("rect")
		.attr("width", width)
		.attr("height", height);

		svg.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + height + ")")
		.call(d3.svg.axis().scale(x).orient("bottom"));

		yaxis = svg.append("g")
		.attr("class", "y axis")
		.call(d3.svg.axis().scale(y).orient("left"));

		var path = svg.append("g")
		.attr("clip-path", "url(#clip)")
		.append("path")
		.data([data])
		.attr("class", "line line1")
		.attr("d", line);

		firstInit = true;

	} else {

		lineCount++;

		if (d3.max(data.map(function(d) { return d.dataFloat; })) > maxY) {
/*
			maxY = d3.max(data.map(function(d) { return d.dataFloat*1; }));

			y = d3.scale.linear()
			.domain([0,maxY])
			.range([height, 0]);

			yaxis.transition()
			.duration(1000).call(d3.svg.axis().scale(y).orient("left"));

			for ($i=0;$i<linestore.length;$i++) {


				linestore[$i].transition()
						.duration(1000)
						.attr("y",function(d,i) { return y(d.dataFloat); });
			}*/

		}

		var line = d3.svg.line()
		.x(function(d) { return x(d.date); })
		.y(function(d,i) { return y(d.dataFloat); });

		linestore.push(line);

		var path = svg.append("g")
		.attr("clip-path", "url(#clip)")
		.append("path")
		.data([data])
		.attr("class", "line line"+ lineCount)
		.attr("d", line);

	}

}

function tick() {

	// push a new data point onto the back
	data.push(random());

	// redraw the line, and slide it to the left
	path
	.attr("d", line)
	.attr("transform", null)
	.transition()
	.duration(500)
	.ease("linear")
	.attr("transform", "translate(" + x(-1) + ")")
	.each("end", tick);

	// pop the old data point off the front
	data.shift();

}

</script>

</body>
</html>