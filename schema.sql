create table if not exists users (
    id int unsigned not null auto_increment,
    full_name varchar(120) not null,
    username varchar(80) not null,
    password varchar(255) not null,
    role enum('student', 'staff') not null,
    primary key (id),
    unique key users_username_unique (username)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists subjects (
    id int unsigned not null auto_increment,
    name varchar(120) not null,
    default_score decimal(7,2) unsigned not null,
    max_score decimal(7,2) unsigned not null,
    primary key (id),
    unique key subjects_name_unique (name)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists student_subject_scores (
    student_id int unsigned not null,
    subject_id int unsigned not null,
    score decimal(7,2) unsigned not null,
    primary key (student_id, subject_id),
    constraint student_subject_scores_student_fk foreign key (student_id) references users (id) on delete cascade,
    constraint student_subject_scores_subject_fk foreign key (subject_id) references subjects (id) on delete cascade
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

create table if not exists attendance (
    id int unsigned not null auto_increment,
    student_id int unsigned not null,
    attendance_date date not null,
    status enum('attended', 'absent') not null,
    primary key (id),
    unique key attendance_student_date_unique (student_id, attendance_date),
    constraint attendance_student_fk foreign key (student_id) references users (id) on delete cascade
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;
