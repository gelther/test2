<?php

/**
 * Description of WebinarSysteemPromotionalNotices
 * Show notices for seasonal pro-plugin promotions.
 */
class WebinarSysteemPromotionalNotices {

    public static $notice_slug = 'ws-notice-';

    /**
     * Created for Valentine.
     *
     * Will be displayed till 1st of February to 16th of February 2016 only to NEW USERS.
     */
    static function valentine() {

        $user_id   = get_current_user_id();
        $meta      = get_user_meta( $user_id, self::$notice_slug . 'valentine', true );
        $rightTime = time() < strtotime( '17 Feb 2016' );
        if ( ! empty( $meta ) || ! $rightTime ) {
            return;
        }
        global $current_user;
        get_currentuserinfo();
        add_user_meta( $user_id, self::$notice_slug . 'valentine', NULL, true );
        ?>
        <div class="ws-notice">
            <div class="notice-image-container">
                <img src="<?php echo plugins_url( './images/webinarbot-valentine-hearteyes.png', __FILE__ ); ?>" height="100">
            </div>
            <div class="notice-text">
                <?php _e( "Hey <strong>$current_user->display_name</strong>, thank you for using my plugin. Want more functionalities like automated, recurring and paid webinars? Then download <strong>WP WebinarSystem Pro during this Valentine's celebration with 30% off!</strong> This promotion is only for you as a user of this free version of WP WebinarSystem.<br>Use coupon <strong>lovewebinarbot</strong> during checkout.<br>Love, Webinarbot", WebinarSysteem::$lang_slug ); ?>
            </div>
            <div class="notice-button-container">
                <a class="button button-primary" href="http://www.wpwebinarsystem.com/?utm_source=pluginfreeversion&utm_medium=notification&utm_content=valentineweekend&utm_campaign=valentinenotification" target="_blank"><?php _e('Yes, download Pro!', WebinarSysteem::$lang_slug); ?></a><br/>
                <div class="welcome-panel-close" data-notice-slug="valentine">
                    Dismiss
                </div>
            </div>
        </div>
        <?php

    }


    /**
     * Ajax call to dismiss a given notice
     */
    static function dismiss() {

        $user_id = get_current_user_id();
        $status  = update_user_meta( $user_id, self::$notice_slug . $_POST['notice_slug'], true );
        echo json_encode( array( 'status' => $status ) );
        wp_die();

    }


}
