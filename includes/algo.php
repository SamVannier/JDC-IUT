<?php

function determiner_etat($float_value) {
    if ($float_value < 0.07) return 'Factory New';
    if ($float_value < 0.15) return 'Minimal Wear';
    if ($float_value < 0.38) return 'Field-Tested';
    if ($float_value < 0.45) return 'Well-Worn';
    return 'Battle-Scarred';
}

function generer_variante($type_carte) {
    // Génère un nombre flottant entre 0.0 et 1.0
    $tirage = mt_rand() / mt_getrandmax(); 

    if (strtoupper($type_carte) === 'SKIN') {
        // 10% de chance d'avoir un skin StatTrak
        return ($tirage <= 0.10) ? 'StatTrak' : 'Normal';
    } else {
        // Pour les Joueurs et Équipes
        if ($tirage <= 0.02) return 'Gold';
        if ($tirage <= 0.08) return 'Foil';
        if ($tirage <= 0.25) return 'Holo';
        return 'Normal';
    }
}

function calculer_prix_vente($type_carte, $variante, $etat, $rarete_skin = null) {
    $prix = 20; // Prix de base pour un Joueur ou une Équipe

    // 1. Base du prix selon la rareté si c'est un skin
    if (strtoupper($type_carte) === 'SKIN') {
        switch ($rarete_skin) {
            case 'Mil-Spec': $prix = 15; break;
            case 'Restricted': $prix = 50; break;
            case 'Classified': $prix = 200; break;
            case 'Covert': $prix = 800; break;
            case 'Gold': $prix = 2500; break;
            default: $prix = 15; break;
        }
        
        // 2. Multiplicateur d'usure
        switch ($etat) {
            case 'Factory New': $prix *= 3.0; break;
            case 'Minimal Wear': $prix *= 1.5; break;
            case 'Field-Tested': $prix *= 1.0; break;
            case 'Well-Worn': $prix *= 0.8; break;
            case 'Battle-Scarred': $prix *= 0.5; break;
        }
    }

    // 3. Multiplicateur cosmétique global
    switch ($variante) {
        case 'Holo': $prix *= 2.0; break;
        case 'Foil': $prix *= 4.0; break;
        case 'Gold': $prix *= 10.0; break;
        case 'StatTrak': $prix *= 2.5; break;
    }

    // Retourne un entier arrondi, avec un minimum de 1 crédit
    return max(1, (int)$prix);
}
?>