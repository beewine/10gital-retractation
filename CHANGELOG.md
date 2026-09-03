# Journal des modifications

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/)
et le versionnage sémantique.

## [1.0.0] — 2026-09-03

Première version publique.

### Ajouté

- Fonction de rétractation en deux temps (déclaration puis confirmation explicite), conforme à l'article 11 bis de la directive (UE) 2011/83 modifiée.
- Accès sans compte : recherche par numéro de commande et adresse e-mail, avec limitation du nombre de tentatives et message d'erreur générique.
- Lien direct signé depuis l'espace client et les e-mails WooCommerce.
- Accusé de réception sur support durable : e-mail horodaté reprenant le contenu intégral de la déclaration, en HTML et en texte.
- Notification interne au marchand, note automatique sur la commande, statut de commande « Rétractation demandée ».
- Sélection des articles unité par unité, avec déduction des quantités déjà déclarées ou remboursées.
- Écran d'administration : liste filtrable et triable, recherche, actions groupées, vue détail, encart sur la fiche commande, export CSV.
- Réglages complets : libellés, emplacements, couleurs, délai et son point de départ, tolérance, statuts concernés, exclusions par produit, catégorie ou nature, purge automatique.
- Code court `[retractation]`, code court `[bouton_retractation]`, bloc Gutenberg « Bouton de rétractation ».
- Surcharge de tous les gabarits depuis le thème, dix points d'extension documentés.
- Mise à jour automatique depuis les publications GitHub, désactivable.
- Français par défaut, catalogue `.pot` et traduction anglaise fournis.
