CREATE DATABASE `measure_it`;
USE `measure_it`;

CREATE USER 'measureit'@'localhost' IDENTIFIED BY 'measureitpasswd';

GRANT SELECT , INSERT , UPDATE , DELETE, LOCK TABLES ON * . * TO 'measureit'@'localhost' IDENTIFIED BY 'measureitpasswd';

CREATE TABLE IF NOT EXISTS `measure_positions` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `position_time` datetime NOT NULL,
  `position_description` varchar(128) NOT NULL,
  `position_sensor` tinyint(1) NOT NULL,
  PRIMARY KEY (`position_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `measure_sensors` (
  `sensor_id` smallint(5) NOT NULL,
  `sensor_title` varchar(100) NOT NULL,
  PRIMARY KEY (`sensor_id`),
  UNIQUE KEY `sensor_id` (`sensor_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `measure_settings` (
  `measure_history` smallint(6) NOT NULL,
  `measure_currency` varchar(5) NOT NULL,
  `measure_sensor` tinyint(1) NOT NULL,
  `measure_price` float NOT NULL,
  `measure_range` varchar(5) NOT NULL,
  `measure_timezone_diff` SMALLINT( 4 ) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


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
  `sensor` tinyint(1) NOT NULL,
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
  UNIQUE KEY `data` (`sensor`,`time`)
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `measure_data_now` (
  `sensor_id` tinyint(2) NOT NULL,
  `watt` smallint(5) NOT NULL,
  `tmpr` float NOT NULL,
  PRIMARY KEY (`sensor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


INSERT INTO `measure_data_now` (`sensor_id`, `watt`, `tmpr`) VALUES
(1, 0, 0),
(2, 0, 0),
(3, 0, 0),
(4, 0, 0),
(5, 0, 0),
(6, 0, 0),
(7, 0, 0),
(8, 0, 0),
(9, 0, 0);

INSERT INTO `measure_it`.`measure_positions` (`position_time`, `position_description`, `position_sensor`) VALUES (now(), 'start position', '1');
INSERT INTO `measure_it`.`measure_sensors` (`sensor_id`, `sensor_title`) VALUES ('1', 'Sensor 1');
INSERT INTO `measure_it`.`measure_settings` (`measure_history`, `measure_currency`, `measure_sensor`, `measure_price`) VALUES ('365', 'Euro', '1', '0.0022');