USE api;

CREATE  TABLE users(
id      int(255) auto_increment not null,
name    varchar(50) not null,
surname varchar(150),
email varchar(255) not null,
image   varchar(255),
password varchar(255) not null,
role    varchar(20),
created_at  datetime DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT pk_users PRIMARY KEY(id)
)ENGINE=InnoDb;


CREATE  TABLE categories(
id      int(255) auto_increment not null,
name    varchar(50) not null,
color varchar(50),
created_at  datetime DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT pk_categories PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE projects(
    id  int(255) auto_increment not null,
    name    varchar(50) not null,
    description varchar(255) not null,
    image   varchar(255),
    category_id int(255) not null,
    user_id int(255) not null,
    created_at  datetime DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_projects PRIMARY KEY(id),
    CONSTRAINT fk_project_user FOREIGN KEY(user_id) REFERENCES users(id),
    CONSTRAINT fk_project_category FOREIGN KEY(category_id) REFERENCES categories(id)
)ENGINE=InnoDb;

CREATE  TABLE follows(
id      int(255) auto_increment not null,
user_id    int(255) not null,
project_id int(255) not null,
created_at  datetime DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT pk_follows PRIMARY KEY(id),
CONSTRAINT fk_follow_user FOREIGN KEY(user_id) REFERENCES users(id),
CONSTRAINT fk_follow_project FOREIGN KEY(project_id) REFERENCES projects(id)
)ENGINE=InnoDb;