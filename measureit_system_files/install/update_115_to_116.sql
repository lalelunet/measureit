USE measure_it;
UPDATE measure_system SET measure_system_setting_value = 116 WHERE measure_system_setting_name = "current_version";
ALTER TABLE measure_settings CHANGE measure_timezone_diff measure_timezone_diff FLOAT( 4 ) NOT NULL DEFAULT '0';
CREATE TABLE IF NOT EXISTS measure_notifications (
  measure_notifications_id smallint(10) NOT NULL AUTO_INCREMENT,
  measure_notifications_sensor smallint(3) NOT NULL,
  measure_notifications_name varchar(256) NOT NULL,
  measure_notifications_check_email smallint(1) NOT NULL,
  measure_notifications_check_twitter smallint(1) NOT NULL,
  measure_notifications_notification text NOT NULL,
  measure_notifications_unit varchar(1) NOT NULL,
  measure_notifications_value int(12) NOT NULL,
  measure_notifications_items int(12) NOT NULL,
  measure_notifications_criteria tinyint(1) NOT NULL,
  PRIMARY KEY (measure_notifications_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
INSERT IGNORE INTO measure_data_now (sensor_id, watt, tmpr) VALUES (10, 0, 0), (20, 0, 0), (30, 0, 0);
