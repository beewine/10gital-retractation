# 10gital Rétractation pour WooCommerce

Fonction de rétractation électronique pour WooCommerce, conforme à l'article 11 bis
de la directive (UE) 2011/83 (inséré par la **directive (UE) 2023/2673**) et à sa
transposition française — **ordonnance n° 2026-2** et **décret n° 2026-3 du 5 janvier 2026**,
articles **L.221-21** et **D.221-5** du code de la consommation.

**Applicable au 19 juin 2026.** En cas de manquement : amende administrative
jusqu'à 75 000 € pour une personne morale, et délai de rétractation prolongé
à 12 mois et 14 jours.

> Ce plugin est un outil de mise en conformité, pas un conseil juridique. Faites
> valider votre configuration et vos CGV par votre conseil.

---

## Ce que fait le plugin

| Exigence légale | Mise en œuvre |
|---|---|
| Fonction de rétractation **disponible en permanence** pendant le délai | Bouton dans « Mon compte » (liste et détail de commande), lien permanent en pied de page, code court, bloc Gutenberg, lien dans les e-mails WooCommerce |
| **Libellé dénué d'ambiguïté** | « Exercer mon droit de rétractation » par défaut, modifiable dans les réglages |
| Déclaration permettant de fournir **nom, identification du contrat, moyen électronique pour l'accusé** | Formulaire en deux temps, articles sélectionnables unité par unité |
| **Second bouton de confirmation** | « Confirmer la rétractation », libellé modifiable |
| **Accès sans compte** | Recherche par numéro de commande + e-mail, ou lien direct signé depuis les e-mails |
| **Accusé de réception sur support durable**, reprenant le contenu, la date et l'heure | E-mail WooCommerce dédié (HTML + texte), horodaté, contenant la déclaration intégrale |
| **Conservation de la preuve** | Table dédiée, référence unique `RET-AAAA-NNNNNN`, contenu figé de la déclaration, export CSV |

Et côté exploitation : statut de commande « Rétractation demandée », note automatique
sur la commande, notification interne, écran d'administration avec filtres, recherche,
actions groupées et détail, encart sur la fiche commande.

## Ce que le plugin ne fait pas — et ne fera pas

- **Aucun traceur**, aucune analytique, aucun pixel, aucun appel « phone home ».
- **Aucune publicité**, aucun encart partenaire, aucun transporteur imposé.
- **Aucune inscription** à un service tiers, aucune clé d'API à créer.
- **Aucun remboursement automatique** : le remboursement reste une décision humaine,
  effectuée avec les outils natifs de WooCommerce.

La seule requête sortante possible est la vérification des mises à jour vers
`api.github.com` : requête publique en lecture, aucune donnée du site transmise.
Elle se désactive dans les réglages, ou définitivement :

```php
define( 'RET10G_DISABLE_UPDATER', true );
```

## Installation

1. Téléchargez `10gital-retractation.zip` depuis la [dernière publication](https://github.com/beewine/10gital-retractation/releases/latest).
2. Extensions → Ajouter → Téléverser une extension.
3. Activez. Le plugin crée la page « Rétractation » (code court `[retractation]`),
   les tables et les réglages par défaut.
4. Rendez-vous dans **WooCommerce → Rétractation — réglages**.

Les mises à jour suivantes remontent automatiquement dans l'écran des extensions
de WordPress, comme pour un plugin du dépôt officiel.

## Réglages

**Général** — page de rétractation, libellés des deux boutons, texte d'introduction, mention légale.

**Affichage** — emplacements du bouton, couleurs, chargement de la feuille de style.

**Éligibilité** — délai (14 jours par défaut), point de départ (statut « Terminée »
ou date de commande), jours de tolérance, statuts concernés, exclusions par produit,
catégorie ou nature (virtuel/téléchargeable), motif facultatif.

**Traitement** — changement de statut, destinataires des notifications, conservation
de l'adresse IP (désactivée par défaut), purge automatique, vérification des mises à jour.

### Un choix de conception à connaître

Par défaut, une demande **hors délai n'est pas bloquée** : elle est enregistrée,
horodatée et signalée comme telle en administration. Le délai légal court à compter
de la **réception du bien**, que WooCommerce ne connaît pas exactement — le statut
« Terminée » n'en est qu'une approximation. Refuser automatiquement exposerait à
rejeter une rétractation légitime. Le réglage « Refuser les demandes hors délai »
existe, mais reste déconseillé.

## Intégration

### Codes courts

```
[retractation]                     Formulaire complet (page dédiée)
[bouton_retractation]              Bouton vers la page
[bouton_retractation libelle="…"]  Bouton avec un libellé spécifique
```

### Bloc

« Bouton de rétractation », dans la catégorie WooCommerce de l'éditeur.

### Surcharge des gabarits

Copiez un fichier de `templates/` vers `wp-content/themes/<votre-theme>/10gital-retractation/`
en conservant le chemin relatif. Exemple : `templates/form/declare.php` devient
`mon-theme/10gital-retractation/form/declare.php`.

### Points d'extension

**Actions**

| Hook | Arguments | Moment |
|---|---|---|
| `ret10g_loaded` | `$plugin` | Plugin chargé |
| `ret10g_declaration_created` | `$declaration`, `$order` | Déclaration enregistrée, avant envoi des e-mails |
| `ret10g_declaration_status_changed` | `$declaration`, `$status` | Changement de statut de traitement |

**Filtres**

| Hook | Retour | Usage |
|---|---|---|
| `ret10g_setting` | mixed | Forcer un réglage |
| `ret10g_period_start_timestamp` | int | Point de départ réel du délai (date de livraison transporteur, par exemple) |
| `ret10g_deadline_timestamp` | int | Date limite calculée |
| `ret10g_is_product_excluded` | bool | Exclure un produit du droit de rétractation |
| `ret10g_available_items` | array | Articles proposés au consommateur |
| `ret10g_evaluate_order` | array | Résultat complet de l'évaluation |
| `ret10g_should_show_button` | bool | Affichage du bouton |
| `ret10g_statement_content` | string | Contenu de la déclaration |
| `ret10g_locate_template` | string | Chemin d'un gabarit |

Exemple — brancher le délai sur une vraie date de livraison :

```php
add_filter( 'ret10g_period_start_timestamp', function ( $start, $order ) {
	$delivered = $order->get_meta( '_date_livraison_transporteur' );

	return $delivered ? strtotime( $delivered ) : $start;
}, 10, 2 );
```

## Traductions

Le plugin est **rédigé en français**. Le dossier `languages/` fournit :

- `10gital-retractation.pot` — modèle pour toute nouvelle langue ;
- `10gital-retractation-en_US.po` / `.mo` — version anglaise.

Pour ajouter une langue, partez du `.pot` et nommez le fichier
`10gital-retractation-<locale>.po`.

## Développement

```bash
php tests/run.php     # bancs d'essai de la logique métier (sans WordPress)
```

Ces tests couvrent le calcul du délai, la sélection des articles, la composition
de la déclaration, les jetons signés et l'assainissement des réglages. Les parcours
qui exigent WordPress et WooCommerce — formulaire public, envoi des e-mails, écrans
d'administration — doivent être vérifiés sur une installation réelle.

Régénérer le catalogue de traduction :

```bash
find . -name '*.php' -not -path './.git/*' -not -path './tests/*' | sort > /tmp/files.txt
xgettext --from-code=UTF-8 --language=PHP \
  --keyword=__ --keyword=_e --keyword=esc_html__ --keyword=esc_html_e \
  --keyword=esc_attr__ --keyword=esc_attr_e --keyword=_x:1,2c \
  --keyword=_n:1,2 --keyword=_n_noop:1,2 --add-comments=translators \
  -o languages/10gital-retractation.pot -f /tmp/files.txt
```

### Publier une version

1. Mettre à jour le numéro de version dans `10gital-retractation.php` (en-tête **et** constante `RET10G_VERSION`), `readme.txt` et `CHANGELOG.md`.
2. `git tag v1.0.1 && git push origin v1.0.1`.
3. Le workflow `release.yml` construit `10gital-retractation.zip` et crée la publication ; les sites clients la voient sous 12 heures.

## Compatibilité

WordPress 6.5+ · WooCommerce 8.0+ · PHP 7.4+ · HPOS et blocs panier/commande déclarés compatibles.

## Licence

GPL-2.0-or-later. Voir [LICENSE](LICENSE).

Développé par [10gital](https://10gital.fr).
