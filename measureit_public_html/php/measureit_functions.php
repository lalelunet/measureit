<?php 
require_once dirname(__FILE__).( '/class.db.php' );

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
		case 'summary_sensor_history_short':
			sensor_data_get_sorted( $_REQUEST );
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
		case 'sensor_statistic_comparison':
			sensor_statistic_comparison( $_REQUEST );
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
		default:
			echo 'this is not a valid request';
		break;
	}
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
	print json_encode($r);
	return true;
}

function sensor_detail( $params = array( ) ){
	if( is_numeric( $params['sensor'] ) ){
		$r['sensor'] = sensor_get( $params['sensor'] );
		$r['sensor']['clamps'] = sensor_clamps_get( $params['sensor'] );
		print json_encode($r);
	}
	return true;
}

function sensor_clamps_get( $sensor ){
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
	return false;
}

function sensor_detail_statistic( $params = array( ) ){
	if( is_numeric( $params['sensor'] ) ){
		$params['timeframe'] = 'static';
		$params['table'] = 'measure_watt_daily';
		$params['unit_value'] = '1';
		$params['unit'] = 'YEAR';
		if( $diff = timezone_diff_get( $params ) ){
			$use_diff = true;
			$diff = timezone_diff_get( $params );
		}
		$q = data_query_build( $params );
		$db = new mydb;
		$query = $db->query( $q );
		while( $d = $db->fetch_array( $query ) ){
			$date = @strtotime( $d['time'].' 00:00' );
			if( $use_diff ){
				$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
			}
			
			$datew = @date( w, $date );
			$daten= @date( n, $date );
			@$r['yeardays'][$datew] += $d['data'];
			@$r['yearsdaysdetail'][$daten-1][$datew] += $d['data'];
			ksort($r['yearsdaysdetail'][$daten-1]);
		}

		$params['table'] = 'measure_watt_hourly';
		$q = data_query_build( $params );
		$db = new mydb;
		$query = $db->query( $q );

		while( $d = $db->fetch_array( $query ) ){
			$date = @strtotime( $d['time'].' '.$d['hour'].':00' );
			if( $use_diff ){
				$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
			}
			$daten = @date( n, $date );
			@$r['yearhours'][$d['hour']] += $d['data'];
			ksort($r['yearhours']);
			@$r['monthshoursdetail'][$daten-1][$d['hour']] += $d['data'];
			ksort($r['monthshoursdetail'][$daten-1]);
		}
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
	print json_encode($r);
	return true;
}

function sensor_history_week( $params = array( ) ){
	$ret = ''; $use_diff = false;
	if( $diff = timezone_diff_get( $params ) ){
		$use_diff = true;
		$diff = timezone_diff_get( $params );
	}
	$q = data_query_build( $params );
	$db = new mydb;
	$query = $db->query( $q );
	while( $d = $db->fetch_array( $query ) ){
		$date = @strtotime( $d['time'].' '.$d['hour'].':00' );
		if( $use_diff ){
			$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
		}
		$r[@date( 'Y-m-d', $date )][@date( 'G', $date )]['data'] = $d['data'];
		ksort($r[@date( 'Y-m-d', $date )]);
	}
	print json_encode($r);
	return true;
}

function sensor_statistic_comparison( $params ){
	$params['table'] = 'measure_watt_monthly';
	$q = data_query_build( $params );
	$db = new mydb;
	$query = $db->query( $q );
	while( $d = $db->fetch_array( $query ) ){
		preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $d['time'], $ret );
		$r[$ret[1]][$ret[2]]['data'] = $d['data'];
	}
	$params['table'] = 'measure_watt_daily';
	$q = data_query_build( $params );
	$query = $db->query( $q );
	while( $d = $db->fetch_array( $query ) ){
		preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $d['time'], $ret );
		$r[$ret[1]][$ret[2]][$ret[3]]['data'] = $d['data'];
	}
	$params['table'] = 'measure_watt_hourly';
	$q = data_query_build( $params );
	$query = $db->query( $q );
	while( $d = $db->fetch_array( $query ) ){
		preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $d['time'], $ret );
		$r[$ret[1]][$ret[2]][$ret[3]][$d['hour']]['data'] = $d['data'];
	}
	print json_encode($r);
	return true;
}

function sensor_history_year( $params = array( ) ){
	$ret = ''; $use_diff = false;
	if( $diff = timezone_diff_get( $params ) ){
		$use_diff = true;
		$diff = timezone_diff_get( $params );
	}
	$q = data_query_build( $params );
	$db = new mydb;
	$query = $db->query( $q );
	while( $d = $db->fetch_array( $query ) ){
		$date = @strtotime( $d['time'].' 00:00' );
		if( $use_diff ){
			$date = $diff['prefix'] == '-' ? $date - $diff['diff'] : $date + $diff['diff'];
		}
		preg_match( '/(\d\d\d\d)-(\d\d)-(\d\d)/', @date( 'Y-m-d', $date ), $ret );
		@$r[$ret[1].'-'.$ret[2]][$ret[3]]['data'] += $d['data'];
	}
	print json_encode($r);
	return true;
}

function sensor_data_get( $params = array( ) ){
	$q = !strpos( $params['table'], 'tmpr' ) ? data_query_build( $params ) : tmpr_get_query( $params );
	if( $q ){
		$t = ''; $use_diff = false;
		$db = new mydb;
		$query = $db->query( $q );
		if( $diff = timezone_diff_get( $params ) ){
			if( isset( $params['debug'] ) ) debug( 'Found timezone settings in sensor_data_get', $params, timezone_diff_get( $params ) );
			$use_diff = true;
			$diff = timezone_diff_get( $params );
		}else{
			if( isset( $params['debug'] ) ) debug( 'NO timezone settings in sensor_data_get', $params, timezone_diff_get( $params ) );
		}
		$dgb = array( );
		while( $d = $db->fetch_array( $query ) ){
			$time =  $ts = preg_match('/hourly/', $params['table']) ? $d['time'].' '.$d['hour'].':00:00' : $d['time'];
			if( $use_diff ){
				$ts = $diff['prefix'] == '-' ? @strtotime( $time ) - $diff['diff'] : @strtotime( $time ) + $diff['diff'];
				if( isset( $params['debug'] ) ) $dbg[$time] = @date( 'Y-m-d H:i:s', $ts );
			}else{
				if( isset( $params['debug'] ) ) $dbg[$time] = @date( 'Y-m-d H:i:s', $time );
			}
			$u = $params['unit_return'] == 'timeframe' ? ( $use_diff ? $ts*1000 : @strtotime( $ts )*1000 ) : $time;
			$t .= '['. $u .', '. $d['data'] .'],';
		}
		$r = preg_replace( '/(.+),$/', "$1", $t );
		$r = '['.$r.']';
		if( isset( $params['debug'] ) ) debug( 'Values in sensor_data_get', false, $dbg );
		print $r;
	}
}

function sensor_data_get_sorted( $params = array( ) ){
	$q = !strpos( $params['table'], 'tmpr' ) ? data_query_build( $params ) : tmpr_get_query( $params );
	if( $q ){
		$t = ''; $use_diff = false; $cnt_data = $ts_start = 0; $cum = $pr = array( );
		$db = new mydb;
		$query = $db->query( $q );
		if( $diff = timezone_diff_get( $params ) ){
			$use_diff = true;
			$diff = timezone_diff_get( $params );
		}
		while( $d = $db->fetch_array( $query ) ){
			$time =  $ts = $d['time'];
			if( $use_diff ){
				$ts = $diff['prefix'] == '+' ? @strtotime( $time ) - $diff['diff'] : @strtotime( $time ) + $diff['diff'];
			}
			if( $cnt_data == 0 ){
				$ts_start = $ts;
			}
			if( $ts <= $ts_start + 21600 ){
				@$cum[6][$ts] = $d['data'];
				@$cum[9][$ts] = $d['data'];
				@$cum[12][$ts] = $d['data'];
				@$cum[15][$ts] = $d['data'];
				@$cum[18][$ts] = $d['data'];
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 32400 ){
				@$cum[9][$ts] = $d['data'];
				@$cum[12][$ts] = $d['data'];
				@$cum[15][$ts] = $d['data'];
				@$cum[18][$ts] = $d['data'];
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 43200 ){
				@$cum[12][$ts] = $d['data'];
				@$cum[15][$ts] = $d['data'];
				@$cum[18][$ts] = $d['data'];
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 54000 ){
				@$cum[15][$ts] = $d['data'];
				@$cum[18][$ts] = $d['data'];
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 64800 ){
				@$cum[18][$ts] = $d['data'];
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 75600 ){
				@$cum[21][$ts] = $d['data'];
				@$cum[24][$ts] = $d['data'];
			}elseif( $ts <= $ts_start + 86400 ){
				@$cum[24][$ts] = $d['data'];
			}
			
			$cnt_data++;
		}
		
		foreach ( $cum as $k => $v){
			$tc = 1;
			foreach( $v as $kv => $vv ){
				$tstp = $kv * 1000; 
				if( $tc == 1 ){
					$tts = $tstp;
				}
				
				if( $k == 6 ){
					if( $tc == 3 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 3 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 9 ){
					if( $tc == 4 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 4 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 12 ){
					if( $tc == 6 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 6 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 15 ){
					if( $tc == 7 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 7 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 18 ){
					if( $tc == 9 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 9 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 21 ){
					if( $tc == 10 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 10 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}elseif( $k == 24 ){
					if( $tc == 11 ){
						$tc = 0;
						$r[$k][$tts] = round( $r[$k][$tts] / 11 );
						@$pr[$k]['data'] .= '['. $tts .', '. $r[$k][$tts] .'],';
					}
					@$r[$k][$tts] += $vv;
				}
				$tc++;
			}
		}
		
		# remove last colon from arrays
		foreach( $pr as $k=>$v ){
			$pr[$k] = preg_replace( '/(.+),$/', "$1", $v );
		}
		
		print json_encode( $pr );
		return true;
	}
}

function sensor_values_now_get( $sensor ){
	if( is_numeric( $sensor ) ){
		$db = new mydb;
		$query = $db->query( "SELECT * FROM measure_data_now WHERE sensor_id = $sensor" );
		return $db->fetch_array( $query );
	}
	return true;
}

function sensor_notifications_get( $params = array( ) ){
	if( !isset( $params['sensor'] ) || !preg_match( '/[0-9]/', $params['sensor'] ) ) return true;
	$db = new mydb;
	$query = $db->query( 'SELECT * FROM measure_notifications WHERE measure_notifications_sensor = '. $params['sensor'] );
	$r = array();
	while( $d = $db->fetch_array( $query ) ){
		$r[$d['measure_notifications_id']]['measure_notifications_name'] = $d['measure_notifications_name'];
		$r[$d['measure_notifications_id']]['measure_notifications_unit'] = $d['measure_notifications_unit'];
		$r[$d['measure_notifications_id']]['measure_notifications_check_email'] = $d['measure_notifications_check_email'];
		$r[$d['measure_notifications_id']]['measure_notifications_check_twitter'] = $d['measure_notifications_check_twitter'];
		$r[$d['measure_notifications_id']]['measure_notifications_notification'] = $d['measure_notifications_notification'];
		$r[$d['measure_notifications_id']]['measure_notifications_items'] = $d['measure_notifications_items'];
		$r[$d['measure_notifications_id']]['measure_notifications_criteria'] = $d['measure_notifications_criteria'];
		$r[$d['measure_notifications_id']]['measure_notifications_value'] = $d['measure_notifications_value'];
	}
	print json_encode( $r );
}

function sensor_notification_save( $params = array( ) ){
	$keys = $values = array( );
	foreach( $params as $k => $v){
		if( $k == 'do' || $k == 'sensor' || ( $k == 'measure_notifications_id' && $v == 0 ) ) continue;
		$keys[] = $k;
		$values[] = '"'.$v.'"';
	}
	$db = new mydb;
	$query = $db->query( 'REPLACE INTO measure_notifications ( '.implode( ',', $keys ).' ) VALUES( '.implode( ',', $values ).' )' );
	return true;
}

function sensor_notification_delete( $params = array( ) ){
	if( !isset( $params['sensor'] ) || !preg_match( '/[0-9]/', $params['sensor'] ) ) return true;
	if( !isset( $params['measure_notifications_id'] ) || !preg_match( '/[0-9]/', $params['measure_notifications_id'] ) ) return true;
	$db = new mydb;
	$query = $db->query( 'DELETE from measure_notifications WHERE measure_notifications_id = "'.$params['measure_notifications_id'].'" AND measure_notifications_sensor = '. $params['sensor'] );
	return true;
}

function sensor_prices_get( $params = array( ) ){
	$subselect = ( isset( $params['sensor'] ) && preg_match( '/[0-9]/', $params['sensor'] ) ) ? ' WHERE costs_sensor = '.$params['sensor'] : '';
	$db = new mydb;
	$query = $db->query( 'SELECT * FROM measure_costs'.$subselect.' ORDER BY costs_since desc' );
	$r = array();
	$cnt = 0;
	while( $d = $db->fetch_array( $query ) ){
		if( !is_numeric( $params['sensor'] ) ) continue;
		$r[$d['costs_since']][$cnt]['costs_id'] = $d['costs_id'];
		$r[$d['costs_since']][$cnt]['costs_sensor'] = $d['costs_sensor'];
		$r[$d['costs_since']][$cnt]['costs_from'] = $d['costs_from'];
		$r[$d['costs_since']][$cnt]['costs_to'] = $d['costs_to'];
		$r[$d['costs_since']][$cnt]['costs_price'] = $d['costs_price'];
		$cnt++;
	}
	print json_encode( $r );
}

function sensor_prices_all_get( ){
	$r = array();
	$db = new mydb;
	$query = $db->query( 'SELECT * FROM measure_costs ORDER BY costs_since desc' );
	
	while( $d = $db->fetch_array( $query ) ){
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
	if( !isset( $params['id'] ) && !preg_match( '/[0-9]/', $params['id'] ) ) return true;
	$db = new mydb;
	$query = $db->query( 'DELETE from measure_costs WHERE costs_id = '.$params['id'] );
	return true;
}

function sensor_prices_delete( $params = array( ) ){
	if( !isset( $params['date'] ) ) return true;
	$db = new mydb;
	$query = $db->query( 'DELETE from measure_costs WHERE costs_since = "'.$params['date'].'"' );
	return true;
}

function sensor_price_add( $params = array( ) ){
	preg_match( '/\d{4,4}-\d{2,2}-\d{2,2}/', $params['date'], $r );
	$params['price'] = preg_replace(  '/,/', '.', $params['price'] );
	if( !is_numeric( $params['price'] ) || !is_numeric( $params['from'] ) || !is_numeric( $params['to'] ) || strlen( $r['0'] ) != 10 ) return true;
	if( $params['from'] == 0 && $params['to'] == 0 ){
		#1 price the whole day
		$params['to'] = 23;
	}
	$q = 'INSERT INTO measure_costs (costs_sensor, costs_from, costs_to, costs_price, costs_since) VALUES ( '.$params['sensor'].', '.$params['from'].', '.$params['to'].', '.$params['price'].', "'.$params['date'].'")';
	$db = new mydb;
	$query = $db->query( $q );
	return true;
}

function sensor_statistic( $params = array( ) ){
	$params['range_to'] = sensor_position_next_date_get( $params );
	sensor_statistic_get( $params );
}

function sensor_data_raw_get( $params = array( ) ){
	if( $q = data_query_build( $params ) ){
		$db = new mydb;
		$query = $db->query( $q );
		$r = array();
		while( $d = $db->fetch_array( $query ) ){
			@$r[$d['hour']] += $d['data'];
		}
	}
	return $r;
}

function sensor_statistic_get( $params = array( ) ){
	if( $q = data_query_build( $params ) ){
		$prices = sensor_prices_all_get( );
		$r = ''; $tmp = array( );
		$db = new mydb;
		$query = $db->query( $q );
		
		while( $d = $db->fetch_array( $query ) ){
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

		print json_encode($r);
	}
}

function sensor_item_get( $params = array( ) ){
	if( data_query_build( $params ) ){
		$db = new mydb;
		$query = $db->query( data_query_build( $params ) );
		$r = array();
		while( $d = $db->fetch_array( $query ) ){
			$r = $d['data'];
		}
	}
	return $r;
}

function lng_get( $params = array( ) ){
	$db = new mydb;
	$query = $db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'language_use'");
	$language = $db->fetch_array( $query );
	
	$r = lng_str_get( 'en_EN' );
	$r = lng_str_get( $language['measure_system_setting_value'], $r );

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
	if( !strpos( $params['table'], 'tmpr' ) ){ return false; }
	$query = "SELECT * FROM $params[table] WHERE time = '$params[select]'";
	return $query;
}

function data_query_build( $params = array( ) ){
	$table = preg_match( '/(measure_watt|measure_watt_hourly|measure_watt_daily|measure_watt_monthly)/', $params['table'] ) ? $params['table'] : error( 'no database table selected: '.$params['table'] );
	$sensor = is_numeric( $params['sensor'] ) ? $params['sensor'] : error( 'no sensor error: '.$params['sensor'] );
	$order = isset( $params['order'] ) ? $params['order'] : 'time';
	$turn = isset( $params['turn'] ) ? $params['turn'] : '';
	
	switch( $params['timeframe'] ){
		case 'static':
			$unit = preg_match( '/(hour|day|month|year)/i', $params['unit'] ) ? $params['unit'] : error( 'unit error: '.$params['unit'] );
			$unit_value = is_numeric( $params['unit_value'] ) ? $params['unit_value'] : error( 'unit value error: '.$params['unit_value'] );
			$timeframe = " AND time > UTC_TIMESTAMP( ) - INTERVAL $unit_value $unit ORDER BY $order";
		break;
		case 'last':
			$timeframe = " ORDER BY $order DESC LIMIT 1";
			# last hour watts has an extra option
			$timeframe = $params['table'] == 'measure_watt_hourly' ? 'AND time = DATE( UTC_TIMESTAMP( ) ) '.$timeframe : $timeframe;
		break;
		case 'limit-last':
			$unit_value = is_numeric( $params['unit_value'] ) ? $params['unit_value'] : error( 'unit value error: '.$params['unit_value'] );
			$timeframe = " ORDER BY $order DESC LIMIT $unit_value";
		break;
		case 'select':
			$timeframe = "AND time = '$params[select]'";
		break;
		case 'range':
			$from = preg_replace('/_/', ' ', $params['range_from']);
			$to = preg_replace('/_/', ' ', $params['range_to']);
			$timeframe = "AND time BETWEEN '$from:00:00' and '$to:00:00'";
		break;
		case 'position':
			$timeframe = "AND time BETWEEN '$params[range_from]' and $params[range_to] ORDER BY $order $turn";
		break;
		case 'limit':
			$unit = preg_match( '/(hour|day| month)/i', $params['unit'] ) ? $params['unit'] : error( 'unit error: '.$params['unit'] );
			$unit_value = is_numeric( $params['unit_value'] ) ? $params['unit_value'] : error( 'unit value error: '.$params['unit_value'] );
			$timeframe = ' AND time > "'.$params['limit'].'"';
		break;
		case 'all':
			$timeframe = '';
		break;
		default:
			error('No timeframe to get data from');
		break;
	}
	$selection = isset( $params['selection'] ) ? mysql_real_escape_string( $params['selection'] ) : '*';
	$query = "SELECT $selection FROM $table WHERE sensor = '$sensor' $timeframe";
	if( isset( $params['debug'] ) ) debug( $query, $params );
	#var_dump('<pre>',$params);
	return $query;
}

function price_sum( $params ){
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
	$db = new mydb;
	$query = $db->query("SELECT position_time FROM measure_positions WHERE position_id > $params[sensor_position] AND position_sensor = $params[sensor]");
	$date = $db->fetch_row( $query );
	$r = is_array( $date ) ? sprintf( "'%s'", substr( $date[0], 0, 10) ) : 'UTC_TIMESTAMP( )';
	return $r;
}

function sensor_position_last_get( $sensor ){
	$db = new mydb;
	$query = $db->query("SELECT * FROM `measure_positions` WHERE position_sensor = $sensor  ORDER BY position_id DESC  LIMIT 1");
	$sp = $db->fetch_row( $query );
	return $sp;
}

function sensor_get( $sensor = '' ){
	if( !is_numeric( $sensor ) ) return true;
	global $demo;
	$db = new mydb;
	$query = $db->query( "
		SELECT * 
		FROM measure_sensors
		LEFT JOIN measure_positions ON measure_positions.position_sensor = measure_sensors.sensor_id
		LEFT JOIN measure_settings ON measure_sensors.sensor_id = measure_settings.measure_sensor
		WHERE measure_sensors.sensor_id = $sensor
		ORDER BY measure_sensors.sensor_id, measure_positions.position_id
	" );
	$r = array();
	while( $d = $db->fetch_array( $query ) ){
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
	}
	#echo '<pre>'; var_dump($r);
	return $r;
}

function sensors_get( ){
	global $demo;
	$db = new mydb;
	$query = $db->query( "
		SELECT * 
		FROM measure_sensors
		LEFT JOIN measure_positions ON measure_positions.position_sensor = measure_sensors.sensor_id
		LEFT JOIN measure_settings ON measure_sensors.sensor_id = measure_settings.measure_sensor
		ORDER BY measure_sensors.sensor_id, measure_positions.position_id
	" );
	$r = array();
	while( $d = $db->fetch_array( $query ) ){
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
	#echo '<pre>'; var_dump($r);
	return $r;
}

function sensors_get_json( ){
	$db = new mydb;
	$query = $db->query( "SELECT * FROM measure_sensors" );
	$r = array();
	while( $d = $db->fetch_array( $query ) ){
		$r[] = $d['sensor_id'];
	}
	print json_encode( $r );
}

function sensor_position_add( $params = array() ){
	$db = new mydb;
	$db->query("INSERT INTO measure_positions ( position_time, position_description, position_sensor ) VALUES ( UTC_TIMESTAMP( ), '$params[sensor_position_name]', '$params[sensor_id]' )");
	return true;
}

function sensor_settings_save( $params = array() ){
	$params['sensor_price'] = preg_replace('/,/', '.', @$params['sensor_price']);
	$db = new mydb;
	$db->query("UPDATE measure_settings SET measure_history = '$params[sensor_history]', measure_currency = '$params[sensor_currency]', measure_timezone_diff = '$params[sensor_timezone_diff]', measure_pvoutput_id = '$params[sensor_pvoutput_id]', measure_pvoutput_api = '$params[sensor_pvoutput_api]', measure_type = '$params[sensor_type]' WHERE measure_sensor = '$params[sensor_id]'");
	return true;
}
function sensor_position_delete( $params = array() ){
	if( !is_numeric( $params[sensor_position_id] ) ){
		error('sensor position is wrong');
	}
	$db = new mydb;
	$db->query("DELETE FROM measure_positions WHERE position_id = $params[sensor_position_id] LIMIT 1");
	return true;
}

function sensor_entry_delete( $params = array() ){
	if( !is_numeric( $params[sensor_id] ) ){
		error('sensor is wrong');
	}
	$db = new mydb;
	$db->query("DELETE FROM measure_sensors WHERE sensor_id = $params[sensor_id] LIMIT 1");
	return true;
}

function sensor_delete( $params = array() ){
	if( !isset( $params['sensor_id'] ) || !is_numeric( $params['sensor_id'] ) ){
		error('sensor is wrong');
	}
	$db = new mydb;
	$db->query("DELETE FROM measure_sensors WHERE sensor_id = $params[sensor_id] LIMIT 1");
	$db->query("DELETE FROM measure_positions WHERE position_sensor = $params[sensor_id]");
	$db->query("DELETE FROM measure_settings WHERE measure_sensor = $params[sensor_id]");
	$db->query("DELETE FROM measure_watt WHERE sensor = $params[sensor_id]");
	$db->query("DELETE FROM measure_data_now WHERE sensor_id = $params[sensor_id]");
	$db->query("DELETE FROM measure_watt_daily WHERE sensor = $params[sensor_id]");
	$db->query("DELETE FROM measure_watt_hourly WHERE sensor = $params[sensor_id]");
	$db->query("DELETE FROM measure_watt_monthly WHERE sensor = $params[sensor_id]");
	return true;
}

function sensor_add( $params = array() ){
	$db = new mydb;
	$db->query("INSERT IGNORE INTO measure_data_now ( sensor_id, watt, tmpr) VALUES ( '$parmams[sensor_id]', '0', '0' )");
	$db->query("INSERT INTO measure_sensors ( sensor_id, sensor_title ) VALUES ( '$params[sensor_id]', '$params[sensor_name]' )");
	$db->query("INSERT INTO measure_settings ( measure_sensor ) VALUES ( '$params[sensor_id]' )");
	return true;
}

function clamp_add( $params ){
	# a clamp is internal just a sensor :)
	$params['sensor_id'] = $params['clamp_id'].$params['sensor_id'];
	sensor_add( $params );
}

function global_settings_get( ){
	global $demo;
	$db = new mydb;
	$r = array();
	$query = $db->query("SELECT * FROM measure_system");
	while( $d = $db->fetch_array( $query ) ){
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
	
	print json_encode( $r );
}

function global_settings_set( $params ){
	$db = new mydb;
	$db->query("DELETE FROM measure_system WHERE measure_system_setting_name NOT LIKE( 'current_version' )");
	foreach( $params['data'] as $k => $v ){
		$db->query("INSERT INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) VALUES ('$k', '$v' )");
	}
}

function backup_create(){
	if( !is_dir('../backup') ){ mkdir( '../backup', 0775 ); }
	$db = new mydb;
	$db->backup();
}

function backup_list_get(){
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
	    print json_encode( $lng );
	}
}

function update_information_get( ){
	$db = new mydb;
	$query = $db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'next_version'");
	$v = $db->fetch_array( $query );
	if( is_array( $v ) ){
		print json_encode( $v );
	}
	return false;
}

function timezone_diff_get( $params = array( ) ){
	$sensor = sensor_get( $params['sensor'] );
	
	if( isset( $sensor[$params['sensor']]['measure_timezone_diff'] ) && is_numeric( $sensor[$params['sensor']]['measure_timezone_diff'] ) ){
		$timezone_diff =  $sensor[$params['sensor']]['measure_timezone_diff'];
	}else{
		$db = new mydb;
		$r = array();
		$query = $db->query("SELECT * FROM measure_system WHERE measure_system_setting_name = 'global_timezone_use'");
		$d = $db->fetch_array( $query );
		
		$timezone_diff = $d['measure_system_setting_value'];
	}
	if( !isset( $timezone_diff ) || $timezone_diff == 0 ){
		return false;
	}
	preg_match( '/(-)?(.+)/', $timezone_diff, $r );
	$diff['prefix'] = $r[1] != '' ? $r[1] : false;
	$diff['diff'] = ( $r[2] * 60 ) * 60;
	return $diff;
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
	}
	print '<hr />';
	if( $request ){
		var_dump( '<pre>', $request );
	}
	print '<hr />';
}

function error( $error = 'unknown' ){
	print 'ERROR: '.$error;
	exit;
}

?>
