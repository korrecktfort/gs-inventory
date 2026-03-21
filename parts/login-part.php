<?php 

//login form 
if ( ! is_user_logged_in() ) {
    $args = array(
        'redirect' => home_url(), // Redirect to homepage after login
        'form_id' => 'loginform',
        'label_username' => __( 'Username' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Log In' ),
        'remember' => true,
    );
    wp_login_form( $args );
}
?>