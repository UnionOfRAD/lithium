CREATE TABLE companies (
  id serial NOT NULL,
  "name" varchar(255) DEFAULT NULL,
  active integer DEFAULT NULL,
  created timestamp DEFAULT NULL,
  modified timestamp DEFAULT NULL,
  CONSTRAINT companies_pk PRIMARY KEY (id)
);