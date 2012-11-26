import serial
import re
import MySQLdb
import datetime
import warnings
import time
import threading
import sys
import platform
import simplemail
import urllib2
import logging
import traceback

config = {}
sensors = {}
settings = {}
sensor_settings = {}
system_settings = {}
db = {}
err_critical = 0
debug = False

usbport = 'COM3'
config_file_name = "C:\measureit\measureit.cfg.php"

if platform.system() == 'Linux':
    usbport = '/dev/ttyUSB0'
    config_file_name = "/usr/local/measureit/measureit.cfg.php"

logger = logging.getLogger('MeasureIt')
hdlr = logging.FileHandler('/tmp/measureit.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)

if 'test' in sys.argv:
    debug = True
    logger.setLevel(logging.INFO)
else:
    logger.setLevel(logging.WARNING)


def sensor_list_get():
    try:
        r = mysql_query('SELECT sensor_id FROM measure_sensors','fetchall')
        for row in r:
            sensor = int(row[0])
            sensors[sensor] = {'tmpr' : 0, 'watt' : 0}
        return sensors
    except:
        logger.error('Error in sensor_list_get. Error: '+traceback.format_exc())


def system_settings_get():
    try:
        r = mysql_query('SELECT measure_system_setting_name, measure_system_setting_value FROM measure_system','fetchall')
        for row in r:
            system_settings[row[0]] = row[1]
        return True
    except:
        logger.warning('Error in system_settings_get Error: '+traceback.format_exc())

def sensor_data_change( coloum, sensor, data):
    try:
        sensor = str(sensor)
        data = str(data)
        mysql_query('UPDATE measure_data_now SET '+coloum+' = "'+data+'" WHERE sensor_id = '+sensor)
    except:
        logger.warning('Error in sensor_data_change while try to change the current volt data Error: '+traceback.format_exc())
        err_critical_count()

def sensor_watt_insert( sensor, watt ):
    try:
        sensor = str(sensor)
        watt = str(watt)
        mysql_query('INSERT INTO measure_watt ( sensor, data, time) values( '+sensor+', '+watt+', NOW() ) ')
    except:
        logger.warning('Error in sensor_watt_insert while inserting watt into database Error: '+traceback.format_exc())
        err_critical_count()

def tmpr_insert( tmpr ):
    try:
        now = datetime.datetime.now()
        mysql_query('INSERT INTO measure_tmpr ( data, time ) values( "'+str(tmpr)+'", NOW() ) ')
        mysql_query('INSERT IGNORE INTO measure_tmpr_hourly ( data, time, hour ) VALUES ( "'+str(tmpr)+'", NOW(), "'+str(now.hour)+'" )')
    
    except:
        logger.warning('Error in tmpr_insert while insert tmpr. Error: '+traceback.format_exc())

def history_update( sensor, hist ):
    try:
        mysql.ping(True)
        date_hour = date_hour_get(hist[1])
        if hist[0] == 'm':
            query = 'INSERT IGNORE INTO measure_watt_monthly ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[0]+'" )'
        if hist[0] == 'd':
            query = 'INSERT IGNORE INTO measure_watt_daily ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[0]+'" )'
        if hist[0] == 'h':
            query = 'INSERT IGNORE INTO measure_watt_hourly ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[1]+'", "'+date_hour[0]+'" )'
        mysql_query(query)
    except:
        logger.warning('Error in history_update. Error: '+traceback.format_exc())
        err_critical_count()

def cron_timer_hourly():
    now = datetime.datetime.now()
    day_from = datetime.date.today()
    day_to = datetime.date.today()
    hour_from = now.hour-1
    hour_to = now.hour
    if now.hour == 0:
        hour_from = 23
        day_from = datetime.date.today() - datetime.timedelta(days=1)
    date_from = str(day_from)+' '+str(hour_from)+':00:00'
    date_to = str(day_to)+' '+str(hour_to)+':00:00'
    try:
        for sensor in sensors:
            usage_sum_hourly = usage_sum_count = sum = 0
            r = mysql_query("select sensor, data from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor),'fetchall')
            for row in r:
                usage_sum_hourly += float(row[1])
                usage_sum_count += 1
            if usage_sum_count != 0:
                sum = (usage_sum_hourly/usage_sum_count)/1000
            query = 'INSERT IGNORE INTO measure_watt_hourly ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(sum)+'", "'+str(hour_from)+'", "'+str(day_from)+'" )'
            mysql_query(query)
        usage_sum_hourly = usage_sum_count = sum = r = 0
    except:
        logger.warning('Error in cron_timer_hourly. Error: '+traceback.format_exc())
        err_critical_count()
        
    timer_hourly = threading.Timer(3600.0, cron_timer_hourly)
    timer_hourly.start()
        
    
def cron_timer_daily():
    global mysql
    timer_daily = threading.Timer(86400.0, cron_timer_daily)
    timer_daily.start()
    try:
        for sensor in sensors:
            usage_sum_daily = usage_sum_count = sum = 0
            date_from = str(datetime.date.today() - datetime.timedelta(days=1))+' 00:00:00'
            date_to = str(datetime.date.today())+' 00:00:00'
            r = mysql_query("select sensor, data from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor),'fetchall')
            try:
                for row in r:
                    usage_sum_daily += float(row[1])
                    usage_sum_count += 1
                if usage_sum_count != 0:
                    sum = ((usage_sum_daily/usage_sum_count)*24)/1000
                    
                query = 'INSERT IGNORE INTO measure_watt_daily ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(sum)+'", "'+date_from+'" )'
                mysql_query(query)
                usage_sum_daily = usage_sum_count = sum = r = 0
            except:
                logger.warning('Error in cron_timer_daily. Error: '+traceback.format_exc())
                err_critical_count()
            try: # delete old watt data
                if sensor_settings.has_key(sensor):
                    if sensor_settings[sensor] > 0:
                        query = 'DELETE FROM measure_watt WHERE sensor = '+str(sensor)+' AND time < NOW( ) - INTERVAL '+str(sensor_settings[sensor])+' DAY'
                        mysql_query(query)
            except:
                logger.warning('Error in cron_timer_hourly while deleting old data Error: '+traceback.format_exc())
                err_critical_count()
    except:
        logger.warning('Error in cron_timer_daily. Error: '+traceback.format_exc())
        err_critical_count()
        
def cron_timer_weekly():
    timer_weekly = threading.Timer(604800.0, cron_timer_weekly)
    timer_weekly.start()
    update_check()

def update_check():
    if system_settings.has_key('current_version'):
        nv = int(system_settings['current_version'])+1
        try:
            r = urllib2.urlopen('https://measureit.googlecode.com/files/measureit-'+int(nv)+'.zip')
            mysql_query('INSERT INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) values ( "next_version", "'+str(nv)+'" )')
        except:
            logger.info('Update: No new version found')

    else:
        mysql_query('INSERT INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) values ( "current_version", 113 )')

def system_settings_get():
    r = mysql_query('SELECT measure_system_setting_name, measure_system_setting_value FROM measure_system','fetchall')
    for row in r:
        system_settings[row[0]] = row[1]
    return True

def sensor_settings_get():
    try:
        r = mysql_query('SELECT measure_sensor, measure_history FROM measure_settings','fetchall')
        for row in r:
            sensor_settings[row[0]] = row[1]
        return True
    except:
        logger.warning('Error in sensor_settings_get Error: '+traceback.format_exc())
                
def date_hour_get( hours ):
    try:
        date = mysql_query('SELECT NOW() - INTERVAL '+hours+' HOUR','fetchone')
        r = re.search(r"(\d+-\d+-\d+) (\d+):.+", str(date[0]) )
        return (r.group(1), r.group(2))
    except:
        logger.warning('Error in data_hour_get. Error: '+traceback.format_exc())

def sensor_data_check( sensor, watt, tmpr ):
    sensor = int(sensor)
    watt = int(watt)
    if sensors and sensors.has_key(sensor):
        if sensors[sensor]['tmpr'] != tmpr:
            sensors[sensor]['tmpr'] = tmpr
            sensor_data_change( 'tmpr', sensor, tmpr )
            tmpr_insert( tmpr )
        if sensors[sensor]['watt'] != watt:
            sensors[sensor]['watt'] = watt
            sensor_data_change( 'watt', sensor, watt )
            sensor_watt_insert( sensor, watt )
        return True    

def mail_send(email_subject, email_body):
    simplemail.Email(
        from_address = email_address,
        to_address = email_address,
        subject = email_subject,
        message = email_body,
        smtp_server = email_smtp_server,
        smtp_user = email_smtp_user,
        smtp_password = email_smtp_passwd, 
        use_tls = email_smtp_tls
    ).send()

def err_critical_count():
    global err_critical
    err_critical += 1
    if err_critical > 3000:
        try:
            mail_send('Message from your measureit installation', 'Please take a look at your installation. It seems there is a problem...')
            err_critical = 0
        except:
            logger.warning('Error in err_critical_count Can not send email. Please check your settings!')

def config_parse():
    try:
        config_file = open(config_file_name, 'r')
    except:
        logger.error('Error in config_parse. '+config_file_name+' could not be opened or read. Please check if file exists and that that the permissions are ok Error: '+traceback.format_exc())
    
    for line in config_file:
        line = line.rstrip()
    
        if not line:
            continue
        
        if line.startswith("#"):
            continue
        
        r = re.search(r".?\$(.+) ?= ?'(.+)';", line)
        if r:
            if r.group(1) and r.group(2):
                config[r.group(1).rstrip()] = r.group(2).rstrip()
    return True

def mysql_query(query, type = False):
    if query:
        try:
            mysql = MySQLdb.connect(host=config['database_host'],port=int(config['database_port']),user=config['database_user'],passwd=config['database_passwd'],db=config['database_name'])
            db = mysql.cursor()
            
            try:
                if debug:
                    logger.info('Try to execute query: '+query)
                db.execute(query)
                if type:
                    if type == 'fetchone':
                        return db.fetchone()
                    if type == 'fetchall':
                        return db.fetchall()
                db.close()
            except:
                logger.error('Can not execute query. Error: '+traceback.format_exc())
            
        except:
            logger.error('Can not connect to database. Is the database on and are the database settings ok? Error: '+traceback.format_exc())
    
    return True

warnings.filterwarnings("ignore")

try:
    ser = serial.Serial(port=usbport, baudrate=57600, bytesize=serial.EIGHTBITS, parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=3)
except:
    logger.error('Can not connect to the com device. Please check the cable is plugged in and if the device has the correctly drivers installed Error: '+traceback.format_exc())

config_parse()
#mysql_connect()

sensors = sensor_list_get()
system_settings_get()
sensor_settings_get()
cron_timer_hourly()
cron_timer_daily()
cron_timer_weekly()

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
        #print mysql
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



            
