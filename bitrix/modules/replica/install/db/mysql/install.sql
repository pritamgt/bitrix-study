CREATE TABLE if not exists b_replica_map
(
	TABLE_NAME varchar(50) not null,
	ID_VALUE varchar(50) not null,
	NODE_TO varchar(50) not null,
	GUID varchar(150) not null,
	INDEX ix_b_replica_map_guid(GUID),
	PRIMARY KEY pk_b_replica_map(TABLE_NAME, ID_VALUE, NODE_TO, GUID)
);

CREATE TABLE if not exists b_replica_log
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp not null,
	EVENT longtext not null,
	PRIMARY KEY pk_b_replica_log(ID)
);

CREATE TABLE if not exists b_replica_file_dl
(
	ID int(11) not null auto_increment,
	FILE_ID int(11) not null,
	FILE_SIZE int(11) not null,
	FILE_SRC varchar(500) not null,
	FILE_UPDATE varchar(500),
	FILE_POS int(11) not null,
	PART_SIZE int(11) not null,
	STATUS char(1) not null default 'Y',
	ERROR_MESSAGE varchar(500),
	PRIMARY KEY pk_b_replica_file_dl(ID)
);
