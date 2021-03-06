CREATE TABLE IF NOT EXISTS b_oauth_client
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID VARCHAR(45) NOT NULL,
	CLIENT_SECRET VARCHAR(45) NOT NULL,
	CLIENT_TYPE CHAR(1) NULL DEFAULT 'A',
	CLIENT_OWNER_ID INT NULL,
	SCOPE VARCHAR(1000) NOT NULL,
	TITLE VARCHAR(255) NULL,
	REDIRECT_URI VARCHAR(555) NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX IX_B_OAUTH_CLIENT1(CLIENT_ID),
	INDEX IX_B_OAUTH_CLIENT2(TITLE)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_version
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	VERSION INT NULL,
	SCOPE VARCHAR(1000) NULL,
	ACTIVE CHAR(1) NULL DEFAULT 'Y',
	PRIMARY KEY (ID),
	UNIQUE INDEX UX_B_OAUTH_CLIENT_VERSION1(CLIENT_ID, VERSION)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_version_uri
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	VERSION_ID INT NOT NULL,
	REDIRECT_URI VARCHAR(555) NOT NULL,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_CLIENT_VERSION_URI1(CLIENT_ID, VERSION_ID, REDIRECT_URI)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_version_install
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	VERSION_ID INT NOT NULL,
	INSTALL_CLIENT_ID INT NOT NULL,
	CREATED DATETIME NULL,
	CHANGED DATETIME NULL,
	ACTIVE CHAR(1) NULL DEFAULT 'Y',
	STATUS CHAR(1) NULL DEFAULT 'F',
	DATE_FINISH DATE NULL,
	IS_TRIALED CHAR(1) NULL DEFAULT 'N',
	PRIMARY KEY (ID),
	UNIQUE INDEX UX_B_OAUTH_CLIENT_VERSION_INSTALL1(VERSION_ID,INSTALL_CLIENT_ID),
	INDEX IX_B_OAUTH_CLIENT_VERSION_INSTALL2(CLIENT_ID),
	INDEX IX_CLIENT_INSTALL_CLIENT (INSTALL_CLIENT_ID, CLIENT_ID)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_profile
(
	ID INT NOT NULL AUTO_INCREMENT,
	USER_ID INT NOT NULL,
	CLIENT_ID INT NOT NULL,
	CLIENT_PROFILE_ID INT NOT NULL,
	CLIENT_PROFILE_ACTIVE CHAR(1) NULL DEFAULT 'Y',
	CLIENT_PROFILE_STATUS CHAR(1) NULL DEFAULT 'U',
	CONFIRM_CODE VARCHAR(50) NULL,
	ACCEPTED CHAR(1) NULL DEFAULT 'N',
	LAST_AUTHORIZE DATETIME NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX IX_B_OAUTH_CLIENT_PROFILE1(USER_ID, CLIENT_ID)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_scope
(
	ID INT NOT NULL AUTO_INCREMENT,
	USER_ID INT NOT NULL,
	CLIENT_ID INT NOT NULL,
	CLIENT_SCOPE VARCHAR(255),
	LAST_AUTHORIZE DATETIME NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX IX_B_OAUTH_CLIENT_SCOPE1(USER_ID, CLIENT_ID),
	INDEX IX_B_OAUTH_CLIENT_SCOPE2(CLIENT_SCOPE)
);

CREATE TABLE IF NOT EXISTS b_oauth_client_feature
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	FEATURE VARCHAR(50) NOT NULL,
	ACTIVE CHAR(1) NULL DEFAULT 'Y',
	PRIMARY KEY (ID),
	UNIQUE INDEX UX_B_OAUTH_CLIENT_FEATURE1(CLIENT_ID, FEATURE)
);

CREATE TABLE IF NOT EXISTS b_oauth_code (
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	CODE VARCHAR(100) NOT NULL,
	EXPIRES INT(11) NOT NULL,
	USED CHAR(1) NOT NULL DEFAULT 'N',
	USER_ID INT NOT NULL DEFAULT 0,
	PARAMETERS VARCHAR(2000) NULL,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_CODE_CODE (CODE),
	INDEX IX_B_OAUTH_CODE_CLIENT_ID (CLIENT_ID),
	INDEX IX_B_OAUTH_CODE_EXPIRES (EXPIRES)
);

CREATE TABLE IF NOT EXISTS b_oauth_token
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	OAUTH_TOKEN VARCHAR(100) NOT NULL,
	USER_ID INT NOT NULL DEFAULT 0,
	EXPIRES INT(11) NOT NULL,
	SCOPE VARCHAR(1000) NULL,
	PARAMETERS VARCHAR(2000) NULL,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_TOKEN_OAUTH_TOKEN (OAUTH_TOKEN),
	INDEX IX_B_OAUTH_TOKEN_CLIENT_ID (CLIENT_ID),
	INDEX IX_B_OAUTH_TOKEN_EXPIRES (EXPIRES)
);

CREATE TABLE IF NOT EXISTS b_oauth_refresh_token
(
	ID INT NOT NULL AUTO_INCREMENT,
	CLIENT_ID INT NOT NULL,
	REFRESH_TOKEN VARCHAR(100) NOT NULL,
	EXPIRES INT(11) NOT NULL,
	USER_ID INT NOT NULL DEFAULT 0,
	OAUTH_TOKEN_ID INT NOT NULL DEFAULT 0,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_REFRESH_TOKEN_REFRESH_TOKEN (REFRESH_TOKEN),
	INDEX IX_B_OAUTH_REFRESH_TOKEN_CLIENT_ID (CLIENT_ID),
	INDEX IX_B_OAUTH_REFRESH_TOKEN_EXPIRES (EXPIRES)
);

CREATE TABLE IF NOT EXISTS b_oauth_user_secret
(
	ID INT NOT NULL AUTO_INCREMENT,
	USER_ID INT NOT NULL,
	SECRET VARCHAR(20) NOT NULL,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_USER_SECRET1 (USER_ID),
	INDEX IX_B_OAUTH_USER_SECRET2 (SECRET)
);

CREATE TABLE IF NOT EXISTS b_oauth_log
(
	ID INT(11) NOT NULL AUTO_INCREMENT,
	TIMESTAMP_X TIMESTAMP NOT NULL,
	CLIENT_ID INT(11) NULL,
	INSTALL_CLIENT_ID INT(11) NULL,
	MESSAGE VARCHAR(255) NOT NULL,
	DETAIL TEXT NULL,
	ERROR VARCHAR(255) NULL,
	RESULT VARCHAR(255) NULL,
	PRIMARY KEY (ID),
	INDEX IX_B_OAUTH_LOG1 (CLIENT_ID,MESSAGE)
);
