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

/**
 * Identifie les doublons d'un inventaire selon le couple (id_ref, variante_cosmetique).
 * Conserve le meilleur exemplaire de chaque groupe (prix le plus élevé, puis meilleur float, puis plus ancien).
 */
function identifier_doublons(array $cartes): array {
    $groupes = [];

    foreach ($cartes as $carte) {
        $carte['_prix'] = calculer_prix_vente(
            $carte['type_c'],
            $carte['variante_cosmetique'],
            $carte['etat'] ?? null,
            $carte['rarete'] ?? null
        );
        $cle = $carte['id_ref'] . '|' . $carte['variante_cosmetique'];
        $groupes[$cle][] = $carte;
    }

    $ids_instances = [];
    $gain_total = 0;

    foreach ($groupes as $groupe) {
        if (count($groupe) <= 1) {
            continue;
        }

        usort($groupe, function ($a, $b) {
            if ($b['_prix'] !== $a['_prix']) {
                return $b['_prix'] <=> $a['_prix'];
            }
            if ($a['float_value'] !== null && $b['float_value'] !== null) {
                $cmpFloat = (float)$a['float_value'] <=> (float)$b['float_value'];
                if ($cmpFloat !== 0) {
                    return $cmpFloat;
                }
            }
            return (int)$a['id_instance'] <=> (int)$b['id_instance'];
        });

        // L'élément 0 est conservé, tous les suivants sont des doublons à vendre
        for ($i = 1, $n = count($groupe); $i < $n; $i++) {
            $ids_instances[] = (int)$groupe[$i]['id_instance'];
            $gain_total += $groupe[$i]['_prix'];
        }
    }

    return [
        'ids_instances' => $ids_instances,
        'nb_doublons' => count($ids_instances),
        'gain_total' => $gain_total
    ];
}
?>