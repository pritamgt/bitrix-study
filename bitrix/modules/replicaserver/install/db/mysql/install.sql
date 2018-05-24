CREATE TABLE if not exists b_replica_log_from
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp not null,
	CMD_STATUS char(1) not null default 'N',
	SIGNATURE varchar(100),
	NODE_FROM varchar(100),
	EVENT longtext not null,
	PRIMARY KEY pk_b_replica_log_from(ID),
	INDEX ix_b_replica_queue_log_1 (CMD_STATUS, ID, NODE_FROM)
);

CREATE TABLE if not exists b_replica_log_to
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp not null,
	CMD_STATUS char(1) not null default 'N',
	SIGNATURE varchar(100),
	NODE_FROM varchar(100),
	NODE_TO varchar(100),
	EVENT longtext not null,
	CMD_ID varchar(32),
	PRIMARY KEY pk_b_replica_log_to(ID),
	INDEX ix_b_replica_log_to_1 (CMD_STATUS, ID, NODE_TO),
	INDEX ix_b_replica_log_to_2 (NODE_FROM, NODE_TO, ID),
	INDEX ix_b_replica_log_to_3 (NODE_FROM(50), NODE_TO(50), CMD_ID)
);

CREATE TABLE if not exists b_replica_node
(
	ID int(11) not null auto_increment,
	NODE_TO varchar(100) not null,
	TIMESTAMP_X timestamp not null,
	LOG_FROM_ID int(11) null,
	LOG_TO_ID int(11) null,
	HTTP_STATUS varchar(100),
	HTTP_RESULT longtext,
	PRIMARY KEY pk_b_replica_node(ID),
	INDEX ix_b_replica_node_1 (NODE_TO, LOG_TO_ID),
	INDEX ix_b_replica_node_2 (TIMESTAMP_X)
);

CREATE TABLE if not exists b_replica_host
(
	NAME varchar(50) not null,
	DOMAIN varchar(120) not null,
	SECRET varchar(32) not null,
	PRIMARY KEY pk_b_replica_host(NAME),
	UNIQUE KEY uk_b_replica_host(DOMAIN)
);

CREATE TABLE if not exists b_replica_relation
(
	ID int(11) not null auto_increment,
	START_DATE datetime not null,
	NODE_FROM varchar(100) not null,
	NODE_TO varchar(100) not null,
	PRIMARY KEY pk_b_replica_relation(ID),
	UNIQUE KEY uk_b_replica_relation(NODE_FROM, NODE_TO),
	KEY ix_b_replica_relation(NODE_FROM, START_DATE)
);

CREATE TABLE if not exists b_replica_stop
(
	ID int(11) not null auto_increment,
	STOP_DATE datetime not null,
	NODE_TO varchar(100) not null,
	KEY ix_b_replica_stop(NODE_TO),
	PRIMARY KEY pk_b_replica_stop(ID)
);
