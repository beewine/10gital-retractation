# Journal des modifications

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/)
et le versionnage sémantique.

## [1.0.2] — 2026-09-03

### Modifié

- **Partage des responsabilités rendu explicite.** La section suggérée pour la politique de confidentialité indique désormais, dans la partie destinée à l'administrateur du site, que le marchand est seul responsable du traitement : les déclarations restent dans la base de son site, et 10gital, éditeur de l'extension, n'y a aucun accès, n'en reçoit aucune copie et n'a donc pas à figurer dans sa politique de confidentialité. Même précision sur l'écran de réglages.
- Mention de l'adresse IP reformulée à destination du consommateur plutôt qu'en vocabulaire de responsable de traitement.

### Ajouté

- Sections « External Services » et « Données personnelles » dans `readme.txt` : le fonctionnement du plugin n'appelle aucun service externe ; seule la vérification facultative des mises à jour interroge GitHub, sans transmettre la moindre donnée du site.

## [1.0.1] — 2026-09-03

Corrections issues d'une revue des retours clients du plugin dont celui-ci s'inspire.

### Corrigé

- **Aperçu et envoi de test des e-mails.** WooCommerce rend les e-mails depuis l'écran de réglages sans passer par leur déclenchement : les gabarits recevaient alors une déclaration nulle et l'aperçu échouait. Les deux e-mails s'appuient désormais sur une déclaration d'exemple construite à partir de la commande de démonstration de WooCommerce.
- **Recherche de commande.** Lorsque le numéro saisi ne correspondait à aucune commande, une commande voisine appartenant à la même adresse e-mail pouvait être renvoyée à sa place. La recherche renvoie désormais un résultat vide.

### Ajouté

- Section suggérée pour la politique de confidentialité, injectée dans Réglages → Confidentialité, listant les données conservées et leur durée selon la configuration du site.
- Banc d'essai de non-régression sur l'aperçu des e-mails : les accesseurs vérifiés sont déduits des gabarits eux-mêmes.

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
