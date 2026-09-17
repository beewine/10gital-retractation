# Journal des modifications

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/)
et le versionnage sémantique.

## [1.2.0] — 2026-09-17

Un seul endroit pour décider qui reçoit les demandes de rétractation, et des e-mails qui partent dans la bonne langue.

### Contexte

Sur un site client, le champ « Destinataire des notifications » du plugin était vide. Les demandes partaient donc sur `admin_email`, l'adresse technique de l'agence, et personne chez le marchand ne les recevait — pendant des jours, sans que rien ne le signale. Le réglage était invisible, sans valeur par défaut parlante, et il doublait un mécanisme que WooCommerce fait déjà bien.

### Modifié

- **Un seul endroit pour le destinataire.** L'e-mail de notification était déjà une classe `WC_Email`, mais son champ « Destinataires » avait une valeur par défaut vide et le plugin retombait silencieusement sur son propre réglage, puis sur l'adresse d'administration. WooCommerce fait désormais seul autorité : destinataire, objet, titre, activation et prévisualisation se règlent dans **WooCommerce → Réglages → E-mails**, au milieu des autres. Le champ est prérempli avec l'adresse d'administration du site, jamais vide.
- **Le réglage maison `ret10g_merchant_email` n'est plus lu.** À la mise à jour, s'il contenait une adresse, elle est recopiée une fois dans le champ WooCommerce — sauf si une adresse y est déjà saisie, auquel cas elle fait foi. L'option reste en base comme trace, et sera supprimée dans une version ultérieure.
- **L'adresse est visible sans quitter les réglages du plugin.** La section « Traitement » affiche le ou les destinataires lus en direct depuis WooCommerce, l'état de l'e-mail et un lien vers son écran. Tant que les demandes partent sur l'adresse d'administration, une alerte s'affiche en haut de l'écran : c'est de ne pas voir l'adresse qui a créé le trou.

### Corrigé

- **Langue de l'accusé de réception.** `WC_Email::setup_locale()` bascule les e-mails client vers la locale du site : sur un site multilingue, un client anglais recevait son accusé en français. L'accusé part désormais dans la langue de la commande (méta `wpml_language`, filtre `ret10g_order_language`), et la notification interne dans la langue par défaut du site — c'est la boutique qui la lit, pas le client.
- Objets, titres et textes additionnels des deux e-mails déclarés dans `wpml-config.xml`, comme les réglages visibles du plugin : WPML String Translation les propose à la traduction.

### Ajouté

- Bancs d'essai sur la reprise du destinataire (quatre cas), sur la valeur par défaut du champ et sur la résolution de la langue d'une commande.

## [1.1.0] — 2026-09-11

Compatibilité multilingue (WPML, Polylang) et robustesse de l'affichage face aux thèmes, à la suite d'un test sur un site WPML français / anglais.

### Corrigé

- **Lien vers la mauvaise langue.** Le réglage ne mémorise qu'une page de rétractation, et tous les liens — bouton de l'espace client, lien de pied de page, e-mails, redirection après dépôt — pointaient vers cette page, quelle que soit la langue du visiteur. Ils pointent désormais vers sa traduction dans la langue courante ; dans les e-mails, vers la langue dans laquelle la commande a été passée (méta `wpml_language` de WooCommerce Multilingual, filtre `ret10g_order_language`).
- **Textes français sur le site étranger.** L'introduction, la mention légale et les libellés des boutons étaient enregistrés en français à l'activation puis affichés tels quels dans toutes les langues. Tant qu'ils restent sur leur valeur d'origine, ils sont maintenant servis traduits par le catalogue du plugin. Un texte personnalisé se traduit dans WPML (String Translation) ou Polylang, grâce au `wpml-config.xml` fourni. L'écran de réglages affiche et réenregistre toujours la valeur brute.
- **Page créée dans la mauvaise langue.** À l'activation, WPML rangeait la page dans la langue affichée à ce moment dans l'administration : si c'était l'anglais, la version française du site n'avait plus de page et redirigeait vers l'anglaise. La page est désormais créée dans la langue par défaut, puis une traduction est créée dans chaque langue active. Sur un site déjà installé, l'écran de réglages signale les langues manquantes et propose un bouton « Créer les traductions manquantes ».
- **Page de rétractation effacée des réglages.** Dans l'administration d'un site multilingue, la liste des pages ne montre que celles de la langue affichée : si la page enregistrée était dans une autre langue, elle n'apparaissait pas, et le simple fait d'enregistrer les réglages la remettait à « Aucune » — bouton, lien de pied de page et lien des e-mails disparaissaient alors du site. La liste présélectionne désormais la traduction de la page, et l'ajoute si elle en est absente.
- **Exclusions entre langues.** Un produit ou une catégorie exclu dans une langue ne l'était pas dans sa traduction. Les identifiants sont désormais comparés aussi dans la langue par défaut, variations comprises.
- **Formulaire déformé par le thème.** Les blocs du formulaire étaient des balises `<section>` ; les thèmes qui stylent toutes les sections (plein écran, flex centré) l'étiraient sur plusieurs hauteurs d'écran. Les gabarits utilisent des `<div>`, et une règle neutralise les gabarits surchargés qui en contiendraient encore.
- **Champs sombres sur un site clair.** La feuille de style basculait les champs en fond sombre selon le réglage du système du visiteur (`prefers-color-scheme`), sans rapport avec le thème du site. Ce mode automatique est retiré.

### Ajouté

- Code court `[lien_retractation]` : simple lien texte vers la page, dans la langue du visiteur, pour les pieds de page et menus des constructeurs (Oxygen, Bricks, Elementor…).
- Marge intérieure autour du formulaire, pour les gabarits de page sans conteneur.
- Bancs d'essai : textes par défaut traduits, cohérence des chaînes par défaut avec le catalogue, exclusions entre langues.

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
