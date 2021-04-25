<?php
/**
 * Abstract class to extend LDLMS_Model to LDLMD_Model_Post.
 *
 * @package LearnDash
 * @since 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Post' ) ) && ( class_exists( 'LDLMS_Model' ) ) ) {
	class LDLMS_Model_Post extends LDLMS_Model {

		/**
		 * Post ID of Model.
		 *
		 * @since 3.2.0
		 *
		 * @var integer $id.
		 */
		protected $post_id = null;

		/**
		 * Post Type of Model.
		 *
		 * @since 3.3.0
		 *
		 * @var string $post_type WP_Post post_type.
		 */
		protected $post_type = null;

		/**
		 * Post Object of Model.
		 *
		 * @since 3.2.0
		 *
		 * @var object $post WP_Post instance.
		 */
		protected $post = null;

		/**
		 * Post Settings of Model.
		 *
		 * @since 3.2.0
		 *
		 * @var array $post_settings Array of Post Settings.
		 */
		protected $settings = null;

		/**
		 * Settings loaded for Model.
		 *
		 * @since 3.2.0
		 *
		 * @var boolean $settings_loaded Set to true when settings have been loaded.
		 */
		protected $settings_loaded = false;

		/**
		 * Settings changed for Model.
		 *
		 * @since 3.4.0
		 *
		 * @var boolean $settings_changed Set to true when settings have changed.
		 */
		protected $settings_changed = false;

		/**
		 * Private constructor for class.
		 *
		 * @since 3.2.0
		 */
		private function __construct() {
		}

		/**
		 * Get class post_type
		 *
		 * @return string.
		 */
		public function get_post_type() {
			return $this->post_type;
		}

		/**
		 * Load the Model Settings.
		 *
		 * @since 3.2.0
		 * @param boolean $force Control reloading of settings.
		 *
		 * @return boolean Status of settings loaded class var.
		 */
		public function load_settings( $force = false ) {
			if ( ( ! empty( $this->post ) ) && ( ( true !== $this->settings_loaded ) || ( true === $force ) ) ) {
				$this->settings_loaded = true;
				$this->settings        = array();

				$meta = get_post_meta( $this->post_id, '_' . $this->post_type, true );
				if ( ( ! empty( $meta ) ) && ( is_array( $meta ) ) ) {
					foreach ( $meta as $k => $v ) {
						$this->settings[ str_replace( $this->post_type . '_', '', $k ) ] = $v;
					}
				}
			}

			return $this->settings_loaded;
		}

		/**
		 * Save the Model Settings.
		 *
		 * @since 3.2.0
		 * @param boolean $force Control reloading of settings.
		 *
		 * @return boolean Status of settings loaded class var.
		 */
		public function save_settings( $force = false ) {
			$return = false;

			if ( ( true === $force ) || ( true === $this->settings_changed ) ) {
				$meta = array();
				foreach ( $this->settings as $k => $v ) {
					$meta[ '_' . $this->post_type . '_' . $k ] = $v;
				}

				$return = update_post_meta( $this->post_id, '_' . $this->post_type, $meta );
			}

			return $return;
		}

		/**
		 * Get Setting.
		 *
		 * @since 3.4.0
		 *
		 * @param string $setting_key           Setting key to retreive. Blank to retreive all settings.
		 * @param string $setting_default_value Setting default value is setting_key is not set.
		 * @param string $force                 Control reloading of settings.
		 *
		 * @return mixed Setting value.
		 */
		public function get_setting( $setting_key = '', $setting_default_value = '', $force = false ) {
			$setting_value = $setting_default_value;

			$this->load_settings( $force );

			if ( ! empty( $setting_key ) ) {
				if ( isset( $this->settings[ $setting_key ] ) ) {
					$setting_value = $this->settings[ $setting_key ];
				}
			} else {
				$setting_value = $this->settings;
			}

			return $setting_value;
		}

		/**
		 * Set Setting.
		 *
		 * @since 3.4.0
		 *
		 * @param string $setting_key           Setting key to retreive. Blank to retreive all settings.
		 * @param string $setting_default_value Setting default value is setting_key is not set.
		 * @param string $update                Control saving postmeta after of settings change.
		 *
		 * @return mixed Setting value.
		 */
		public function set_setting( $setting_key = '', $setting_value = '', $update = true ) {
			$this->load_settings( $force );

			if ( ! empty( $setting_key ) ) {
				$this->settings[ $setting_key ] = $setting_value;
				$this->settings_changed         = true;

				//$update
			}

			return $setting_value;
		}

		// End of functions.
	}
}
