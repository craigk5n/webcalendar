CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(25),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75),
  PRIMARY KEY ( cal_login )
);

INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', 'admin', 'Administrator', 'Default', 'Y' );


CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT,
  cal_create_by VARCHAR(25) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT,
  cal_mod_date INT,
  cal_mod_time INT,
  cal_duration INT NOT NULL,
  cal_priority INT DEFAULT 2,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_description TEXT,
  PRIMARY KEY ( cal_id )
);


CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT '0' NOT NULL,
   cal_type VARCHAR(20),
   cal_end INT,
   cal_frequency INT DEFAULT '1',
   cal_days CHAR(7),
   PRIMARY KEY (cal_id)
);


CREATE TABLE webcal_entry_user (
  cal_id int DEFAULT '0' NOT NULL,
  cal_login varchar(25) DEFAULT '' NOT NULL, 
  cal_status char(1) DEFAULT 'A' NOT NULL,
  PRIMARY KEY ( cal_id,cal_login )
);



CREATE TABLE webcal_user_pref (
  cal_login varchar(25) NOT NULL,
  cal_setting varchar(25) NOT NULL,
  cal_value varchar(50),
  PRIMARY KEY ( cal_login, cal_setting )
);



CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT '0' NOT NULL,
  cal_login varchar(25) NOT NULL,
  cal_layeruser varchar(25) NOT NULL,
  cal_color varchar(25),
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);



CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT '0',
  cal_remind INT DEFAULT '0',
  cal_data TEXT,
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);


CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_event_date INT NOT NULL DEFAULT 0,
  cal_last_sent INT NOT NULL DEFAULT 0,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);

