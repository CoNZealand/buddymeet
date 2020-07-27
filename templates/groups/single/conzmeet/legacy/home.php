<?php
$action = conzmeet_get_current_action();
$user_id = get_current_user_id();
$group_id = bp_get_group_id();
$display_meet_members = conzmeet_is_meet_members_enabled($group_id);
if(!$display_meet_members){
    $action = 'group';
}
$user_rooms = conzmeet_get_user_rooms($group_id, $user_id);
$current_room = conzmeet_get_current_user_room_from_path();
?>
    <div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'ConzMeet secondary navigation', 'conzmeet' ); ?>" role="navigation">
        <ul>
             <?php $display_meet_members ? bp_get_options_nav(conzmeet_get_slug()) : ''; ?>

            <?php if($action !== 'group') :?>
                <li id="room-filter-select" class="last">
                    <label for="active-rooms"></label>
                    <select id="active-rooms">
                        <option value=""><?php _e('Select a room', 'conzmeet') ?></option>
                        <?php if(is_array($user_rooms)) :?>
                            <?php foreach ($user_rooms as $user_room) :?>
                                <option value="<?php esc_attr_e($user_room['id'])?>" <?php esc_attr_e(($current_room && $user_room['id'] === $current_room) ? 'selected' : '')?>>
                                    <?php esc_html_e($user_room['name']);?>
                                </option>
                            <?php endforeach;?>
                        <?php endif;?>
                        <?php do_action( 'conzmeet_group_rooms_filter_options' ); ?>
                    </select>
                </li>
            <?php endif;?>
        </ul>
    </div>
<?php

if(!$current_room || conzmeet_is_member_of_room($user_id, $current_room, $group_id)) {
    switch ( $action ) {
        case 'group' :
            bp_get_template_part('groups/single/conzmeet/group');
            break;
        case 'members' :
            bp_get_template_part('groups/single/conzmeet/members');
    }
} else {
    echo '<div id="message" class="error"><p>' . __('This content is only available to invited members.', 'career') . '</p></div>';
}
