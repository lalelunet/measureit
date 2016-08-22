<?php
require_once dirname(__FILE__).( '/class.db.pdo.mysql.php' );
# in demo mode no sensor actions please
global $demo;
$demo = false;
#$demo = true;

if( isset( $_REQUEST['do'] ) ){
	switch( $_REQUEST['do'] ){
		case 'navigation_main':
			navigation_main();
		break;
		case 'summary_start':
			summary_start( $_REQUEST );
		break;
		case 'sensor_detail':
			sensor_detail( $_REQUEST );
		break;
		case 'summary_sensor':
			sensor_data_get( $_REQUEST );
		break;
		case 'sensor_detail_statistic':
			sensor_detail_statistic( $_REQUEST );
		break;
		case 'sensor_history_week':
			sensor_history_week( $_REQUEST );
		break;
		case 'sensor_history_year':
			sensor_history_year( $_REQUEST );
		break;
		case 'sensor_statistic':
			sensor_statistic( $_REQUEST );
		break;
		case 'sensors_get_json':
			sensors_get_json( $_REQUEST );
		break;
		case 'sensor_add':
			if($demo){ return true; }
			sensor_add( $_REQUEST );
		break;
		case 'sensor_delete':
			if($demo){ return true; }
			sensor_delete( $_REQUEST );
		break;
		case 'clamp_add':
			if($demo){ return true; }
			clamp_add( $_REQUEST );
		break;
		case 'sensor_position_add':
			if($demo){ return true; }
			sensor_position_add( $_REQUEST );
		break;
		case 'sensor_settings_save':
			if($demo){ return true; }
			sensor_settings_save( $_REQUEST );
		break;
		case 'sensor_position_delete':
			if($demo){ return true; }
			sensor_position_delete( $_REQUEST );
		break;
		case 'sensor_entry_delete':
			if($demo){ return true; }
			sensor_entry_delete( $_REQUEST );
		break;
		case 'sensor_notifications_get':
			if($demo){ return true; }
			sensor_notifications_get( $_REQUEST );
		break;
		case 'sensor_notification_save':
			if($demo){ return true; }
			sensor_notification_save( $_REQUEST );
		break;
		case 'sensor_notification_delete':
			if($demo){ return true; }
			sensor_notification_delete( $_REQUEST );
		break;
		case 'backup_list_get':
			if($demo){ return true; }
			backup_list_get();
		break;
		case 'backup_create':
			if($demo){ return true; }
			backup_create();
		break;
		case 'backup_delete':
			if($demo){ return true; }
			backup_delete( $_REQUEST );
		break;
		case 'lng_get':
			lng_get( $_REQUEST );
		break;
		case 'sensor_prices_get':
			sensor_prices_get( $_REQUEST );
		break;
		case 'sensor_prices_set':
			if($demo){ return true; }
			sensor_prices_set( $_REQUEST );
		break;
		case 'sensor_price_delete':
			if($demo){ return true; }
			sensor_price_delete( $_REQUEST );
		break;
		case 'sensor_prices_delete':
			if($demo){ return true; }
			sensor_prices_delete( $_REQUEST );
		break;
		case 'sensor_price_add':
			if($demo){ return true; }
			sensor_price_add( $_REQUEST );
		break;
		case 'global_settings_get':
			global_settings_get( );
		break;
		case 'global_settings_set':
			if($demo){ return true; }
			global_settings_set( $_REQUEST );
		break;
		case 'languages_get':
			languages_get( );
		break;
		case 'update_information_get':
			update_information_get( );
		break;
		case 'grabber_restart_init':
			if($demo){ return true; }
			grabber_restart_init( );
		break;
		case 'grabber_status_get':
			if($demo){ return true; }
			grabber_status_get( );
		break;
		case 'tmpr_view_main':
			tmpr_view_main( $_REQUEST );
		break;
		default:
			echo 'this is not a valid request';
		break;
	}
}

function tmpr_view_main( $params ){
	$ret = ''; $use_diff = false; $r = array();
	if( $diff = timezone_diff_get( $params ) ){
		$use_diff = true;
		$diff = timezone_diff_get( $params );
	}

	$db = new db;
	$db->query( 'select hour_id, time, hour, data, unix_timestamp(ts) from measure_tmpr_hourly order by hour_id desc limit 72' );
	$ret = $db->results( );

	foreach( $ret as $d  ){
		$ts = $d['unix_timestamp(ts)'];

		# update old data entries
		if( $d['unix_timestamp(ts)'] == 0 ){
			$ts = strtotime( $d['time'].' '.$d['hour'].':00:00' );
			$date = $d['time'].' '.$d['hour'].':00:00';
			$db->query( 'update measure_tmpr_hourly set ts = "'.$date.'" where hour_id = '.$d['hour_id'] );
		}
		if( $use_diff ){
			$ts = $diff['prefix'] == '-' ? $ts - $diff['diff'] : $ts + $diff['diff'];
		}

		$r[][$ts.'000']['data'] = $d['data'];
		//$r[@date( 'Y-m-d', $ts )][@date( 'G', $ts )]['data'] = $d['data'];
		//ksort($r[@date( 'Y-m-d', $ts )]);

		#$r .= '['.$ts.','.$d['data'].'],';
	}
	header( 'Content-Type: application/json' );
	print json_encode($r);
	return true;


	while( $d = $db->fetch_array( $query ) ){

		$r[@date( 'Y-m-d', $date )][@date( 'G', $date )]['data'] = $d['data'];
		ksort($r[@date( 'Y-m-d', $date )]);
	}
	print json_encode($r);
	return true;

}

function navigation_main( ){
	$sensors = sensors_get();
	$r = array();
	foreach( $sensors as $k=>$v ){
		$r[$k]['sensor'] = $v;
		if( $clamps = sensor_clamps_get( $k ) ){
			$r[$k]['sensor']['clamps'] = $clamps;
		}
	}
	header( 'Content-Type: application/json' );
	print json_encode($r);
	return true;
}

function sensor_detail( $params = array( ) ){
	if( isset( $params['sensor'] ) && is_numeric( $params['sensor'] ) ){
		$r['sensor'] = sensor_get( $params['sensor'] );
		$r['sensor']['clamps'] = sensor_clamps_get( $params['sensor'] );
		header( 'Content-Type: application/json' );
		print json_encode($r);
	}
	return true;
}

function sensor_clamps_get( $sensor ){
	if( isset( $sensor ) && is_numeric( $sensor ) ){
		$sensors = sensors_get();
		$clamps = array();
		foreach( $sensors as $k => $v){
			for( $i=1; $i<4; $i++){
				if( $v['sensor_id'] == $i.$sensor ){
					$clamps[$v['sensor_id']] = $v['position_description'];
				}
			}
		}
		if( count( $clamps ) > 0 ){
			return $clamps;
		}
	}
	return false;
}

function sensor_detail_statistic( $params = array( ) ){
	if( isset( $params['sensor'] ) && is_numeric( $params['sensor'] ) ){
		$params['timeframe'] = 'static';
		$params['table'] = 'measure_watt_daily';
		$params['unit_value'] = '1';
		$params['unit'] = 'YEAR';
		$use_diff = false;
		$cnt = 1;
		if( $diff = timezone_diff_get( $params ) ){
			$use_diff = true;
			$diff = timezone_diff_get( $params );
		}
		$q = data_query_run( $params );
		foreach( $q as $d ){
			$date = @strtotime( $d['time'].' 00:00' );
			if( isset( $params['debug'] ) && $cnt <= 1 && $use_diff ) { debug( 'date before timezone changing: '.$date.' date_str: '.$d['time'].' 00:00', false, $d ); };
			if( $use_diff ){
				$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
				if( isset( $params['debug'] ) && $cnt <= 1 ) { debug( 'date after timezone changing: '.$date ); };
			}

			$datew = @date( w, $date );
			$daten= @date( n, $date );
			@$r['yeardays'][$datew] += $d['data'];
			@$r['yearsdaysdetail'][$daten-1][$datew] += $d['data'];
			ksort($r['yearsdaysdetail'][$daten-1]);
			$cnt++;
		}

		$cnt = 1;
		$params['table'] = 'measure_watt_hourly';
		$q = data_query_run( $params );

		foreach( $q as $d ){
			$date = @strtotime( $d['time'].' '.$d['hour'].':00' );
			if( isset( $params['debug'] ) && $cnt <= 1 && $use_diff ) { debug( 'date before timezone changing: '.$date.' date_str: '.$d['time'].' '.$d['hour'].':00', false, $d ); };
			if( $use_diff ){
				$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
				if( isset( $params['debug'] ) && $cnt <= 1 ) { debug( 'date after timezone changing: '.$date ); };
			}
			$daten = @date( n, $date );
			@$r['yearhours'][$d['hour']] += $d['data'];
			ksort($r['yearhours']);
			@$r['monthshoursdetail'][$daten-1][$d['hour']] += $d['data'];
			ksort($r['monthshoursdetail'][$daten-1]);
			$cnt++;
		}
		header( 'Content-Type: application/json' );
		print json_encode($r);
		return true;
	}
	return true;
}

function summary_start( $request = array( ) ){
	$debug = isset( $request['debug'] ) ? 'debug' : 'nd';
	$sensors = sensors_get();
	$prices = sensor_prices_all_get( );
	foreach( $sensors as $k=>$v ){
		$p = end( $v['positions'] );
		$vn = sensor_values_now_get( $k );
		$r[$k]['sensor'] = $v;
		$r[$k]['tmpr'] = $vn['tmpr'];
		$r[$k]['watt'] = $vn['watt'];
		$r[$k]['daily'] = price_sum( array( 'sensor'=>$k, 'data'=>sensor_data_raw_get( array( 'sensor'=> $k, 'unit_value'=> 24, 'unit'=> 'day', 'table'=> 'measure_watt_hourly', 'timeframe'=> 'limit-last', 'order' => 'time DESC, hour', $debug => 1 ) ), 'prices'=>$prices ) );
		$r[$k]['hourly'] = price_sum( array( 'sensor'=>$k, 'data'=>sensor_data_raw_get( array( 'sensor'=> $k, 'unit_value'=> 1, 'unit'=> 'day', 'table'=> 'measure_watt_hourly', 'timeframe'=> 'limit-last', 'order' => 'time DESC, hour', $debug => 1 ) ), 'prices'=>$prices ) );
		$r[$k]['weekly'] = price_sum( array( 'sensor'=>$k, 'data'=>sensor_data_raw_get( array( 'sensor'=> $k, 'unit_value'=> 168, 'unit'=> 'day', 'table'=> 'measure_watt_hourly', 'timeframe'=> 'limit-last', 'order' => 'time DESC, hour', $debug => 1 ) ), 'prices'=>$prices ) );
		$r[$k]['monthly'] = price_sum( array( 'sensor'=>$k, 'data'=>sensor_data_raw_get( array( 'sensor'=> $k, 'unit_value'=> 730, 'unit'=> 'day', 'table'=> 'measure_watt_hourly', 'timeframe'=> 'limit-last', 'order' => 'time DESC, hour', $debug => 1 ) ), 'prices'=>$prices ) );

	}
	header( 'Content-Type: application/json' );
	print json_encode($r);
	return true;
}

function sensor_history_week( $params = array( ) ){
	$ret = ''; $use_diff = false;
	$r = array( );
	if( $diff = timezone_diff_get( $params ) ){
		$use_diff = true;
		$diff = timezone_diff_get( $params );
	}
	$q = data_query_run( $params );
	foreach( $q as $d ){
		$date = @strtotime( $d['time'].' '.$d['hour'].':00' );
		if( $use_diff ){
			$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
		}
		$r[@date( 'Y-m-d', $date )][@date( 'G', $date )]['data'] = $d['data'];
		ksort($r[@date( 'Y-m-d', $date )]);
	}
	header( 'Content-Type: application/json' );
	print json_encode($r);
	return true;
}

function sensor_history_year( $params = array( ) ){
	$ret = ''; $use_diff = false;
	$r = array( );
	if( $diff = timezone_diff_get( $params ) ){
		$use_diff = true;
		$diff = timezone_diff_get( $params );
	}
	$q = data_query_run( $params );
	foreach( $q as $d ){
		$date = @strtotime( $d['time'].' 00:00' );
		if( $use_diff ){
			$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
		}
		preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', @date( 'Y-m-d', $date ), $ret );
		@$r[$ret[1].'-'.$ret[2]][$ret[3]]['data'] += $d['data'];
	}
	header( 'Content-Type: application/json' );
	print json_encode($r);
	return true;
}

function sensor_data_get( $params = array( ) ){
	$q = !strpos( $params['table'], 'tmpr' ) ? data_query_run( $params ) : tmpr_get_query( $params );
	if( $q ){
		$t = ''; $use_diff = false;
		if( $diff = timezone_diff_get( $params ) ){
			if( isset( $params['debug'] ) ) debug( 'Found timezone settings in sensor_data_get', $params, timezone_diff_get( $params ) );
			$use_diff = true;
			$diff = timezone_diff_get( $params );
		}else{
			if( isset( $params['debug'] ) ) debug( 'NO timezone settings in sensor_data_get', $params, timezone_diff_get( $params ) );
		}
		$dgb = array( );

		foreach( $q as $d ){
			$time = preg_match('/hourly/', $params['table']) ? $d['time'].' '.$d['hour'].':00:00' : $d['time'];
			$ts = @strtotime( $time, time( ) );
			if( $use_diff ){
				$ts = $diff['prefix'] == '-' ? $ts - $diff['diff'] : $ts + $diff['diff'];
				if( isset( $params['debug'] ) ) $dbg[$time] = @date( 'Y-m-d H:i:s', $ts );
			}else{
				if( isset( $params['debug'] ) ) $dbg[$time] = $time;
			}
			$u = $params['unit_return'] == 'timeframe' ? $ts*1000 : $time;
			$t .= '['. $u .', '. $d['data'] .'],';
		}
		$r = preg_replace( '/(.+),$/', "$1", $t );
		$r = '['.$r.']';
		if( isset( $params['debug'] ) ) debug( 'Values in sensor_data_get', false, $dbg );
		print $r;
	}
}

function sensor_values_now_get( $sensor ){
	if( isset( $sensor ) && is_numeric( $sensor ) ){
		$db = new db;
		$db->query( 'SELECT * FROM measure_data_now WHERE sensor_id = :sensor' );
		$db->data( 'sensor', $sensor );

		return $db->result( );
	}
	return true;
}

function sensor_notifications_get( $params = array( ) ){
	if( !isset( $params['sensor'] ) || !is_numeric( $params['sensor'] ) ) return false;

	$db = new db;
	$db->query( 'SELECT * FROM measure_notifications WHERE measure_notifications_sensor = :sensor' );
	$db->data( 'sensor', $params['sensor'] );
	$ret = $db->results( );

	$r = array();
	foreach( $ret as $d ){
		$r[$d['measure_notifications_id']]['measure_notifications_name'] = $d['measure_notifications_name'];
		$r[$d['measure_notifications_id']]['measure_notifications_unit'] = $d['measure_notifications_unit'];
		$r[$d['measure_notifications_id']]['measure_notifications_check_email'] = $d['measure_notifications_check_email'];
		$r[$d['measure_notifications_id']]['measure_notifications_check_twitter'] = $d['measure_notifications_check_twitter'];
		$r[$d['measure_notifications_id']]['measure_notifications_notification'] = $d['measure_notifications_notification'];
		$r[$d['measure_notifications_id']]['measure_notifications_items'] = $d['measure_notifications_items'];
		$r[$d['measure_notifications_id']]['measure_notifications_criteria'] = $d['measure_notifications_criteria'];
		$r[$d['measure_notifications_id']]['measure_notifications_value'] = $d['measure_notifications_value'];
	}
	header( 'Content-Type: application/json' );
	print json_encode( $r );
}

function sensor_notification_save( $params = array( ) ){
	$keys = $values = array( );
	$cnt = 1;
	$db = new db;
	$db->query( 'REPLACE INTO measure_notifications ( measure_notifications_sensor, measure_notifications_name, measure_notifications_unit, measure_notifications_check_email, measure_notifications_check_twitter, measure_notifications_notification, measure_notifications_items, measure_notifications_criteria, measure_notifications_value, measure_notifications_id ) VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )' );
	foreach( $params as $k => $v){
		if( $k == 'do' || $k == 'sensor' ) continue;
		$db->data( $cnt, $v );
		$cnt++;
	}
	$db->execute( );

	return true;
}

function sensor_notification_delete( $params = array( ) ){
	if( !isset( $params['sensor'] ) || !is_numeric( $params['sensor'] ) ) return false;
	if( !isset( $params['measure_notifications_id'] ) || !is_numeric( $params['measure_notifications_id'] ) ) return false;
	$db = new db;
	$db->query( 'DELETE from measure_notifications WHERE measure_notifications_id = :id AND measure_notifications_sensor = :sensor' );
	$db->data( 'id', $params['measure_notifications_id'] );
	$db->data( 'sensor', $params['sensor'] );
	$db->execute( );
	return true;
}

function sensor_prices_get( $params = array( ) ){
	if( !isset( $params['sensor'] ) || !is_numeric( $params['sensor'] ) ) return false;
	$db = new db;
	$db->query( 'SELECT * FROM measure_costs WHERE costs_sensor = :sensor ORDER BY costs_since desc' );
	$db->data( 'sensor', $params['sensor'] );
	$ret = $db->results( );
	$r = array();
	$cnt = 0;
	foreach( $ret as $d ){
		if( isset( $params['sensor'] ) && !is_numeric( $params['sensor'] ) ) continue;
		$r[$d['costs_since']][$cnt]['costs_id'] = $d['costs_id'];
		$r[$d['costs_since']][$cnt]['costs_sensor'] = $d['costs_sensor'];
		$r[$d['costs_since']][$cnt]['costs_from'] = $d['costs_from'];
		$r[$d['costs_since']][$cnt]['costs_to'] = $d['costs_to'];
		$r[$d['costs_since']][$cnt]['costs_price'] = $d['costs_price'];
		$cnt++;
	}
	header( 'Content-Type: application/json' );
	print json_encode( $r );
}

function sensor_prices_all_get( ){
	$r = array();
	$db = new db;
	$db->query( 'SELECT * FROM measure_costs ORDER BY costs_since desc' );
	$ret = $db->results( );

	foreach( $ret as $d ){
		$from = @strtotime( $d['costs_since'] );
		if( $d['costs_from'] > $d['costs_to'] ){
			for( $i=$d['costs_from']; $i<24; $i++ ){
				$r[$d['costs_sensor']][$from][$i] = $d['costs_price'];
			}
			for( $i=0; $i<=$d['costs_to']; $i++ ){
				$r[$d['costs_sensor']][$from][$i] = $d['costs_price'];
			}
		}
		if($d['costs_from'] < $d['costs_to'] ){
			for( $i=$d['costs_from']; $i<=$d['costs_to']; $i++ ){
				$r[$d['costs_sensor']][$from][$i] = $d['costs_price'];
			}
		}

	}
	return $r;
}

function sensor_price_delete( $params = array( ) ){
	if( !isset( $params['id'] ) || !is_numeric( $params['id'] ) ) return false;
	$db = new db;
	$db->query( 'DELETE from measure_costs WHERE costs_id = :id' );
	$db->data( 'id', $params['id'] );
	$db->execute( );
	return true;
}

function sensor_prices_delete( $params = array( ) ){
	if( !isset( $params['date'] ) ) return true;
	$db = new db;
	$db->query( 'DELETE from measure_costs WHERE costs_since = :date' );
	$db->data( 'date', $params['date'] );
	$db->execute( );
	return true;
}

function sensor_price_add( $params = array( ) ){
	preg_match( '/\d{4,4}-\d{2,2}-\d{2,2}/', $params['date'], $r );
	$params['price'] = preg_replace(  '/,/', '.', $params['price'] );
	if( !is_numeric( $params['price'] ) || !is_numeric( $params['from'] ) || !is_numeric( $params['to'] ) || strlen( $r['0'] ) != 10 ) return true;
	if( $params['from'] == 0 && $params['to'] == 0 ){
		# 1 price the whole day
		$params['to'] = 23;
	}

	$db = new db;
	$db->query( 'INSERT INTO measure_costs (costs_sensor, costs_from, costs_to, costs_price, costs_since) VALUES ( :sensor, :from, :to, :price, :date )' );
	$db->data( 'sensor', $params['sensor'] );
	$db->data( 'from', $params['from'] );
	$db->data( 'to', $params['to'] );
	$db->data( 'price', $params['price'] );
	$db->data( 'date', $params['date'] );
	$db->execute( );
	return true;
}

function sensor_statistic( $params = array( ) ){
	$params['range_to'] = sensor_position_next_date_get( $params );
	sensor_statistic_get( $params );
}

function sensor_data_raw_get( $params = array( ) ){
	$r = array( );
	if( $q = data_query_run( $params ) ){
		$r = array();
		foreach( $q as $d ){
			@$r[$d['hour']] += $d['data'];
		}
	}
	return $r;
}

function sensor_statistic_get( $params = array( ) ){
	if( $q = data_query_run( $params ) ){
		$prices = sensor_prices_all_get( );
		$r = ''; $tmp = array( );
		foreach( $q as $d ){
			@$tmp[$d['time']][$d['hour']] += $d['data'];
		}

		foreach( $tmp as $day => $usage ){
			preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $day, $t);
			$ts = @strtotime( $day );
			$month = @date( 'n', $ts );
			$get_day_data = price_sum_statistic( array( 'sensor'=>$params['sensor'], 'data'=>$tmp[$day], 'day'=>( $ts -1 ), 'prices'=>$prices ) );
			@$r[$t[1]][$month][$t[3]]['data'] = $get_day_data['sum'];
			@$r[$t[1]][$month][$t[3]]['price'] = $get_day_data['price'];
			@$r[$t[1]][$month][$t[3]]['weekday'] = @date( 'w', $ts );
		}
		header( 'Content-Type: application/json' );
		print json_encode($r);
	}
}

function sensor_item_get( $params = array( ) ){
	if( $q = data_query_run( $params ) ){
		$r = array();
		foreach( $q as $d ){
			$r = $d['data'];
		}
	}
	return $r;
}

function lng_get( $params = array( ) ){
	$db = new db;
	$db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'language_use'");
	$language = $db->result( );

	$r = lng_str_get( 'en_EN' );
	$r = lng_str_get( $language['measure_system_setting_value'], $r );
	header( 'Content-Type: application/json' );
	print json_encode( $r );
}

function lng_str_get( $language = false, $lng_str = array( ) ){
	if( isset( $language ) && $language != '' && is_file( '../lng/'.$language.'.txt' ) ){
		$lng = @file_get_contents( '../lng/'.$language.'.txt' );
		$lng = preg_replace( '/[\n\t]/', '', $lng );
		$lng = explode( ',', $lng );
		foreach( $lng as $k => $v ){
			$r = explode( ':', $v );
			@$lng_str[$r[0]] = $r[1];
		}
	}

	return $lng_str;
}

function tmpr_get_query( $params = array( )){
	$db = new db;
	$db->query( "SELECT * FROM measure_tmpr_hourly WHERE time = :select" );
	$db->data( 'select', $params['select'] );
	return( $db->results( ) );
}

function data_query_run( $params = array( ) ){

	$sensor = ( isset( $params['sensor'] ) || is_numeric( $params['sensor'] ) ) ? $params['sensor'] : error( 'no sensor error: '.$params['sensor'] );
	$order = isset( $params['order'] ) ? $params['order'] : '';
	$unit = isset( $params['unit'] ) ? $params['unit'] : '';
	$turn = ( isset( $params['turn'] ) && $params['turn'] == 'desc' ) ? 'desc' : '';
	$select = isset( $params['select'] ) ? $params['select'] : '';
	$from = isset( $params['range_from'] ) ? $params['range_from'] : '';
	$to = isset( $params['range_to'] ) ? $params['range_to'] : '';
	$unit_value = isset( $params['unit_value'] ) ? $params['unit_value'] : '';
	$limit = isset( $params['limit'] ) ? $params['limit'] : '';
	$timeframe = ( isset( $params['timeframe'] ) && $params['timeframe'] != '' ) ? $params['timeframe'] : 'all';


	#var_dump('<pre>',$params);

	# define the table from where data should be reeding
	switch( $params['table'] ){
		case 'measure_watt':
			$table = 'measure_watt';
		break;
		case 'measure_watt_hourly':
			$table = 'measure_watt_hourly';
		break;
		case 'measure_watt_daily':
			$table = 'measure_watt_daily';
		break;
		case 'measure_watt_monthly':
			$table = 'measure_watt_monthly';
		break;
		default:
			error( 'no database table selected: '.$params['table'] );
	}

	switch( $order ){
		case 'hour_id':
			$order = 'hour_id';
		break;
		default:
			$order = 'time';
	}

	switch( strtolower( $unit ) ){
		case 'year':
			$unit = 'YEAR';
			break;
		case 'day':
			$unit = 'DAY';
			break;
		default:
			$unit = 'HOUR';
	}

	$db = new db;

	switch( $timeframe ){
		case 'static':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor  AND time > UTC_TIMESTAMP( ) - INTERVAL :unit_value $unit ORDER BY $order" );
			$db->data( 'sensor', $sensor );
			$db->data( 'unit_value', $unit_value );
			return( $db->results( ) );
		break;
		case 'last':
			# last hour watts has an extra option
			$timeframe = $table == 'measure_watt_hourly' ? 'AND time = DATE( UTC_TIMESTAMP( ) ) ' : '';
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor $timeframe ORDER BY $order DESC LIMIT 1" );
			$db->data( 'sensor', $sensor );
			return( $db->results( ) );
		break;
		case 'limit-last':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor ORDER BY $order DESC LIMIT :unit_value" );
			$db->data( 'sensor', $sensor );
			$db->data( 'unit_value', $unit_value );
			return( $db->results( ) );
		break;
		case 'select':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor AND time = :select" );
			$db->data( 'sensor', $sensor );
			$db->data( 'select', $select );
			return( $db->results( ) );
		break;
		case 'range':
			$from = preg_replace('/_/', ' ', $from);
			$to = preg_replace('/_/', ' ', $to);
			$from .= ':00:00';
			$to .= ':00:00';
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor AND time BETWEEN :from and :to" );
			$db->data( 'sensor', $sensor );
			$db->data( 'from', $from );
			$db->data( 'to', $to );
			return( $db->results( ) );
		break;
		case 'position':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor AND time BETWEEN :from and $to ORDER BY $order $turn" );
			$db->data( 'sensor', $sensor );
			$db->data( 'from', $from );
			return( $db->results( ) );
		break;
		case 'limit':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor AND time > :limit" );
			$db->data( 'sensor', $sensor );
			$db->data( 'limit', $limit );
			return( $db->results( ) );
		break;
		case 'all':
			$db->query( "SELECT * FROM $table WHERE sensor = :sensor" );
			$db->data( 'sensor', $sensor );
			return( $db->results( ) );
		break;
		default:
			error('No timeframe to get data from');
		break;
	}

	#$query = "SELECT $selection FROM $table WHERE sensor = '$sensor' $timeframe";

	return false;
	#var_dump('<pre>',$params);
	#return mysql_real_escape_string( $query );
}

function price_sum( $params = array( ) ){
	$prices = array();
	if( array_key_exists( $params['sensor'], $params['prices'] ) ){
		$prices = array_shift(  $params['prices'][$params['sensor']] );
	}elseif( array_key_exists( 400, $params['prices'] ) ){
		$prices = array_shift(  $params['prices'][400] );
	}
	$sum = $price = 0;
	foreach( $params['data'] as $k=>$v ){
		$sum += $v;
		if( isset( $prices[$k] ) ){
			$price += $v * $prices[$k];
		}
	}

	return round( $sum, 3 ).' Kwh<br />'.round( $price, 2 );
}

function price_sum_statistic( $params ){
	global $to;
	if( isset( $_REQUEST['debug'] ) ) debug( 'function price_sum_statistic', $params );
	if( array_key_exists( $params['sensor'], $params['prices'] ) ){
		$prices = $params['prices'][$params['sensor']];
		if( isset( $_REQUEST['debug'] ) ) debug( 'prices and sensor found', $params['prices'] );
	}elseif( array_key_exists( 400, $params['prices'] ) ){
		$prices = $params['prices'][400];
		if( isset( $_REQUEST['debug'] ) ) debug( 'no sensor found. using system prices', $params['prices'] );
	}

	$last_date = ''; $sum = $price = $cnt = 0;
	foreach( $prices as $k => $v ){
		if( $cnt == 0 ){
			$to = @strtotime( @date( 'Y-m-d' ) );
			if( in_range( $k, $to, $params['day'] ) ){
				$prices = $prices[$k];
			}
			$to = $k;
		}else{
			if( in_range( $k, $to, $params['day'] ) ){
				$prices = $prices[$k];
			}
			$to = $k;
		}
		$cnt++;
	}

	foreach( $params['data'] as $k=>$v ){
		$sum += $v;
		@$price += $v * $prices[$k];
	}
	return array( 'sum'=>round( $sum, 3 ), 'price'=>round( $price, 2 ) );
}

function in_range( $from = false, $to = false, $day = false ){
	if( !$from || !$to || !$day ){
		return false;
	}
	if( $day > $from && $day < $to ){
		return true;
	}
	return false;
}

function sensor_position_next_date_get( $params = array( ) ){
	if( !is_numeric( $params['sensor_position'] ) || !is_numeric( $params['sensor'] ) || !isset( $params['sensor_position'] ) || !isset( $params['sensor'] ) ){ return false; }
	$db = new db;
	$db->query( 'SELECT position_time FROM measure_positions WHERE position_id > :position AND position_sensor = :sensor' );
	$db->data( 'position', $params['sensor_position'] );
	$db->data( 'sensor', $params['sensor'] );
	$date = $db->result( );
	$r = is_array( $date ) ? sprintf( "'%s'", substr( $date[0], 0, 10) ) : 'UTC_TIMESTAMP( )';
	return $r;
}

function sensor_position_last_get( $sensor = false ){
	if( !is_numeric( $params['sensor'] ) ){ return false; }
	$db = new db;
	$db->query( 'SELECT * FROM measure_positions WHERE position_sensor = :sensor  ORDER BY position_id DESC LIMIT 1' );
	$db->data( 'sensor', $sensor );
	$sp = $db->result( );
	return $sp;
}

function sensor_get( $sensor = false ){
	if( !is_numeric( $sensor ) ) return true;
	global $demo;
	$db = new db;
	$db->query( '
		SELECT *
		FROM measure_sensors
		LEFT JOIN measure_positions ON measure_positions.position_sensor = measure_sensors.sensor_id
		LEFT JOIN measure_settings ON measure_sensors.sensor_id = measure_settings.measure_sensor
		WHERE measure_sensors.sensor_id = :sensor
		ORDER BY measure_sensors.sensor_id, measure_positions.position_id
	' );
	$db->data( 'sensor', $sensor );
	$d = $db->result( );

	$r = array();

	foreach( $d as $k => $v){
		$item = !is_numeric( $k ) ? $k : 'x';
		$r[$d['sensor_id']][$item] = $d[$k];
	}

	$r[$d['sensor_id']]['positions'][$d['position_id']]['position'] = $d['position_id'];
	$r[$d['sensor_id']]['positions'][$d['position_id']]['time'] = $d['position_time'];
	$r[$d['sensor_id']]['positions'][$d['position_id']]['description'] = $d['position_description'];
	if( $demo ){
		$r[$d['sensor_id']]['measure_pvoutput_api'] = '';
	}

	return $r;
}

function sensors_get( ){
	global $demo;
	$db = new db;
	$db->query( "
		SELECT *
		FROM measure_sensors
		LEFT JOIN measure_positions ON measure_positions.position_sensor = measure_sensors.sensor_id
		LEFT JOIN measure_settings ON measure_sensors.sensor_id = measure_settings.measure_sensor
		ORDER BY measure_sensors.sensor_id, measure_positions.position_id
	" );
	$ret = $db->results( );
	$r = array();
	foreach( $ret as $d ){
		foreach( $d as $k => $v){
			$item = !is_numeric( $k ) ? $k : 'x';
			$r[$d['sensor_id']][$item] = $d[$k];
		}
		if( $demo ){
			$r[$d['sensor_id']]['measure_pvoutput_api'] = '';
			$r[$d['sensor_id']]['measure_pvoutput_id'] = '';
		}
		$r[$d['sensor_id']]['positions'][$d['position_id']]['position'] = $d['position_id'];
		$r[$d['sensor_id']]['positions'][$d['position_id']]['time'] = $d['position_time'];
		$r[$d['sensor_id']]['positions'][$d['position_id']]['description'] = $d['position_description'];
	}
	return $r;
}

function sensors_get_json( ){
	$db = new db;
	$db->query( "SELECT * FROM measure_sensors" );
	$ret = $db->results( );
	$r = array();
	foreach( $ret as $d ){
		$r[] = $d['sensor_id'];
	}
	header( 'Content-Type: application/json' );
	print json_encode( $r );
}

function sensor_position_add( $params = array() ){
	$db = new db;
	$db->query( 'INSERT INTO measure_positions ( position_time, position_description, position_sensor ) VALUES ( UTC_TIMESTAMP( ), :name, :sensor )' );
	$db->data( 'name', $params['sensor_position_name'] );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	return true;
}

function sensor_settings_save( $params = array() ){
	$params['sensor_price'] = preg_replace('/,/', '.', @$params['sensor_price']);
	$db = new db;
	$db->query(  'UPDATE measure_settings SET measure_history = :history,
		measure_currency = :currency,
		measure_timezone_diff = :diff,
		measure_pvoutput_id = :id,
		measure_pvoutput_api = :api,
		measure_scale_factor = :scale,
		measure_lower_limit = :lower,
		measure_type = :type WHERE measure_sensor = :sensor' );
	$db->data( 'history', $params['sensor_history'] );
	$db->data( 'currency', $params['sensor_currency'] );
	$db->data( 'diff', $params['sensor_timezone_diff'] );
	$db->data( 'id', $params['sensor_pvoutput_id'] );
	$db->data( 'api', $params['sensor_pvoutput_api'] );
	$db->data( 'scale', $params['sensor_scale_factor'] );
	$db->data( 'lower', $params['sensor_lower_limit'] );
	$db->data( 'type', $params['sensor_type'] );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	return true;
}
function sensor_position_delete( $params = array() ){
	if( !isset( $params['sensor_position_id'] ) || !is_numeric( $params['sensor_position_id'] ) ){
		error('sensor position is wrong');
		return false;
	}
	$db = new db;
	$db->query("DELETE FROM measure_positions WHERE position_id = :id LIMIT 1");
	$db->data( 'id', $params['sensor_position_id'] );
	$db->execute( );
	return true;
}

function sensor_entry_delete( $params = array() ){
	if( !isset( $params['sensor_id'] ) || !is_numeric( $params['sensor_id'] ) ){
		error('sensor is wrong');
		return false;
	}
	$db = new db;
	$db->query("DELETE FROM measure_sensors WHERE sensor_id = :sensor LIMIT 1");
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	return true;
}

function sensor_delete( $params = array() ){
	if( !isset( $params['sensor_id'] ) || !is_numeric( $params['sensor_id'] ) ){
		error('sensor is wrong');
		return false;
	}
	$db = new db;
	$db->query( 'DELETE FROM measure_sensors WHERE sensor_id = :sensor LIMIT 1' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_positions WHERE position_sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_settings WHERE measure_sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_watt WHERE sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_data_now WHERE sensor_id = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_watt_daily WHERE sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_watt_hourly WHERE sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'DELETE FROM measure_watt_monthly WHERE sensor = :sensor' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	return true;
}

function sensor_add( $params = array() ){
	if( !isset( $params['sensor_id'] ) || !is_numeric( $params['sensor_id'] ) ){ return false; }
	$db = new db;
	$db->query( 'INSERT IGNORE INTO measure_data_now ( sensor_id, watt, tmpr) VALUES ( :sensor, 0, 0 )' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	$db->query( 'INSERT INTO measure_sensors ( sensor_id, sensor_title ) VALUES ( :sensor, :name )' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->data( 'name', $params['sensor_name'] );
	$db->execute( );
	$db->query( 'INSERT INTO measure_settings ( measure_sensor ) VALUES ( :sensor )' );
	$db->data( 'sensor', $params['sensor_id'] );
	$db->execute( );
	return true;
}

function clamp_add( $params ){
	if( !isset( $params['sensor_id'] ) || !isset( $params['clamp_id'] ) || !is_numeric( $params['sensor_id'] ) || !is_numeric( $params['clamp_id'] ) ){ return false; }
	# a clamp is internal just a sensor :)
	$params['sensor_id'] = $params['clamp_id'].$params['sensor_id'];
	sensor_add( $params );
}

function global_settings_get( ){
	global $demo;
	$db = new db;
	$r = array();
	$db->query("SELECT * FROM measure_system");
	$ret = $db->results( );
	foreach( $ret as $d ){
		$r[$d['measure_system_setting_name']] = stripslashes( $d['measure_system_setting_value'] );
	}
	$r['system_settings_demo'] = 0;
	if( $demo ){
		$r['system_settings_demo'] = 1;
		$r['system_settings_pvoutput_api'] = '';
		$r['system_settings_twitter_app_key'] = '';
		$r['system_settings_twitter_app_secret'] = '';
		$r['system_settings_twitter_oauth_token'] = '';
		$r['system_settings_twitter_oauth_token_secret'] = '';
		$r['system_settings_email_address'] = '';
		$r['system_settings_email_pass'] = '';
	}
	header( 'Content-Type: application/json' );
	print json_encode( $r );
}

function global_settings_set( $params ){
	$db = new db;
	$db->query( "DELETE FROM measure_system WHERE measure_system_setting_name NOT LIKE( 'current_version' )" );
	$db->execute( );
	foreach( $params['data'] as $k => $v ){
		$db->query( 'INSERT INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) VALUES ( :key, :value )' );
		$db->data( 'key', $k );
		$db->data( 'value', $v );
		$db->execute( );
	}
}

function backup_create(){
	if( !is_dir('../backup') ){ mkdir( '../backup', 0775 ); }
	$db = new db;
	$db->backup();
}

function backup_list_get(){
	if( !is_dir('../backup') ){ mkdir( '../backup', 0775 ); }
	$dir = opendir('../backup');
	$files = array( );
	while( false !== ( $file = readdir( $dir ) ) ){
		if ( preg_match( '/\.gz/', $file ) ) {
			$day = preg_replace( '/(\d{4,})(\d{2,})(\d{2,})/', "$1-$2-$3", substr( $file, 17, 8 ) );
			$time = preg_replace( '/(\d{2,})(\d{2,})(\d{2,})/', "$1:$2:$3", substr( $file, 26, 6 ) );
			$files[$file]['file'] = 'backup/'.$file;
			$files[$file]['filename'] = $file;
			$files[$file]['day'] = $day;
			$files[$file]['time'] = $time;
			$files[$file]['size'] = format_bytes( filesize( '../backup/'.$file ) );
		}
	}
	header( 'Content-Type: application/json' );
	print json_encode($files);
}

function backup_delete( $params = array() ){
	if( isset( $params['filename'] ) && file_exists( '../backup/'.$params['filename'] ) && preg_match( '/^measureit_backup_(\d{8,8})-(\d{6,6}).gz$/', $params['filename'] ) ){
		unlink( '../backup/'.$params['filename'] );
	}
}

function languages_get( ){
	if( $h = opendir( '../lng' ) ){
		$cnt = 1;
		while ( false !== ( $file = readdir( $h ) ) ) {
	        if( preg_match( '/([a-z]{2,2}_[a-z]{2,2})\.txt/i', $file, $r ) ){
	        	$lng[$cnt++] = $r[1];
	        }
	    }
	    header( 'Content-Type: application/json' );
	    print json_encode( $lng );
	}
}

function update_information_get( ){
	$db = new db;
	$db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'next_version'");
	$v = $db->result( );
	if( is_array( $v ) ){
		print json_encode( $v );
	}
	return false;
}

function timezone_diff_get( $params = array( ) ){
	if( isset( $params['sensor'] ) && !is_numeric( $params['sensor'] ) ){ return false; }
	$sensor = sensor_get( $params['sensor'] );
	date_default_timezone_set('UTC');
	if( isset( $sensor[$params['sensor']]['measure_timezone_diff'] ) && is_numeric( $sensor[$params['sensor']]['measure_timezone_diff'] ) && $sensor[$params['sensor']]['measure_timezone_diff'] >= 1 ){
		if( isset( $params['debug'] ) ) debug( 'Found timezone settings in sensor in timezone_diff_get', $sensor[$params['sensor']] );
		$timezone_diff =  $sensor[$params['sensor']]['measure_timezone_diff'];
	}else{
		$db = new db;
		$r = array();
		$db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'global_timezone_use'");
		$d = $db->result( );
		if( isset( $params['debug'] ) ) debug( 'No timezone settings in sensor. Search global timezone settings in db in timezone_diff_get', $d );
		$timezone_diff = $d['measure_system_setting_value'];
	}
	if( !isset( $timezone_diff ) || $timezone_diff == 0 ){
		if( isset( $params['debug'] ) ) debug( 'Found no timezone settings in timezone_diff_get', $timezone_diff );
		return false;
	}
	preg_match( '/(-)?(.+)/', $timezone_diff, $r );
	$diff['prefix'] = $r[1] != '' ? $r[1] : false;
	$diff['diff'] = ( $r[2] * 60 ) * 60;
	return $diff;
}

function grabber_restart_init( ){
	system( 'touch /tmp/measureit_grabber_restart' );
	return true;
}

function grabber_status_get( ){
	$pid = system( "ps a -C python | grep data-input.py | head -1 | cut -d ' ' -f1" );
	if( !is_numeric( $pid ) ) return false;
	$proc_info = system( 'ps -p '.$pid.' -o etime=' );
	print json_decode($proc_info);
	return true;
}

function format_bytes($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}

function debug( $info, $request = false, $dump = false ){
	print $info.'<hr />';
	if( $dump ){
		var_dump( '<pre>', $dump );
		print '<hr />';
	}

	if( $request ){
		var_dump( '<pre>', $request );
		print '<hr />';
	}

}

function error( $error = 'unknown' ){
	print 'ERROR: '.$error;
	exit;
}

?>
