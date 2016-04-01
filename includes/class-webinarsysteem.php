<?php

class WebinarSysteem {

    protected $_FILE_, $post_slug, $localkey_status, $plugin_version, $db_tablename_questions, $db_version;
    public static $lang_slug;

    /**
     * Don't edit, remove or comment anything in this file if you are not sure what you are doing.
     * It will cause to break the plugin or even the Wordpress website.
     */
    public function __construct( $theFile = null, $version = null ) {

        $this->_FILE_         = $theFile;
        $this->plugin_version = $version;
        $this->setAttributes( $theFile );

        add_action( 'init', array( $this, 'registerWebinars' ) );
        register_activation_hook( $this->_FILE_, array( $this, 'install' ) );
        add_filter( "manage_{$this->post_slug}_posts_columns", array( $this, 'webinarBrowseColumns' ) );
        add_action( "manage_{$this->post_slug}_posts_custom_column", array( $this, 'webinarBrowseCustomColumns' ), 10, 2 );

        add_action( 'init', array( $this, 'dismissAdminNotices' ) );
        add_action( 'init', array( 'WebinarSysteemAttendees', 'createCsvFile' ) );
        add_action( 'init', array( 'WebinarSysteemAttendees', 'createBccFile' ) );
        add_action( 'init', array( $this, 'goProRedirect' ) );
        add_action( 'init', array( $this, 'databaseMigrations' ) );
        add_action( 'init', array( 'WebinarSysteemQuestions', 'exportQuestionsAsCSV' ) );
        add_action( 'init', array( 'WebinarSysteemQuestions', 'exportQuestionsAsBCC' ) );

        add_action( 'admin_menu', array( $this, 'webinar_menut' ) );
        add_action( 'template_include', array( $this, 'myThemeRedirect' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'loadPluginScripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'loadFrontScripts' ), 1000 );
        add_action( 'admin_init', array( $this, 'registerOptions' ) );
        add_action( 'admin_init', array( $this, 'setPermissions' ) );
        add_action( 'admin_init', array( $this, 'resetInvalidKeyProperty' ) );
        add_action( 'wp_footer', array( $this, 'liveControlBar' ) );

        add_action( 'wp_ajax_saveQuestionAjax', array( $this, 'saveQuestionAjax' ) );
        add_action( 'wp_ajax_nopriv_saveQuestionAjax', array( $this, 'saveQuestionAjax' ) );
        add_action( 'wp_ajax_nopriv_retrieveQuestions', array( new WebinarSysteemQuestions(), 'retrieveQuestions' ) );
        add_action( 'wp_ajax_retrieveQuestions', array( new WebinarSysteemQuestions(), 'retrieveQuestions' ) );
        add_action( 'wp_ajax_quickchangestatus', array( $this, 'quickchangestatus' ) );
        add_action( 'wp_ajax_previewemails', array( 'WebinarSysteemPreviewMails', 'previewMails' ) );
        add_action( 'wp_ajax_remove_attendee', array( 'WebinarSysteemAttendees', 'removeAttendee' ) );
        add_action( 'wp_ajax_checkWebinarStatus', array( $this, 'ajaxCheckIfWebinarStatusLive' ) );
        add_action( 'wp_ajax_nopriv_checkWebinarStatus', array( $this, 'ajaxCheckIfWebinarStatusLive' ) );
        add_action( 'wp_ajax_nopriv_updateLastSeen', array( 'WebinarSysteemAjax', 'updateLastSeen' ) );
        add_action( 'wp_ajax_updateLastSeen', array( 'WebinarSysteemAjax', 'updateLastSeen' ) );
        add_action( 'wp_ajax_nopriv_getOnlineCount', array( 'WebinarSysteemAjax', 'getOnlineCount' ) );
        add_action( 'wp_ajax_getOnlineCount', array( 'WebinarSysteemAjax', 'getOnlineCount' ) );
        add_action( 'wp_ajax_dropQuestions', array( new WebinarSysteemQuestions(), 'dropQuestions' ) );
        add_action( 'wp_ajax_toggleLivePageAskQuestionForm', array( new WebinarSysteemQuestions(), 'toggleLivePageAskQuestionForm' ) );
        add_action( 'wp_ajax_dismissNotice', array( 'WebinarSysteemPromotionalNotices', 'dismiss' ) );

        add_action( 'wp_head', array( $this, 'webinarsysteem_ajaxurl' ) );

        add_action( 'wp_ajax_checkEnomailAPIkey', array( $this, 'checkEnomailAPIkey' ) );

        add_filter( 'meta_content', 'wptexturize' );
        add_filter( 'meta_content', 'convert_smilies' );
        add_filter( 'meta_content', 'convert_chars' );
        add_filter( 'meta_content', 'wpautop' );
        add_filter( 'meta_content', 'shortcode_unautop' );
        add_filter( 'meta_content', 'prepend_attachment' );

        add_action( 'after_setup_theme', array( $this, 'load_languages' ) );

        add_action( 'admin_init', array( $this, 'addDeleteWebinarHook' ) );

        new WebinarSysteemMetabox( $this->_FILE_, $this->post_slug );

        add_action( 'admin_action_wswebinar_duplicate_post_as_draft', array( $this, 'wswebinar_duplicate_post_as_draft' ) );
        add_filter( 'post_row_actions', array( $this, 'postRow' ), 10, 2 );

        new WebinarSysteemMails;
        register_activation_hook( $this->_FILE_, array( $this, 'setDefaultMailTemplates' ) );

        add_action( 'admin_notices', array( 'WebinarSysteemPromotionalNotices', 'valentine' ) );

        add_action( 'admin_bar_init', array( $this, 'my_admin_bar_init' ) );

    }


    public function quickchangestatus() {

        $webinar_id = (int) $_POST['webinar_id'];
        $stat       = $_POST['status'];
        if ( empty( $stat ) ) {
            die();
        }
        update_post_meta( $webinar_id, '_wswebinar_gener_webinar_status', $stat );
        echo json_encode( array( 'status' => true, 'updated' => $stat ) );
        die();

    }


    /**
     * Define ajax url for ajax requests.
     */
    public function webinarsysteem_ajaxurl() {

        ?>
        <script type="text/javascript">
            var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
        </script>
        <?php

    }


    public function resetInvalidKeyProperty() {

        if ( ! isset( $_GET['settings-updated'] ) || ! $_GET['settings-updated'] || empty( $_GET['page'] ) || $_GET['page'] !== 'wswbn-options' || $_GET['post_type'] !== $this->post_slug ) {
            return;
        }
        update_option( '_wswebinar_invalid_key', '0' );

    }


    /**
     * Check webinar status via AJAX
     */
    public function ajaxCheckIfWebinarStatusLive() {

        if ( empty( $_POST['post_id'] ) ) {
            echo json_encode( false );
            die();
        }
        $post_id = $_POST['post_id'];

        $is_recur                  = WebinarSysteem::isRecurring( $post_id );
        $_wswebinar_gener_duration = self::getWebinarDuration( $post_id );

        $attendee  = WebinarSysteemAttendees::getAttendee( $post_id );
        $webiner_t = WebinarSysteemMails::getWebinarTime( $post_id, $attendee );
        if ( $is_recur ) {
            if ( $webiner_t <= WebinarSysteem::populateDateTime( $post_id ) && WebinarSysteem::populateDateTime( $post_id ) <= ($webiner_t + $_wswebinar_gener_duration) ) {
                echo json_encode( true );
            } else {
                echo json_encode( false );
            }
            die();
        }


        $wbstatus = get_post_meta( $post_id, '_wswebinar_gener_webinar_status', true );

        if ( $wbstatus == 'liv' ) {
            echo json_encode( true );
        } else {
            echo json_encode( false );
        }

        die();

    }


    /**
     * Run migrations
     */
    public function databaseMigrations() {

        $db = new WebinarsysteemDbMigrations();
        $db->runMigrations();

    }


    /**
     * Load language files
     */
    public function load_languages() {

        load_plugin_textdomain( self::$lang_slug, false, dirname( plugin_basename( $this->_FILE_ ) ) . '/localization/' );

    }


    /**
     * Adds webinarDelete function to the delete_post hook if current use have rights.
     */
    public function addDeleteWebinarHook() {

        if ( current_user_can( 'delete_posts' ) ) {
            add_action( 'delete_post', array( $this, 'webinarDelete' ), 10 );
        }

    }


    /**
     * Deleting questions that belongs to the deleted webinar.
     */
    public function webinarDelete( $pid ) {

        //wp_die(get_post_type($pid));
        if ( get_post_type( $pid ) !== $this->post_slug ) {
            return;
        }
        global $wpdb;
        $tabl = $wpdb->prefix . $this->db_tablename_questions;
        if ( $wpdb->get_var( $wpdb->prepare( 'SELECT webinar_id FROM ' . $tabl . ' WHERE webinar_id = %d', $pid ) ) ) {
            return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $tabl . ' WHERE webinar_id = %d', $pid ) );
        }
        return true;

    }


    /**
     * Control the ajax request of adding question
     */
    public function saveQuestionAjax() {

        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_tablename_questions;

        $num = $wpdb->insert(
                $table_name, array(
            'name'       => $_POST['name'],
            'email'      => $_POST['email'],
            'question'   => $_POST['question'],
            'time'       => current_time( 'mysql' ),
            'webinar_id' => $_POST['webinar_id'],
                )
        );

        if ( $num == 1 ) {
            echo json_encode( array( 'status' => true, 'question' => str_replace( '\\', '', $_POST['question'] ), 'time' => date( 'Y-m-d H:i A', WebinarSysteem::populateDateTime( $_POST['webinar_id'] ) ) ) );
        } else {
            echo json_encode( array( 'status' => false ) );
        }
        die();

    }


    /**
     * Load admin scripts
     */
    public function loadPluginScripts() {

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core', false, array( 'jquery' ) );
        wp_enqueue_script( 'jquery-ui-tabs', false, array( 'jquery' ) );
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ) );
        wp_enqueue_script( 'jquery-ui-accordion', false, array( 'jquery' ) );
        wp_enqueue_script( 'wp-color-picker', false, array( 'jquery' ) );
        wp_enqueue_script( 'bootstrap-switch-script', plugin_dir_url( $this->_FILE_ ) . 'includes/js/bootstrap-switch.min.js' );
        wp_enqueue_script( 'webinar-systeem', plugin_dir_url( $this->_FILE_ ) . 'includes/js/webinar-systeem.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ) );
        wp_enqueue_script( 'ZeroClipboard_script', plugin_dir_url( $this->_FILE_ ) . 'includes/js/ZeroClipboard.min.js' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'webinar-admin', plugin_dir_url( $this->_FILE_ ) . 'includes/css/webinar-admin.css' );
        wp_enqueue_style( 'wswebinar-jquery-ui', plugin_dir_url( $this->_FILE_ ) . 'includes/css/jquery-ui.theme.min.css' );
        wp_enqueue_style( 'wswebinar-jquery-ui-structure', plugin_dir_url( $this->_FILE_ ) . 'includes/css/jquery-ui.structure.min.css' );
        wp_enqueue_style( 'webinar-admin-icons', plugin_dir_url( $this->_FILE_ ) . 'includes/css/icons.css' );
        wp_enqueue_style( 'bootstrap-switch-style', plugin_dir_url( $this->_FILE_ ) . 'includes/css/bootstrap-switch.min.css' );
        wp_enqueue_media();

    }


    public function loadFrontScripts() {

        $post_types = get_post_type( get_the_ID() );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core', false, array( 'jquery' ) );
        wp_enqueue_script( 'zero-clipboard', plugin_dir_url( $this->_FILE_ ) . 'includes/js/ZeroClipboard.min.js', array( 'jquery', 'jquery-ui-core' ) );
        wp_enqueue_script( 'add-event', plugin_dir_url( $this->_FILE_ ) . 'includes/js/addEvent.js', array( 'jquery', 'jquery-ui-core' ) );
        wp_enqueue_script( 'wpwebinarsystem', plugin_dir_url( $this->_FILE_ ) . 'includes/js/front-end.js', array( 'jquery', ) );
        wp_enqueue_script( 'flipclock', plugin_dir_url( $this->_FILE_ ) . 'includes/js/flipclock.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'google-platform', 'https://apis.google.com/js/platform.js', array( 'jquery' ) );
        wp_enqueue_style( 'flipclock', plugin_dir_url( $this->_FILE_ ) . 'includes/css/flipclock.css' );
        wp_enqueue_style( 'webinar', plugin_dir_url( $this->_FILE_ ) . 'includes/css/webinar.css' );

        if ( $post_types == 'wswebinars' && is_single() ) {
            wp_enqueue_style( 'bootstrap', plugin_dir_url( $this->_FILE_ ) . 'includes/css/bootstrap.css' );
        }

        wp_enqueue_style( 'ubuntu-font', 'http://fonts.googleapis.com/css?family=Ubuntu:300,400,500' );
        wp_enqueue_style( 'webinar-admin-icons', plugin_dir_url( $this->_FILE_ ) . 'includes/css/icons.css' );
        wp_enqueue_style( 'fontawesome-icons', plugin_dir_url( $this->_FILE_ ) . 'includes/css/font-awesome.min.css' );
        wp_enqueue_media();

    }


    /**
     * Register options needed for the options page.
     */
    public function registerOptions() {

        register_setting( 'wswebinar_options', '_wswebinar_licensekey' );
        register_setting( 'wswebinar_options', '_wswebinar_email_sentFrom' );
        register_setting( 'wswebinar_options', '_wswebinar_email_senderAddress' );
        register_setting( 'wswebinar_options', '_wswebinar_email_headerImg' );
        register_setting( 'wswebinar_options', '_wswebinar_email_footerTxt' );
        register_setting( 'wswebinar_options', '_wswebinar_email_baseCLR' );
        register_setting( 'wswebinar_options', '_wswebinar_email_bckCLR' );
        register_setting( 'wswebinar_options', '_wswebinar_email_bodyBck' );
        register_setting( 'wswebinar_options', '_wswebinar_email_bodyTXT' );
        register_setting( 'wswebinar_options', '_wswebinar_AdminEmailAddress' );
        register_setting( 'wswebinar_options', '_wswebinar_email_templatereset' );
        register_setting( 'wswebinar_options', '_wswebinar_24hrb4content' );
        register_setting( 'wswebinar_options', '_wswebinar_1hrb4content' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnstarted' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnreplay' );
        register_setting( 'wswebinar_options', '_wswebinar_24hrb4subject' );
        register_setting( 'wswebinar_options', '_wswebinar_1hrb4subject' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnstartedsubject' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnreplaysubject' );
        register_setting( 'wswebinar_options', '_wswebinar_1hrb4enable' );
        register_setting( 'wswebinar_options', '_wswebinar_24hrb4enable' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnstartedenable' );
        register_setting( 'wswebinar_options', '_wswebinar_wbnreplayenable' );
        register_setting( 'wswebinar_options', '_wswebinar_mailchimpapikey' );
        register_setting( 'wswebinar_options', '_wswebinar_enormailapikey' );
        $this->registerPermissionSettings();

    }


    /**
     * Add the WebinarSysteem admin menus.
     */
    function webinar_menut() {

        add_menu_page( __( 'WP WebinarSystem', self::$lang_slug ), __( 'WebinarSystem', self::$lang_slug ), '_wswebinar_createwebinars', 'edit.php?post_type=' . $this->post_slug, '', 'none', 59 );
        add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'Webinars', self::$lang_slug ), __( 'Webinars', self::$lang_slug ), 'manage_options', 'edit.php?post_type=' . $this->post_slug );
        if ( current_user_can( '_wswebinar_createwebinars' ) ) {
            add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'New webinar', self::$lang_slug ), __( 'New webinar', self::$lang_slug ), '_wswebinar_createwebinars', 'post-new.php?post_type=' . $this->post_slug );
        }
        add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'Attendee Lists', self::$lang_slug ), __( 'Attendee Lists', self::$lang_slug ), '_wswebinar_managesubscribers', 'wswbn-attendees', array( 'WebinarSysteemAttendees', 'wbn_attendees_list' ) );
        add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'Webinar Questions', self::$lang_slug ), __( 'Webinar Questions', self::$lang_slug ), '_wswebinar_changesettings', 'wswbn-questions', array( new WebinarSysteemQuestions(), 'showPage' ) );
        $options = new WebinarSysteemOptions( $this->localkey_status );
        add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'Settings', self::$lang_slug ), __( 'Settings', self::$lang_slug ), '_wswebinar_changesettings', 'wswbn-options', array( $options, 'wbn_gengeral_settings' ) );
        add_submenu_page( 'edit.php?post_type=' . $this->post_slug, __( 'Go Pro', self::$lang_slug ), '<span style="color:#090;">' . __( 'More Functionalities', self::$lang_slug ) . '</span>', '_wswebinar_changesettings', 'wswbn-gopro', array( $this, 'goProRedirect' ) );

    }


    /**
     * Set required class variables.
     */
    protected function setAttributes( $file = NULL ) {

        global $wpdb;
        if ( ! empty( $file ) ) {
            define( 'WSWEB_FILE', $this->_FILE_ );
            define( 'WSWEB_OPTION_PREFIX', '_wswebnar_' );
            define( 'WSWEB_DB_TABLE_PREFIX', $wpdb->prefix . 'wswebinars_' );
        }

        $this->post_slug              = 'wswebinars';
        $this->db_tablename_questions = 'wswebinars_questions';
        $this->db_version             = '1.0';
        self::$lang_slug              = '_wswebinar';

    }


    /**
     * Add post row links to Webinars
     */
    public function postRow( $actions, $post ) {

        if ( $post->post_type == $this->post_slug ) {
            $new = Array();
            foreach ( $actions as $key => $val ) {
                if ( $key == 'view' ) {
                    /* $new['settings'] = "<a href='#'>Settings</a>"; */
                    $questions        = new WebinarSysteemQuestions;
                    $new['questions'] = '<a href="edit.php?post_type=wswebinars&page=wswbn-questions&webinar_id=' . $post->ID . '">' . __( 'Questions', self::$lang_slug ) . '</a>';
                    /* $new['registrations'] = "<a href='#'>" . __('Registrations', self::$lang_slug) . "</a>";
                      $new['statistics'] = "<a href='#'>" . __('Statistics', self::$lang_slug) . "</a>";
                      $new['preview']    = "<a href='#'>" . __('Preview', self::$lang_slug) . "</a>"; */
                    if ( current_user_can( '_wswebinar_createwebinars' ) ) {
                        $new['duplicate'] = '<a href="admin.php?action=wswebinar_duplicate_post_as_draft&amp;post=' . $post->ID . '" title="' . __( 'Duplicate this Webinar' ) . '" rel="permalink">' . __( 'Duplicate', self::$lang_slug ) . '</a>';
                    }
                }
                $new[ $key ] = $val;
            }
            return $new;
        }
        return $actions;

    }


    /**
     * Adds columns to the Webinar browse page
     */
    public function webinarBrowseColumns( $columns ) {

        $new = array();
        foreach ( $columns as $key => $title ) {
            if ( $key == 'date' ) {
                $new['wswebinar_views']         = __( 'Views', self::$lang_slug );
                $new['wswebinar_registrations'] = __( 'Registrations', self::$lang_slug );
                $new['wswebinar_questions']     = __( 'Questions', self::$lang_slug );
                $new['wswebinar_status']        = __( 'Status', self::$lang_slug );
            }
            $new[ $key ] = $title;
        }
        return $new;

    }


    /**
     * Assign contents to the Webinar custom columns
     */
    public function webinarBrowseCustomColumns( $column, $post_id ) {

        switch ( $column ) {
            case 'wswebinar_views' :
                $views = get_post_meta( $post_id, '_wswebinar_views', true );
                echo empty( $views ) ? '-' : (int) $views;
                break;
            case 'wswebinar_registrations' :
                $subs = WebinarSysteemAttendees::getNumberOfSubscriptions( $post_id );
                echo empty( $subs ) ? '-' : '<a href="edit.php?post_type=wswebinars&page=wswbn-attendees&id=' . $post_id . '">' . $subs . '</a>';
                break;
            case 'wswebinar_questions' :
                $questions = new WebinarSysteemQuestions;
                $subsData  = $questions->getQuestionsFromDb( $post_id );
                $subs      = $subsData['num_of_rows'];
                echo empty( $subs ) ? '-' : '<a href="edit.php?post_type=wswebinars&page=wswbn-questions&webinar_id=' . $post_id . '">' . $subs . '</a>';
                break;
            case 'wswebinar_status':
                $saved_status = get_post_meta( $post_id, '_wswebinar_gener_webinar_status', true );
                ?>
                <select class="quickstatusupdater" webinar="<?php echo $post_id; ?>">
                    <option value="cou" <?php echo $saved_status == 'cou' ? 'selected' : ''; ?>>Countdown</option>
                    <option value="liv" <?php echo $saved_status == 'liv' ? 'selected' : ''; ?>>Live</option>
                    <option value="rep" <?php echo $saved_status == 'rep' ? 'selected' : ''; ?>>Replay</option>
                    <option value="clo" <?php echo $saved_status == 'clo' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <span class="wswaiticon" id="waitingIcon_<?php echo $post_id; ?>"><img src="<?php echo plugin_dir_url( $this->_FILE_ ); ?>includes/images/wait.GIF"></span>
                <span id="checkIcon_<?php echo $post_id; ?>" class="webi-class-check"></span>
                <?php
                break;
        }

    }


    /**
     * Register Webinar type
     */
    public function registerWebinars() {

        register_post_type( $this->post_slug, array(
            'labels' => array(
                'name'           => __( 'Webinars', self::$lang_slug ),
                'singular_name'  => __( 'Webinar', self::$lang_slug ),
                'name_admin_bar' => __( 'Webinar', self::$lang_slug ),
                'add_new'        => __( 'Add New Webinar', self::$lang_slug ),
                'add_new_item'   => __( 'Add New Webinar', self::$lang_slug ),
                'new_item'       => __( 'New Webinar', self::$lang_slug ),
                'edit_item'      => __( 'Edit Webinar', self::$lang_slug ),
                'view_item'      => __( 'View Webinar', self::$lang_slug ),
            ),
            'public'            => true,
            'has_archive'       => true,
            'show_in_menu'      => false,
            'rewrite'           => array( 'slug' => 'webinars', 'with_front' => false ),
            'show_in_admin_bar' => true,
            'supports'          => array( 'title', 'editor' ),
                )
        );

    }


    /**
     * Plugin installation hook function.
     */
    public function install() {

        $this->registerWebinars();
        flush_rewrite_rules();

        WebinarSysteemOptions::DoResetDefaults();

    }


    /**
     * Run the database migrations.
     */
    private function runDatabaseMigrations() {

        $curr_db_version = get_option( '_wswebnar_db_version', 'no' );

        if ( $curr_db_version == $this->db_version ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_tablename_questions;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name tinytext NOT NULL,
        email text NOT NULL,
        question text NOT NULL,
        webinar_id int(11) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option( '_wswebnar_db_version', $this->db_version );

    }


    /**
     * Saving registration form data.
     */
    private function saveRegFormData( $post_id, $inputName, $inputEmail, $inputDay = NULL, $inputTime = NULL, $inputTab = 'register' ) {

        $gener_air_type_saved   = get_post_meta( $post_id, '_wswebinar_gener_air_type', true );
        $gener_time_occur_saved = get_post_meta( $post_id, '_wswebinar_gener_time_occur', true );
        if ( empty( $inputName ) || empty( $inputEmail ) || (($gener_air_type_saved == 'rec' && $gener_time_occur_saved == 'recur') && (empty( $inputDay ) || empty( $inputTime ) )) ) {
            $errorUrl = '';
            if ( ($gener_air_type_saved == 'rec' && $gener_time_occur_saved == 'recur') && (empty( $inputDay ) || empty( $inputTime ) ) ) {
                $errorUrl = empty( $inputDay ) ? 'inputday' : 'inputtime';
            } elseif ( empty( $inputName ) || empty( $inputEmail ) ) {
                $errorUrl = empty( $inputName ) ? 'inputname' : 'inputemail';
            }

            if ( $inputTab == 'login' ) {
                $errorUrl = 'notregisterd';
            }

            $emailUrl = empty( $inputEmail ) ? '' : urlencode( $inputEmail );
            $nameUrl  = empty( $inputName ) ? '' : urlencode( $inputName );
            wp_redirect( get_permalink( $post_id ) . '?error=' . $errorUrl . '&inputemail=' . $emailUrl . '&inputname=' . $nameUrl );
            exit();
        }
        $rand               = rand( 888888, 889888 );
        $data               = Array();
        $data['name']       = trim( $inputName );
        $data['email']      = trim( $inputEmail );
        $data['time']       = current_time( date( 'Y-m-d H:i:s' ) );
        $data['exact_time'] = date( 'Y-m-d H:i:s', strtotime( '' . $inputDay . ' ' . $inputTime ) );
        $data['secretkey']  = $rand;
        $data['webinar_id'] = $post_id;

        $data['active'] = 1;
        //if (!empty($inputDay))
        $data['watch_day'] = $inputDay;
        //if (!empty($inputTime))
        $data['watch_time'] = $inputTime;
        WebinarSysteemAttendees::saveAttendie( $data, array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );
        $this->setUserSession( $rand, $data['email'] );


        $ws_webinar_sendmail = new WebinarSysteemMails;
        $ws_webinar_sendmail->SendMailtoAdmin( $inputName, $post_id, $inputEmail );
        $ws_webinar_sendmail->SendMailtoReader( $inputName, $inputEmail, $post_id );

        return array( 'success' => true );

    }


    private function setUserSession( $rand, $email ) {

        unset( $_COOKIE['_wswebinar_registered'] );
        unset( $_COOKIE['_wswebinar_registered_key'] );
        unset( $_COOKIE['_wswebinar_registered_email'] );
        setcookie( '_wswebinar_registered', '', time() - 3600, '/' );
        setcookie( '_wswebinar_registered_email', '', time() - 3600, '/' );
        setcookie( '_wswebinar_registered_key', '', time() - 3600, '/' );

        setcookie( '_wswebinar_registered', 'yes', time() + 60 * 60 * 24 * 30, '/' );
        setcookie( '_wswebinar_registered_email', $email, time() + 60 * 60 * 24 * 30, '/' );
        setcookie( '_wswebinar_registered_key', $rand, time() + 60 * 60 * 24 * 30, '/' );

        $_COOKIE['_wswebinar_registered']       = 'yes';
        $_COOKIE['_wswebinar_registered_key']   = $rand;
        $_COOKIE['_wswebinar_registered_email'] = $email;

    }


    /**
     * Redirect the template url to Webinar custom template.
     */
    public function myThemeRedirect( $original_template ) {

        global $wp;
        if ( ! isset( $wp->query_vars['post_type'] ) || $wp->query_vars['post_type'] !== $this->post_slug ) {
            return $original_template;
        }

        global $post;
        $already_regd    = false;
        $new_registd     = false;
        $return_template = '';
        $plugindir       = dirname( $this->_FILE_ );
        $webinar_status  = $this->checkWebinarStatusForNow( $post->ID );
        $is_Admin        = current_user_can( 'manage_options' );

        $registration_filename = $plugindir . '/' . 'includes/tmp-registration.php';
        $thankyou_filename     = $plugindir . '/' . 'includes/tmp-post.php';
        $live_filename         = $plugindir . '/' . 'includes/tmp-live.php';
        $countd_filename       = $plugindir . '/' . 'includes/tmp-countdown.php';
        $replay_filename       = $plugindir . '/' . 'includes/tmp-live.php';
        $closed_filename       = $plugindir . '/' . 'includes/tmp-closed.php';

        /**
         * Saving registration form data.
         */

        if ( isset( $_POST['webinarRegForm'] ) && $_POST['webinarRegForm'] == 'submit' ) {
            $inputEmail = $_POST['inputemail'];
            $inputName  = $_POST['inputname'];
            $inputTab   = $_POST['webinarTab'];
            $inputDay   = isset( $_POST['inputday'] ) ? $_POST['inputday'] : '';
            $inputTime  = isset( $_POST['inputtime'] ) ? $_POST['inputtime'] : '';
            if ( $this->checkUserAlreadyRegisteredForWebinar( $post->ID, $inputEmail ) ) {
                $already_regd = true;
            } else {
                $rettt       = $this->saveRegFormData( $post->ID, $inputName, $inputEmail, $inputDay, $inputTime, $inputTab );
                $new_registd = true;
            }
        } elseif ( $this->checkUserForSavedSessions() ) {
            $already_regd = true;
        }

        if ( $new_registd ) {
            $return_template = $thankyou_filename;
        }

        $is_recur                  = WebinarSysteem::isRecurring( $post->ID );
        $_wswebinar_gener_duration = self::getWebinarDuration( $post->ID );

        $attendee  = WebinarSysteemAttendees::getAttendee( $post->ID );
        $time_st   = WebinarSysteemMails::getWebinarTime( $post->ID, $attendee );
        $webiner_t = $time_st;




        if ( $already_regd && $webinar_status == 'cou' ) :
            $return_template = $countd_filename;
        elseif ( $already_regd && $webinar_status == 'liv' ):
            $return_template = $live_filename;
        elseif ( $already_regd && $webinar_status == 'clo' ):
            $return_template = $closed_filename;
        elseif ( $already_regd && $webinar_status == 'rep' ):
            $return_template = $replay_filename;
        endif;

        $one_time_register = get_post_meta( $post->ID, '_wswebinar_gener_onetimeregist', true );

        if ( $already_regd && $is_recur && $attendee->active == '1' ) {
            if ( $one_time_register !== '1' ) {
                if ( $webiner_t <= WebinarSysteem::populateDateTime( $post->ID ) && WebinarSysteem::populateDateTime( $post->ID ) <= ($webiner_t + $_wswebinar_gener_duration) ) {
                    $return_template = $live_filename;
                } elseif ( WebinarSysteem::populateDateTime( $post->ID ) <= $webiner_t ) {
                    $return_template = $countd_filename;
                } else {
                    WebinarSysteemAttendees::modifyAttendee( $attendee->id, array( 'active' => '0' ), array( '%d' ) );
                    $return_template = $registration_filename;
                }
            } elseif ( $webiner_t <= WebinarSysteem::populateDateTime( $post->ID ) && WebinarSysteem::populateDateTime( $post->ID ) <= ($webiner_t + $_wswebinar_gener_duration) ) {
                $return_template = $live_filename;
            } else {
                $return_template = $countd_filename;
            }
        }

        if ( ! $already_regd && ! $new_registd ) {
            $return_template = $registration_filename;
        }


        if ( intval( $attendee->active ) !== 1 ) {
            $return_template = $registration_filename;
        }

        /**
         * Instantly redirect admin to pages.
         */

        if ( $is_Admin ) {
            if ( $webinar_status == 'cou' ) :
                $return_template = $countd_filename;
            elseif ( $webinar_status == 'liv' ):
                $return_template = $live_filename;
            elseif ( $webinar_status == 'clo' ):
                $return_template = $closed_filename;
            elseif ( $webinar_status == 'rep' ):
                $return_template = $replay_filename;
            endif;
        }

        /**
         * Overwrite if force show available.
         */
        if ( ! empty( $_GET['force_show'] ) ) {
            switch ( $_GET['force_show'] ) {
                case 'live':
                    $return_template = $live_filename;
                    break;
                case 'register':
                    $return_template = $registration_filename;
                    break;
                case 'thankyou':
                    $return_template = $thankyou_filename;
                    break;
                case 'countdown':
                    $return_template = $countd_filename;
                    break;
                case 'closed':
                    $return_template = $closed_filename;
                    break;
                case 'replay':
                    $return_template = $replay_filename;
                    break;
                default:
                    break;
            }
        }

        $this->doThemeRedirect( $return_template );

    }


    /**
     * Remove admin bar in Webinar pages
     */
    public function my_admin_bar_init() {

        $post_types = get_post_type( get_the_ID() );
        if ( $post_types == 'wswebinars' && is_single() ) {
            remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
            remove_action( 'wp_head', '_admin_bar_bump_cb' );
        }

    }


    public function admin_notices() {

        ?>
        <div class="updated wswebinar_adnotice">

            <div class="closeIcon">
                <a href="#">Dismiss</a>
            </div>
        </div>
        <?php

    }


    private function doThemeRedirect( $url ) {

        global $post, $wp_query;
        if ( have_posts() ) {
            include( $url );
            die();
        } else {
            $wp_query->is_404 = true;
        }

    }


    public function dismissAdminNotices() {

        if ( empty( $_GET['wswebinar_ajax_dismiss'] ) || $_GET['wswebinar_ajax_dismiss'] !== '1' ) {
            return;
        }
        $userInfo_ = wp_get_current_user();
        add_user_meta( $userInfo_->ID, '_wswebinar_notdismiss', 'yes', true );
        header( 'Content-Type: application/json' );
        echo json_encode( true );
        exit();

    }


    public function assignAdminNotices() {

        $userInfo = wp_get_current_user();
        if ( get_user_meta( $userInfo->ID, '_wswebinar_notdismiss', true ) == 'yes' ) {
            return;
        }
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

    }


    /**
     * Set the Webinar views data
     */
    public static function setPostData( $post_id ) {

        $current = get_post_meta( $post_id, '_wswebinar_views', true );
        if ( empty( $current ) ) {
            $current = 0;
        }
        $new = 1 + (int) $current;
        update_post_meta( $post_id, '_wswebinar_views', $new );

    }


    private function checkUserForSavedSessions() {

        if ( isset( $_COOKIE['_wswebinar_registered'] ) && isset( $_COOKIE['_wswebinar_registered_key'] ) ) {
            $int = (int) $_COOKIE['_wswebinar_registered_key'];
            if ( $int < 889888 && $int > 888888 ) {
                return true;
            }
        }
        return false;

    }


    private function checkWebinarStatusForNow( $post_id ) {

        $getStatus = get_post_meta( $post_id, '_wswebinar_gener_webinar_status', true );
        if ( empty( $getStatus ) ) {
            $getStatus = 'cou';
        }
        return $getStatus;

    }


    function getWebinarStatusText( $webinar_id ) {

        $stat   = $this->checkWebinarStatusForNow( $webinar_id );
        $string = '';
        switch ( $stat ) {
            case 'cou':
                $string = 'Countdown';
                break;
            case 'liv':
                $string = 'Live';
                break;
            case 'rep':
                $string = 'Replay';
                break;
            case 'clo':
                $string = 'Closed';
                break;
            default:
                break;
        }
        return $string;

    }


    private function checkUserAlreadyRegisteredForWebinar( $post_id, $email ) {

        // todo Write a mysql check without looping whole attendies.
        $regists = WebinarSysteemAttendees::getAttendies( $post_id );
        foreach ( $regists as $arr ) {
            if ( $arr->email == trim( $email ) ) {
                $rand = rand( 888888, 889888 );
                $this->setUserSession( $rand, $email );
                return true;
            }
        }
        return false;

    }


    /**
     * Duplicate Webinar
     */
    function wswebinar_duplicate_post_as_draft() {

        global $wpdb;
        if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || ( isset( $_REQUEST['action'] ) && 'wswebinar_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
            wp_die( 'No Webinar to duplicate has been supplied!' );
        }

        /**
         * get the original post id
         */
        $post_id = (isset( $_GET['post'] ) ? $_GET['post'] : $_POST['post']);
        /**
         * and all the original post data then
         */
        $post = get_post( $post_id );

        /**
         * if you don't want current user to be the new post author,
         * then change next couple of lines to this: $new_post_author = $post->post_author;
         */
        $current_user    = wp_get_current_user();
        $new_post_author = $current_user->ID;

        /**
         * if post data exists, create the post duplicate
         */
        if ( isset( $post ) && $post != null ) {

            /**
             * new post data array
             */
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status'    => $post->ping_status,
                'post_author'    => $new_post_author,
                'post_content'   => $post->post_content,
                'post_excerpt'   => $post->post_excerpt,
                'post_name'      => $post->post_name,
                'post_parent'    => $post->post_parent,
                'post_password'  => $post->post_password,
                'post_status'    => 'draft',
                'post_title'     => 'Copy of ' . $post->post_title,
                'post_type'      => $post->post_type,
                'to_ping'        => $post->to_ping,
                'menu_order'     => $post->menu_order
            );

            /**
             * insert the post by wp_insert_post() function
             */
            $new_post_id = wp_insert_post( $args );

            /**
             * get all current post terms ad set them to the new post draft
             */
            $taxonomies = get_object_taxonomies( $post->post_type ); // returns array of taxonomy names for post type, ex array("category", "post_tag");
            foreach ( $taxonomies as $taxonomy ) {
                $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
                wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
            }

            /**
             * duplicate all post meta
             */
            $post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id" );
            if ( count( $post_meta_infos ) != 0 ) {
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ( $post_meta_infos as $meta_info ) {
                    $meta_key        = $meta_info->meta_key;
                    $meta_value      = addslashes( $meta_info->meta_value );
                    $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
                }
                $sql_query .= implode( ' UNION ALL ', $sql_query_sel );
                $wpdb->query( $sql_query );
            }


            /**
             * finally, redirect to the edit post screen for the new draft
             */
            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
            exit;
        } else {
            wp_die( 'Webinar creation failed, could not find original Webinar: ' . $post_id );
        }

    }


    public static function getDefaultMailTemplates() {

        $_wswebinar_wbn1hr_template = __( 'Hi', WebinarSysteem::$lang_slug ) . " [receiver-name]\r\n" . __( 'The webinar you signed up for starts in one hour. Below you will find the link to attend the webinar.', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Webinar name:', WebinarSysteem::$lang_slug ) . " [webinar-title]\r\n" . __( 'Date:', WebinarSysteem::$lang_slug ) . " [webinar-date]\r\n" . __( 'Time:', WebinarSysteem::$lang_slug ) . " [webinar-time]\r\n[webinar-link]\r\n" . __( 'See you then!', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Regards', WebinarSysteem::$lang_slug ) . ",\r\n" . get_bloginfo( 'name' );

        $_wswebinar_wbn24hr_template = __( 'Hi', WebinarSysteem::$lang_slug ) . " [receiver-name]\r\n" . __( 'This is a reminder for your upcoming webinar tomorrow. Below you will find the details of the webinar.', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Webinar name:', WebinarSysteem::$lang_slug ) . " [webinar-title]\r\n" . __( 'Date:', WebinarSysteem::$lang_slug ) . " [webinar-date]\r\n" . __( 'Time:', WebinarSysteem::$lang_slug ) . " [webinar-time]\r\n[webinar-link]\r\n" . __( 'See you then!', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Regards', WebinarSysteem::$lang_slug ) . ",\r\n" . get_bloginfo( 'name' );

        $_wswebinar_wbnstarted_template = __( 'We are going live now', WebinarSysteem::$lang_slug ) . " [receiver-name]\r\n" . __( 'The webinar you signed up for starts in one hour. Below you will find the link to attend the webinar.', WebinarSysteem::$lang_slug ) . "\r\n[webinar-link]\r\n\r\n" . __( 'See you later!', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Regards', WebinarSysteem::$lang_slug ) . ",\r\n" . get_bloginfo( 'name' );

        $_wswebinar_wbnreplay_template = __( 'Hi', WebinarSysteem::$lang_slug ) . " [receiver-name]\r\n\r\n" . __( 'Make sure to join the webinar via this link:', WebinarSysteem::$lang_slug ) . " [webinar-link]\r\n\r\n" . __( 'See you later!', WebinarSysteem::$lang_slug ) . "\r\n" . __( 'Regards', WebinarSysteem::$lang_slug ) . ",\r\n" . get_bloginfo( 'name' );

        return array( '1hr' => $_wswebinar_wbn1hr_template, '24hr' => $_wswebinar_wbn24hr_template, 'started' => $_wswebinar_wbnstarted_template, 'replay' => $_wswebinar_wbnreplay_template );

    }


    public function setDefaultMailTemplates() {

        $template = self::getDefaultMailTemplates();
        update_option( self::$lang_slug . '_1hrb4content', $template['1hr'] );
        update_option( self::$lang_slug . '_24hrb4content', $template['24hr'] );
        update_option( self::$lang_slug . '_wbnstarted', $template['started'] );
        update_option( self::$lang_slug . '_wbnreplay', $template['replay'] );

        $name        = get_bloginfo( 'name' );
        $admin_email = get_option( 'admin_email' );
        update_option( self::$lang_slug . '_email_sentFrom', $name );
        update_option( self::$lang_slug . '_email_senderAddress', $admin_email );

    }


    public static function getYoutubeIdFromUrl( $link ) {

        preg_match( "#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $link, $matches );
        if ( ! empty( $matches[0] ) ) {
            return $matches[0];
        }
        return false;

    }


    public function goProRedirect() {

        if ( ! isset( $_REQUEST['post_type'] ) || ! isset( $_REQUEST['page'] ) || $_REQUEST['post_type'] !== $this->post_slug || $_REQUEST['page'] !== 'wswbn-gopro' ) {
            return;
        }
        wp_redirect( 'http://www.wpwebinarsystem.com/?utm_source=freeplugin&utm_medium=menulink&utm_content=menulink&utm_campaign=freeplugin-gopro' );
        exit();

    }


    function registerPermissionSettings() {

        global $wp_roles;
        $roles = $wp_roles->get_names();
        foreach ( $roles as $roleSlug => $roleName ) {
            register_setting( 'wswebinar_options', '_wswebinar_createwebinars_' . $roleSlug );
            register_setting( 'wswebinar_options', '_wswebinar_managesubscribers_' . $roleSlug );
            register_setting( 'wswebinar_options', '_wswebinar_changesettings_' . $roleSlug );
        }

    }


    function setPermissions() {

        global $wp_roles;
        $roles = $wp_roles->get_names();
        foreach ( $roles as $roleSlug => $roleName ) {
            $role              = get_role( $roleSlug );
            $createWebinars    = $roleSlug == 'administrator' ? 'on' : get_option( '_wswebinar_createwebinars_' . $roleSlug );
            $manageSubscribers = $roleSlug == 'administrator' ? 'on' : get_option( '_wswebinar_managesubscribers_' . $roleSlug );
            $changeSettings    = $roleSlug == 'administrator' ? 'on' : get_option( '_wswebinar_changesettings_' . $roleSlug );
            //Add caps
            ! empty( $createWebinars ) ? $role->add_cap( '_wswebinar_createwebinars' ) : $role->remove_cap( '_wswebinar_createwebinars' );
            ! empty( $manageSubscribers ) ? $role->add_cap( '_wswebinar_managesubscribers' ) : $role->remove_cap( '_wswebinar_managesubscribers' );
            ! empty( $changeSettings ) ? $role->add_cap( '_wswebinar_changesettings' ) : $role->remove_cap( '_wswebinar_changesettings' );
        }

    }


    public static function webinarAirType( $webinar_id ) {

        $gener_air_type_saved = get_post_meta( $webinar_id, '_wswebinar_gener_air_type', true );
        if ( empty( $gener_air_type_saved ) ) {
            $gener_air_type_saved = 'live';
        }
        return $gener_air_type_saved;

    }


    public static function isRecurring( $webinar_id ) {

        $air_type               = self::webinarAirType( $webinar_id );
        $gener_time_occur_saved = get_post_meta( $webinar_id, '_wswebinar_gener_time_occur', true );
        if ( ! empty( $gener_time_occur_saved ) && $air_type == 'rec' && $gener_time_occur_saved == 'recur' ) {
            return true;
        }
        return false;

    }


    public static function getRecurringInstances( $webinar_id ) {

        $gener_rec_days_array = array();
        $gener_rec_days_saved = get_post_meta( $webinar_id, '_wswebinar_gener_rec_days', true );
        if ( ! empty( $gener_rec_days_saved ) ) {
            $gener_rec_days_array = json_decode( $gener_rec_days_saved, true );
        }

        $gener_rec_times_saved = get_post_meta( $webinar_id, '_wswebinar_gener_rec_times', true );
        $gener_rec_times_array = array();
        if ( ! empty( $gener_rec_times_saved ) ) {
            $gener_rec_times_array = json_decode( $gener_rec_times_saved, true );
        }

        return array( 'days' => $gener_rec_days_array, 'times' => $gener_rec_times_array );

    }


    /**
     * Return the plugin information
     */
    public static function plugin_info( $needs = false ) {

        $plugin_info = get_plugin_data( WSWEB_FILE );
        return ($needs == false ? $plugin_info : $plugin_info[ $needs ]);

    }


    /**
     * Return recurring time integers
     */
    public static function getRecurringInstancesInTime( $webinar_id ) {

        $array = array();
        $ins   = self::getRecurringInstances( $webinar_id );
        if ( count( $ins['days'] ) < 1 || count( $ins['times'] ) < 1 ) {
            return $array;
        }
        foreach ( $ins['days'] as $day ) {
            foreach ( $ins['times'] as $time ) {
                array_push( $array, array( 'day' => $day, 'time' => $time, 'datetime' => strtotime( WebinarSysteemMetabox::getWeekDayArray( $day ) . ' ' . $time ) ) );
            }
        }
        return $array;

    }


    public static function getWebinarTime( $webinar_id, $attendee = NULL ) {

        if ( ! self::isRecurring( $webinar_id ) ) {
            return get_post_meta( $webinar_id, '_wswebinar_gener_time', true );
        }
        if ( ! empty( $attendee ) ) {
            $one_time_reg = get_post_meta( $webinar_id, '_wswebinar_gener_onetimeregist', true );
            if ( floatval( $one_time_reg ) <> 1 ) {
                return strtotime( $attendee->exact_time );
            }
            $duration = WebinarSysteem::getWebinarDuration( $webinar_id );

            $last_time_instance = strtotime( '' . WebinarSysteemMetabox::getWeekDayArray( $attendee->watch_day ) . ' ' . $attendee->watch_time );
            if ( WebinarSysteem::populateDateTime( $post->ID ) <= $last_time_instance + $duration ) {
                return $last_time_instance;
            }
            return strtotime( 'next ' . WebinarSysteemMetabox::getWeekDayArray( $attendee->watch_day ) . ' ' . $attendee->watch_time );
        }
        return false;

    }


    public static function getWebinarDuration( $webinar_id ) {

        $_wswebinar_gener_duration = get_post_meta( $webinar_id, '_wswebinar_gener_duration', true );
        if ( empty( $_wswebinar_gener_duration ) ) {
            $_wswebinar_gener_duration = 3600;
        }
        $_wswebinar_gener_duration = floatval( $_wswebinar_gener_duration );
        return $_wswebinar_gener_duration;

    }


    public function liveControlBar() {

        global $is_live_page;
        if ( ! isset( $is_live_page ) || ! $is_live_page || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        return WebinarSysteemViews::get_header_bar( $this );

    }


    public static function getTimezone() {

        $timezone;
        $xyz;
        $gmt_opt = get_option( 'gmt_offset' );
        $hourint = (int) $gmt_opt;
        $xyz     = ($hourint > 0 ? '+' : '');
        $float   = $gmt_opt - intval( $gmt_opt );
        if ( $float == 0 ) {
            $timezone = '00';
        } elseif ( $float == 0.5 ) {
            $timezone = '30';
        } elseif ( $float == 0.75 ) {
            $timezone = '45';
        } else {
            $timezone = '00';
        }
        $timezone_string = get_option( 'timezone_string' );
        return $xyz . $hourint . ':' . $timezone . (empty( $timezone_string ) ? '' : " ($timezone_string)");

    }


    public static function getTimezoneIdentifiers() {

        $time_zones              = timezone_identifiers_list();
        $time_to_use             = 'now'; # just a dummy time
        $time_zone_abbreviations = array();
        foreach ( $time_zones as $time_zone_id ) {
            $dateTime = new DateTime( $time_to_use );
            $dateTime->setTimeZone( new DateTimeZone( $time_zone_id ) );
            $abbreviation                             = $dateTime->format( 'T' );
            $time_zone_abbreviations[ $time_zone_id ] = $time_zone_id . ' - ' . $abbreviation;
        }
        return $time_zone_abbreviations;

    }


    /**
     * Uplift current time to webinar's timezone.
     *
     * @param  integer $webinar_id
     * @return integer             UNIX Timestamp
     */
    public static function populateDateTime( $webinar_id ) {

        $time_zone = get_post_meta( $webinar_id, '_wswebinar_timezoneidentifier', true );

        if ( $time_zone ) {
            try {
                $defTimeZone = date_default_timezone_get();
                $date        = date_create( date( 'Y-m-d H:i:s' ), timezone_open( $defTimeZone ) );
                date_timezone_set( $date, timezone_open( $time_zone ) );
                $formattedDate = date_format( $date, 'Y-m-d H:i:s' );

                return strtotime( $formattedDate );
            } catch (Exception $e) {
                return current_time( 'timestamp' );
            }
        } else {
            return current_time( 'timestamp' );
        }

    }


    public function formatTimezone( $timeZone ) {

        $sign     = ($timeZone >= 0) ? '+' : '-';
        $timeZone = str_replace( array( '+', '-' ), array( ' ', ' ' ), $timeZone );
        $init     = $timeZone * 60 * 60;
        $hours    = floor( $init / 3600 );
        $minutes  = floor( ($init / 60) % 60 );
        return $sign . $hours . (($minutes > 0) ? '.' . $minutes : '' );

    }


    /**
     * Get timezone abbreviation from name.
     *
     * @param  string $timezone_id Ex: "Asia/Colombo"
     * @return string              Timezone abbr or FALSE on failure.
     */
    public static function getTimezoneAbbreviation( $timezone_id ) {

        if ( ! $timezone_id ) {
            return false;
        }

        $time_zones = timezone_identifiers_list();

        foreach ( $time_zones as $time_zone_id ) {
            if ( $time_zone_id != $timezone_id ) {
                continue;
            }

            $dateTime = new DateTime();
            $dateTime->setTimeZone( new DateTimeZone( $timezone_id ) );
            return strtoupper( $dateTime->format( 'T' ) );
        }

    }


}
