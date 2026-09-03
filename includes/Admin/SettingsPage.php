<?php
/**
 * Écran de réglages, généré à partir du schéma de Settings.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Admin;

use Dixgital\Retractation\Core\Install;
use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Réglages du plugin.
 */
class SettingsPage {

	/**
	 * Identifiant de la page.
	 */
	public const SLUG = 'ret10g-reglages';

	/**
	 * Groupe d'options.
	 */
	private const GROUP = 'ret10g_settings';

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 21 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . RET10G_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Ajoute l'entrée de menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Réglages de la rétractation', '10gital-retractation' ),
			__( 'Rétractation — réglages', '10gital-retractation' ),
			DeclarationsPage::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Liens rapides dans la liste des extensions.
	 *
	 * @param string[] $links Liens existants.
	 * @return string[]
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
				esc_html__( 'Réglages', '10gital-retractation' )
			)
		);

		return $links;
	}

	/**
	 * Déclare chaque option auprès de l'API des réglages.
	 *
	 * @return void
	 */
	public function register_settings() {
		foreach ( Settings::schema() as $key => $field ) {
			register_setting(
				self::GROUP,
				Settings::PREFIX . $key,
				array(
					'sanitize_callback' => static function ( $value ) use ( $field ) {
						return Settings::sanitize( $value, $field );
					},
					'default'           => $field['default'],
				)
			);
		}
	}

	/**
	 * Rend l'écran.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( DeclarationsPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.', '10gital-retractation' ) );
		}

		$schema   = Settings::schema();
		$sections = Settings::sections();
		$page_id  = (int) Settings::get( 'page_id' );
		?>
		<div class="wrap ret10g-admin ret10g-admin--settings">
			<h1><?php esc_html_e( 'Rétractation — réglages', '10gital-retractation' ); ?></h1>

			<?php if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) : ?>
				<div class="notice notice-error">
					<p>
						<?php esc_html_e( 'Aucune page de rétractation n\'est configurée. Créez une page contenant le code court [retractation], puis sélectionnez-la ci-dessous.', '10gital-retractation' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="ret10g-card ret10g-card--intro">
				<p>
					<?php esc_html_e( 'Ce plugin met en œuvre la fonction de rétractation exigée à compter du 19 juin 2026 par l\'article 11 bis de la directive (UE) 2011/83 modifiée, transposé en France aux articles L.221-21 et D.221-5 du code de la consommation.', '10gital-retractation' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Il ne collecte aucune donnée d\'usage, n\'affiche aucune publicité et n\'appelle aucun service tiers, à l\'exception facultative de la vérification des mises à jour sur GitHub.', '10gital-retractation' ); ?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<?php foreach ( $sections as $section_key => $section_label ) : ?>
					<h2><?php echo esc_html( $section_label ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<?php foreach ( $schema as $key => $field ) : ?>
								<?php if ( $field['section'] !== $section_key ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( Settings::PREFIX . $key ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
										</label>
									</th>
									<td>
										<?php $this->render_field( $key, $field ); ?>
										<?php if ( ! empty( $field['desc'] ) ) : ?>
											<p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>

			<div class="ret10g-card">
				<h2><?php esc_html_e( 'Codes courts et bloc', '10gital-retractation' ); ?></h2>
				<ul>
					<li><code>[retractation]</code> — <?php esc_html_e( 'formulaire complet, à placer sur la page dédiée.', '10gital-retractation' ); ?></li>
					<li><code>[bouton_retractation]</code> — <?php esc_html_e( 'bouton renvoyant vers cette page, à placer où vous le souhaitez.', '10gital-retractation' ); ?></li>
					<li><?php esc_html_e( 'Bloc « Bouton de rétractation » disponible dans l\'éditeur.', '10gital-retractation' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Rend un champ selon son type déclaré.
	 *
	 * @param string $key   Clé sans préfixe.
	 * @param array  $field Définition.
	 * @return void
	 */
	private function render_field( $key, array $field ) {
		$name  = Settings::PREFIX . $key;
		$value = Settings::get( $key );

		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>',
					esc_attr( $name ),
					checked( 'yes', $value, false ),
					esc_html__( 'Activer', '10gital-retractation' )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%1$s" value="%2$s" min="%3$s" max="%4$s" step="1" class="small-text">',
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? 0 ) ),
					esc_attr( (string) ( $field['max'] ?? 9999 ) )
				);
				break;

			case 'color':
				printf(
					'<input type="color" id="%1$s" name="%1$s" value="%2$s"> <code>%2$s</code>',
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="4" class="large-text">%2$s</textarea>',
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';

				foreach ( $field['options'] as $option_key => $option_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $option_key ),
						selected( $option_key, $value, false ),
						esc_html( $option_label )
					);
				}

				echo '</select>';
				break;

			case 'page':
				wp_dropdown_pages(
					array(
						'name'              => $name,
						'id'                => $name,
						'selected'          => (int) $value,
						'show_option_none'  => __( '— Aucune —', '10gital-retractation' ),
						'option_none_value' => '0',
					)
				);

				if ( (int) $value ) {
					printf(
						' <a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
						esc_url( (string) get_permalink( (int) $value ) ),
						esc_html__( 'Voir la page', '10gital-retractation' )
					);
				} else {
					printf(
						' <a href="%1$s">%2$s</a>',
						esc_url( wp_nonce_url( add_query_arg( 'ret10g_create_page', 1 ), 'ret10g_create_page' ) ),
						esc_html__( 'Créer la page automatiquement', '10gital-retractation' )
					);
				}
				break;

			case 'order_statuses':
				$selected = (array) $value;

				foreach ( Settings::order_status_choices() as $status_key => $status_label ) {
					printf(
						'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s> %4$s</label>',
						esc_attr( $name ),
						esc_attr( $status_key ),
						checked( in_array( $status_key, $selected, true ), true, false ),
						esc_html( $status_label )
					);
				}
				break;

			case 'ids':
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="12, 34, 56">',
					esc_attr( $name ),
					esc_attr( implode( ', ', array_map( 'intval', (array) $value ) ) )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text">',
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
		}
	}

	/**
	 * Crée la page à la demande depuis l'écran de réglages.
	 *
	 * @return void
	 */
	public static function maybe_create_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['ret10g_create_page'] ) || ! current_user_can( DeclarationsPage::CAPABILITY ) ) {
			return;
		}

		check_admin_referer( 'ret10g_create_page' );

		Install::ensure_page();

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}
}
