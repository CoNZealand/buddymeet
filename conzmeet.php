<?php
/**
Plugin Name: Conzmeet
Plugin URI:
Description: Adds a meeting room with video and audio capabilities to BuddyPress. Powered by <a target="_blank" href="https://jitsi.org/"> Jitsi Meet </a>.
Version: 1.8.1
Requires at least: 4.6.0
Tags: buddypress
License: GPL V2
Author: Themis Dakanalis <tdakanalis@cytech,gr>
Author URI: https://www.cytechmobile.com/employee/themis-dakanalis/
Text Domain: conzmeet
Domain Path: /languages
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Conzmeet' ) ) :
/**
 * Main Conzmeet Class
 */
class Conzmeet {

    const USER_ROOMS_PREFIX = 'conzmeet_user_room_';
    const ROOM_MEMBERS_PREFIX = 'conzmeet_room_members_';

	private static $instance;

	/**
	 * Required BuddyPress version for the plugin.
	 *
	 * @package Conzmeet
	 * @since 1.0.0
	 *
	 * @var  string
	 */
	public static $required_bp_version = '2.5.0';

	/**
	 * BuddyPress config.
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $bp_config = array();

	/**
	 * Main ConzMeet Instance
	 *
	 * Avoids the use of a global
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 *
	 * @uses ConzMeet::setup_globals() to set the global needed
	 * @uses ConzMeet::includes() to include the required files
	 * @uses ConzMeet::setup_actions() to set up the hooks
	 * @return object the instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new ConzMeet;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}


	private function __construct() { /* Do nothing here */ }

	/**
	 * Some usefull vars
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 *
	 * @uses plugin_basename()
	 * @uses plugin_dir_path() to build ConzMeet plugin path
	 * @uses plugin_dir_url() to build ConzMeet plugin url
	 */
	private function setup_globals() {
		$this->version    = '1.8.1';

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = apply_filters( 'conzmeet_plugin_basename', plugin_basename( $this->file ) );
		$this->plugin_dir = apply_filters( 'conzmeet_plugin_dir_path', plugin_dir_path( $this->file ) );
		$this->plugin_url = apply_filters( 'conzmeet_plugin_dir_url',  plugin_dir_url ( $this->file ) );

		// Includes
		$this->includes_dir = apply_filters( 'conzmeet_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'conzmeet_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );

		// Languages
		$this->lang_dir  = apply_filters( 'conzmeet_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );

		// ConzMeet slug and name
		$this->conzmeet_slug = apply_filters( 'conzmeet_slug', 'conzmeet' );
		$this->conzmeet_name = apply_filters( 'conzmeet_name', 'ConzMeet' );

		$this->domain           = 'conzmeet';
		$this->errors           = new WP_Error(); // Feedback
	}

	/**
	 * Ιncludes the needed files
	 *
	 * @package Conzmeet
	 * @since 1.0.0
	 *
	 * @uses is_admin() for the settings files
	 */
	private function includes() {
		require( $this->includes_dir . 'conzmeet-actions.php'         );
		require( $this->includes_dir . 'conzmeet-functions.php'       );

		//TODO CHECK ADMIN INTERFACES
		/*if( is_admin() ){
			require( $this->includes_dir . 'admin/conzmeet-admin.php' );
		}*/
	}


	/**
	 * The main hook used is bp_include to load our custom BuddyPress component
     *
     * @package ConzMeet
	 * @since 1.0.0
	 */
	private function setup_actions() {
		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'conzmeet_activation'   );
		add_action( 'deactivate_' . $this->basename, 'conzmeet_deactivation' );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'bp_loaded',  array( $this, 'load_textdomain' ) );
		add_action( 'bp_include', array( $this, 'load_component'  ) );

        add_action( 'bp_setup_nav', array($this, 'set_default_groups_nav'), 20 );

        add_filter( 'conzmeet_custom_settings', array($this, 'conzmeet_post_settings'), 9 );

        add_shortcode( 'conzmeet', array($this, 'add_shortcode'));

		do_action_ref_array( 'conzmeet_after_setup_actions', array( &$this ) );
	}

    public function set_default_groups_nav() {
        bp_core_new_nav_default (
            array(
                'parent_slug'       => conzmeet(),
                'subnav_slug'       => 'members',
                'screen_function'   => 'conzmeet_screen_members'
            )
        );
    }

	/**
	 * Loads the translation
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 * @uses get_locale()
	 * @uses load_textdomain()
	 */
	public function load_textdomain() {
		$locale = apply_filters( 'conzmeet_load_textdomain_get_locale', get_locale(), $this->domain );
		$mofile = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );
		$mofile_global = WP_LANG_DIR . '/conzmeet/' . $mofile;

		if ( ! load_textdomain( $this->domain, $mofile_global ) ) {
			load_plugin_textdomain( $this->domain, false, basename( $this->plugin_dir ) . '/languages' );
		}
	}

    public function enqueue_styles(){
        if(function_exists( 'buddypress' )) {
            global $bp;
            if (conzmeet_get_slug() === $bp->current_action) {
                $sub_action = conzmeet_get_current_action();
                if ('members' === $sub_action) {
                    //Enqueue the jquery autocomplete library
                    wp_enqueue_style('conzmeet-invites-css', conzmeet_get_plugin_url() . "assets/css/invites.css", '', conzmeet_get_version(), 'screen');
                }
            }
        }

        wp_enqueue_style('conzmeet-css', conzmeet_get_plugin_url() . "assets/css/conzmeet.css", '', conzmeet_get_version(), 'screen');
    }

    public function enqueue_scripts(){
        $load_scripts = false;
        if(is_page() || is_single()){
            $post = get_post();
            if($post && has_shortcode($post->post_content, conzmeet_get_slug())){
                $load_scripts = true;
            } else if( function_exists( 'buddypress' )){
                global $bp;
                if(conzmeet_get_slug() === $bp->current_action){
                    $load_scripts = true;
                }
            }
        }

        if($load_scripts){
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script( 'conzmeet-invites-js', conzmeet_get_plugin_url()  . 'assets/js/invites.js', array( 'jquery-ui-autocomplete' ) );
            wp_localize_script('conzmeet-invites-js', 'args', array(
                'ajaxurl' =>  admin_url( 'admin-ajax.php', 'relative' )
            ));

            $handle = 'conzmeet-jitsi-js';
            wp_enqueue_script( $handle, "https://meet.jit.si/external_api.js", array(), conzmeet_get_version(), true);
        }
    }

	/**
	 * Finally, Load the component
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 */
	public function load_component() {
		if ( self::bail() ) {
			add_action( self::$bp_config['network_admin'] ? 'network_admin_notices' : 'admin_notices', array( $this, 'warning' ) );
		} else {
			require( $this->includes_dir . 'conzmeet-component-class.php' );
		}
	}

	/**
	 * Checks BuddyPress version
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 */
	public static function version_check() {
		// taking no risk
		if ( ! defined( 'BP_VERSION' ) )
			return false;

		return version_compare( BP_VERSION, self::$required_bp_version, '>=' );
	}

	/**
	 * Checks if your plugin's config is similar to BuddyPress
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 */
	public static function config_check() {
		/**
		 * blog_status    : true if your plugin is activated on the same blog
		 * network_active : true when your plugin is activated on the network
		 * network_status : BuddyPress & your plugin share the same network status
		 */
		self::$bp_config = array(
			'blog_status'    => false,
			'network_active' => false,
			'network_status' => true,
			'network_admin'  => false
		);

		$buddypress = false;

		if ( function_exists( 'buddypress' ) ) {
			$buddypress = buddypress()->basename;
		}

		if ( $buddypress && get_current_blog_id() == bp_get_root_blog_id() ) {
			self::$bp_config['blog_status'] = true;
		}

		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

		// No Network plugins
		if ( empty( $network_plugins ) )
			return self::$bp_config;

		$conzmeet = plugin_basename( __FILE__ );

		// Looking for ConzMeet
		$check = array( $conzmeet );

		// And for BuddyPress if set
		if ( ! empty( $buddypress ) )
			$check = wp_parse_args($check, $buddypress);

		// Are they active on the network ?
		$network_active = array_diff( $check, array_keys( $network_plugins ) );

		// If result is 1, your plugin is network activated
		// and not BuddyPress or vice & versa. Config is not ok
		if ( count( $network_active ) == 1 )
			self::$bp_config['network_status'] = false;

		self::$bp_config['network_active'] = isset( $network_plugins[ $conzmeet ] );

		// We need to know if the BuddyPress is network activated to choose the right
		// notice ( admin or network_admin ) to display the warning message.
		self::$bp_config['network_admin']  = ! empty( $buddypress ) && isset( $network_plugins[ $buddypress ] );

		return self::$bp_config;
	}

	/**
	 * Bail if BuddyPress config is different than this plugin
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 */
	public static function bail() {
		$retval = false;

		$config = self::config_check();

		if ( ! self::version_check() || ! $config['blog_status'] || ! $config['network_status'] )
			$retval = true;

		return $retval;
	}

	/**
	 * Display a warning message to admin
	 *
	 * @package ConzMeet
	 * @since 1.0.0
	 */
	public function warning() {
		$warnings = $resolve = array();

		if ( ! self::version_check() ) {
			$warnings[] = sprintf( esc_html__( 'ConzMeet requires at least version %s of BuddyPress.', 'conzmeet' ), self::$required_bp_version );
			$resolve[]  = sprintf( esc_html__( 'Upgrade BuddyPress to at least version %s', 'conzmeet' ), self::$required_bp_version );
		}

		if ( ! empty( self::$bp_config ) ) {
			$config = self::$bp_config;
		} else {
			$config = self::config_check();
		}

		if ( ! $config['blog_status'] ) {
			$warnings[] = esc_html__( 'ConzMeet requires to be activated on the blog where BuddyPress is activated.', 'conzmeet' );
			$resolve[]  = esc_html__( 'Activate ConzMeet on the same blog than BuddyPress', 'conzmeet' );
		}

		if ( ! $config['network_status'] ) {
			$warnings[] = esc_html__( 'ConzMeet and BuddyPress need to share the same network configuration.', 'conzmeet' );
			$resolve[]  = esc_html__( 'Make sure ConzMeet is activated at the same level than BuddyPress on the network', 'conzmeet' );
		}

		if ( ! empty( $warnings ) ) {
			// Give some more explanations to administrator
			if ( is_super_admin() ) {
				$deactivate_link = ! empty( $config['network_active'] ) ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
				$deactivate_link = '<a href="' . esc_url( $deactivate_link ) . '">' . esc_html__( 'deactivate', 'conzmeet' ) . '</a>';
				$resolve_message = '<ol><li>' . sprintf( __( 'You should %s ConzMeet', 'conzmeet' ), $deactivate_link ) . '</li>';

				foreach ( (array) $resolve as $step ) {
					$resolve_message .= '<li>' . $step . '</li>';
				}

				if ( $config['network_status'] && $config['blog_status']  )
					$resolve_message .= '<li>' . esc_html__( 'Once done try to activate ConzMeet again.', 'conzmeet' ) . '</li></ol>';

				$warnings[] = $resolve_message;
			}

		?>
		<div id="message" class="error">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php esc_html_e($warning); ?></p>
			<?php endforeach ; ?>
		</div>
		<?php
		}
	}

    /**
     * Registers the conzmeet shortcode
     * @param $params
     */
    public function add_shortcode($params) {
        global $wp;
        $params = apply_filters('conzmeet_custom_settings', $params);
        $params = wp_parse_args($params, conzmeet_default_settings());
        $hangoutMessage = __("The video call has been ended.", "conzmeet");

        $script = sprintf(
            $this->get_jitsi_init_template(),
            $params['domain'],
            $params['settings'],
            $params['toolbar'],
            $params['room'],
            $params['width'],
            $params['height'],
            $params['parent_node'],
            $params['start_audio_only'] === "true" || $params['start_audio_only'] === true ? 1 : 0,
            $params['default_language'],
            $params['film_strip_only'] === "true" || $params['film_strip_only'] === true? 1 : 0,
            $params['background_color'],
            $params['show_watermark'] === "true" || $params['show_watermark'] === true? 1 : 0,
            $params['show_brand_watermark'] === "true" || $params['show_brand_watermark'] === true? 1 : 0,
            $params['brand_watermark_link'],
            $params['disable_video_quality_label'] === "true" || $params['disable_video_quality_label'] === true ? 1 : 0,
            isset($params['user']) ? $params['user'] : '',
            $params['subject'],
            isset($params['avatar']) ? $params['avatar'] : '',
            isset($params['password']) ? $params['password'] : '',
            $hangoutMessage
        );

        if(wp_doing_ajax()){
            //when initializing the meet via an ajax request we need to return the script to the caller to
            //add it in the page
            echo '<script>' . $script . '</script>';
        } else {
            $handle = "conzmeet-jitsi-js";
            wp_add_inline_script($handle, $script);
        }

        return '<div id="meet"></div>';
    }

    public function get_jitsi_init_template(){
        return 'const public_domain = "meet.jit.si";
            const domain = "%1$s";
            const settings = "%2$s"; 
            const toolbar = "%3$s"; 
            const options = {
                roomName: "%4$s",
                width: "%5$s",
                height: %6$d,
                parentNode: document.querySelector("%7$s"),
                configOverwrite: {
                    startAudioOnly: %8$b === 1,
		    startWithAudioMuted: true,
                    defaultLanguage: "%9$s",
                },
                interfaceConfigOverwrite: {
                    filmStripOnly: %10$b === 1,
                    DEFAULT_BACKGROUND: "%11$s",
                    DEFAULT_REMOTE_DISPLAY_NAME: "",
                    SHOW_JITSI_WATERMARK: %12$b === 1,
                    SHOW_WATERMARK_FOR_GUESTS: %12$b === 1,
                    SHOW_BRAND_WATERMARK: %13$b === 1,
                    BRAND_WATERMARK_LINK: "%14$s",
                    LANG_DETECTION: true,
                    CONNECTION_INDICATOR_DISABLED: false,
                    VIDEO_QUALITY_LABEL_DISABLED: %15$b === 1,
                    SETTINGS_SECTIONS: settings.split(","),
                    TOOLBAR_BUTTONS: toolbar.split(","),
                },
            };
            const api = new JitsiMeetExternalAPI(domain, options);
            api.executeCommand("displayName", "%16$s");
            api.executeCommand("subject", "%17$s");
            api.executeCommand("avatarUrl", "%18$s");
            api.on("videoConferenceJoined", () => {
	    	api.executeCommand("toggleChat","");
                if(domain === public_domain && "%19$s"){
                    api.executeCommand("password", "%19$s");
                }
            });
            /** 
             * If we are on a self hosted Jitsi domain, we need to become moderators before setting a password
             * Issue: https://community.jitsi.org/t/lock-failed-on-jitsimeetexternalapi/32060
             */
            api.addEventListener("participantRoleChanged", (event) => {
                if (domain !== public_domain && "%19$s" && event.role === "moderator"){
                    api.executeCommand("password", "%19$s");
                }
            });
            api.on("readyToClose", () => {
                 api.dispose();
                 jQuery("#meet").addClass("hangoutMessage").html("%20$s");
            });

            window.api = api;';
    }

    public function conzmeet_post_settings($settings){
        $extra = array();
        if(is_page() || is_single()) {
            $post = get_post();
            if ($post && has_shortcode($post->post_content, conzmeet_get_slug())) {
                $user = wp_get_current_user();
                if($user->exists()) {
                    if (!array_key_exists('user', $settings)) {
                        $extra['user'] = $user->display_name;
                    }
                    if (!array_key_exists('avatar', $settings)) {
                        $extra['avatar'] = get_avatar_url($user->ID);
                    }
                }
            }
        }

        return wp_parse_args($extra, $settings);
    }
}

function conzmeet() {
	return conzmeet::instance();
}

conzmeet();

/**
 * ConzMeet unistall Hook registration
 */
register_uninstall_hook( __FILE__, 'conzmeet_uninstall' );

endif;

