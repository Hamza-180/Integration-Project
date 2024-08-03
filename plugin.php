<?php
/*
Plugin Name: mijn Plugin
Description: Testen
Version: 1.0
Author: Hamza
*/

// Exemple d'ajout d'une fonction à un hook
function afficher_message() {
    echo '<p>hello dit is een test om te zien als ik op wordpress iets zie !</p>';
}
add_action('wp_footer', 'afficher_message');

