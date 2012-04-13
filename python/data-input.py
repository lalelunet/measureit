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