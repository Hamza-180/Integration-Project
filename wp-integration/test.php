<?php
// URL de l'API FOSSBilling
$api_urls = [

    'http://192.168.129.101/api/clients/create',
    'http://192.168.129.101/api/client',
    'http://192.168.129.101/api/v1/client/create'
];

// Clé API FOSSBilling
$api_key = 'iga8FSS2348vg4DmbNwZquhXlYi4N6TS'; // Remplacez par votre clé API

// Données de l'utilisateur à envoyer à l'API
$user_data = array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'username' => 'johndoe',
    'password' => 'securepassword' // Assurez-vous que le mot de passe répond aux exigences de sécurité
);

// Configuration des en-têtes de la requête
$headers = array(
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($api_key . ':')
);

// Configuration de la requête
$options = array(
    'http' => array(
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => json_encode($user_data),
        'ignore_errors' => true // Pour obtenir le contenu même en cas d'erreur HTTP
    )
);

// Création du contexte de la requête
$context = stream_context_create($options);

// Exécution de la requête
$response = file_get_contents($api_url, false, $context);

// Affichage de la réponse
if ($response === FALSE) {
    echo "Erreur lors de l'envoi de la requête.";
} else {
    echo "Réponse de l'API : " . $response;
}

?>
