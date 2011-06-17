CREATE TABLE galleries (
  id serial NOT NULL,
  name varchar(50) DEFAULT NULL,
  CONSTRAINT galleries_pk PRIMARY KEY (id)
);