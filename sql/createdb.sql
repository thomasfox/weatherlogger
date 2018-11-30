CREATE TABLE `pressure` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `pressure` smallint(4) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `rain` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `rate` smallint(5) unsigned DEFAULT NULL,
 `yearly` smallint(5) unsigned DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `temperature` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `temperature` smallint(6) DEFAULT NULL,
 `humidity` smallint(6) DEFAULT NULL,
 `water_temperature` smallint(6) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `wind` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `direction` smallint(6) DEFAULT NULL,
 `speed` smallint(6) DEFAULT NULL,
 `gusts` smallint(6) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `time` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `second` tinyint DEFAULT NULL,
 `minute` tinyint DEFAULT NULL,
 `hour` tinyint DEFAULT NULL,
 `day` tinyint DEFAULT NULL,
 `month` tinyint DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;


create index idx_wind_speed on wind(speed);
create index idx_wind_direction on wind (direction);
create index idx_wind_time on wind (`time`);