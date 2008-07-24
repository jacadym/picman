
CREATE TABLE categories (
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
  grrows integer DEFAULT 4,
  grcols integer DEFAULT 4,
  description text,
  PRIMARY KEY (id),
  UNIQUE (uniqid)
);

CREATE TABLE groups (
  id integer NOT NULL,
  uniqid varchar(50) NOT NULL,
  uid_cat varchar(50) NOT NULL,
  onum integer DEFAULT 1 NOT NULL,
  name varchar,
  date_create timestamp,
  title varchar,
  header varchar,
  description text,
  startnum integer DEFAULT 1 NOT NULL,
  quantity integer,
  holes varchar DEFAULT '',
  grdir varchar DEFAULT '',
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
);

CREATE TABLE links (
  id integer NOT NULL,
  uid_cat varchar(50) NOT NULL,
  uid_group varchar(50) NOT NULL,
  onum integer DEFAULT 1 NOT NULL,
  date_create timestamp,
  PRIMARY KEY (id)
);

CREATE INDEX idx_groups_date_create ON groups (date_create);

CREATE INDEX idx_groups_uid ON groups (uid_cat);

CREATE UNIQUE INDEX links_uid_cat_key ON links (uid_cat, uid_group);

