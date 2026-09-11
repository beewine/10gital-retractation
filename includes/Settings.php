<?php
/**
 * Registre des réglages du plugin.
 *
 * Toutes les options sont déclarées ici, avec leur valeur par défaut, leur type
 * et leur assainissement. L'interface d'administration est générée à partir de
 * ce schéma (voir Admin\SettingsPage).
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation;

defined( 'ABSPATH' ) || exit;

/**
 * Accès centralisé aux options.
 */
class Settings {

	public const PREFIX = 'ret10g_';

	/**
	 * Réglages affichés aux visiteurs, donc à traduire sur un site multilingue.
	 *
	 * Ils sont aussi déclarés dans wpml-config.xml, pour que WPML et Polylang
	 * proposent de traduire une valeur personnalisée.
	 */
	public const TRANSLATABLE = array( 'button_label', 'confirm_label', 'intro_text', 'legal_notice' );

	/**
	 * Cache mémoire des valeurs déjà lues.
	 *
	 * @var array<string,mixed>
	 */
	private static $cache = array();

	/**
	 * Schéma complet des réglages.
	 *
	 * Chaque entrée : type, default, label, description, section, et selon le
	 * type : options (select/multicheck), min/max (number).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function schema() {
		return array(
			// --- Général ---------------------------------------------------.
			'page_id'              => array(
				'section' => 'general',
				'type'    => 'page',
				'default' => 0,
				'label'   => __( 'Page de rétractation', '10gital-retractation' ),
				'desc'    => __( 'Page contenant le code court [retractation]. Créée automatiquement à l\'activation.', '10gital-retractation' ),
			),
			'button_label'         => array(
				'section' => 'general',
				'type'    => 'text',
				'default' => 'Exercer mon droit de rétractation',
				'label'   => __( 'Libellé du bouton', '10gital-retractation' ),
				'desc'    => __( 'Doit être une formule dénuée d\'ambiguïté (art. D.221-5). Formulations sûres : « Renoncer au contrat ici », « Se rétracter du contrat ici », « Exercer mon droit de rétractation ».', '10gital-retractation' ),
			),
			'confirm_label'        => array(
				'section' => 'general',
				'type'    => 'text',
				'default' => 'Confirmer la rétractation',
				'label'   => __( 'Libellé du bouton de confirmation', '10gital-retractation' ),
				'desc'    => __( 'Second bouton, exigé par l\'article 11 bis de la directive (UE) 2011/83 modifiée.', '10gital-retractation' ),
			),
			'intro_text'           => array(
				'section' => 'general',
				'type'    => 'textarea',
				'default' => "Vous disposez d'un délai de 14 jours pour vous rétracter de votre commande, sans avoir à motiver votre décision. Renseignez le formulaire ci-dessous : un accusé de réception horodaté vous sera envoyé immédiatement par e-mail.",
				'label'   => __( 'Texte d\'introduction', '10gital-retractation' ),
				'desc'    => __( 'Affiché en haut de la page de rétractation.', '10gital-retractation' ),
			),
			'legal_notice'         => array(
				'section' => 'general',
				'type'    => 'textarea',
				'default' => "Certains biens et services sont exclus du droit de rétractation par l'article L.221-28 du code de la consommation (biens confectionnés sur mesure, biens périssables, enregistrements descellés, contenus numériques fournis avec votre accord préalable, etc.).",
				'label'   => __( 'Mention légale', '10gital-retractation' ),
				'desc'    => __( 'Affichée sous le formulaire. Laisser vide pour ne rien afficher.', '10gital-retractation' ),
			),

			// --- Affichage -------------------------------------------------.
			'display_myaccount'    => array(
				'section' => 'display',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Liste des commandes (Mon compte)', '10gital-retractation' ),
				'desc'    => __( 'Ajoute le bouton en face de chaque commande éligible.', '10gital-retractation' ),
			),
			'display_order_detail' => array(
				'section' => 'display',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Détail d\'une commande (Mon compte)', '10gital-retractation' ),
				'desc'    => __( 'Ajoute le bouton sous le détail de la commande.', '10gital-retractation' ),
			),
			'display_footer'       => array(
				'section' => 'display',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Lien permanent en pied de page', '10gital-retractation' ),
				'desc'    => __( 'Recommandé : la fonction doit être « disponible en permanence » et « aisément accessible » pendant tout le délai de rétractation.', '10gital-retractation' ),
			),
			'display_emails'       => array(
				'section' => 'display',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Lien dans les e-mails WooCommerce', '10gital-retractation' ),
				'desc'    => __( 'Ajoute le lien vers la page de rétractation au bas des e-mails client.', '10gital-retractation' ),
			),
			'button_bg'            => array(
				'section' => 'display',
				'type'    => 'color',
				'default' => '#1f2937',
				'label'   => __( 'Couleur de fond du bouton', '10gital-retractation' ),
			),
			'button_color'         => array(
				'section' => 'display',
				'type'    => 'color',
				'default' => '#ffffff',
				'label'   => __( 'Couleur du texte du bouton', '10gital-retractation' ),
			),
			'load_styles'          => array(
				'section' => 'display',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Charger la feuille de style du plugin', '10gital-retractation' ),
				'desc'    => __( 'Décocher pour styler entièrement depuis le thème.', '10gital-retractation' ),
			),

			// --- Éligibilité -----------------------------------------------.
			'period_days'          => array(
				'section' => 'eligibility',
				'type'    => 'number',
				'default' => 14,
				'min'     => 1,
				'max'     => 365,
				'label'   => __( 'Délai de rétractation (jours)', '10gital-retractation' ),
				'desc'    => __( 'Minimum légal : 14 jours. Vous pouvez offrir davantage.', '10gital-retractation' ),
			),
			'period_start'         => array(
				'section' => 'eligibility',
				'type'    => 'select',
				'default' => 'completed',
				'options' => array(
					'completed'  => 'Date de passage au statut « Terminée » (livraison)',
					'order_date' => 'Date de la commande',
				),
				'label'   => __( 'Point de départ du délai', '10gital-retractation' ),
				'desc'    => __( 'Pour les biens, le délai court à compter de la réception. Le statut « Terminée » en est l\'approximation la plus fidèle ; à défaut, la date de commande est utilisée.', '10gital-retractation' ),
			),
			'period_grace'         => array(
				'section' => 'eligibility',
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 90,
				'label'   => __( 'Jours de tolérance supplémentaires', '10gital-retractation' ),
				'desc'    => __( 'Marge appliquée après le délai, pour absorber les écarts entre la date de livraison réelle et le statut de la commande.', '10gital-retractation' ),
			),
			'allowed_statuses'     => array(
				'section' => 'eligibility',
				'type'    => 'order_statuses',
				'default' => array( 'processing', 'completed', 'on-hold' ),
				'label'   => __( 'Statuts de commande concernés', '10gital-retractation' ),
				'desc'    => __( 'Seules les commandes dans ces statuts affichent le bouton.', '10gital-retractation' ),
			),
			'block_out_of_period'  => array(
				'section' => 'eligibility',
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Refuser les demandes hors délai', '10gital-retractation' ),
				'desc'    => __( 'Déconseillé. Par défaut, une demande hors délai est enregistrée et signalée comme telle : c\'est vous qui tranchez, et vous conservez la preuve de la demande.', '10gital-retractation' ),
			),
			'exclude_virtual'      => array(
				'section' => 'eligibility',
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Exclure les produits virtuels ou téléchargeables', '10gital-retractation' ),
				'desc'    => __( 'À n\'activer que si le consommateur a expressément renoncé à son droit avant l\'exécution (art. L.221-28 13°).', '10gital-retractation' ),
			),
			'excluded_products'    => array(
				'section' => 'eligibility',
				'type'    => 'ids',
				'default' => array(),
				'label'   => __( 'Produits exclus', '10gital-retractation' ),
				'desc'    => __( 'Identifiants de produits séparés par des virgules.', '10gital-retractation' ),
			),
			'excluded_categories'  => array(
				'section' => 'eligibility',
				'type'    => 'ids',
				'default' => array(),
				'label'   => __( 'Catégories exclues', '10gital-retractation' ),
				'desc'    => __( 'Identifiants de catégories de produits séparés par des virgules.', '10gital-retractation' ),
			),
			'collect_reason'       => array(
				'section' => 'eligibility',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Proposer un motif (facultatif)', '10gital-retractation' ),
				'desc'    => __( 'Le motif ne peut jamais être rendu obligatoire : le droit de rétractation s\'exerce sans justification.', '10gital-retractation' ),
			),

			// --- Traitement et e-mails -------------------------------------.
			'change_order_status'  => array(
				'section' => 'processing',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Changer le statut de la commande', '10gital-retractation' ),
				'desc'    => __( 'Bascule la commande vers « Rétractation demandée » lorsque la déclaration porte sur la totalité de la commande.', '10gital-retractation' ),
			),
			'merchant_email'       => array(
				'section' => 'processing',
				'type'    => 'text',
				'default' => '',
				'label'   => __( 'Destinataire des notifications', '10gital-retractation' ),
				'desc'    => __( 'Adresses séparées par des virgules. Vide = adresse d\'administration du site.', '10gital-retractation' ),
			),
			'store_ip'             => array(
				'section' => 'processing',
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Conserver l\'adresse IP du déclarant', '10gital-retractation' ),
				'desc'    => __( 'Non nécessaire à la conformité. À n\'activer qu\'avec une base légale et une mention dans votre politique de confidentialité.', '10gital-retractation' ),
			),
			'retention_days'       => array(
				'section' => 'processing',
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 3650,
				'label'   => __( 'Purge automatique après (jours)', '10gital-retractation' ),
				'desc'    => __( '0 = aucune purge. Les déclarations servent de preuve : conserver au moins la durée de prescription applicable.', '10gital-retractation' ),
			),
			'check_updates'        => array(
				'section' => 'processing',
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Vérifier les mises à jour sur GitHub', '10gital-retractation' ),
				'desc'    => __( 'Interroge api.github.com deux fois par jour pour connaître la dernière version publiée. Aucune donnée du site n\'est transmise.', '10gital-retractation' ),
			),
		);
	}

	/**
	 * Sections de réglages, dans l'ordre d'affichage.
	 *
	 * @return array<string,string>
	 */
	public static function sections() {
		return array(
			'general'     => __( 'Général', '10gital-retractation' ),
			'display'     => __( 'Affichage', '10gital-retractation' ),
			'eligibility' => __( 'Éligibilité', '10gital-retractation' ),
			'processing'  => __( 'Traitement', '10gital-retractation' ),
		);
	}

	/**
	 * Lit une option.
	 *
	 * @param string $key Clé sans préfixe.
	 * @return mixed
	 */
	public static function get( $key ) {
		$translatable = in_array( $key, self::TRANSLATABLE, true );

		if ( ! $translatable && array_key_exists( $key, self::$cache ) ) {
			return self::$cache[ $key ];
		}

		$schema = self::schema();

		if ( ! isset( $schema[ $key ] ) ) {
			return null;
		}

		$value = self::raw( $key );

		// Texte resté sur sa valeur d'origine : on sert sa traduction gettext,
		// pour que le site anglais n'affiche pas le texte français par défaut.
		if ( $translatable && self::is_default_text( $value, $schema[ $key ]['default'] ) ) {
			$value = self::translated_default( $key );
		}

		/**
		 * Filtre la valeur d'un réglage.
		 *
		 * @param mixed  $value Valeur.
		 * @param string $key   Clé sans préfixe.
		 */
		$value = apply_filters( 'ret10g_setting', $value, $key );

		// Les textes traduisibles ne sont pas mis en cache : la langue peut
		// changer en cours de requête (e-mail envoyé dans la langue du client).
		if ( ! $translatable ) {
			self::$cache[ $key ] = $value;
		}

		return $value;
	}

	/**
	 * Valeur enregistrée, sans traduction ni filtre.
	 *
	 * C'est elle que l'écran de réglages affiche et réenregistre.
	 *
	 * @param string $key Clé sans préfixe.
	 * @return mixed
	 */
	public static function raw( $key ) {
		$schema = self::schema();

		if ( ! isset( $schema[ $key ] ) ) {
			return null;
		}

		return self::cast( get_option( self::PREFIX . $key, $schema[ $key ]['default'] ), $schema[ $key ] );
	}

	/**
	 * Un texte enregistré est-il resté sur sa valeur d'origine ?
	 *
	 * La comparaison ignore les différences d'espacement qu'un passage par le
	 * formulaire de réglages peut introduire (retours à la ligne, espaces).
	 *
	 * @param mixed  $value   Valeur enregistrée.
	 * @param string $default Valeur par défaut du schéma.
	 * @return bool
	 */
	private static function is_default_text( $value, $default ) {
		$normalize = static function ( $text ) {
			return trim( (string) preg_replace( '/\s+/u', ' ', (string) $text ) );
		};

		return is_string( $value ) && $normalize( $value ) === $normalize( $default );
	}

	/**
	 * Valeur par défaut d'un texte, traduite dans la langue courante.
	 *
	 * Les chaînes sont répétées ici en toutes lettres pour que les outils
	 * d'extraction gettext les trouvent ; elles doivent rester identiques aux
	 * valeurs `default` du schéma.
	 *
	 * @param string $key Clé sans préfixe.
	 * @return string
	 */
	public static function translated_default( $key ) {
		switch ( $key ) {
			case 'button_label':
				return __( 'Exercer mon droit de rétractation', '10gital-retractation' );

			case 'confirm_label':
				return __( 'Confirmer la rétractation', '10gital-retractation' );

			case 'intro_text':
				return __( "Vous disposez d'un délai de 14 jours pour vous rétracter de votre commande, sans avoir à motiver votre décision. Renseignez le formulaire ci-dessous : un accusé de réception horodaté vous sera envoyé immédiatement par e-mail.", '10gital-retractation' );

			case 'legal_notice':
				return __( "Certains biens et services sont exclus du droit de rétractation par l'article L.221-28 du code de la consommation (biens confectionnés sur mesure, biens périssables, enregistrements descellés, contenus numériques fournis avec votre accord préalable, etc.).", '10gital-retractation' );
		}

		$schema = self::schema();

		return isset( $schema[ $key ] ) ? (string) $schema[ $key ]['default'] : '';
	}

	/**
	 * Raccourci booléen pour les cases à cocher.
	 *
	 * @param string $key Clé sans préfixe.
	 * @return bool
	 */
	public static function is_on( $key ) {
		return 'yes' === self::get( $key );
	}

	/**
	 * Écrit une option.
	 *
	 * @param string $key   Clé sans préfixe.
	 * @param mixed  $value Valeur.
	 * @return void
	 */
	public static function set( $key, $value ) {
		update_option( self::PREFIX . $key, $value );
		unset( self::$cache[ $key ] );
	}

	/**
	 * Vide le cache mémoire des réglages.
	 *
	 * Utile après une écriture directe en base ou dans les bancs d'essai.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$cache = array();
	}

	/**
	 * Assainit une valeur soumise selon son type déclaré.
	 *
	 * @param mixed $value Valeur brute.
	 * @param array $field Définition du champ.
	 * @return mixed
	 */
	public static function sanitize( $value, array $field ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return $value ? 'yes' : 'no';

			case 'number':
				$number = (int) $value;
				$number = max( isset( $field['min'] ) ? (int) $field['min'] : 0, $number );

				if ( isset( $field['max'] ) ) {
					$number = min( (int) $field['max'], $number );
				}

				return $number;

			case 'page':
				return absint( $value );

			case 'color':
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );

				return $color ? $color : $field['default'];

			case 'select':
				return isset( $field['options'][ $value ] ) ? (string) $value : $field['default'];

			case 'order_statuses':
				$statuses = array_map( 'sanitize_key', (array) $value );

				return array_values( array_intersect( $statuses, array_keys( self::order_status_choices() ) ) );

			case 'ids':
				if ( is_string( $value ) ) {
					$value = preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
				}

				return array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) );

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Normalise une valeur relue depuis la base.
	 *
	 * @param mixed $value Valeur stockée.
	 * @param array $field Définition du champ.
	 * @return mixed
	 */
	private static function cast( $value, array $field ) {
		switch ( $field['type'] ) {
			case 'number':
			case 'page':
				return (int) $value;

			case 'order_statuses':
			case 'ids':
				return is_array( $value ) ? $value : $field['default'];

			case 'checkbox':
				return 'yes' === $value ? 'yes' : 'no';

			default:
				return $value;
		}
	}

	/**
	 * Statuts de commande sélectionnables (hors statuts terminaux du plugin).
	 *
	 * @return array<string,string>
	 */
	public static function order_status_choices() {
		$statuses = array();

		foreach ( wc_get_order_statuses() as $key => $label ) {
			$key = 'wc-' === substr( $key, 0, 3 ) ? substr( $key, 3 ) : $key;

			if ( in_array( $key, array( 'cancelled', 'refunded', 'failed', 'pending', 'checkout-draft', OrderStatus::KEY ), true ) ) {
				continue;
			}

			$statuses[ $key ] = $label;
		}

		return $statuses;
	}

	/**
	 * Adresses destinataires des notifications marchand.
	 *
	 * @return string[]
	 */
	public static function merchant_recipients() {
		$raw = (string) self::get( 'merchant_email' );

		if ( '' === trim( $raw ) ) {
			$raw = (string) get_option( 'admin_email' );
		}

		$emails = array_filter( array_map( 'trim', explode( ',', $raw ) ), 'is_email' );

		return array_values( $emails );
	}

	/**
	 * Une page de rétractation publiée est-elle configurée ?
	 *
	 * @return bool
	 */
	public static function has_page() {
		$page_id = (int) self::get( 'page_id' );

		return $page_id > 0 && 'publish' === get_post_status( $page_id );
	}

	/**
	 * Page de rétractation dans une langue donnée.
	 *
	 * Sur un site multilingue, le réglage ne mémorise qu'une page : on renvoie
	 * sa traduction dans la langue demandée, ou la page elle-même si elle n'est
	 * pas encore traduite.
	 *
	 * @param string|null $lang Code langue ; null = langue courante.
	 * @return int
	 */
	public static function page_id( $lang = null ) {
		return Multilingual::translate_id( (int) self::get( 'page_id' ), 'page', $lang );
	}

	/**
	 * URL publique de la page de rétractation.
	 *
	 * @param \WC_Order|null $order Commande à pré-sélectionner.
	 * @param string|null    $lang  Code langue ; null = langue courante.
	 * @return string
	 */
	public static function page_url( $order = null, $lang = null ) {
		$page_id = self::page_id( $lang );
		$url     = $page_id ? get_permalink( $page_id ) : home_url( '/' );

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		if ( $order instanceof \WC_Order ) {
			$url = add_query_arg(
				array(
					'commande' => rawurlencode( (string) $order->get_order_number() ),
					'cle'      => rawurlencode( (string) $order->get_order_key() ),
				),
				$url
			);
		}

		return $url;
	}
}
