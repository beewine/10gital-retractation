<?php
/**
 * Mise à jour automatique depuis les publications GitHub.
 *
 * Aucune donnée du site n'est transmise : le plugin effectue une simple
 * requête GET publique vers l'API GitHub pour connaître la dernière version
 * publiée, et compare le numéro de version. La vérification est désactivable
 * dans les réglages ou via la constante RET10G_DISABLE_UPDATER.
 *
 * @package Dixgital\Retractation
 */

namespace Dixgital\Retractation\Core;

use Dixgital\Retractation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Client de mise à jour GitHub.
 */
class Updater {

	/**
	 * Clé du transitoire de cache.
	 */
	private const CACHE_KEY = 'ret10g_update_payload';

	/**
	 * Durée du cache.
	 */
	private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Accroche les hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( defined( 'RET10G_DISABLE_UPDATER' ) && RET10G_DISABLE_UPDATER ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_directory' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'flush_cache' ), 10, 2 );
	}

	/**
	 * Ajoute notre plugin à la liste des mises à jour disponibles.
	 *
	 * @param mixed $transient Transitoire des mises à jour.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_release();

		if ( ! $release || version_compare( $release['version'], RET10G_VERSION, '<=' ) ) {
			if ( isset( $transient->no_update ) ) {
				$transient->no_update[ RET10G_BASENAME ] = $this->build_response( $release ?: array(), false );
			}

			return $transient;
		}

		$transient->response[ RET10G_BASENAME ] = $this->build_response( $release, true );

		return $transient;
	}

	/**
	 * Alimente la fenêtre « Voir les détails ».
	 *
	 * @param mixed  $result Résultat courant.
	 * @param string $action Action demandée.
	 * @param object $args   Arguments.
	 * @return mixed
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || RET10G_SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();

		if ( ! $release ) {
			return $result;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( RET10G_FILE, false, false );

		return (object) array(
			'name'           => $data['Name'],
			'slug'           => RET10G_SLUG,
			'version'        => $release['version'],
			'author'         => $data['Author'],
			'homepage'       => 'https://github.com/' . RET10G_REPO,
			'download_link'  => $release['package'],
			'requires'       => $data['RequiresWP'],
			'requires_php'   => $data['RequiresPHP'],
			'last_updated'   => $release['published_at'],
			'sections'       => array(
				'description' => wpautop( esc_html( $data['Description'] ) ),
				'changelog'   => $this->format_changelog( $release['notes'] ),
			),
			'banners'        => array(),
			'external'       => true,
		);
	}

	/**
	 * Renomme le dossier extrait si l'archive ne porte pas le bon nom.
	 *
	 * @param string       $source        Dossier extrait.
	 * @param string       $remote_source Dossier temporaire parent.
	 * @param \WP_Upgrader $upgrader      Instance d'installation.
	 * @param array        $args          Arguments.
	 * @return string|\WP_Error
	 */
	public function fix_source_directory( $source, $remote_source, $upgrader, $args = array() ) {
		global $wp_filesystem;

		if ( empty( $args['plugin'] ) || RET10G_BASENAME !== $args['plugin'] ) {
			return $source;
		}

		$expected = trailingslashit( $remote_source ) . RET10G_SLUG;

		if ( untrailingslashit( $source ) === $expected || ! $wp_filesystem ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $expected ) ) {
			return trailingslashit( $expected );
		}

		return $source;
	}

	/**
	 * Vide le cache après une mise à jour.
	 *
	 * @param \WP_Upgrader $upgrader Instance.
	 * @param array        $data     Données de l'opération.
	 * @return void
	 */
	public function flush_cache( $upgrader, $data ) {
		if ( isset( $data['action'], $data['type'] ) && 'update' === $data['action'] && 'plugin' === $data['type'] ) {
			delete_transient( self::CACHE_KEY );
		}
	}

	/**
	 * Construit la réponse attendue par WordPress.
	 *
	 * @param array $release   Données de publication.
	 * @param bool  $available Une mise à jour est disponible.
	 * @return object
	 */
	private function build_response( array $release, $available ) {
		return (object) array(
			'id'          => 'github.com/' . RET10G_REPO,
			'slug'        => RET10G_SLUG,
			'plugin'      => RET10G_BASENAME,
			'new_version' => $available && isset( $release['version'] ) ? $release['version'] : RET10G_VERSION,
			'url'         => 'https://github.com/' . RET10G_REPO,
			'package'     => $available && isset( $release['package'] ) ? $release['package'] : '',
			'tested'      => isset( $release['tested'] ) ? $release['tested'] : '',
			'icons'       => array(),
			'banners'     => array(),
		);
	}

	/**
	 * Récupère la dernière publication, en cache.
	 *
	 * @return array{version:string,package:string,notes:string,published_at:string}|null
	 */
	private function get_release() {
		if ( ! Settings::is_on( 'check_updates' ) ) {
			return null;
		}

		$cached = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) ) {
			return $cached ? $cached : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . RET10G_REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache court en cas d'échec, pour ne pas marteler l'API.
			set_transient( self::CACHE_KEY, array(), HOUR_IN_SECONDS );

			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_transient( self::CACHE_KEY, array(), HOUR_IN_SECONDS );

			return null;
		}

		$version = ltrim( (string) $body['tag_name'], 'vV' );
		$package = '';

		foreach ( (array) ( $body['assets'] ?? array() ) as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && RET10G_SLUG . '.zip' === $asset['name'] ) {
				$package = (string) $asset['browser_download_url'];
				break;
			}
		}

		if ( '' === $package ) {
			$package = (string) ( $body['zipball_url'] ?? '' );
		}

		$release = array(
			'version'      => $version,
			'package'      => $package,
			'notes'        => (string) ( $body['body'] ?? '' ),
			'published_at' => (string) ( $body['published_at'] ?? '' ),
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Met en forme les notes de publication Markdown.
	 *
	 * @param string $notes Notes brutes.
	 * @return string
	 */
	private function format_changelog( $notes ) {
		$notes = trim( (string) $notes );

		if ( '' === $notes ) {
			return '<p>' . esc_html__( 'Aucune note de publication.', '10gital-retractation' ) . '</p>';
		}

		$lines  = preg_split( '/\R/', $notes );
		$output = '';
		$in_ul  = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			if ( preg_match( '/^[-*]\s+(.*)$/', $line, $matches ) ) {
				if ( ! $in_ul ) {
					$output .= '<ul>';
					$in_ul   = true;
				}

				$output .= '<li>' . esc_html( $matches[1] ) . '</li>';
				continue;
			}

			if ( $in_ul ) {
				$output .= '</ul>';
				$in_ul   = false;
			}

			if ( preg_match( '/^#{1,6}\s+(.*)$/', $line, $matches ) ) {
				$output .= '<h4>' . esc_html( $matches[1] ) . '</h4>';
				continue;
			}

			$output .= '<p>' . esc_html( $line ) . '</p>';
		}

		if ( $in_ul ) {
			$output .= '</ul>';
		}

		return $output;
	}
}
