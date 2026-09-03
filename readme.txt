=== 10gital Rétractation pour WooCommerce ===
Contributors: 10gital
Tags: woocommerce, rétractation, conformité, RGPD, e-commerce
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fonction de rétractation électronique pour WooCommerce, conforme à la directive (UE) 2023/2673. Sans traceur, sans publicité, sans service tiers.

== Description ==

À compter du **19 juin 2026**, tout professionnel vendant à distance à des consommateurs via une interface en ligne doit mettre à disposition une fonction de rétractation dédiée : article 11 bis de la directive (UE) 2011/83 modifiée par la directive (UE) 2023/2673, transposé en France par l'ordonnance n° 2026-2 et le décret n° 2026-3 du 5 janvier 2026 (articles L.221-21 et D.221-5 du code de la consommation).

Ce plugin met cette obligation en œuvre dans WooCommerce, de bout en bout.

= Ce qu'il apporte =

* Bouton de rétractation disponible en permanence : espace client, pied de page, code court, bloc, e-mails WooCommerce.
* Parcours en deux temps : déclaration, puis bouton de confirmation distinct.
* Accès sans création de compte, par numéro de commande et adresse e-mail.
* Accusé de réception horodaté sur support durable, reprenant le contenu intégral de la déclaration.
* Conservation de la preuve : référence unique, contenu figé, export CSV.
* Statut de commande dédié, notification interne, note automatique sur la commande.
* Sélection des articles unité par unité, exclusions par produit, catégorie ou nature.
* Français par défaut, traduisible, version anglaise fournie.

= Ce qu'il ne fait pas =

* Aucun traceur, aucune analytique, aucun pixel.
* Aucune publicité, aucun partenaire commercial, aucun transporteur imposé.
* Aucune inscription à un service tiers.
* Aucun remboursement automatique : la décision reste humaine.

La seule requête sortante possible est la vérification des mises à jour vers api.github.com. Aucune donnée du site n'est transmise, et elle se désactive dans les réglages.

== Installation ==

1. Téléversez l'archive dans Extensions → Ajouter → Téléverser une extension.
2. Activez le plugin. La page « Rétractation » est créée automatiquement.
3. Configurez dans WooCommerce → Rétractation — réglages.

== Frequently Asked Questions ==

= Le plugin bloque-t-il les demandes hors délai ? =

Non, par défaut. Le délai légal court à compter de la réception du bien, que WooCommerce ne connaît pas exactement. Une demande tardive est enregistrée, horodatée et signalée en administration : c'est le marchand qui tranche. Un réglage permet de bloquer, mais il est déconseillé.

= Faut-il un compte client ? =

Non. Le formulaire est accessible avec le numéro de commande et l'adresse e-mail utilisée lors de l'achat.

= Les données sont-elles supprimées à la désinstallation ? =

Non, les déclarations sont conservées : elles constituent une preuve. Pour tout effacer, définissez `RET10G_REMOVE_ALL_DATA` à `true` dans wp-config.php avant de supprimer le plugin.

= Ce plugin garantit-il ma conformité ? =

Il met en œuvre les exigences techniques du texte. Vos CGV, votre information précontractuelle et votre politique de remboursement relèvent de votre conseil juridique.

== Changelog ==

= 1.0.0 =
* Première version publique.
