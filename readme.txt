=== WP e-Commerce Simple Paypal ===
Contributors: 6WWW
Donate link: http://wpcb.fr/boutique
Tags: wp-e-commerce, wpec, paypal, ipn
Requires at least: 2.7
Tested up to: 3.2.1
Stable tag: 1.1.3

Simple (and working) paypal gateway for WP e-Commerce plugin
Plugin de paiement simplifié par Paypal (Plugin requis : WP e-Commerce)

== Description ==

Plugin de paiement simplifié par Paypal (Plugin requis : WP e-Commerce)

Au moment du réglement le client pourra choisir Paypal
IPN configurable, avec fichier d'exemple inclu


== Installation ==

1. Envoyer `wp-e-commerce-simple-paypal` vers le dossier `/wp-content/plugins/`
2. Activer le plugin sur le menu 'Extensions' de WordPress
3. Aller sur 'Réglages -> Boutique -> Paiement' et editer -> 'Simple Paypal'
4. Documentation supplémentaire sur : http://wpcb.fr/plugin-simple-paypal (en Français)

== Frequently Asked Questions ==

Si vous avez une question ou si vous constatez des bugs dans l'utilisation du plugin, consultez le forum : http://wpcb.fr/support/simple-paypal/

= La méthode de paiement n'aparait pas dans la liste, comment faire ? =
Certaines installation de wpec ne reconnaisse pas le paiement gateway lorsqu'il est dans le dossier plugin. C'est à dire qu'il n'apparait pas dans la liste. Pour résoudre cela déplacer (et non copier) le fichier simple_paypal.php (uniquement) vers le dossier wp-e-commerce/wpsc-merchants.

== Screenshots ==

1. Réglages du plugin
2. Réglages de paypal


== Changelog ==

= 1.1.3 =
* Ajout de la fonction sandbox
* Settings API
* Mise à jour de sécurité mineure

= 1.1.2 =
* submit() & $sessionid=$this->cart_data['session_id'];

= 1.1 =
* Api 2.0 syntax
* loader.php and copy sytem added

= 1.0 =
* Première version

