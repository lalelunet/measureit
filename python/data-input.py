import serial
import re
import MySQLdb
import datetime
import warnings
import time
import threading
import sys
import platform

sensors = {}
settings = {}
db = {}
debug = False

usbport = 'COM3'
if platform.system() == 'Linux':
    usbport = '/dev/ttyUSB0'

warnings.filterwarnings("ignore")
# mysql connection
mysql = MySQLdb.connect(host="10.0.0.12",port=10001,user="measureit",passwd="nhjw_4k=0)/_rhje$$/e34%",db="measure_it" )
#mysql = MySQLdb.connect(host="localhost",port=3306,user="measureit",passwd="measureitpasswd",db="measure_it" )
db = mysql.cursor()

def sensor_list_get():
    db.execute('SELECT sensor_id FROM measure_sensors')
    r = db.fetchall()
    for row in r:
        sensor = int(row[0])
        sensors[sensor] = {'tmpr' : 0, 'watt' : 0}
    return sensors

def sensor_settings_get():
    sensors_settings = {}
    db.execute('SELECT sensor_id, measure_history FROM measure_sensors LEFT JOIN measure_settings ON measure_settings.measure_sensor = measure_sensors.sensor_id')
    r = db.fetchall()
    for row in r:
        sensor = int(row[0])
        sensors_settings[sensor] = row[1]
    return sensors_settings

def system_settings_get():
    system_settings = {}
    db.execute('SELECT measure_system_setting_name, measure_system_setting_value FROM measure_system')
    r = db.fetchall()
    return r

def sensor_data_change( coloum, sensor, data):
    sensor = str(sensor)
    data = str(data)
    db.execute('UPDATE measure_data_now SET '+coloum+' = "'+data+'" WHERE sensor_id = '+sensor)

def sensor_watt_insert( sensor, watt ):
    sensor = str(sensor)
    watt = str(watt)
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

def cron_timer_hourly():
    now = datetime.datetime.now()
    day_from = datetime.date.today()
    hour_from = now.hour-1
    hour_to = now.hour
    if now.hour == 0:
        hour_from = 23
        day_from = datetime.date.today() - datetime.timedelta(days=1)
    date_from = str(day_from)+' '+str(hour_from)+':00:00'
    date_to = str(day_from)+' '+str(hour_to)+':00:00'
    for sensor in sensors:
        usage_sum_hourly = usage_sum_count = sum = 0
        db.execute("select sensor, data from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor)) 
        r = db.fetchall() 
        for row in r:
            usage_sum_hourly += float(row[1])
            usage_sum_count += 1
        if usage_sum_count != 0:
            sum = (usage_sum_hourly/usage_sum_count)/1000
        query = 'INSERT IGNORE INTO measure_watt_hourly ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(sum)+'", "'+str(hour_from)+'", "'+str(day_from)+'" )'
        db.execute(query)
        usage_sum_hourly = usage_sum_count = sum = r = 0
        
    timer_hourly = threading.Timer(3600.0, cron_timer_hourly)
    timer_hourly.start()
        
    
def cron_timer_daily():
    for sensor in sensors:
        usage_sum_daily = usage_sum_count = sum = 0
        date_from = str(datetime.date.today() - datetime.timedelta(days=1))+' 00:00:00'
        date_to = str(datetime.date.today())+' 00:00:00'
        db.execute("select sensor, data from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor)) 
        r = db.fetchall() 
        for row in r:
            usage_sum_daily += float(row[1])
            usage_sum_count += 1
        if usage_sum_count != 0:
            sum = ((usage_sum_daily/usage_sum_count)*24)/1000
        query = 'INSERT IGNORE INTO measure_watt_daily ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(sum)+'", "'+date_from+'" )'
        db.execute(query)
        usage_sum_daily = usage_sum_count = sum = r = 0
    
    timer_daily = threading.Timer(86400.0, cron_timer_daily)
    timer_daily.start()

def date_hour_get( hours ):
    db.execute('SELECT NOW() - INTERVAL '+hours+' HOUR')
    date = db.fetchone()
    r = re.search(r"(\d+-\d+-\d+) (\d+):.+", str(date[0]) )
    return (r.group(1), r.group(2))

def sensor_data_check( sensor, watt, tmpr ):
    sensor = int(sensor)
    watt = int(watt)
    if sensors.has_key(sensor):
        if sensors[sensor]['tmpr'] != tmpr:
            sensors[sensor]['tmpr'] = tmpr
            sensor_data_change( 'tmpr', sensor, tmpr )
            tmpr_insert( tmpr )
        if sensors[sensor]['watt'] != watt:
            sensors[sensor]['watt'] = watt
            sensor_data_change( 'watt', sensor, watt )
            sensor_watt_insert( sensor, watt )
        return True    

sensors = sensor_list_get()

ser = serial.Serial(port=usbport, baudrate=57600, bytesize=serial.EIGHTBITS, parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=3)

if 'test' in sys.argv:
    debug = True

cron_timer_hourly()
cron_timer_daily()

while True:
    line = ser.readline()
    line = line.rstrip('\r\n')
    clamps = False
    if debug:
        print line
    
    # parsing from history_output 
    #r = re.search(r"<hist>", line)
    #if r:
    #    for s in sensors:
    #        r = re.search(r"<data><sensor>"+str(s)+"</sensor>(.+?)</data>", line)
    #        if r:
    #            d = re.findall(r"<(d|m|h)(\d+)>(.+?)</.+?>", r.group(1) )
    #           if d:
    #                for f in d:
    #                    history_update(s,f)
    
    r = re.search(r"<tmpr>(.+?)</tmpr><sensor>(\d)+</sensor>.+<ch1><watts>(\d+)<\/watts><\/ch1>(<ch2><watts>(\d+)<\/watts><\/ch2>)?(<ch3><watts>(\d+)<\/watts><\/ch3>)?", line)
    if r:
        tmpr = r.group(1)
        watt_sum = int(r.group(3))
        # more than 1 clamp
        if r.group(5):
            sensor = '2'+r.group(2)
            watt = int(r.group(5))
            watt_sum += watt
            sensor_data_check( sensor, watt, tmpr )
            clamps = True

        if r.group(7):
            sensor = '3'+r.group(2)
            watt = int(r.group(7))
            watt_sum += watt
            sensor_data_check( sensor, watt, tmpr )
            clamps = True
            
        if clamps:
            sensor = '1'+r.group(2)
            watt = int(r.group(3))
            sensor_data_check( sensor, watt, tmpr )
           
        sensor_data_check( r.group(2), watt_sum, tmpr )

