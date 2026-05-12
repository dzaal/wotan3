-- Wotan3 Framework — Database Schema
-- Run this on a fresh database: mysql -u user -p your_db < install/schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- Users & access control
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(64)  NOT NULL DEFAULT '',
  `email`         VARCHAR(128) NOT NULL DEFAULT '',
  `password`      VARCHAR(64)  NOT NULL DEFAULT '*',
  `hpassword`     VARCHAR(255) NOT NULL DEFAULT '',
  `reset_token`   VARCHAR(64)  DEFAULT NULL,
  `reset_expires` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `usergroup`     VARCHAR(64)  NOT NULL DEFAULT 'members',
  `admin`         TEXT,
  `access`        TEXT,
  `deny`          TEXT,
  `home`          VARCHAR(255) DEFAULT NULL,
  `settings`      VARCHAR(64)  DEFAULT NULL,
  `config`        VARCHAR(64)  DEFAULT NULL,
  `style`         VARCHAR(64)  DEFAULT NULL,
  `host`          VARCHAR(128) DEFAULT NULL,
  `acc_ip`        TINYINT(1)   NOT NULL DEFAULT '0',
  `guest_ip`      VARCHAR(45)  DEFAULT NULL,
  `name`          VARCHAR(128) DEFAULT NULL,
  `city`          VARCHAR(64)  DEFAULT NULL,
  `country`       VARCHAR(64)  DEFAULT NULL,
  `online`        TINYINT(1)   NOT NULL DEFAULT '1',
  `phpsessid`     VARCHAR(64)  DEFAULT NULL,
  `timestamp`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: guest user (required by chkusr3 access control)
INSERT IGNORE INTO `users` (`id`,`username`,`usergroup`,`online`) VALUES (1,'guest','',1);

-- Admin user is created interactively by install/install.sh

-- --------------------------------------------------------
-- Brute-force login protection
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ip`  VARCHAR(45)  NOT NULL,
  `ts`  INT UNSIGNED NOT NULL,
  INDEX `idx_ip_ts` (`ip`, `ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- System log
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `systemlog` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id`   INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `guest_ip`   VARCHAR(45)  DEFAULT NULL,
  `properties` VARCHAR(128) DEFAULT NULL,
  `msg`        VARCHAR(255) DEFAULT NULL,
  `ses`        TEXT,
  `_post`      TEXT,
  `post`       TEXT,
  `_get`       TEXT,
  `get`        TEXT,
  `timestamp`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_users_id` (`users_id`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Users session log
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users_log` (
  `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id`  INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `guest_ip`  VARCHAR(45)  DEFAULT NULL,
  `info`      VARCHAR(64)  DEFAULT NULL,
  `error`     TINYINT(1)   NOT NULL DEFAULT '0',
  `errors`    TEXT,
  `debug`     TEXT,
  `runttime`  FLOAT        DEFAULT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- UID click log (used by chkusr3)
CREATE TABLE IF NOT EXISTS `uidlog` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`         INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `phpsessid`   VARCHAR(64)  DEFAULT NULL,
  `guest_ip`    VARCHAR(45)  DEFAULT NULL,
  `guest_fwd`   VARCHAR(45)  DEFAULT NULL,
  `guest_dns`   VARCHAR(128) DEFAULT NULL,
  `timestamp`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- CMS — webpages / navigation
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `webpages` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id`         INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `category`         VARCHAR(128) DEFAULT NULL,
  `subcat`           VARCHAR(128) DEFAULT NULL,
  `name_english`     VARCHAR(255) DEFAULT NULL,
  `name_dutch`       VARCHAR(255) DEFAULT NULL,
  `seotitle_english` VARCHAR(255) DEFAULT NULL,
  `seotitle_dutch`   VARCHAR(255) DEFAULT NULL,
  `description_english` TEXT,
  `description_dutch`   TEXT,
  `template`         VARCHAR(128) DEFAULT NULL,
  `sort`             SMALLINT(5)  NOT NULL DEFAULT '0',
  `online`           TINYINT(1)   NOT NULL DEFAULT '1',
  `timestamp`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_online` (`online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Translations / i18n
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `translations` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_org`    VARCHAR(255) NOT NULL DEFAULT '',
  `dutch`       VARCHAR(255) DEFAULT NULL,
  `english`     VARCHAR(255) DEFAULT NULL,
  `online`      TINYINT(1)   NOT NULL DEFAULT '1',
  `timestamp`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name_org` (`name_org`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Countries (used by language detection)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `countrys` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `countryname_en`    VARCHAR(64) DEFAULT NULL,
  `countryname_nl`    VARCHAR(64) DEFAULT NULL,
  `languagename_en`   VARCHAR(32) DEFAULT NULL,
  `locale`            VARCHAR(32) DEFAULT NULL,
  `selected`          TINYINT(1)  NOT NULL DEFAULT '0',
  `online`            TINYINT(1)  NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `countrys` (`countryname_en`,`countryname_nl`,`languagename_en`,`locale`,`selected`) VALUES
('Netherlands','Nederland','dutch','nl_NL.utf8',1),
('United Kingdom','Engeland','english','en_GB.utf8',1);
