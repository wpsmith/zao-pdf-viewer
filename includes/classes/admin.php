<?php
/**
 * CMB2 Theme Options
 * @version 0.1.0
 */
class ZPDF_Viewer_Admin {

	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = ZPDFV_OPT_KEY;

	/**
	 * Options page metabox id
	 * @var string
	 */
	private $metabox_id = 'zpdfv_option_metabox';

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Holds an instance of the object
	 *
	 * @var ZPDF_Viewer_Admin
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	protected function __construct() {
		// Set our title
		$this->title = __( 'Zao PDF Viewer', 'zpdfv' );
	}

	/**
	 * Returns the ZPDF_Viewer_Admin object
	 *
	 * @return ZPDF_Viewer_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );

		add_action( 'cmb2_render_text_number', array( $this, 'render_text_number' ), 10, 5 );
		add_filter( 'cmb2_sanitize_text_number', array( $this, 'sanitize_text_number' ), 10, 2 );

		add_action( 'register_shortcode_ui', array( $this, 'shortcode_ui' ) );
	}

	public function is_admin() {
		if ( isset( $_GET['page'] ) && $this->key === $_GET['page'] ) {
			return true;
		}
		return false;
	}

	/**
	* Register our setting to WP
	* @since  0.1.0
	*/
	public function register_setting() {
		register_setting( $this->key, $this->key );
	}

	/**
	* Add menu options page
	* @since 0.1.0
	*/
	public function add_options_page() {
		$this->options_page = add_options_page( $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );

		// Include CMB CSS in the head to avoid FOUT
		if ( $this->is_admin() ) {
			add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
		}
	}

	/**
	* Admin page markup. Mostly handled by CMB2
	* @since  0.1.0
	*/
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	public function add_options_page_metabox() {
		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Default PDF viewer height ratio', 'zpdfv' ),
			'desc'    => '%',
			'id'      => 'height',
			'type'    => 'text_number',
			'default' => '56.25',
			'after'   => '<p class="cmb2-metabox-description">'. __( 'Enter the ratio percentage. Default (56.25%) is 16/9 ratio.', 'zpdfv' ) .'</p>',
		) );

	}

	public function render_text_number( $field, $escaped_value, $object_id, $object_type, $type ) {
		echo $type>input( array(
			'class' => 'cmb2-text-small',
			'type'  => 'number',
			'desc' => $type>_desc(),
		) );
	}

	// sanitize the field
	public function sanitize_text_number( $null, $new ) {
		$new = preg_replace( "/[^0-9]/", "", $new );

		return $new;
	}


	/**
	 * Shortcode UI setup for the pdfviewer shortcode.
	 *
	 * It is called when the Shortcake action hook `register_shortcode_ui` is called.
	 *
	 * @since 0.1.0
	 */
	public function shortcode_ui() {
		/*
		 * Define the Shortcode UI arguments.
		 */
		$shortcode_ui_args = array(
			'label' => esc_html__( 'Zao PDF Viewer', 'zpdfv' ),
			'listItemImage' => 'dashicons-media-document',
			'attrs' => array(
				array(
					'label'       => esc_html__( 'Select PDF', 'zpdfv' ),
					'attr'        => 'id',
					'type'        => 'attachment',
					'libraryType' => array( 'application/pdf' ),
					'addButton'   => esc_html__( 'Select PDF', 'zpdfv' ),
					'frameTitle'  => esc_html__( 'Select PDF', 'zpdfv' ),
				),
				array(
					'label'       => esc_html__( 'Optional PDF URL', 'zpdfv' ),
					'description' => esc_html__( 'Add URL if PDF is on this server, but not in the media library.', 'zpdfv' ),
					'attr'        => 'url',
					'type'        => 'text',
					'encode'      => false,
					'meta'        => array(
						'data-test'   => 1,
					),
				),
				array(
					'label'       => esc_html__( 'PDF viewer height ratio', 'zpdfv' ),
					'description' => esc_html__( 'Enter the ratio percentage. Default (56.25%) is 16/9 ratio.', 'zpdfv' ),
					'attr'        => 'height',
					'type'        => 'number',
					'meta'        => array(
						'placeholder' => '56.25',
					),
				),
			),
		);

		shortcode_ui_register_for_shortcode( ZPDFV_SHORTCODE_TAG, $shortcode_ui_args );
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}
		throw new Exception( 'Invalid property: ' . $field );
	}
}
