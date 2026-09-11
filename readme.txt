=== 10gital Rétractation pour WooCommerce ===
Contributors: 10gital
Tags: woocommerce, rétractation, conformité, RGPD, e-commerce
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.0
Stable tag: 1.1.0
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

== External Services ==

Le fonctionnement du plugin ne fait appel à **aucun service externe**. Les déclarations de rétractation sont enregistrées dans la base de données de votre site et n'en sortent jamais.

La seule requête sortante possible est la vérification des mises à jour :

* **Service :** GitHub (api.github.com), opéré par GitHub, Inc.
* **Quand :** deux fois par jour au maximum, uniquement dans l'administration.
* **Ce qui est envoyé :** rien d'autre que la requête elle-même. Aucune donnée de commande, de client, de configuration, ni l'adresse du site. Il s'agit d'une lecture publique de la dernière version publiée du plugin.
* **Comment la désactiver :** décocher « Vérifier les mises à jour sur GitHub » dans les réglages, ou définir `define( 'RET10G_DISABLE_UPDATER', true );` dans wp-config.php.
* Conditions de GitHub : https://docs.github.com/site-policy/github-terms/github-terms-of-service — Confidentialité : https://docs.github.com/site-policy/privacy-policies/github-privacy-statement

== Données personnelles ==

Le plugin enregistre, pour chaque déclaration : nom, prénom, adresse e-mail de contact, commande concernée, articles et quantités, motif éventuel, date et heure. L'adresse IP n'est enregistrée que si vous activez explicitement ce réglage.

**Vous êtes seul responsable de ce traitement.** 10gital, éditeur de l'extension, n'a aucun accès à ces données et n'en reçoit aucune copie : il n'a donc pas à figurer dans votre politique de confidentialité. Le plugin vous propose en revanche une section prête à adapter dans Réglages → Confidentialité.

== Changelog ==

= 1.1.0 =
* Correction : sur un site multilingue (WPML, Polylang), le bouton, le lien de pied de page et les e-mails renvoyaient tous vers une seule version de la page de rétractation. Ils pointent désormais vers la page dans la langue du visiteur, ou du client pour les e-mails.
* Correction : les textes par défaut (introduction, mention légale, libellés des boutons) restaient en français sur les versions étrangères du site. Ils sont traduits ; un texte personnalisé se traduit avec WPML ou Polylang (wpml-config.xml fourni).
* Correction : la page créée à l'activation prenait la langue affichée dans l'administration. Elle est créée dans la langue par défaut, puis traduite dans chaque langue active ; un bouton des réglages crée les traductions manquantes.
* Correction : les exclusions par produit ou par catégorie s'appliquent aussi aux traductions des produits et des catégories exclus.
* Correction d'affichage : le formulaire n'utilise plus de balises <section>, que certains thèmes affichent en plein écran ; styles durcis, mode sombre automatique retiré.
* Ajout : code court [lien_retractation], simple lien texte pour le pied de page ou un menu du thème.

= 1.0.2 =
* Précision : le marchand est seul responsable du traitement des déclarations ; 10gital, éditeur de l'extension, n'y a aucun accès et n'a pas à figurer dans sa politique de confidentialité.
* Ajout des sections « External Services » et « Données personnelles ».

= 1.0.1 =
* Correction : l'aperçu et l'envoi de test des e-mails depuis les réglages WooCommerce échouaient.
* Correction : la recherche de commande pouvait renvoyer une commande voisine lorsque le numéro saisi était introuvable.
* Ajout : section suggérée pour la politique de confidentialité (Réglages → Confidentialité).

= 1.0.0 =
* Première version publique.
