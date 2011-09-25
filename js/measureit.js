if( pc === 1 ){
	// load navi
	$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
		navigation_main( data );
	})

	var timer = setTimeout(hist_update, 10000);
	$('.ui-tabs').click(function(){ $('.ui-tabs').css( 'height', '100%' ) });
}


// functions

// data from start page
function hist_update(stop){
	clearTimeout(timer);
	if( stop == '1'){ return true; }
	$.getJSON('php/measureit_functions.php', { 'do' : 'summary_start' }, function(data) {
		$.each(data, function(d){
				$('#'+data[d].sensor.sensor_id).remove();
				$('#summary').append('<div id="'+data[d].sensor.sensor_id+'">');
				$('#'+data[d].sensor.sensor_id).addClass('ui-widget-content ui-corner-all sensor').append('<h5 class="ui-widget-header ui-corner-all">' + data[d].sensor.position_description + '</h5>');
				$('#'+data[d].sensor.sensor_id).append( '<div id="tmpr'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">temperature</h5></div><div class="data refresh">'+data[d].tmpr+' C</div></div>' );
				$('#'+data[d].sensor.sensor_id).append( '<div id="watt'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">current watt</h5></div><div class="data refresh">'+data[d].watt + 'W</div></div>' );
				$('#'+data[d].sensor.sensor_id).append( '<div id="hourly"'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">last hour</h5></div><div class="data">'+data[d].hourly + price_format( data[d].hourly, data[d].sensor.measure_price, data[d].sensor.measure_currency, 'kWh')+'</div></div>' );
				$('#'+data[d].sensor.sensor_id).append( '<div id="daily"'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">last day</h5></div><div class="data">'+data[d].daily + price_format( data[d].daily, data[d].sensor.measure_price, data[d].sensor.measure_currency, 'kWh') +'</div></div>' );
				$('#'+data[d].sensor.sensor_id).append( '<div id="weekly"'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">last 7 days</h5></div><div class="data">'+data[d].weekly + price_format( data[d].weekly, data[d].sensor.measure_price, data[d].sensor.measure_currency, 'kWh')+'</div></div>' );
				$('#'+data[d].sensor.sensor_id).append( '<div id="monthly"'+data[d].sensor.sensor_id+'" class="ui-widget-content ui-corner-all sensor-inner"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">last 30 days</h5></div><div class="data">'+data[d].monthly + price_format( data[d].monthly, data[d].sensor.measure_price, data[d].sensor.measure_currency, 'kWh') +'</div></div>' );
		});
	})
	$('.refresh').css('background-color','darkred');
	timer = setTimeout(hist_update, 10000)
}

function navigation_main( data ) {
	hist_update('0');
	$('#tabcontainer li').remove();
	$('#tabcontainer').append('<li class="ui-state-default ui-corner-top" value="0"><a href="#tabs-0" name="0">Home</a></li>');
	
	$.each( data, function(d){
		$('#tabcontainer').append('<li class="ui-state-default ui-corner-top" value="'+data[d].sensor.sensor_id+'"><a href="#tabs-' + data[d].sensor.sensor_id + '" name="'+data[d].sensor.sensor_id+'">' + data[d].sensor.position_description + '</a></li>');
		$('#tabs').append('<div id="tabs-'+data[d].sensor.sensor_id+'"><div id="menu'+data[d].sensor.sensor_id+'" class="menu" /><div id="det'+data[d].sensor.sensor_id+'" class="det"><div class="placeholder" id="placeholder' +data[d].sensor.sensor_id+'" /><div class="overview" id="overview' +data[d].sensor.sensor_id+'" /></div></div>');
		});
	$('#tabcontainer').append('<li class="ui-state-default ui-corner-top" value="11"><a href="#tabs-11" name="11">Setup</a></li>');
	$('#tabs').tabs({
	    select: function(event, ui){
			$('.tooltip').hide();
			if(data[ui.index]){
				sensor_detail($(ui.tab).attr('name'));
				hist_update('1');
				return true;
			}
			if(ui.index == '0'){
				hist_update('0');
				return true;
			}
			if(!data[ui.index]){
				measureit_admin( data );
				hist_update('1');
				return true;
			}
	    }
	});
	
};

function sensor_statistic( data, sensor ){
	$('#statistic'+sensor).remove();
	container_get('#menu'+ sensor, 'statistic'+sensor,'statistic');
	button_get('#statistic'+sensor,'sensor_statistic'+sensor,'costs');
	button_get('#statistic'+sensor,'sensor_statistic_multiple_week'+sensor,'last 7 days');
	button_get('#statistic'+sensor,'sensor_statistic_multiple_year'+sensor,'last 12 months');
	$('#sensor_statistic'+sensor).click(function( ) { 
		$('#placeholder'+sensor).empty();
		$('#overview'+sensor).empty();
		$('#placeholder'+sensor).unbind();
		$('.tooltip').remove();
		$('.sensor_legend').remove();
		
		$.each( data, function(d){
			div_get('#placeholder'+sensor,'sensor_position'+d,'');
			div_get('#sensor_position'+d,'sensor_position_statistic'+d,data[d].description,'pointer statistic_position');
			$('#sensor_position_statistic'+d).click(function( ){
				$('.sensor_statistic_table').remove();
				sensor_statistic_generate(data, sensor, d);	
			});
		});
		
	});
	
	$('#sensor_statistic_multiple_week'+sensor).click( function(){ sensor_history_get( sensor, 'week' ); });
	$('#sensor_statistic_multiple_year'+sensor).click( function(){ sensor_history_get( sensor, 'month' ); });
}

function sensor_statistic_generate( data, sensor, position ){
	var price_kwh = 0;
	var currency = '';
	$.getJSON('php/measureit_functions.php', { 'do' : 'sensor_detail', 'sensor' : sensor }, function(d){
		price_kwh = d.sensor[sensor].measure_price;
		currency = d.sensor[sensor].measure_currency;
	} );
	$.getJSON('php/measureit_functions.php', {
		'do' : 'sensor_statistic',
		'sensor_position': position,
		'sensor' : sensor,
		'table' : 'measure_watt_daily',
		'timeframe' : 'position',
		'range_from' : data[position].time.substr( 0, 10 ),
		'order' : 'day_id',
		'turn' : 'desc' 
		}, function(d) {
			$.each( d, function( v ){
				div_get('#sensor_position'+position, 'sensor_statistic_table'+v, v+span_get('sensor_statistic_year_watt'+v, '', 'float_right statistic_year_watt statistic_data'), 'sensor_statistic_table level1');
				
				// year
				$.each( $(d[v]), function( y ){
					var kwh_year = 0;
					$('#sensor_statistic_table'+y).remove();
					div_get('#sensor_statistic_table'+v, 'sensor_statistic_year'+v, '', 'level2');
					// month
					$.each( this, function( m ){
						var statistics_val_month = button_get();
						div_get('#sensor_statistic_year'+v, 'sensor_statistic_month'+v+m, '', 'level3 pointer');
						div_get('#sensor_statistic_month'+v+m,'',m+span_get('sensor_statistic_month_watt'+v+m, '', 'float_right statistic_month_watt statistic_data'), 'month_header');
						div_get('#sensor_statistic_month'+v+m, 'sensor_statistic_month_container'+v+m, '', 'level3 hidden');
						
						var kwh_month = 0;
						$('#sensor_statistic_month'+v+m).click(function(){
							$('#sensor_statistic_month_container'+v+m).toggle('slow');
							$('#tabs').css('height','100%');
							$('#overview'+sensor).css('display','none');
							});
						//days
						$.each( this, function( d ){
							div_get('#sensor_statistic_month_container'+v+m, 'sensor_statistic_day'+v+m+d, '', 'level4');
							div_get('#sensor_statistic_day'+v+m+d,'',d+' '+this.weekday+span_get('sensor_statistic_day_watt'+v+m+d, '', 'float_right statistic_day_data statistic_data'),'day_header');
							$('#sensor_statistic_day_watt'+v+m+d).append( parseFloat(this.data).toFixed(2) + ' kwh - ' );
							$('#sensor_statistic_day_watt'+v+m+d).append( ( parseFloat(this.data).toFixed(2) .replace('.','') * price_kwh ).toFixed(2) + ' ' + currency );
							kwh_month = kwh_month + parseFloat(this.data);
							});
							$('#sensor_statistic_month_watt'+v+m).append( kwh_month.toFixed(2) + ' kwh - ' );
							$('#sensor_statistic_month_watt'+v+m).append( ( kwh_month.toFixed(2).replace('.','') * price_kwh ).toFixed(2) + ' ' + currency );
							kwh_year += kwh_month;
						});
					
						$('.level4:even').addClass('even');
						$('#sensor_statistic_year_watt'+v).append( kwh_year.toFixed(2) + ' kwh - ' );
						$('#sensor_statistic_year_watt'+v).append( ( kwh_year.toFixed(2) .replace('.','') * price_kwh ).toFixed(2) + ' ' + currency );
					});
				});
		});
}

function sensor_positions( data, sensor ){
	$('#positions'+sensor).remove();
	container_get('#menu'+ sensor, 'positions'+sensor,'positions');
	$.each( data, function(d){
		button_get('#positions'+sensor,'sensor_position'+sensor,data[d].description);
	});
	$("a", ".button").click(function( ) { return false; });
}

function sensor_data_selection( sensor ){
	$('#date'+ sensor).remove();
	// choose day
	container_get('#menu'+sensor,'date'+sensor,'Sensor details');
	$('#date'+ sensor).append( '<button id="reload'+sensor+'" class="date button">refresh</button><br />' );
	$('#date'+ sensor).append( '<input id="date_picker'+sensor+'" class="date button" value="choose day"/>' );

	$('#date_picker'+sensor).datepicker({
		maxDate: '+0D',
		showButtonPanel : true,
		onSelect: function(dateText, inst) {
			sensor_data_selected( inst, sensor, inst.selectedDay+'-'+inst.selectedMonth+'-'+inst.selectedYear+' Kwh' );
			$('.ui-datepicker-inline').hide();
	 	}
	});

	var datepicker_from = 'date_picker_from'+sensor;
	var datepicker_to = 'date_picker_to'+sensor;	
	$('#date'+ sensor).append( '<br /><input id="'+datepicker_from+'" class="date dateselect button" value="day from" /><input id="'+datepicker_to+'" class="date dateselect button" value="day to" />' );

	$('.dateselect').datepicker({
		maxDate: '+0D',
		showButtonPanel : true,
		onSelect: function() {
			var from_str = $('#'+datepicker_from).val();
			var to_str = $('#'+datepicker_to).val();
			if( from_str !== 'day from' && to_str !== 'day to'){
					var from = from_str.split('/');
					var to = to_str.split('/');
					var data = {
						'range_to' : to[2]+'-'+to[0]+'-'+to[1]+'_0',
						'range_from' : from[2]+'-'+from[0]+'-'+from[1]+'_0',
						'day_range' : true
						}
					sensor_data_selected( data, sensor, from[2]+'-'+from[0]+'-'+from[1]+' - '+to[2]+'-'+to[0]+'-'+to[1]+' Kwh' );
				};
	 	}
	});
	$('#date'+ sensor).append( '<br /><button id="select_output_'+sensor+'" class="button"><span id="w'+sensor+'" class="active_element">Watt</span> / <span id="t'+sensor+'">Temperature</span></button><input type="hidden" class="current_display" id="show'+sensor+'" value="w" />' );
	$('#date'+ sensor).append( '<input type="hidden" class="current_display" id="show'+sensor+'" value="w" />' );
	
	$('#select_output_'+sensor).toggle(
			function() {
			$('#show'+sensor).val('t');
			$('#t'+sensor).addClass('active_element');
			$('#w'+sensor).removeClass('active_element');
			$('#'+ datepicker_from).fadeOut('slow');
			$('#'+ datepicker_to).fadeOut('slow');
			$('#reload'+sensor).fadeOut('slow');
		}, function() {
			$('#show'+sensor).val('w');
			$('#w'+sensor).addClass('active_element');
			$('#t'+sensor).removeClass('active_element');
			$('#'+ datepicker_from).fadeIn('slow');
			$('#'+ datepicker_to).fadeIn('slow');
			$('#reload'+sensor).fadeIn('slow');
		});
	$('#reload'+sensor).click( function(){ sensor_detail(sensor) } );
	$(".button").button();
}

function sensor_data_selected( data, sensor, info ){
	var unit_value = $('#show'+sensor).val() === 't' ? '24' : '2';
	var unit = 'HOUR';
	var table = $('#show'+sensor).val() === 't' ? 'measure_tmpr' : 'measure_watt';
	var timeframe = 'static';
	var select = 'time';
	var xaxis = 'time';
	var lines = true;
	var points = false;
	var bars = false;
	var range_from = false;
	var range_to = false;
	var hoverable = false;
	var clickable = false;
	var selection = 'x';
	var query = false;
	var options = false;
	var info = info !== undefined ? info : 'Watt last 2 hours';

	if(data.selectedDay){
		var unit = 'DAY';
		var month = data.selectedMonth + 1;
		var select = data.selectedYear + '-' + month + '-' + data.selectedDay;
		if($('#show'+sensor).val() === 't'){
			var table = 'measure_tmpr_hourly';
			var unit_value = '1';
			var info = select+' C hourly';
		}else{
			var table = 'measure_watt_hourly';
			var info = select+' W 2 hourly';
		}
		var timeframe = 'select';
		var clickable = true;
		var hoverable = true;
		var points = true;	
		
	}else if(data.day_range){
		var unit = 'DAY';
		var timeframe = 'range';
		var clickable = true;
		var hoverable = true;
		var points = true;
		var range_from = data.range_from;
		var range_to = data.range_to;
		
		if($('#show'+sensor).val() === 't'){
			var table = 'measure_tmpr_hourly';
			var unit_value = '1';	
			var info = select+' C hourly';
		}else{
			var table = 'measure_watt_daily';
		}
	}else if(data.range_from){
		var timeframe = $('#show'+sensor).val() === 't' ? 'static' : 'range';
		var range_from = data.range_from;
		var range_to = data.range_to;
	}

	var query = {
		"do" : "summary_sensor",
		"sensor" : sensor,
		"timeframe" : timeframe,
		"unit" : unit,
		"unit_return" : "timeframe",
		"unit_value" : unit_value,
		"table" : table,
		"select" : select,
		"range_from" : range_from,
		"range_to" : range_to
	}
	
	var options = {
        xaxis: { mode: xaxis },
        selection: { mode: selection },
        lines: { show: lines, lineWidth: 0.5, fill: true, fillColor: "rgba(255, 255, 255, 0.7)" },
        points: { show: points, radius: 2 },
        select : select,
        grid: { hoverable: hoverable,
            	clickable: clickable,
            	backgroundColor: { colors: ["#fff", "#888"] } }
	};
	graph_draw(sensor,query,options, info);
	$('#placeholder'+sensor).unbind();
}

function sensor_detail(data){
	$.getJSON('php/measureit_functions.php', { 'do' : 'sensor_detail', 'sensor' : data }, function(d){
		if(  d === null ){ return true; }
		sensor_data_selected(false, data);
		sensor_statistic( d.sensor[data].positions, data );
		sensor_data_selection( data, 'last 2 hour watt' );
	});
};

function graph_draw(sensor, query, options, info){
	$('.tooltip').remove();
	$('.sensor_legend').empty();
	$('#overview'+sensor).css('display','inline');
	$.getJSON('php/measureit_functions.php', query, function(d) {
				var plot = false;
	    		var placeholder = '#placeholder'+sensor;
			    var timeline = '#overview'+sensor;
			    var overview = '#overview'+sensor;
			    $(placeholder).empty();
			    $(timeline).empty();
			    var plot = $.plot($(placeholder), [d], options);
			    var overview = $.plot($(timeline), [d], {
			        series: {
			            lines: { show: true, lineWidth: 1, steps: true },
			            shadowSize: 0
			        },
			        xaxis: { ticks: [], mode: "time" },
			        yaxis: { ticks: [], min: 0, autoscaleMargin: 0.1 },
			        selection: { mode: "x" },
			        legend: { show: true, position: 'no' },
			        grid: { hoverable: true, clickable: true }
			    });

			    $(placeholder).bind("plothover", function (e, pos, item) {
			        $("#x").text(pos.x.toFixed(2));
			        $("#y").text(pos.y.toFixed(2));
			            if (item) {
		                    $("#tooltip"+sensor).remove();
		                    var x = item.datapoint[0].toFixed(2),
		                        y = item.datapoint[1].toFixed(2);
		                    showTooltip(item.pageX, item.pageY, y, sensor);
			            }
			    });
			    
			    if( $('#show'+sensor).val() !== 't' ){
				    	$(placeholder).bind("plotclick", function (e, pos, item) {
					        if (item) {
								$("#tooltip"+sensor).remove();
								if(options.select !== 'time'){
					        		var d = new Date(item.datapoint[0]);
						        	var hour_from = d.getHours()-2;
						        	var hour_to = d.getHours()-1;
									var dat = {
											"range_from" : options.select+'_'+hour_from,
											"range_to" : options.select+'_'+hour_to
										}
									var info = options.select+' '+hour_from+'-'+hour_to;
								}else if(options.select === 'time'){
					        		var d = new Date(item.datapoint[0]);
									var dat = {
											"selectedDay" : d.getDate(),
											"selectedMonth" : d.getMonth(),
											"selectedYear" : d.getFullYear()
										}
								}
								sensor_data_selected( dat, sensor, info );
								$(placeholder).unbind();
					        }
					    });
				    }
			    
			    $(placeholder).bind("plotselected", function (event, ranges) {
			        plot = $.plot($(placeholder), [d],
			                      $.extend(true, {}, options, {
			                          xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
			                      }));
			        overview.setSelection(ranges, true);
			    });
			    
			    $(timeline).bind("plotselected", function (event, ranges) {
			        plot.setSelection(ranges);
			    });

			    infobox(placeholder, info);
		});
}

function graph_draw_multiple( d, sensor, range, exclude){
	$('#placeholder'+sensor).unbind();
	$('.sensor_legend').empty();
	$('#overview'+sensor).empty();
	$('.tooltip').remove();
	div_get('#placeholder'+sensor,'sensor_usage'+sensor,'');
	div_get('#tabs-'+sensor,'sensor_legend'+sensor,'','sensor_legend');
	div_get('#sensor_legend'+sensor,'container-legend','','legend-container float_left');
	div_get('#sensor_legend'+sensor,'container-selection','','selection-container float_left');
	
	var dataset = [];
	var cnt = 1;
	var label = '';
	
	$.each(d, function(dat){
		var label = range == 'week' ? day_get(dat) : month_get(dat);
		var tmp = [];
		div_get('#container-selection','container-'+dat,'','check-container float_left');
		checkbox_get('#container-'+dat,dat,'displaythis',dat+' sensor_legend',dat,1);
		$('#container-'+dat).append(dat+' '+label);
		$.each( d[dat], function( set ){
			tmp.push([parseFloat(set), parseFloat(d[dat][set].data)]);
		});
		var day = {
            label: dat+' '+label,
            id: dat,
            data: tmp,
            yaxes: cnt
        };
		dataset.push(day);
		cnt++;
	});

	$("#placeholder"+sensor).bind("plothover", function (e, pos, item, sensor) {
        $("#x").text(pos.x.toFixed(2));
        $("#y").text(pos.y.toFixed(2));
            if (item) {
            	var description = dataset.length == 8 ? item.series.label+'<br />'+item.datapoint[0]+':00 ' : item.series.label+' day: '+item.datapoint[0]+'<br />';
                $("#tooltip"+sensor).remove();
                var x = item.datapoint[0].toFixed(2),
                    y = item.datapoint[1].toFixed(2);
                showTooltip(item.pageX, item.pageY, description+' '+y+' kwh', sensor);
            }
    });
	$.plot($("#placeholder"+sensor), dataset, {
        selection: { mode: "x" },
        grid: { hoverable: true },
        lines: { show: true, lineWidth: 1 },
        points: { show: true, radius: 2 },
        legend: { show: true, container: $('#container-legend') }
    });
	
	$('#tabs').css('height','100%');
	
	$('.check-container').find('input.sensor_legend').click(function(){
		$('.tooltip').remove();
		var selection = [];
		var dataset = [];
		var cnt = 1;
		$('.check-container').find('input:checked').each(function(){ 
			selection.push($(this)[0].id);
		});
			
		$.each(d, function(dat){
			if( $.inArray(dat, selection) != -1 ){
				var label = range == 'week' ? day_get(dat) : month_get(dat);
				var tmp = [];
				$.each( d[dat], function( set ){
					tmp.push([parseFloat(set), parseFloat(d[dat][set].data)]);
				});
				var day = {
		            label: dat+' '+label,
		            id: dat,
		            data: tmp,
		            yaxes: cnt
		        };
				dataset.push(day);
				cnt++;
			}
			
		});
		
		$("#placeholder"+sensor).bind("plothover", function (e, pos, item, sensor, range) {
	        $("#x").text(pos.x.toFixed(2));
	        $("#y").text(pos.y.toFixed(2));
	            if (item) {
	            	var description = dataset.length == 8 ? item.series.label+'<br />'+item.datapoint[0]+':00 ' : item.series.label+' day: '+item.datapoint[0]+'<br />';
	                $("#tooltip"+sensor).remove();
	                var x = item.datapoint[0].toFixed(2),
	                    y = item.datapoint[1].toFixed(2);
	                showTooltip(item.pageX, item.pageY, description+' '+y+' kwh', sensor);
	            }
	    });
		$.plot($("#placeholder"+sensor), dataset, {
	        selection: { mode: "x" },
	        grid: { hoverable: true },
	        lines: { show: true, lineWidth: 1 },
	        points: { show: true, radius: 2 },
	        legend: { show: true, container: $('#container-legend') }
	    });
	});
}

function sensor_history_get( sensor, range ){
	var days = range == 'week' ? 8 : 365;
	var table = range == 'week' ? 'measure_watt_hourly' : 'measure_watt_daily';
	var arrange = range == 'week' ? false : 'month';
	var call = range == 'week' ? 'sensor_history_week' : 'sensor_history_year';
	$.getJSON('php/measureit_functions.php', {
		"do" : call,
		"sensor" : sensor,
		"timeframe" : 'static',
		"unit" : 'day',
		"unit_return" : "timeframe",
		"unit_value" : days,
		"table" : table,
		'arrange' : arrange
	}, function( d ) {
		graph_draw_multiple( d, sensor, range );
	});	
}

function showTooltip(x, y, contents, sensor ) {
	$('.tooltip').remove();
	$('<div class="tooltip">' + contents + '</div>').css( {
		position: 'absolute',
		display: 'none',
		top: y + 5,
		left: x + 5,
		border: '1px solid #fdd',
		padding: '2px',
		'background-color': '#fee',
		opacity: 0.80
	}).appendTo("body").fadeIn(200);
}

function infobox(placeholder, info){
	$(placeholder).append('<div id="infobox" class="ui-widget-content ui-corner-all" style="display: none;">'+info+'</div>');
	$("#infobox").show('clip',{},100,function(){
		setTimeout(function(){ 
			$("#infobox:visible").removeAttr('style').hide().fadeOut(); 
			}, 3000);
		});
};

function price_format( d,p,c,u ){
	if( d === null || d == '' ){return ' - ' + u + '<br />---';}
	return ' ' + u + '<br />'+ (parseFloat(d).toFixed(2).replace('.','') * p).toFixed(2) + ' ' + c;
}

function div_empty_get(parent,id,css){
	$(parent).append('<div id="'+id+'" class="'+css+'" />');
}

function div_get(parent,id,value,css){
	$(parent).append('<div id="'+id+'" class="'+css+'">'+value+'</div>');
}

function input_get(parent,id,value,css){
	var css = typeof(css) != 'undefined' ? css : '';
	var value = typeof(value) != 'undefined' ? value : '';
	$(parent).append( '<input id="'+id+'" class="input '+css+'" value="'+value+'" />');
}

function input_get_button(parent,id,value,css){
	var css = typeof(css) != 'undefined' ? css : '';
	var value = typeof(value) != 'undefined' ? value : '';
	$(parent).append( '<input id="'+id+'" class="date dateselect button '+css+'" value="'+value+'" />');
	$("button, input:submit, a", ".button").button();
}
function span_get(id,value,css){
	return '<span id="'+id+'" class="'+css+'">'+value+'</span>';
}

function button_get(parent,id,title,css,value){
	var css = typeof(css) != 'undefined' ? css : '';
	$(parent).append( '<button id="'+id+'" class="button '+css+'" value="'+value+'">'+title+'</button>' );
	$("button, input:submit, a", ".button").button();
}

function checkbox_get(parent,id,name,css,value,checked){
	var css = typeof(css) != 'undefined' ? css : '';
	var checked = checked == 1 ? ' checked="checked"' : '';
	$(parent).append( '<input id="'+id+'" class="checkbox '+css+'" type="checkbox" name="'+name+'" value="'+value+'"'+checked+'>' );
	$("button, input:submit, a", ".button").button();
}

function container_get(parent,id,title,css){
	var css = typeof(css) != 'undefined' ? css : '';
	$(parent).append('<div id="'+id+'" class="ui-widget-content ui-corner-all sensor-inner '+css+'"><div class="title"><h5 class="ui-widget-header ui-corner-all inner">'+title+'</h5></div>');
}

function day_get(date){
	var d=new Date(date);
	var weekday=new Array(7);
	weekday[0]="Sunday";
	weekday[1]="Monday";
	weekday[2]="Tuesday";
	weekday[3]="Wednesday";
	weekday[4]="Thursday";
	weekday[5]="Friday";
	weekday[6]="Saturday";
	return weekday[d.getDay()];
}

function month_get(date){
	var d=new Date(date);
	var month=new Array(12);
	month[0]="January";
	month[1]="February";
	month[2]="March";
	month[3]="April";
	month[4]="May";
	month[5]="June";
	month[6]="July";
	month[7]="August";
	month[8]="September";
	month[9]="October";
	month[10]="November";
	month[11]="December";
	return month[d.getMonth()];
}

function iphone_navigation_main( data ) {
	$('#tabcontainer li').remove();
	$.each( data, function(d){
		$('#tabcontainer').append('<li class="edgetoedge" value="'+data[d].sensor.sensor_id+'"><a href="#page'+data[d].sensor.sensor_id + '" name="'+data[d].sensor.sensor_id+'" class="slideleft'+data[d].sensor.sensor_id+'">' + data[d].sensor.position_description + '</a></li>');
		//$('#contentconatainer').append('');
		$('#jqt').append('<div id="page'+data[d].sensor.sensor_id+'" class="info"><div class="toolbar"><a href="#" class="back">back</a><h1>' + data[d].sensor.position_description + '</h1></div>');
		$('#page'+data[d].sensor.sensor_id).append('<div class="info">The title for this page was automatically set from it&#8217;s referring link, no extra scripts required. Just include the extension and this happens.</div></div>');
		});
	$('#tabcontainer').append('<li class="edgetoedge" value="11"><a href="#tabs-11" name="11">Setup</a></li>');
	$('#home').addClass('current');
	
};
