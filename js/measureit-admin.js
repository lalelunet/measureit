
function measureit_admin( data ){
	$('#adminmenu').remove();
	$('#tabs-11').append('<div id="adminmenu" />');
	sensor_list(data);
}

function sensor_settings_clean(){
	$('.sensor_settings').remove();
	$('.sensor_list_settings').remove();
}

function sensor_settings_detail_clean(){
	$('.sensor_settings_detail').remove();
}

function sensor_positions_admin( data, sensor ){
	sensor_settings_detail_clean();
	div_empty_get('#adminmenu','sensor_positions_detail'+sensor,'sensor_settings_detail');
	container_get('#sensor_positions_detail'+sensor,'positions'+sensor,'Positions');

	$.each( data[sensor].sensor.positions, function(d){
		if(data[sensor].sensor.positions[d].description != null){
			button_get('#positions'+sensor,'sensor_position_delete'+d,data[sensor].sensor.positions[d].description+'<br />'+data[sensor].sensor.positions[d].time,'sensor_position_select',d);
			}
		
	});
	button_get('#positions'+sensor, 'position_add'+sensor, 'add position');
	$('#position_add'+sensor).click(function(){
			sensor_position_add(sensor);
		});
	$('.sensor_position_select').click(function(){
		sensor_position_delete(this,sensor);
	});
}

function sensor_position_delete(item,sensor){
	$('#'+$(item).attr('id')).dialog({
		resizable: true,
		height:400,
		modal: true,
		buttons: {
			'delete this position': function() {
				$(this).dialog('close');
				$.get('php/measureit_functions.php',{ 'do' : 'sensor_position_delete', 'sensor_position_id' : $(item).attr('value') }, function(){
					sensor_settings_clean();
					sensor_settings_detail_clean();
					delete data;
					$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
						navigation_main( data );
						measureit_admin( data );
						sensor_admin_list_items( data, sensor );
						sensor_positions_admin( data, sensor );
						hist_update('1');
						});
					});
				},
				'chancel': function() {
					$(this).dialog('close');
					$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
						navigation_main( data );
						measureit_admin( data );
						sensor_admin_list_items( data, sensor );
						sensor_positions_admin( data, sensor );
						hist_update('1');
						});
				}
			}
	});
}

function sensor_position_add(sensor){
	sensor_settings_detail_clean();
	div_empty_get('#adminmenu','sensor_position_add'+sensor,'sensor_settings_detail');
	container_get('#sensor_position_add'+sensor,'position_add'+sensor,'add position');
	$('#position_add'+sensor).append(span_get('#position_add'+sensor,'Title','padding5')+'<br /><input type="text" id="sensor_position_name" />');
	button_get('#position_add'+sensor,'sensor_position_add_action','Add','padding5');
	$('#sensor_position_add_action').click(function(){
			if($('#sensor_position_name').val() != ''){
					$.get('php/measureit_functions.php',{ 'do' : 'sensor_position_add', 'sensor_id' : sensor, 'sensor_position_name' : $('#sensor_position_name').val() }, function(d){
						sensor_settings_clean();
						sensor_settings_detail_clean();
						delete data;
						$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
							navigation_main( data );
							measureit_admin( data );
							sensor_admin_list_items( data, sensor );
							sensor_positions_admin( data, sensor );
							hist_update('1');
							});
						});
				}
		});
}

function sensor_list( data ){
	sensor_settings_clean();
	sensor_settings_detail_clean();
	container_get('#adminmenu','sensor_admin','Sensor');
	$.each( data, function(d){
		button_get('#sensor_admin','sensor_admin'+d,data[d].sensor.sensor_title);
		$('#sensor_admin'+d).click(function() {
			sensor_settings_clean();
			sensor_settings_detail_clean();
			sensor_admin_list_items(data,d);
			});
	});
	button_get('#sensor_admin','sensor_add','Add Sensor');
	sensor_add(data);
}

function sensor_admin_list_items( data, sensor ){
	sensor_settings_clean();
	container_get('#adminmenu','sensor_admin_list',data[sensor].sensor.sensor_title,'sensor_list_settings');
	button_get('#sensor_admin_list','sensor_admin_positions'+sensor,'Positions');
	button_get('#sensor_admin_list','sensor_admin_settings'+sensor,'Settings');
	button_get('#sensor_admin_list','sensor_admin_sensor_delete'+sensor,'Delete');
	$('#sensor_admin_positions'+sensor).click(function(){
		sensor_positions_admin(data,sensor);
		});
	$('#sensor_admin_settings'+sensor).click(function(){
			sensor_admin_settings(data, sensor);
		});
	$('#sensor_admin_sensor_delete'+sensor).click(function(){
		sensor_delete(sensor);
	});
}

function sensor_admin_settings(data, sensor){
		sensor_settings_detail_clean();
		container_get('#adminmenu','sensor_settings_container','Settings', 'sensor_settings_detail');
		div_get('#sensor_settings_container','sensor_id','Price:','padding5');
		input_get('#sensor_settings_container','sensor_price',data[sensor].sensor.measure_price === undefined ? '0.00' : data[sensor].sensor.measure_price);
		div_get('#sensor_settings_container','sensor_id','currency (Euro/Pound):','padding5');
		input_get('#sensor_settings_container','sensor_currency',data[sensor].sensor.measure_currency === undefined ? 'Û' : data[sensor].sensor.measure_currency);
		div_get('#sensor_settings_container','sensor_id','days keep history:','padding5');
		input_get('#sensor_settings_container','sensor_history',data[sensor].sensor.measure_history === undefined ? '365' : data[sensor].sensor.measure_history);
		button_get('#sensor_settings_container','sensor_admin_settings_save'+sensor,'Save settings');

		$('#sensor_admin_settings_save'+sensor).click(function(){
			if($('#sensor_price').val() !== '' && $('#sensor_currency').val() !== '' && $('#sensor_history').val() !== ''){
				$.get('php/measureit_functions.php', { 
					'do' : 'sensor_settings_save',
					'sensor_id' : sensor,
					'sensor_currency' : $('#sensor_currency').val(),
					'sensor_price' : $('#sensor_price').val(),
					'sensor_history' : $('#sensor_history').val()
					}, function(sensor){
						delete data;
						$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
							navigation_main( data );
							measureit_admin( data );
							sensor_admin_list_items( data, sensor );
							//sensor_admin_settings( data, sensor );
							hist_update('1');
							});
					});
					console.log($('#sensor_price').val());
				}
			});

}

function sensor_delete(sensor){
	sensor_settings_detail_clean();
	div_get('#main','delete_confirm','<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>PLEASE NOTE: delete complete will delete all data from sensor including the positions and every stored watt and temperature data. delete entry will just remove the sensor entry and keep the data</p>');

	$("#delete_confirm").dialog({
			resizable: true,
			height:400,
			modal: true,
			buttons: {
				'delete entry': function() {
					$(this).dialog('close');
					$.get('php/measureit_functions.php', { 'do' : 'sensor_entry_delete', 'sensor_id' : sensor}, function(){
						delete data;
						$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
							navigation_main( data );
							measureit_admin( data );
							hist_update('1');
							});
						})
				},
				'delete complete': function() {
					$(this).dialog('close');
					$.get('php/measureit_functions.php', { 'do' : 'sensor_delete', 'sensor_id' : sensor}, function(){
						delete data;
						$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
							navigation_main( data );
							measureit_admin( data );
							hist_update('1');
							});
						})
				}
			}
		});
}

function sensor_add(data){
	$('#sensor_add').click(function(){
		sensor_settings_clean();
		sensor_settings_detail_clean();
		container_get('#adminmenu','sensor_add_container','Sensor', 'sensor_settings');
		div_get('#sensor_add_container','sensor_id',span_get('sensor_id_select_text','Sensor ID: ','float_left padding5'));
		for( i=1;i<10;i++){
				if(!data[i]){
						div_get('#sensor_id','',i,'sensor_id');
					}
			}
		$('.sensor_id').click(function(){
				$('.sensor_id').removeClass('selected');
				$(this).addClass('selected');
			});
		$('#sensor_add_container').append(span_get('sensor_title_select_text','Title','padding5')+'<br /><input type="text" id="sensor_name" />');
		button_get('#sensor_add_container','sensor_add_action','Add','padding5');
		$('#sensor_add_action').click(function(){
				if($('#sensor_name').val() != '' && $('.selected').html() != null){
						$.get('php/measureit_functions.php',{ 'do' : 'sensor_add', 'sensor_id' : $('.selected').html(), 'sensor_name' : $('#sensor_name').val() }, function(){
							delete data;
							$.getJSON('php/measureit_functions.php', { 'do' : 'navigation_main' }, function(data) {
								navigation_main( data );
								measureit_admin( data );
								hist_update('1');
								});
							});
					}
			});
		});
}
