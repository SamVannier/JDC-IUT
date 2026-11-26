create table UTILISATEUR (
    id_user int AUTO_INCREMENT primary key,
    pseudo varchar(15) not null unique,
    mdp varchar(255) not null,
    solde_credit int default 1000,
    pp varchar(255)
);

create table CARTE(
    id_ref int AUTO_INCREMENT primary key,
    type_c ENUM('Equipe', 'Joueur', 'Skin'),
    nom varchar(50),
    image_url varchar(255)
);

create table CARTE_EQUIPE(
    id_ref int primary key,
    region varchar(50),
    classement int,
    foreign key(id_ref) references CARTE(id_ref)
);

create table CARTE_JOUEUR(
    id_ref int primary key,
    role_j ENUM('Awper', 'Igl', 'Lurker', 'Support', 'EntryFragger'),
    rating decimal(3,2) not null,
    foreign key(id_ref) references CARTE(id_ref)
);

create table CARTE_SKIN(
    id_ref int primary key,
    arme varchar(50),
    rarete ENUM('Mil-Spec', 'Restricted', 'Classified', 'Covert', 'Gold') NOT NULL,
    foreign key(id_ref) references CARTE(id_ref)
);

CREATE TABLE INVENTAIRE(
    id_instance int AUTO_INCREMENT primary key,
    id_user int not null,
    id_ref int not null,
    variante_cosmetique ENUM('Normal', 'Holo', 'Gold', 'Foil', 'StatTrak') DEFAULT 'Normal',
    etat ENUM('Battle-Scarred', 'Well-Worn', 'Field-Tested', 'Minimal Wear', 'Factory New'),
    float_value DECIMAL(16,15) NULL, -- Précision jusqu'à 15 décimales (ex: 0.0453123491)   
    date_obtention DATETIME DEFAULT CURRENT_TIMESTAMP,
    foreign key (id_user) references UTILISATEUR(id_user),
    foreign key (id_ref) references CARTE(id_ref)
);