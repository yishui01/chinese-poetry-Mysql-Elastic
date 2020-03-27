create table IF NOT EXISTS `poems` (
    id int unsigned not null primary key auto_increment,
    cate varchar (50) not null ,
    sn varchar (32) not null ,
    title varchar (200) not null ,
    author varchar (200) default "",
    rhythmic varchar (200) default "",
    chapter varchar (200) default "" ,
    section varchar (200) default "" ,
    comment varchar (2000)default "" ,
    notes varchar (2000) default "" ,
    paragraphs varchar (5000) default "" ,
    content varchar (2000) default "" ,
    create_time timestamp
)engine=innodb charset=utf8mb4;