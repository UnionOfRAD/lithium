CREATE TABLE images (
  id serial NOT NULL,
  gallery_id integer DEFAULT NULL,
  image text,
  title varchar(50) DEFAULT NULL,
  CONSTRAINT images_pk PRIMARY KEY (id)
);