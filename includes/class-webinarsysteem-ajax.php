<?php

class WebinarSysteemAjax {

    private static function returnError( $message = 'Unknown' ) {

        header( 'Content-Type:application/json' );
        echo json_encode( array( 'status' => false, 'message' => $message ) );
        wp_die();

    }


    private static function returnData( $data ) {

        header( 'Content-Type:application/json' );
        echo json_encode( array( 'status' => true, 'data' => $data ) );
        wp_die();

    }


    public static function updateLastSeen() {

        $web_id   = $_POST['webinar_id'];
        $attendee = WebinarSysteemAttendees::getAttendee( $web_id );
        if ( isset( $attendee->id ) && $attendee->id > 0 ) {
            if ( WebinarSysteemAttendees::modifyAttendee( $attendee->id, array( 'last_seen' => gmdate( 'Y-m-d H:i:s' ) ), array( '%s' ) ) ) {
                self::returnData( array( 'status' => true ) );
            }
        }
        self::returnError();

    }


    public static function getOnlineCount() {

        $web_id = $_POST['webinar_id'];
        if ( ! isset( $web_id ) || empty( $web_id ) ) {
            self::returnError();
        }
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';
        $query = "SELECT name FROM $table WHERE webinar_id = $web_id AND last_seen BETWEEN '" . date( 'Y-m-d H:i:s', strtotime( '-18 seconds' ) ) . "' AND '" . date( 'Y-m-d H:i:s' ) . "'";
        $data  = $wpdb->get_results( $query );

        self::returnData( array( 'count' => count( $data ), 'attendees' => $data ) );

    }


}
