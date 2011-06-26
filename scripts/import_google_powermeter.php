<?php

require_once( '../php/class.db.php' );
$count = $count_daily = $count_hourly = $daily = 0;
$usage = array();
if( !isset( $argv[1] ) || !isset( $argv[2] ) ){
	$param = '';
	if( !isset( $argv[1] ) || !is_numeric( $argv[1] ) || $argv[1] > 9 || $argv[1] == 0 ){ $param .= "\nsensor-id must be numeric [1-9]"; }
	if( !isset( $argv[2] ) || !preg_match( 'raw\.csv', $argv[2] ) ){ $param .= "\npath to the google export file [for example /tmp/raw.csv or just raw.csv if it is in the same directory like this script]"; }
	die("ERROR:\nusage ./import_google_powermeter.php sensor-id -path-to-raw.csv\n".$param."\n\n");
}

$fp = @fopen( $argv[2], 'r') or die ('can not open file. '.$argv[2]." was not found\n");
	print "file was found. Import is now starting.";
	$db = new mydb;
	while($dataset = fgets($fp, 1024)){
	 	$data = explode( ',', $dataset);
	 	preg_match( "/^(\d{4,})(\d{2,})(\d{2,})T(\d{2,})(\d{2,})(\d{2,})\..*/ism", $data[0], $ret);
		if( preg_match( '/StartTime/i', $data[0]) ){ continue; }
		$day = $ret[1].'-'.$ret[2].'-'.$ret[3];
		$date = $day.' '.$ret[4].':'.$ret[5].':'.$ret[6];
	 	if( $count == 0 ){
	 		$position = $date;
	 	}
		$watt = ( $data[2] * 6 ) * 1000;
		@$usage[$day][$ret[4]]['watt'] += $watt;
		#var_dump(round($watt));
		$db->query( "INSERT INTO measure_watt ( sensor, data, time) values( $argv[1], round( $watt ), '$date' )" );
	 	$count++;
	}
	$db->query( "INSERT INTO measure_positions ( position_time, position_description, position_sensor) values( '$position', 'google Power Meter import', $argv[1] )" );
	#var_dump($usage);exit;
	foreach($usage as $k => $v){
		foreach( $v as $vk => $vv){
			@$daily += $vv[watt];
			$count_hourly++;
			@$w = number_format( round($vv[watt]), 0, ',', '.' );
			$db->query( "INSERT INTO measure_watt_hourly( sensor, data, hour, time) values( $argv[1], $w, $vk, '$k' )" );
		}
		$count_daily++;
		$daily = number_format( round($daily), 0, ',', '.' );
		$db->query( "INSERT INTO measure_watt_daily( sensor, data, time) values( $argv[1], $daily, '$k' )" );
	}
	#var_dump($usage);
fclose($fp);

print "\nDONE. $count data rows importet\n\n";
print "Importet days: ".$count_daily."\nImportet hours :".$count_hourly."\n";

?>