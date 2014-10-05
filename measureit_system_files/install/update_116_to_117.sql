USE measure_it;
UPDATE measure_system SET measure_system_setting_value = 117 WHERE measure_system_setting_name = "current_version";
ALTER TABLE `measure_settings` ADD `measure_scale_factor` decimal(10,2) NOT NULL DEFAULT '1.00';
ALTER TABLE `measure_settings` ADD `measure_lower_limit` smallint(5) NOT NULL;
ALTER TABLE `measure_settings` ADD `measure_upper_limit` smallint(5) NOT NULL;
