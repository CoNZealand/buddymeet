<?php
/**
 * ConzMeet Actions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// BuddyPress / WordPress actions to ConzMeet ones
add_action( 'bp_init',                  'conzmeet_init',                     14 );
add_action( 'bp_ready',                 'conzmeet_ready',                    10 );
add_action( 'bp_setup_current_user',    'conzmeet_setup_current_user',       10 );
add_action( 'bp_setup_theme',           'conzmeet_setup_theme',              10 );
add_action( 'bp_after_setup_theme',     'conzmeet_after_setup_theme',        10 );
add_action( 'bp_enqueue_scripts',       'conzmeet_register_scripts',          1 );
add_action( 'bp_admin_enqueue_scripts', 'conzmeet_register_scripts',          1 );
add_action( 'bp_enqueue_scripts',       'conzmeet_enqueue_scripts',          10 );
add_action( 'bp_setup_admin_bar',       'conzmeet_setup_admin_bar',          10 );
add_action( 'bp_actions',               'conzmeet_actions',                  10 );
add_action( 'bp_screens',               'conzmeet_screens',                  10 );
add_action( 'admin_init',               'conzmeet_admin_init',               10 );
add_action( 'admin_head',               'conzmeet_admin_head',               10 );

function conzmeet_init(){
	do_action( 'conzmeet_init' );
}

function conzmeet_ready(){
	do_action( 'conzmeet_ready' );
}

function conzmeet_setup_current_user(){
	do_action( 'conzmeet_setup_current_user' );
}

function conzmeet_setup_theme(){
	do_action( 'conzmeet_setup_theme' );
}

function conzmeet_after_setup_theme(){
	do_action( 'conzmeet_after_setup_theme' );
}

function conzmeet_register_scripts() {
	do_action( 'conzmeet_register_scripts' );
}

function conzmeet_enqueue_scripts(){
	do_action( 'conzmeet_enqueue_scripts' );
}

function conzmeet_setup_admin_bar(){
	do_action( 'conzmeet_setup_admin_bar' );
}

function conzmeet_actions(){
	do_action( 'conzmeet_actions' );
}

function conzmeet_screens(){
	do_action( 'conzmeet_screens' );
}

function conzmeet_admin_init() {
	do_action( 'conzmeet_admin_init' );
}

function conzmeet_admin_head() {
	do_action( 'conzmeet_admin_head' );
}