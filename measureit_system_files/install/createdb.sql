CREATE DATABASE measure_it;
USE measure_it;

CREATE USER 'measureit'@'localhost' IDENTIFIED BY 'measureitpasswd';

GRANT SELECT , INSERT , UPDATE , DELETE, LOCK TABLES ON * . * TO 'measureit'@'localhost' IDENTIFIED BY 'measureitpasswd';

CREATE TABLE IF NOT EXISTS `measure_costs` (
  `costs_id` int(11) NOT NULL AUTO_INCREMENT,
  `costs_sensor` smallint(3) NOT NULL DEFAULT '100',
  `costs_from` tinyint(2) NOT NULL,
  `costs_to` tinyint(2) NOT NULL,
  `costs_price` float NOT NULL,
  `costs_since` date NOT NULL,
  PRIMARY KEY (`costs_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_data_now` (
  `sensor_id` tinyint(2) NOT NULL,
  `watt` smallint(5) NOT NULL,
  `tmpr` float NOT NULL,
  PRIMARY KEY (`sensor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_positions` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `position_time` datetime NOT NULL,
  `position_description` varchar(128) NOT NULL,
  `position_sensor` tinyint(3) NOT NULL,
  PRIMARY KEY (`position_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_sensors` (
  `sensor_id` smallint(5) NOT NULL,
  `sensor_title` varchar(100) NOT NULL,
  `sensor_clamp` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sensor_id`),
  UNIQUE KEY `sensor_id` (`sensor_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_settings` (
  `measure_history` smallint(6) NOT NULL DEFAULT '365',
  `measure_currency` varchar(5) NOT NULL DEFAULT 'Pound',
  `measure_sensor` tinyint(5) NOT NULL,
  `measure_range` varchar(5) NOT NULL,
  `measure_timeframe` smallint(4) NOT NULL,
  `measure_timezone` varchar(128) NOT NULL DEFAULT 'GMT0',
  `measure_timezone_diff` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_system` (
  `measure_system_setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `measure_system_setting_name` varchar(256) NOT NULL,
  `measure_system_setting_value` varchar(256) NOT NULL,
  PRIMARY KEY (`measure_system_setting_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_tmpr` (
  `tmpr_id` int(128) NOT NULL AUTO_INCREMENT,
  `data` float NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`tmpr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_tmpr_hourly` (
  `hour_id` int(11) NOT NULL AUTO_INCREMENT,
  `data` float NOT NULL,
  `hour` tinyint(2) NOT NULL,
  `time` date NOT NULL,
  PRIMARY KEY (`hour_id`),
  UNIQUE KEY `data` (`hour`,`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_watt` (
  `watt_id` int(128) NOT NULL AUTO_INCREMENT,
  `sensor` tinyint(3) NOT NULL,
  `data` smallint(5) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`watt_id`),
  KEY `time_sensor` (`time`,`sensor`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_watt_daily` (
  `day_id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` tinyint(1) NOT NULL,
  `data` float NOT NULL,
  `time` date NOT NULL,
  PRIMARY KEY (`day_id`),
  UNIQUE KEY `data` (`sensor`,`time`),
  KEY `time_sensor` (`time`,`sensor`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_watt_hourly` (
  `hour_id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` tinyint(1) NOT NULL,
  `data` float NOT NULL,
  `hour` tinyint(2) NOT NULL,
  `time` date NOT NULL,
  PRIMARY KEY (`hour_id`),
  UNIQUE KEY `data` (`sensor`,`hour`,`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_watt_monthly` (
  `month_id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` tinyint(1) NOT NULL,
  `data` float NOT NULL,
  `time` date NOT NULL,
  PRIMARY KEY (`month_id`),
  UNIQUE KEY `data` (`sensor`,`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

INSERT INTO measure_data_now (sensor_id, watt, tmpr) VALUES
(0, 0, 0),
(1, 0, 0),
(2, 0, 0),
(3, 0, 0),
(4, 0, 0),
(5, 0, 0),
(6, 0, 0),
(7, 0, 0),
(8, 0, 0),
(9, 0, 0),
(11, 0, 0),
(21, 0, 0),
(31, 0, 0),
(12, 0, 0),
(22, 0, 0),
(32, 0, 0),
(13, 0, 0),
(23, 0, 0),
(33, 0, 0),
(14, 0, 0),
(24, 0, 0),
(34, 0, 0),
(15, 0, 0),
(25, 0, 0),
(35, 0, 0),
(16, 0, 0),
(26, 0, 0),
(36, 0, 0),
(17, 0, 0),
(27, 0, 0),
(37, 0, 0),
(18, 0, 0),
(28, 0, 0),
(38, 0, 0),
(19, 0, 0),
(29, 0, 0),
(39, 0, 0);

INSERT INTO measure_it.measure_positions (position_time, position_description, position_sensor) VALUES (now(), 'start position', '0');
INSERT INTO measure_it.measure_sensors (sensor_id, sensor_title) VALUES ('0', 'Sensor 0');
INSERT INTO measure_it.measure_settings (measure_history, measure_currency, measure_sensor) VALUES ('365', 'Euro', '0');