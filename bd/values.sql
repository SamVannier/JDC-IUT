-- =========================================
-- JEU D'ESSAI (Données de test)
-- =========================================

-- 1. Création d'un joueur (mot de passe de test : shoto123)
INSERT INTO UTILISATEUR (pseudo, mdp, solde_credit, pp) 
VALUES ('Shoto', '$2y$10$33nSnF3EuqZAjwS8m6tFOO83.R8PbHAig64ZK49UBrMTUxbfpSFYS', 5000, '/img/shoto_pp.png');

-- 2. Création des références de cartes (La "bibliothèque" du jeu)

-- Une carte JOUEUR : ZywOo
INSERT INTO CARTE (type_c, nom, image_url) 
VALUES ('Joueur', 'Mathieu "ZywOo" Herbaut', '/img/zywoo.png');
SET @id_zywoo = LAST_INSERT_ID();
INSERT INTO CARTE_JOUEUR (id_ref, role_j, rating) 
VALUES (@id_zywoo, 'Awper', 1.30);

-- Une carte EQUIPE : Vitality
INSERT INTO CARTE (type_c, nom, image_url) 
VALUES ('Equipe', 'Team Vitality', '/img/vitality.png');
SET @id_vita = LAST_INSERT_ID();
INSERT INTO CARTE_EQUIPE (id_ref, region, classement) 
VALUES (@id_vita, 'Europe', 1);

-- Une carte SKIN : AK-47 Redline
INSERT INTO CARTE (type_c, nom, image_url) 
VALUES ('Skin', 'AK-47 | Redline', '/img/ak_redline.png');
SET @id_ak = LAST_INSERT_ID();
INSERT INTO CARTE_SKIN (id_ref, arme, rarete) 
VALUES (@id_ak, 'AK-47', 'Classified');

-- Une carte SKIN très rare : AWP Dragon Lore
INSERT INTO CARTE (type_c, nom, image_url) 
VALUES ('Skin', 'AWP | Dragon Lore', '/img/awp_dlore.png');
SET @id_dlore = LAST_INSERT_ID();
INSERT INTO CARTE_SKIN (id_ref, arme, rarete) 
VALUES (@id_dlore, 'AWP', 'Covert');

-- 3. Ajout des cartes dans l'inventaire réel de Shoto (id_user = 1)

-- Un ZywOo version Holo (Pas d'usure ni de float pour un joueur)
INSERT INTO INVENTAIRE (id_user, id_ref, variante_cosmetique, etat, float_value) 
VALUES (1, @id_zywoo, 'Holo', NULL, NULL);

-- Un sticker Vitality version Gold (Pas d'usure ni de float)
INSERT INTO INVENTAIRE (id_user, id_ref, variante_cosmetique, etat, float_value) 
VALUES (1, @id_vita, 'Gold', NULL, NULL);

-- Une AK-47 Redline StatTrak en état Field-Tested avec son float précis
INSERT INTO INVENTAIRE (id_user, id_ref, variante_cosmetique, etat, float_value) 
VALUES (1, @id_ak, 'StatTrak', 'Field-Tested', 0.151234567890123);

-- Un AWP Dragon Lore Normal mais en parfait état (Factory New)
INSERT INTO INVENTAIRE (id_user, id_ref, variante_cosmetique, etat, float_value) 
VALUES (1, @id_dlore, 'Normal', 'Factory New', 0.012345678912345);