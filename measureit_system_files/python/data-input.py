import serial
import re
import MySQLdb
import datetime
import warnings
import time
import threading
import sys
import platform
import smtplib
import urllib2
import logging
import traceback
import os
import subprocess
from twython import Twython

config = {}
sensors = {}
settings = {}
sensor_settings = {}
system_settings = {}
db = {}
err_critical = 0
info = False
debug = False
annouying = False
system = 'envi'
logger = logging.getLogger('MeasureIt')

usbport = '/dev/ttyUSB0'
config_file_name = "/web/measureit/measureit_system_files/measureit.cfg.php"
hdlr = logging.FileHandler('/tmp/measureit.log')
#clear logfile
subprocess.call('echo "" > /tmp/measureit.log', shell=True)

formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)

if 'test' in sys.argv:
	logger.setLevel(logging.INFO)
	info = True
elif 'debug' in sys.argv:
	debug = True
	if 'v' in sys.argv:
		annouying = True
	logger.setLevel(logging.DEBUG)
else:
	logger.setLevel(logging.WARNING)

def sensor_list_get():
	try:
		logger.info('Try to get sensor list in sensor_list_get')
		r = mysql_query('SELECT sensor_id FROM measure_sensors','fetchall')
		for row in r:
			logger.info('Get sensor in sensor_list_get Sensor: '+str(row[0]))
			sensor = int(row[0]) 
			sensors[sensor] = {'tmpr' : 0, 'watt' : 0, 'counter' :0, 'pvoutput_watt_sum' : {}, 'pvoutput_batch_string' : {} }
		logger.info('Get sensor list successful')
		logger.debug(sensors)
		return sensors
	except:
		logger.error('Error in sensor_list_get. Error: '+traceback.format_exc())
		err_critical_count()

def system_settings_get():
	try:
		logger.info('Try to get systems settings in system_settings_get')
		r = mysql_query('SELECT measure_system_setting_name, measure_system_setting_value FROM measure_system','fetchall')
		for row in r:
			system_settings[row[0]] = row[1]
		logger.info('Get system settings successful')
		logger.debug(system_settings)
		return True
	except:
		logger.warning('Error in system_settings_get Error: '+traceback.format_exc())
		err_critical_count()

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
		mysql_query('INSERT INTO measure_watt ( sensor, data, time) values( '+sensor+', '+watt+', UTC_TIMESTAMP( ) ) ')
	except:
		logger.warning('Error in sensor_watt_insert while inserting watt into database Error: '+traceback.format_exc())
		err_critical_count()

def tmpr_insert( tmpr ):
	try:
		now = datetime.datetime.utcnow( )
		mysql_query('INSERT INTO measure_tmpr ( data, time ) values( "'+str(tmpr)+'", UTC_TIMESTAMP( ) ) ')
		mysql_query('INSERT IGNORE INTO measure_tmpr_hourly ( data, time, hour ) VALUES ( "'+str(tmpr)+'", UTC_TIMESTAMP( ), "'+str(now.hour)+'" )')
	
	except:
		logger.warning('Error in tmpr_insert while insert tmpr. Error: '+traceback.format_exc())
		err_critical_count()

def history_update( sensor, hist ):
	try:
		logger.info('Try to update history in history_update from sensor '+str(sensor))
		logger.debug(hist)
		date_hour = date_hour_get(hist[1])
		if hist[0] == 'm':
			query = 'INSERT IGNORE INTO measure_watt_monthly ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "UTC_TIMESTAMP( ) - INTERVAL '+date_hour[0]+'" )'
		#if hist[0] == 'd':
			#query = 'INSERT IGNORE INTO measure_watt_daily_histrory ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[0]+'" )'
		#if hist[0] == 'h':
			#query = 'INSERT IGNORE INTO measure_watt_hourly_histrory ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(hist[2])+'", "'+date_hour[1]+'", "'+date_hour[0]+'" )'
		mysql_query(query)
		logger.info('update history successful from sensor '+str(sensor))
	except:
		logger.warning('Error in history_update. Error: '+traceback.format_exc())
		err_critical_count()

def cron_timer_hourly():
	logger.info('Try to run hourly job in cron_timer_hourly')
	now = datetime.datetime.utcnow( )
	day_from = day_to = str(now.year)+'-'+str(now.month)+'-'+str(now.day)

	logger.debug('Make hourly usage from day_from to day_to: '+str(day_from) )
	hour_from = now.hour-1
	hour_to = now.hour
	epoch = 0
	if now.hour != 0:
		logger.debug('Make hourly usage from hour_from_from to hour_to in utc time: '+str(hour_from)+' - '+str(hour_to) )

	if now.hour == 0:
		logger.debug('Make hourly usage. It is Midnight in utc time: ' )
		hour_from = 23
		n = datetime.datetime.utcnow( ) - datetime.timedelta(days=1)
		day_from = str(n.year)+'-'+str(n.month)+'-'+str(n.day)
		logger.debug('Make hourly usage from day_from to day_to in utc time: '+str(day_from)+' - '+str(day_to) )
		logger.debug('Make hourly usage from hour_from_from to hour_to  in utc time: '+str(hour_from)+' - '+str(hour_to) )
	date_from = str(day_from)+' '+str(hour_from)+':00:00'
	date_to = str(day_to)+' '+str(hour_to)+':00:00'
	pattern = '%Y-%m-%d %H:%M:%S'
	epoch = int(time.mktime(time.strptime(date_from, pattern)))
	try:
		for sensor in sensors:
			usage_sum_hourly = usage_sum_count = sum = delta_time = previous_epoch_time = usage_sum_hourly_epoch = 0
			r = mysql_query("select sensor, data, unix_timestamp(time) from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor),'fetchall')
			for row in r:
				usage_sum_hourly_epoch = int(row[2])
				if usage_sum_count == 0:
					delta_time = usage_sum_hourly_epoch - epoch
				else:
					delta_time = usage_sum_hourly_epoch - previous_epoch_time
				usage_sum_hourly += (float(row[1])*delta_time)/3600000
				usage_sum_count += 1
				previous_epoch_time = usage_sum_hourly_epoch
			query = 'INSERT IGNORE INTO measure_watt_hourly ( sensor, data, hour, time ) VALUES ( "'+str(sensor)+'", "'+str(usage_sum_hourly)+'", "'+str(hour_from)+'", "'+str(day_from)+'" )'
			mysql_query(query)
			if system_settings['use_twitter'] or system_settings.has_key('use_email'):
				sensor_notifications_cron_check(sensor, sensor_settings[sensor]['notifications'])
		usage_sum_hourly = usage_sum_count = sum = r = 0
	except:
		logger.warning('Error in cron_timer_hourly. Error: '+traceback.format_exc())
		err_critical_count()
	
	timer_hourly = threading.Timer(3600.0, cron_timer_hourly)
	timer_hourly.start()
		
	
def cron_timer_daily():
	logger.info('Try to run daily job in cron_timer_daily')
	timer_daily = threading.Timer(86400.0, cron_timer_daily)
	timer_daily.start()
	try:
		logger.info('Try to run about all sensors')
		for sensor in sensors:
			logger.info('Found Sensor '+str(sensor))
			usage_sum_daily = usage_sum_count = sum = 0
			date_from = str(datetime.date.today() - datetime.timedelta(days=1))+' 00:00:00'
			date_to = str(datetime.date.today())+' 00:00:00'
			epoch = 0
			pattern = '%Y-%m-%d %H:%M:%S'
			epoch = int(time.mktime(time.strptime(date_from, pattern)))
			r = mysql_query("select sensor, data, unix_timestamp(time) from measure_watt where time between '"+date_from+"' AND '"+date_to+"' AND sensor="+str(sensor),'fetchall')
			logger.info('Read data from sensor: '+str(sensor))
			try:
				for row in r:
					usage_sum_daily_epoch = int(row[2])
					if usage_sum_count == 0:
						delta_time = usage_sum_daily_epoch - epoch
					else:
						delta_time = usage_sum_daily_epoch - previous_epoch_time
					usage_sum_daily += (float(row[1])*delta_time)/3600000
					usage_sum_count += 1
					previous_epoch_time = usage_sum_daily_epoch
				query = 'INSERT IGNORE INTO measure_watt_daily ( sensor, data, time ) VALUES ( "'+str(sensor)+'", "'+str(usage_sum_daily)+'", "'+date_from+'" )'
				mysql_query(query)
				usage_sum_daily = usage_sum_count = sum = r = 0
				logger.info('Sensor data successful collected from sensor: '+str(sensor))
			except:
				logger.warning('Error in cron_timer_daily. Error: '+traceback.format_exc())
				err_critical_count()
			try: # delete old watt data
				logger.info('Try to delete old data from sensor: '+str(sensor))
				if sensor_settings.has_key(sensor):
					if sensor_settings[sensor]['history'] > 0:
						query = 'DELETE FROM measure_watt WHERE sensor = '+str(sensor)+' AND time < UTC_TIMESTAMP( ) - INTERVAL '+str(sensor_settings[sensor]['history'])+' DAY'
						mysql_query(query)
						query = 'DELETE FROM measure_tmpr WHERE time < UTC_TIMESTAMP( ) - INTERVAL '+str(sensor_settings[sensor]['history'])+' DAY'
						mysql_query(query)
						logger.info('Delete successful from old data from sensor: '+str(sensor))
			except:
				logger.warning('Error in cron_timer_hourly while deleting old data Error: '+traceback.format_exc())
				err_critical_count()
	except:
		logger.warning('Error in cron_timer_daily. Error: '+traceback.format_exc())
		err_critical_count()
		
def cron_timer_weekly():
	logger.info('Try to run weekly job in cron_timer_weekly')
	timer_weekly = threading.Timer(604800.0, cron_timer_weekly)
	timer_weekly.start()
	update_check()
	logger.info('weekly job in cron_timer_weekly successful')

def cron_timer_1minute_restart():
	# delete file that restart the grabber with cron
	timer_1minute_restart = threading.Timer(60.0, cron_timer_1minute_restart)
	timer_1minute_restart.start();

	if annouying:
		logger.info('Look if I should restart')
	try:
		os.remove('/tmp/measureit_grabber_restart')
		logger.info('Found file /tmp/measureit_grabber_restart. Try to kill me in 3 seconds')
		time.sleep(3)
		try:
			killstr = 'kill -9 '+str(os.getpid())
			subprocess.call(killstr, shell=True)
		except:
			logger.warning('Something went wrong while killing me. Error: '+traceback.format_exc())
	except:
		logger.info('No restart file found')
		

def update_check():
	if system_settings.has_key('current_version'):
		nv = int(system_settings['current_version'])+1

		try:
			r = urllib2.urlopen('https://measureit.googlecode.com/files/measureit-'+str(nv)+'.zip')
			mysql_query('INSERT INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) values ( "next_version", "'+str(nv)+'" )')
			logger.info('Update: New version found')
		except:
			logger.info('Update: No new version found')

	else:
		mysql_query('INSERT IGNORE INTO measure_system ( measure_system_setting_name, measure_system_setting_value ) values ( "current_version", 118 )')

def sensor_settings_get():
	try:
		logger.info('Try to get sensor settings from sensor_settings_get')
		r = mysql_query('SELECT * FROM measure_settings','fetchall')
		for row in r:
			sensor_settings[row[2]] = {}
			sensor_settings[row[2]]['history'] = row[0]
			sensor_settings[row[2]]['timezone_diff'] = row[6]
			sensor_settings[row[2]]['type'] = row[7]
			sensor_settings[row[2]]['pvoutput'] = False
			sensor_settings[row[2]]['pvoutput_id'] = int(row[8])
			sensor_settings[row[2]]['pvoutput_api'] = row[9]
			sensor_settings[row[2]]['scale_factor'] = row[10]
			sensor_settings[row[2]]['lower_limit'] = row[11]
			sensor_settings[row[2]]['notifications'] = {}
			sensor_settings[row[2]]['notifications_realtime'] = {}
			logger.info('Sensor '+str(row[2])+' Check if there are any PVOutput settings for this sensor')
			sensor_data_pvoutput_init(row[2])
		logger.info('Get sensor settings successful')
		logger.debug(sensor_settings)
		return True
	except:
		logger.warning('Error in sensor_settings_get Error: '+traceback.format_exc())
		err_critical_count()

def sensor_notifications_get():
	if system_settings.has_key('system_settings_twitter_app_key') and system_settings['system_settings_twitter_app_key'] != '':
		logger.debug('Found system_settings_twitter_app_key in the system settings so twitter will be enabled')
		system_settings['use_twitter'] = True
	else:
		system_settings['use_twitter'] = False
		logger.debug('No twitter settings found. Twitter notifications will not be used')
	
	if system_settings.has_key('system_settings_email_address') and system_settings['system_settings_email_address'] != '' and system_settings.has_key('system_settings_email_pass') and system_settings['system_settings_email_pass'] != '':
		logger.debug('Found system_settings_email_address and system_settings_email_pass in the system settings so email will be enabled')
		system_settings['use_email'] = True
	else:
		system_settings['use_email'] = False
		logger.debug('No email settings found. Email notifications will not be used')
		
	if system_settings['use_twitter'] == False and system_settings['use_email'] == False:
		logger.debug('No notification settings found. Notifications will not be used')
		return True

	try:
		logger.info('Try to get notification settings from sensor_notifications_get')
		r = mysql_query('SELECT * FROM measure_notifications','fetchall')
		cnt = 1
		for row in r:
			unit = 'notifications_realtime' if row[6] == 'n' else 'notifications'
			sensor_settings[row[1]][unit][cnt] = {}
			sensor_settings[row[1]][unit][cnt]['notification_name'] = row[2]
			sensor_settings[row[1]][unit][cnt]['notification_email'] = row[3]
			sensor_settings[row[1]][unit][cnt]['notification_twitter'] = row[4]
			sensor_settings[row[1]][unit][cnt]['notification_notification'] = row[5]
			sensor_settings[row[1]][unit][cnt]['notification_unit'] = row[6]
			sensor_settings[row[1]][unit][cnt]['notification_value'] = row[7]
			sensor_settings[row[1]][unit][cnt]['notification_items'] = row[8]
			sensor_settings[row[1]][unit][cnt]['notification_criteria'] = row[9]
			cnt+=1

		logger.info('Get sensor notifications successful')
		logger.debug(sensor_settings)
		return True
	except:
		logger.warning('Error in sensor_notifications_get Error: '+traceback.format_exc())
		err_critical_count()

def sensor_notifications_check(sensor, data, notifications):
	for notification in notifications:
		notify = False
		if notifications[notification]['notification_criteria'] == 1:
			if annouying:
				logger.debug('Found notification smaller than on sensor' +str(sensor))
				logger.debug(notifications[notification])
			criteria = '<'
			if  sensor_notification_data_compare( data, notifications[notification]['notification_value'] , '<' ):
				if annouying:
					logger.debug('Notification is true on sensor' +str(sensor))
					logger.debug(str(data)+' is smaller than '+str(notifications[notification]['notification_value']))
				notify = True
			else:
				if annouying:
					logger.debug('Notification is false on sensor' +str(sensor))
					logger.debug(str(data)+' is  not bigger than '+str(notifications[notification]['notification_value']))
				
		if notifications[notification]['notification_criteria'] == 2:
			if annouying:
				logger.debug('Found notification bigger than on sensor' +str(sensor))
				logger.debug(notifications[notification])
			criteria = '>'
			if sensor_notification_data_compare( data, notifications[notification]['notification_value'],'>'):
				if annouying:
					logger.debug('Notification is true on sensor' +str(sensor))
					logger.debug(str(data)+' is bigger than '+str(notifications[notification]['notification_value']))
				notify = True
			else:
				if annouying:
					logger.debug('Notification is false on sensor' +str(sensor))
					logger.debug(str(data)+' is  not bigger than '+str(notifications[notification]['notification_value']))
		if notify:
			sensor_notification_send( 'Current usage '+criteria+' '+str(notifications[notification]['notification_value'])+' Watt ('+str(notifications[notification]['notification_value']/1000)+' kwh)'+' Usage: '+str(data)+' Watt ('+str(data/1000)+' kwh) '+notifications[notification]['notification_notification'], notifications[notification]['notification_email'], notifications[notification]['notification_twitter'] )


def sensor_notifications_cron_check(sensor, notifications):
	d = sensor_notifications_data_get( sensor, notifications )
	for notification in notifications:
		notify = False
		sum = 0
		cnt = 1
		for data in d[notifications[notification]['notification_unit']]:
			if cnt <= notifications[notification]['notification_items']:
				sum += d[notifications[notification]['notification_unit']][data]
			cnt += 1
		if notifications[notification]['notification_criteria'] == 1:
			logger.debug('Found notification smaller than on sensor' +str(sensor))
			logger.debug(notifications[notification])
			criteria = '<'
			if  sensor_notification_data_compare( sum*1000, notifications[notification]['notification_value'] , '<' ):
				logger.debug('Notification is true on sensor' +str(sensor))
				logger.debug(str(sum*1000)+' is smaller than '+str(notifications[notification]['notification_value']))
				notify = True
			else:
				logger.debug('Notification is false on sensor' +str(sensor))
				logger.debug(str(sum*1000)+' is  not smaller than '+str(notifications[notification]['notification_value']))
				
		if notifications[notification]['notification_criteria'] == 2:
			criteria = '>'
			if sensor_notification_data_compare( sum*1000, notifications[notification]['notification_value'],'>'):
				logger.debug('Notification is true on sensor' +str(sensor))
				logger.debug(str(sum*1000)+' is bigger than '+str(notifications[notification]['notification_value']))
				notify = True
			else:
				logger.debug('Notification is false on sensor' +str(sensor))
				logger.debug(str(sum*1000)+' is  not bigger than '+str(notifications[notification]['notification_value']))
		if notify:
			sensor_notification_send( 'Usage last '+str(notifications[notification]['notification_items'])+' '+notifications[notification]['notification_unit']+' '+criteria+' '+str(notifications[notification]['notification_value'])+' Watt ('+str(notifications[notification]['notification_value']/1000)+' kwh)'+' Usage: '+str(sum*1000)+' Watt ('+str(sum)+' kwh) '+notifications[notification]['notification_notification'], notifications[notification]['notification_email'], notifications[notification]['notification_twitter'] )

def sensor_notification_send( notification, email, twitter ):
	if email == 1:
		try:
			logger.debug('Try to send notification per email')
			logger.debug(notification)
			mail_send('Notification from your measureit installation', notification)
			logger.debug('Notification sending successfully')
		except:
			logger.warning('Error in sensor_notification_send. Can not send email. Please check your settings! '+traceback.format_exc())

	if twitter == 1:
		twitter = Twython(system_settings['system_settings_twitter_app_key'],system_settings['system_settings_twitter_app_secret'],system_settings['system_settings_twitter_oauth_token'],system_settings['system_settings_twitter_oauth_token_secret'])
		try:
			logger.debug('Try to send notification per twitter')
			tn = (notification[:137] + '...') if len(notification) > 137 else notification
			if annouying:
				logger.debug(tn)
			twitter.update_status(status=tn)
			logger.debug('Send notification per twitter successfully')
		except:
			logger.debug('Can not send notification with twitter'+traceback.format_exc())

	return True


def sensor_notification_data_compare( d1, d2 , type = '>' ):
	if type == '<':
		if d1 < d2:
			return True
	if type == '>':
		if d1 > d2:
			return True
	return False

def sensor_notifications_data_get( sensor, notifications ):
	# group by unit and build query
	sensor_data = {}
	groups = {}
	groups['h'] = groups['d'] = groups['m'] = 0
	for notification in notifications:
		groups[notifications[notification]['notification_unit']] = int(notifications[notification]['notification_items']) if int(notifications[notification]['notification_items']) > groups[notifications[notification]['notification_unit']] else groups[notifications[notification]['notification_unit']]
	
	for unit in groups:
		if groups[unit] > 0:
			sensor_data[unit] = {}
			cnt = 1
			if unit == 'h':
				table = 'measure_watt_hourly'
				order = 'hour_id'
			elif unit == 'd':
				table = 'measure_watt_daily'
				order = 'day_id'
			else:
				table = 'measure_watt_monthly'
				order = 'month_id'
			logger.debug('Try to get notifications from sensor ' +str(sensor))
			logger.debug('SELECT * FROM '+table+' WHERE sensor = '+str(sensor)+' ORDER BY '+order+' desc LIMIT '+str(groups[unit]))
			r = mysql_query('SELECT * FROM '+table+' WHERE sensor = '+str(sensor)+' ORDER BY '+order+' desc LIMIT '+str(groups[unit]),'fetchall')
			for row in r:
				sensor_data[unit][row[0]] = row[2]
	
	return sensor_data

def date_hour_get( hours ):
	try:
		date = mysql_query('SELECT UTC_TIMESTAMP( ) - INTERVAL '+hours+' HOUR','fetchone')
		r = re.search(r"(\d+-\d+-\d+) (\d+):.+", str(date[0]) )
		return (r.group(1), r.group(2))
	except:
		logger.warning('Error in data_hour_get. Error: '+traceback.format_exc())
		err_critical_count()

def sensor_data_check( sensor, watt, tmpr ):
	sensor = int(sensor)
	watt = int(watt)
	tmpr = float(tmpr)
	if sensors and sensors.has_key(sensor):
		if sensors[sensor]['tmpr'] != tmpr:
			sensors[sensor]['tmpr'] = tmpr
			sensor_data_change( 'tmpr', sensor, tmpr )
			if sensor == 0:
				tmpr_insert( tmpr )
		if sensor_settings[sensor]['scale_factor'] != 1:
			watt *= sensor_settings[sensor]['scale_factor']
		if watt < sensor_settings[sensor]['lower_limit']:
			watt = 0
		if ( system_settings.has_key('system_settings_data_save_type') and int(system_settings['system_settings_data_save_type']) == 1 ) or sensors[sensor]['watt'] != watt:
			sensors[sensor]['watt'] = watt
			sensor_data_change( 'watt', sensor, watt )
			sensor_watt_insert( sensor, watt )
			if sensor_settings[sensor]['pvoutput']:
				sensor_data_pvoutput_status( sensor, watt, tmpr )
			if system_settings.has_key('use_twitter') or system_settings.has_key('use_email'):
				sensor_notifications_check( sensor, watt, sensor_settings[sensor]['notifications_realtime'] )
			
		return True

def sensor_data_pvoutput_init( sensor ):
	if sensor_settings[sensor]['pvoutput_id'] > 0:
		logger.info('Sensor '+str(sensor)+' has a PVOutput ID')
		logger.info(sensor_settings[sensor]['pvoutput_id'])
		logger.info('Sensor '+str(sensor)+' Now checking if there is a PVOutput API key')
		
		sensor_settings[sensor]['pvoutput_cnt'] = 0
		sensor_settings[sensor]['pvoutput_batch_str'] = ''
		sensor_settings[sensor]['timezone_diff_value'] = 0
		sensor_settings[sensor]['timezone_diff_prefix'] = False
		
		if sensor_settings[sensor]['pvoutput_api'] == '':
			logger.info('Sensor '+str(sensor)+' has no PVOutput API. Next check if there is a global API key')
			
		elif sensor_settings[sensor]['pvoutput_api'] != '':
			logger.info('Sensor '+str(sensor)+' has PVOutput API.')
			logger.debug(sensor_settings[sensor]['pvoutput_api'])
			
			sensor_settings[sensor]['pvoutput_api'] = sensor_settings[sensor]['pvoutput_api']
			sensor_settings[sensor]['pvoutput'] = True
		
		if system_settings.has_key('system_settings_pvoutput_api') and system_settings['system_settings_pvoutput_api'] != '':
			logger.info('Found PVOutput API key in the system settings.')
			logger.debug(system_settings['system_settings_pvoutput_api'])
			
			sensor_settings[sensor]['pvoutput_api'] = system_settings['system_settings_pvoutput_api']
			sensor_settings[sensor]['pvoutput'] = True
			
		if sensor_settings[sensor]['timezone_diff'] != 0:
			time_offset = sensor_settings[sensor]['timezone_diff']
		elif system_settings.has_key('global_timezone_use') and system_settings['global_timezone_use'] != 0:
			time_offset = system_settings['global_timezone_use']
		else:
			time_offset = 0
		
		r = re.search(r"(-?)(.+)", str(time_offset))
		if r:
			if r.group(1) and r.group(2):
				sensor_settings[sensor]['timezone_diff_prefix'] = r.group(1)
				sensor_settings[sensor]['timezone_diff_value'] = r.group(2)
			if r.group(2):
				sensor_settings[sensor]['timezone_diff_value'] = r.group(2)

		if sensor_settings[sensor]['pvoutput']:
			logger.info('Using PVOutput for this sensor')
			logger.debug(sensor_settings[sensor])
		else:
			logger.info('Sensor '+str(sensor)+' has no PVOutput API key settings. Set PVOutput system id to 0')
	else:
		logger.info('Sensor '+str(sensor)+' has no PVOutput settings. Set PVOutput system id to 0')
		sensor_settings[sensor]['pvoutput_id'] = 0
		logger.debug(sensor_settings[sensor])
		return True

def sensor_data_pvoutput_status( sensor, watt, tmpr ):
	#logger.debug(sensor, watt, tmpr)
	diff = float(sensor_settings[sensor]['timezone_diff_value'])
	if annouying:
		logger.debug('sensor: '+str(sensor))
		logger.debug('current local datetime: '+str(datetime.datetime.now( )))
		logger.debug('current utc datetime: '+str(datetime.datetime.utcnow( )))
	if sensor_settings[sensor]['timezone_diff_prefix']:
		d = datetime.datetime.utcnow( ) - datetime.timedelta(hours=diff)
		if annouying:
			logger.debug('current usage: '+str(datetime.datetime.utcnow( ) - datetime.timedelta(hours=diff)))
	else:
		d = datetime.datetime.utcnow( ) + datetime.timedelta(hours=diff)
		if annouying:
			logger.debug('current usage: '+str(datetime.datetime.utcnow( ) + datetime.timedelta(hours=diff)))

	if annouying:
		logger.debug('time_str: '+str(d.strftime("%Y%m%d %H%M")))

	day = d.strftime("%Y%m%d")
	time = str(d.strftime('%H'))+'%3A'+str(d.strftime('%M'))
	time_str = int(d.strftime("%H%M"))

	if 'time_str' not in sensors[sensor]['pvoutput_watt_sum']:
		if annouying:
			logger.debug('sensor: '+str(sensor)+'time_str not in sensors[sensor][pvoutput_watt_sum]')
			logger.debug('current time_str: '+str(sensors[sensor]['pvoutput_watt_sum']['time_str']))
		sensors[sensor]['pvoutput_watt_sum']['time_str'] = time_str
		if annouying:
			logger.debug('new time_str: '+str(sensors[sensor]['pvoutput_watt_sum']['time_str']))
	if 'watt_sum' not in sensors[sensor]['pvoutput_watt_sum']:
		sensors[sensor]['pvoutput_watt_sum']['watt_sum'] = 0
	if 'day' not in sensors[sensor]['pvoutput_watt_sum']:
		sensors[sensor]['pvoutput_watt_sum']['day'] = day
	if 'time' not in sensors[sensor]['pvoutput_watt_sum']:
		sensors[sensor]['pvoutput_watt_sum']['time'] = time
	
	if annouying:
		logger.debug( 'PVOutput watt sum = '+str(sensors[sensor]['pvoutput_watt_sum']['watt_sum']) )
		logger.debug( 'PVOutput watt sum = '+str(sensors[sensor]['pvoutput_watt_sum']['watt_sum'])+' + '+str(watt)) 
	
	sensors[sensor]['pvoutput_watt_sum']['watt_sum'] += watt
	if annouying:
		logger.debug( 'PVOutput watt sum = '+str(sensors[sensor]['pvoutput_watt_sum']['watt_sum']) )

	sensors[sensor]['pvoutput_watt_sum']['time'] = time
	sensors[sensor]['pvoutput_watt_sum']['day'] = day
	
	# midnight
	if time_str <= 1:
		if annouying:
			logger.debug('sensor: '+str(sensor)+'time_str is <= 0')
		sensors[sensor]['pvoutput_watt_sum']['time_str'] = time_str
		if annouying:
			logger.debug('new time_str: '+str(time_str))
	
	if time_str - sensors[sensor]['pvoutput_watt_sum']['time_str'] < 5:
		if annouying:
			logger.debug('sensor: '+str(sensor)+' time_str is < 5')
			logger.debug('current value: '+str(time_str - sensors[sensor]['pvoutput_watt_sum']['time_str']))
		sensor_settings[sensor]['pvoutput_cnt']+=1
		if annouying:
			logger.debug('new time_str: '+str(sensor_settings[sensor]['pvoutput_cnt']))
	
	elif time_str - sensors[sensor]['pvoutput_watt_sum']['time_str'] >= 5:
		#next 5 minutes block
		sensor_data_pvoutput_status_generate( sensor )
		sensors[sensor]['pvoutput_watt_sum']['time_str'] = time_str
		sensor_settings[sensor]['pvoutput_cnt'] = 1
		sensors[sensor]['pvoutput_watt_sum']['watt_sum'] = watt

def sensor_data_pvoutput_status_generate( sensor ):
	type = 'v4' if sensor_settings[sensor]['type'] == 0 else 'v2'
	if annouying:
		logger.debug('PVOutput watt sum last 5 min is '+str(sensors[sensor]['pvoutput_watt_sum']['watt_sum']))
		logger.debug('PVOutput watt counter last 5 min is '+str(sensor_settings[sensor]['pvoutput_cnt']))
		logger.debug('PVOutput watt average last 5 min is '+str(sensors[sensor]['pvoutput_watt_sum']['watt_sum'] / sensor_settings[sensor]['pvoutput_cnt']))
	sum = str(sensors[sensor]['pvoutput_watt_sum']['watt_sum'] / sensor_settings[sensor]['pvoutput_cnt'])
	
	#convert fahrenheit to celsius
	if system_settings.has_key('system_settings_tmpr'):
		if annouying:
			logger.debug('Temperature setting found '+ str(system_settings['system_settings_tmpr']))
		tmpr = sensors[sensor]['tmpr'] if system_settings['system_settings_tmpr'] == 'c' else ((float(sensors[sensor]['tmpr'])-32)/9)*5
	
	url = 'http://pvoutput.org/service/r2/addstatus.jsp?key='+sensor_settings[sensor]['pvoutput_api']+'&sid='+str(sensor_settings[sensor]['pvoutput_id'])+'&d='+sensors[sensor]['pvoutput_watt_sum']['day']+'&t='+sensors[sensor]['pvoutput_watt_sum']['time']+'&'+type+'='+str(sum)+'&v5='+str(tmpr);

	try:
		r = urllib2.urlopen(url)
		logger.info('Try to update PVOutput from sensor : '+str(sensor)+' Output: '+str(r.read()))
		r = re.search(r"(OK 200)", str(r.read()))
		if r:
			if r.group(1):
				logger.info('Sensor '+str(sensor)+'PVOutput update sucessful from sensor : '+str(sensor)+' Output: '+str(r.read()))
	
	except:
		logger.warning('Sensor '+str(sensor)+'sensor_data_pvoutput_status_generate. Error: '+traceback.format_exc())
		logger.info(url)
		logger.debug(traceback)

	logger.debug(url)
	sensors[sensor]['pvoutput_watt_sum']['day'] = False
	sensors[sensor]['pvoutput_watt_sum']['time'] = False


def mail_send(email_subject, email_body):
	if system_settings.has_key('system_settings_email_address') and system_settings.has_key('system_settings_email_pass'):
		logger.debug('Found email system settings. Try to send email')
		try:
			header  = 'From: '+system_settings['system_settings_email_address']+'\n'
			header += 'To: '+system_settings['system_settings_email_address']+'\n'
			header += 'Subject: %s\n\n' % email_subject
			email_body = header + email_body
			server = smtplib.SMTP("smtp.gmail.com:587")
			server.starttls()
			server.login(system_settings['system_settings_email_address'],system_settings['system_settings_email_pass'])
			server.sendmail(system_settings['system_settings_email_address'],system_settings['system_settings_email_address'], email_body)
			server.quit()
		except:
			logger.debug('Not possible to send email: '+traceback.format_exc())
	else:
		logger.debug('Email system settings are not complete')

def err_critical_count():
	global err_critical
	err_critical += 1
	if debug:
		logger.debug('Critical system error occured')
		logger.debug('Alert Message will send at 5000. Current counter is now: '+str(err_critical))
	if err_critical > 5000:
		try:
			mail_send('Message from your measureit installation', 'Please take a look at your installation. It seems there is a problem...')
			err_critical = 0
		except:
			logger.warning('Error in err_critical_count Can not send email. Please check your settings!')

def config_parse():
	try:
		logger.info('Try to parse config file in config_parse')
		config_file = open(config_file_name, 'r')

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
					system_settings[r.group(1).rstrip()] = r.group(2).rstrip()
		logger.info('Parsing config file successful')
		logger.debug(config)
		return True
	except:
		logger.error('Error in config_parse. '+config_file_name+' could not be opened or read. Please check if file exists and that that the permissions are ok Error: '+traceback.format_exc())
		err_critical_count()

def mysql_query(query, type = False):
	if query:
		try:
			mysql = MySQLdb.connect(host=config['database_host'],port=int(config['database_port']),user=config['database_user'],passwd=config['database_passwd'],db=config['database_name'])
			db = mysql.cursor()
			try:
				if annouying:
					logger.debug('Try to execute query: '+query)
				db.execute(query)
				if type:
					if type == 'fetchone':
						return db.fetchone()
					if type == 'fetchall':
						return db.fetchall()
				db.close()
				if annouying:
					logger.debug('Execute query successful')
			except:
				logger.error('Can not execute query. Error: '+traceback.format_exc())
				err_critical_count()
			
		except:
			logger.error('Can not connect to database. Is the database on and are the database settings ok? Error: '+traceback.format_exc())
			err_critical_count()
	
	return True

warnings.filterwarnings("ignore")

try:
	logger.info('Try to connect to the serial device '+usbport)
	ser = serial.Serial(port=usbport, baudrate=57600, bytesize=serial.EIGHTBITS, parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=3)
	logger.info('Connected to the serial device '+usbport)
except:
	logger.error('Can not connect to /dev/ttyUSB0. I try now /dev/ttyUSB1')
	usbport = '/dev/ttyUSB1'
	try:
		ser = serial.Serial(port=usbport, baudrate=57600, bytesize=serial.EIGHTBITS, parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=3)
		logger.info('Connected to the serial device '+usbport)
	
	except:
		logger.error('Can not connect to the serial device. Please check the cable is plugged in and if the device has the correctly drivers installed Error: '+traceback.format_exc())
		err_critical_count()

try:
	config_parse()
	sensor_list_get()
	system_settings_get()
	sensor_settings_get()
	sensor_notifications_get()
	cron_timer_hourly()
	cron_timer_daily()
	cron_timer_weekly()
	cron_timer_1minute_restart()
	logger.info('Start parsing XML')
	
	if system_settings.has_key('system_settings_system') and system_settings['system_settings_system'] == 'classic':
		system = 'classic'
		logger.debug('System is a Classic device from CC so searching for XML in classic format')
	
	while True:
		try:
			line = ser.readline()
			line = line.rstrip('\r\n')
			clamps = False
		
			if info or debug:
				print(line)
			# parsing from history_output 
			# data will not be used because of the data is buggy and not detailed enough :)
			# but saving them is not an error. maybe we can use the data later
			r = re.search(r"<hist>", line)
			if r:
				for s in sensors:
					r = re.search(r"<data><sensor>"+str(s)+"</sensor>(.+?)</data>", line)
					if r:
						d = re.findall(r"<(m)(\d+)>(.+?)</.+?>", r.group(1) )
						if d:
						 for f in d:
							 history_update(s,f)
			
			# arghhhhh xml is changing when fahrenheit is used instead of celsius.
			# who is doing something like this???
			if system_settings.has_key('system_settings_tmpr'):
				tmpr_node = 'tmpr' if system_settings['system_settings_tmpr'] == 'c' else 'tmprF'
			else:
				tmpr_node = 'tmpr'
			
			if system == 'classic':
				r = re.search(r"<ch1><watts>(\d+)<\/watts><\/ch1><ch2><watts>(\d+)<\/watts><\/ch2><ch3><watts>(\d+)<\/watts><\/ch3><tmpr>(.+?)</tmpr>", line)
				if r:
					tmpr = r.group(4)
					s = 1
					clamp1 = r.group(1)
					clamp2 = r.group(2) if int(r.group(2)) > 0 else False
					clamp3 = r.group(3) if int(r.group(3)) > 0 else False
			else:
				r = re.search(r"<"+tmpr_node+">(.+?)</"+tmpr_node+"><sensor>(\d)+</sensor>.+<ch1><watts>(\d+)<\/watts><\/ch1>(<ch2><watts>(\d+)<\/watts><\/ch2>)?(<ch3><watts>(\d+)<\/watts><\/ch3>)?", line)
				if r:
					tmpr = r.group(1)
					s = r.group(2)
					clamp1 = r.group(3)
					clamp2 = r.group(5) if r.group(5) else False
					clamp3 = r.group(7) if r.group(7) else False
					
			if r:
				watt_sum = int(clamp1)
				# more than 1 clamp
				if clamp2:
					if annouying:
						logger.debug('Found clamp 2 on sensor '+str(s))
					sensor = int('2'+str(s))
					if sensors and sensors.has_key(sensor):
						if annouying:
							logger.debug('Clamp 2 is in the sensor list')
						watt = int(clamp2)
						watt_sum += watt
						sensor_data_check( sensor, watt, tmpr )
						clamps = True
					else:
						if annouying:
							logger.debug('Clamp 2 is NOT in the sensor list')
		
				if clamp3:
					if annouying:
						logger.info('Found clamp 3 on sensor '+str(s))
					sensor = int('3'+str(s))
					if sensors and sensors.has_key(sensor):
						if annouying:
							logger.debug('Clamp 3 is in the sensor list')
						watt = int(clamp3)
						watt_sum += watt
						sensor_data_check( sensor, watt, tmpr )
						clamps = True
					else:
						if annouying:
							logger.debug('Clamp 3 is NOT in the sensor list')
					
				if clamps:
					if annouying:
						logger.debug('Clamps found on sensor '+str(s)+'. Add data to clamps')
					sensor = int('1'+str(s))
					watt = int(clamp1)
					sensor_data_check( sensor, watt, tmpr )
				else:
					if annouying:
						logger.debug('No clamps found on sensor '+str(s))
				   
				sensor_data_check( s, watt_sum, tmpr )
		except:
			logger.error('Can not connect to the serial device: '+traceback.format_exc())
			err_critical_count()



except (KeyboardInterrupt, SystemExit):
	r = re.search(r"Linux", platform.system())
	if r:
		killstr = 'kill -9 '+str(os.getpid())
		subprocess.call(killstr, shell=True)
	if platform.system() == '':
		print('On Windows you can close the CMD window')
		print('I can not recognize which OS you are using. Try a google search how to kill a python script + your OS')
