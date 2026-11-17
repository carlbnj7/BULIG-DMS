-- BULIG-DMS (BULIG Data and Monitoring System) — database tables. Import this first.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS municipalities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS districts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  municipality_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY one_name (municipality_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_code VARCHAR(12) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  district_id INT UNSIGNED NULL,
  head_name VARCHAR(120) NOT NULL DEFAULT '',
  head_position VARCHAR(60) NOT NULL DEFAULT '',
  KEY (district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- role: division (whole division), municipal, district, school (head or reading coordinator), teacher.
-- scope_id: municipality id, district id or school id, depending on the role.
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('division','municipal','district','school','teacher') NOT NULL,
  scope_id INT UNSIGNED NULL,
  user_code VARCHAR(20) NULL UNIQUE,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80) NOT NULL DEFAULT '',
  middle_name VARCHAR(80) NOT NULL DEFAULT '',
  last_name VARCHAR(80) NOT NULL DEFAULT '',
  sex ENUM('M','F','') NOT NULL DEFAULT '',
  position VARCHAR(60) NOT NULL DEFAULT '',
  email VARCHAR(150) NOT NULL DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  must_change TINYINT(1) NOT NULL DEFAULT 1,
  setup_done TINYINT(1) NOT NULL DEFAULT 0,
  merged_into INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  KEY (role, scope_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS classes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  school_id INT UNSIGNED NOT NULL,
  school_year VARCHAR(9) NOT NULL,
  grade TINYINT NOT NULL,
  section VARCHAR(80) NOT NULL,
  tool ENUM('CRLA','PHIL-IRI') NOT NULL DEFAULT 'CRLA',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_class (teacher_id, school_year, grade, section),
  KEY (school_id, school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS learners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lrn VARCHAR(12) NULL UNIQUE,
  last_name VARCHAR(80) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80) NOT NULL DEFAULT '',
  sex ENUM('M','F') NOT NULL,
  birthdate DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY name_bd (last_name, first_name, birthdate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A learner in a class, with the BULIG level from the pre-assessment and the reading profiles.
CREATE TABLE IF NOT EXISTS enrolments (
  class_id INT UNSIGNED NOT NULL,
  learner_id INT UNSIGNED NOT NULL,
  pre_level VARCHAR(8) NULL,
  key_stage TINYINT NULL,
  bosy VARCHAR(20) NULL, mosy VARCHAR(20) NULL, eosy VARCHAR(20) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (class_id, learner_id),
  KEY (learner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Result of each assessment period (AP 1 to 15): PASSED moves the learner up one level.
CREATE TABLE IF NOT EXISTS assessments (
  class_id INT UNSIGNED NOT NULL,
  learner_id INT UNSIGNED NOT NULL,
  ap TINYINT NOT NULL,
  remark ENUM('PASSED','NOT READY','-') NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (class_id, learner_id, ap)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ap_terms (
  class_id INT UNSIGNED NOT NULL,
  ap TINYINT NOT NULL,
  term ENUM('1ST','2ND','3RD','4TH','SUMMER') NOT NULL,
  PRIMARY KEY (class_id, ap)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_tries (
  ip VARCHAR(45) NOT NULL,
  tried_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (ip, tried_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- First Division admin. Sign in with DIV0001 (or username admin) and password ChangeMe123!
-- The first sign-in asks for a new password and your own username.
INSERT IGNORE INTO users (role, user_code, username, password_hash, first_name, last_name, position, must_change)
VALUES ('division', 'DIV0001', 'admin', '$2y$10$5ibaweDNw992pdkNA.J5IOGslbLCpwi6k08hUvbr6D0pcFgJvOTI.', 'Division', 'Administrator', 'Division Admin', 1);
INSERT IGNORE INTO settings (k, v) VALUES ('division_name', 'Division of Bukidnon'), ('region_name', 'Region X - Northern Mindanao'), ('school_year', '2026-2027');
