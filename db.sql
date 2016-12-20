CREATE TABLE IF NOT EXISTS acl(
	id int auto_increment,
	user varchar(200) not null,
	role varchar(200) not null,
	token varchar(400),
	dt timestamp default current_timestamp,
	primary key(id),
	unique(user)
);

CREATE TABLE IF NOT EXISTS ical(
	id int auto_increment,
	acl int(11) not null,
	debu DATETIME not null,
	fin DATETIME not null,
	rec varchar(200) ,
	state int(1) not null,
	rappel time ,
	obj varchar(500) not null,
	des varchar(1000),
	ref varchar(1000),
	dt timestamp default current_timestamp,
	primary key(id)
);

CREATE TABLE IF NOT EXISTS dbconf(
	id int auto_increment,
	host varchar(500) default 'localhost' not null,
	user varchar(200) default 'root' not null,
	pw varchar(200) ,
	db varchar(200) default 'ical' not null,
	primary key(id)
);

ALTER TABLE ical add constraint fk_acl_ical foreign key(acl) references acl(id);

