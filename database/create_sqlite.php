#!/usr/bin/php -q
<?php

if (!($dbh = sqlite_open('picman.db', 0666, $error))) {
	die('Error! While create db file:'. $error);
}

sqlite_query($dbh, "CREATE TABLE sequences (
	num integer DEFAULT 1,
	name varchar(50)
);");

sqlite_query($dbh, "CREATE TABLE categories (
    id integer NOT NULL,
    uniqid varchar(50) NOT NULL,
    id_parent integer DEFAULT 0 NOT NULL,
    lnum integer NOT NULL,
    rnum integer NOT NULL,
    mod_struct integer DEFAULT 0 NOT NULL,
    name varchar NOT NULL,
    date_create timestamp,
    title varchar,
    header varchar,
    hidden integer DEFAULT 0,
    catdir varchar DEFAULT '',
    template varchar DEFAULT 'DEFAULT',
    options varchar DEFAULT '',
    catrows integer DEFAULT 8,
    catcols integer DEFAULT 1,
    colrows integer DEFAULT 4,
    colcols integer DEFAULT 4,
    description text,
	PRIMARY KEY (id),
	UNIQUE (uniqid)
);");

sqlite_query($dbh, "CREATE TABLE collections (
    id integer NOT NULL,
    uniqid varchar(50) NOT NULL,
    uid_cat varchar(50) NOT NULL,
    weight integer DEFAULT 1 NOT NULL,
    name varchar,
    date_create timestamp,
    title varchar,
    header varchar,
    description text,
    startnum integer DEFAULT 1 NOT NULL,
    quantity integer,
    holes varchar DEFAULT '',
    coldir varchar DEFAULT '',
    picsubdir varchar DEFAULT '',
    thumbsubdir varchar DEFAULT '',
    pictemp varchar,
    thumbtemp varchar,
    pgnumtemp varchar DEFAULT '%02d',
    imgindex varchar DEFAULT '',
    icoindex varchar DEFAULT '',
    rows integer DEFAULT 4,
    cols integer DEFAULT 5,
    options varchar DEFAULT 'LP',
	PRIMARY KEY (id),
	UNIQUE (uniqid)
);");

sqlite_query($dbh, "CREATE TABLE links (
    id integer NOT NULL,
    uid_cat varchar(50) NOT NULL,
    uid_col varchar(50) NOT NULL,
    weight integer DEFAULT 1 NOT NULL,
    date_create timestamp,
	PRIMARY KEY (id)
);");

sqlite_query($dbh, "CREATE TABLE tags (
	id integer NOT NULL,
	name varchar(200),
	weight integer DEFAULT 1 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);");

sqlite_query($dbh, "CREATE TABLE tag_collections (
	id_tag integer NOT NULL,
	id_col integer NOT NULL
);");

sqlite_query($dbh, "CREATE INDEX idx_collections_date_create ON collections (date_create);");
sqlite_query($dbh, "CREATE INDEX idx_collections_uid ON collections (uid_cat);");
sqlite_query($dbh, "CREATE UNIQUE INDEX links_uid_cat_key ON links (uid_cat, uid_col);");

sqlite_query($dbh, "INSERT INTO sequences (num, name) VALUES (1,'seq_category');");
sqlite_query($dbh, "INSERT INTO sequences (num, name) VALUES (1,'seq_collection');");
sqlite_query($dbh, "INSERT INTO sequences (num, name) VALUES (1,'seq_link');");
sqlite_query($dbh, "INSERT INTO sequences (num, name) VALUES (1,'seq_tag');");

sqlite_close($dbh);

?>