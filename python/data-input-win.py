import serial
import re
import MySQLdb
import datetime
import warnings
import time

warnings.filterwarnings("ignore")

# mysql connection
mysql = MySQLdb.connect("localhost","measureit","measureitpasswd","measure_it" )
db = mysql.cursor()
# com connection
ser = serial.Serial(port='COM3', baudrate=57600, bytesize=serial.EIGHTBITS, parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=3)

def sensor_list_get():
	sensors = {}
	db.execute('SELECT sensor_id FROM measure_sensors')
	r = db.fetchall()
	for row in r:
		sensors[row[0]] = {'tmpr' : 0, 'watt' : 0}
	return sensors

def sensor_data_change( coloum, sensor, data):
	db.execute('UPDATE measure_data_now SET '+coloum+' = "'+data+'" WHERE sensor_id = '+sensor)

def sensor_watt_insert( sensor, watt ):
	db.execute('INSERT INTO measure_watt ( sensor, data, time) values( '+sensor+', '+watt+', NOW() ) ')

def tmpr_insert( tmpr ):
	now = datetime.datetime.now()
	db.execute('INSERT INTO measure_tmpr ( data, time ) values( "'+str(tmpr)+'", NOW() ) ')
	db.execute('INSERT IGNORE INTO measure_tmpr_hourly ( data, time, hour ) VALUES ( "'+str(tmpr)+'", NOW(), "'+str(now.hour)+'" )')
	
def history_update( sensor, hist ):
	date_hour = date_hour_get(hist[1])
	if hist[0] == 'm':
		query = 'INSERT IGNORE INTO measure_watt_monthly ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[0]+'" )'
	if hist[0] == 'd':
		query = 'INSERT IGNORE INTO measure_watt_daily ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[0]+'" )'
	if hist[0] == 'h':
		query = 'INSERT IGNORE INTO measure_watt_hourly ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[1]+'", "'+date_hour[0]+'" )'
	db.execute(query)

def date_hour_get( hours ):
	db.execute('SELECT NOW() - INTERVAL '+hours+' HOUR')
	date = db.fetchone()
	r = re.search(r"(\d+-\d+-\d+) (\d+):.+", str(date[0]) )
	return (r.group(1), r.group(2))
	
sensors = sensor_list_get()

print 'Data grabbing is startet. Leave this window open to get permanet data from your device. In a few moments you should see your current watt usage'
time.sleep(3)
														 
while True:
	line = ser.readline()
	line = line.rstrip('\r\n')
	r = re.search(r"<hist>", line)
	if r:
		for s in sensors:
			r = re.search(r"<data><sensor>"+str(s)+"</sensor>(.+?)</data>", line)
			if r:
				d = re.findall(r"<(d|m|h)(\d+)>(.+?)</.+?>", r.group(1) )
				if d:
					for f in d:
						history_update(s,f)
				
	r = re.search(r"<tmpr>(.+?)</tmpr><sensor>(\d+)</sensor>.*<watts>(\d+)</watts>", line)
	if r: 
		if sensors[int( r.group(2) )]['tmpr'] != r.group(1):
				sensors[int( r.group(2) )]['tmpr'] = r.group(1)
				sensor_data_change( 'tmpr', r.group(2), r.group(1) )
				tmpr_insert( r.group(1) )
		if sensors[int( r.group(2) )]['watt'] != r.group(1):
				sensors[int( r.group(2) )]['watt'] = r.group(3)
				sensor_data_change( 'watt', r.group(2), r.group(3) )
				sensor_watt_insert( r.group(2), r.group(3) )	
		
		print 'Sensor '+r.group(2)+' Watt '+r.group(3)