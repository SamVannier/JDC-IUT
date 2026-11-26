-- Migration pour ajouter la colonne mdp sur une base existante
ALTER TABLE UTILISATEUR
    ADD COLUMN mdp VARCHAR(255) NOT NULL DEFAULT '' AFTER pseudo;

-- Définit le mot de passe 'shoto123' pour le compte de test Shoto s'il existe déjà
UPDATE UTILISATEUR
SET mdp = '$2y$10$33nSnF3EuqZAjwS8m6tFOO83.R8PbHAig64ZK49UBrMTUxbfpSFYS'
WHERE pseudo = 'Shoto' AND mdp = '';
