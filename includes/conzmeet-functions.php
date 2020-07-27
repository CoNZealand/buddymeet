<?php
/**
 * ConzMeet functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * What is the version of the plugin.
 *
 * @uses conzmeet()
 * @return string the version of the plugin
 */
function conzmeet_get_version() {
	return conzmeet()->version;
}

/**
 * Gets the slug of the plugin
 *
 * @uses conzmeet() to get plugin's globals
 * @uses buddypress() to get directory pages global settings
 * @return string the slug
 */
function conzmeet_get_slug() {
    $slug = function_exists( 'buddypress' ) && isset( buddypress()->pages->conzmeet->slug ) ?
        buddypress()->pages->conzmeet->slug : conzmeet()->conzmeet_slug ;

    return apply_filters( 'conzmeet_get_slug', $slug );
}

/**
 * Gets the name of the plugin
 *
 * @uses conzmeet() to get plugin's globals
 * @uses buddypress() to get directory pages global settings
 * @return string the name
 */
function conzmeet_get_name() {
    $name = function_exists( 'buddypress' ) && isset( buddypress()->pages->conzmeet->slug ) ?
        buddypress()->pages->conzmeet->title : conzmeet()->conzmeet_name ;

    return apply_filters( 'conzmeet_get_name', $name );
}


/**
 * What is the path to the includes dir ?
 *
 * @uses  conzmeet()
 * @return string the path
 */
function conzmeet_get_includes_dir() {
	return conzmeet()->includes_dir;
}

/**
 * What is the path of the plugin dir ?
 *
 * @uses  conzmeet()
 * @return string the path
 */
function conzmeet_get_plugin_dir() {
	return conzmeet()->plugin_dir;
}

/**
 * What is the url to the plugin dir ?
 *
 * @uses  conzmeet()
 * @return string the url
 */
function conzmeet_get_plugin_url() {
	return conzmeet()->plugin_url;
}

/**
 * Handles Plugin activation
 *
 * @uses bp_core_update_directory_page_ids() to update the BuddyPres component pages ids
 */
function conzmeet_activation() {
    if(function_exists('buddypress')) {
        conzmeet_register_custom_email_templates();
    }

    update_option('_conzmeet_enabled', true);

    do_action( 'conzmeet_activation' );
}

/**
 * Handles plugin deactivation
 */
function conzmeet_deactivation() {
	update_option('_conzmeet_enabled', false);

	do_action( 'conzmeet_deactivation' );
}

/**
 * Handles plugin uninstall
 */
function conzmeet_uninstall() {
    update_option('_conzmeet_enabled', false);
}

/**
 * Checks plugin version against db and updates
 *
 * @uses conzmeet_get_version() to get ConzMeet plugin version
 */
function conzmeet_check_version() {
	// Bail if config does not match what we need
	if ( conzmeet::bail() ) {
		return;
	}

	// Finally upgrade plugin version
	update_option( '_conzmeet_version', conzmeet_get_version() );
}
add_action( 'conzmeet_admin_init', 'conzmeet_check_version' );

function conzmeet_default_settings(){
    return array(
        'enabled' => true,
        'meet_members_enabled' => true,
        'room' => '',
        'domain' => 'meet.jit.si',
        'password' => '',
        'film_strip_only' => false,
        'width' => '100%',
        'height' => 700,
        'start_audio_only' => false,
        'parent_node' => '#meet',
        'default_language' => 'en',
        'background_color' => '#464646',
        'show_watermark' => true,
        'show_brand_watermark' => false,
        'brand_watermark_link' => '',
        'settings' => 'devices,language',
        'disable_video_quality_label' => false,
        'toolbar' => 'microphone,camera,hangup,desktop,fullscreen,profile,chat,recording,settings,raisehand,videoquality,tileview'
    );
}

function conzmeet_groups_get_groupmeta($group_id, $meta_key, $default){
    $value = groups_get_groupmeta( $group_id, $meta_key, true);

    if($value === false || $value === ""){
        $value = $default;
    }

    return $value === "1" ? true : ($value === "0" ? false : $value);
}

function conzmeet_groups_update_groupmeta($group_id, $meta_key, $default){
    $value = isset($_POST[$meta_key]) ? sanitize_text_field($_POST[$meta_key]) : $default;
    groups_update_groupmeta( $group_id, $meta_key, $value );
}

function conzmeet_get_current_action(){
    $bp = buddypress();
    $action = 'members';

    $actions = bp_action_variables();
    if(!empty($actions)){
        $action = $actions[0];
    }
    $bp->action_variables = array($action);
    return $action;
}

function conzmeet_get_current_user_room(){
    $group_id = bp_get_group_id();
    $user_id = get_current_user_id();
    $room_id = conzmeet_get_current_user_room_from_path();

    if($room_id){
        return conzmeet_get_user_room_info($group_id, $user_id, $room_id);
    }
    return false;
}

function conzmeet_get_current_user_room_from_path(){
    global $wp;

    $path_params = explode('members/', wp_parse_url($wp->request)['path']);
    if(count($path_params) > 1) {
        return $path_params[1];
    }
    return false;
}

function conzmeet_register_custom_email_templates() {

    // Do not create if it already exists and is not in the trash
    $post_exists = post_exists( '[{{{site.name}}}] You have a new meet request in group: {{group.name}}"' );

    if ( $post_exists != 0 && get_post_status( $post_exists ) == 'publish' ) {
        return;
    }

    // Create post object
    $my_post = array(
        /* translators: do not remove {} brackets or translate its contents. */
        'post_title'   => __( '[{{{site.name}}}] You have a new meet request in group: {{group.name}}', 'conzmeet' ),
        /* translators: do not remove {} brackets or translate its contents. */
        'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join a meet as member of the group &quot;{{group.name}}&quot;. <a href=\"{{{meet.url}}}\">\nGo here to enter the meet</a> or <a href=\"{{{group.url}}}\">visit the group</a> to learn more.", 'conzmeet' ),
        /* translators: do not remove {} brackets or translate its contents. */
        'post_excerpt' => __( "{{inviter.name}} has invited you to join a meet as member of the group \"{{group.name}}\". To join the meet, visit: {{{meet.url}}}. To learn more about the group, visit: {{{group.url}}}. To view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'conzmeet' ),
        'post_status'   => 'publish',
        'post_type' => bp_get_email_post_type() // this is the post type for emails
    );

    // Insert the email post into the database
    $post_id = wp_insert_post( $my_post );

    if ( $post_id ) {
        // add our email to the taxonomy term 'post_received_comment'
        // Email is a custom post type, therefore use wp_set_object_terms

        $tt_ids = wp_set_object_terms( $post_id, 'budymeet_send_invitation', bp_get_email_tax_type() );
        foreach ( $tt_ids as $tt_id ) {
            $term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
            wp_update_term( (int) $term->term_id, bp_get_email_tax_type(), array(
                'description' => 'A member sent a meet request.',
            ) );
        }
    }
}

/**
 * Check if conzmeet plugin is enabled for a specific group or generally in the site.
 *
 * @param bool $group_id
 * @return bool
 */

function conzmeet_is_enabled($group_id = false){
    if($group_id){
        $enabled = get_option('_conzmeet_enabled') && groups_get_groupmeta($group_id, 'conzmeet_enabled', true);
    } else {
        $enabled = get_option('_conzmeet_enabled') === "1";
    }
    return $enabled;
}

function conzmeet_get_room_members($room, $group_id, $initialize = true){
    $room_members = false;
    if($room !== null) {
        $room_members_key = ConzMeet::ROOM_MEMBERS_PREFIX . $room;
        $room_members = groups_get_groupmeta($group_id, $room_members_key);
        if (!$room_members && $initialize) {
            $room_members = array(get_current_user_id());
            groups_update_groupmeta($group_id, $room_members_key, $room_members);
        }
    }
    return $room_members;
}

function conzmeet_is_member_of_room($user_id, $room_id, $group_id){
    $members = conzmeet_get_room_members($room_id, $group_id);
    return !$members || in_array($user_id, $members);
}

function conzmeet_get_user_rooms($group_id, $user_id){
    $user_rooms_option_key = ConzMeet::USER_ROOMS_PREFIX . $user_id;
    return groups_get_groupmeta($group_id, $user_rooms_option_key);
}

function conzmeet_get_user_room_info($group_id, $user_id, $room_id){
    $rooms = conzmeet_get_user_rooms($group_id, $user_id);
    foreach($rooms as $room){
        if($room['id'] === $room_id){
            return $room;
        }
    }
    return false;
}

function conzmeet_is_meet_members_enabled($group_id = false){
    if($group_id){
        $enabled = groups_get_groupmeta($group_id, 'conzmeet_meet_members_enabled', true);
    } else {
        $enabled = false;
    }
    return $enabled;
}

function conzmeet_render_jitsi_meet($room = null, $subject = null){
    if(!bp_is_group_single()){
       return;
    }

    global $bp;
    $group_id = $bp->groups->current_group->id;

    if(is_null($room)){
        $room = groups_get_groupmeta( $group_id, 'conzmeet_room', true);
    }

    if(is_null($subject)){
        $group_name = esc_js($bp->groups->current_group->name);
        $subject = $group_name;
    }

    $user_name = esc_js($bp->loggedin_user->userdata->display_name);
    $avatar_url = esc_js(get_avatar_url($bp->loggedin_user->userdata->ID));

    //apply group settings
    $password = groups_get_groupmeta( $group_id, 'conzmeet_password', true);

    $domain =  groups_get_groupmeta( $group_id, 'conzmeet_domain', true);
    $film_strip_only =  groups_get_groupmeta( $group_id, 'conzmeet_film_strip_only', true) === '1' ?  'true' : 'false';
    $width =  groups_get_groupmeta( $group_id, 'conzmeet_width', true);
    $height =  groups_get_groupmeta( $group_id, 'conzmeet_height', true);
    $start_audio_only =  groups_get_groupmeta( $group_id, 'conzmeet_start_audio_only', true) === '1' ? 'true' : 'false';
    $default_language =  groups_get_groupmeta( $group_id, 'conzmeet_default_language', true);
    $background_color =  groups_get_groupmeta( $group_id, 'conzmeet_background_color', true);
    $show_watermark =  groups_get_groupmeta( $group_id, 'conzmeet_show_watermark', true)  === '1' ? 'true' : 'false';
    $show_brand_watermark =  groups_get_groupmeta( $group_id, 'conzmeet_show_brand_watermark', true)  === '1' ? 'true' : 'false';
    $brand_watermark_link =  groups_get_groupmeta( $group_id, 'conzmeet_brand_watermark_link', true);
    $disable_video_quality_label =  groups_get_groupmeta( $group_id, 'conzmeet_disable_video_quality_label', true) === '1' ? 'true' : 'false';
    $settings =  groups_get_groupmeta( $group_id, 'conzmeet_settings', true);
    $toolbar =  groups_get_groupmeta( $group_id, 'conzmeet_toolbar', true);

    $content = '[conzmeet 
            room = "' . $room . '" 
            subject = "' . $subject . '"
            user = "' . $user_name . '"
            avatar = "' . $avatar_url . '"
            password = "' . $password . '"
            domain = "' . $domain . '"
            film_strip_only = "' . $film_strip_only . '"
            width = "' . $width . '"
            height = "' . $height . '"
            start_audio_only = "' . $start_audio_only . '"
            default_language = "' . $default_language . '"
            background_color = "' . $background_color . '"
            show_watermark = "' . $show_watermark . '"
            show_brand_watermark = "' . $show_brand_watermark . '"
            brand_watermark_link = "' . $brand_watermark_link . '"
            disable_video_quality_label = "' . $disable_video_quality_label . '"
            settings = "' . $settings . '"
            toolbar = "' . $toolbar . '"
        ]';

    echo do_shortcode($content);
}

function conzmeet_generate_unique_room() {
    return sprintf(
        '%04x%04x%04x%04x%04x%04x%04x%04x',
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff )
    );
}