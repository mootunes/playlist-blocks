<?php

/**
 * Plugin Name: Filmbites Students
 * Description: Adds students and class data to Wordpress for use by Filmbites Students
 * Version: 1.0
 * Author: Tim Green
*/

/*
  *REGISTER POST TYPE - STUDENT
*/

function register_fimbites_student_post_type() {
    register_post_type('filmbites_student',
        array(
            'labels'      => array(
                'name'          => __('Students', 'textdomain'),
                'singular_name' => __('Student', 'textdomain'),
            ),
                'public'      => false,
                'has_archive' => false,
                'show_ui' => true,
                'supports' => array(
                  'title',
                  'custom-fields'
                )
        )
    );
}
add_action('init', 'register_fimbites_student_post_type');

/*
  *REGISTER TAXONOMY - class
*/

function register_filmbites_class_taxonomy() {
  register_taxonomy( 'filmbites_class', array('post', 'filmbites_student'),
    array(
      'labels' => array(
        'name' => 'Classes',
        'singular_name' => 'Class',
        'all_items' => 'All Classes',
        'edit_item' => 'Edit Class',
        'view_item' => 'View Class',
        'update_item' => 'Update Class',
        'add_new_item' => 'Add New Class',
        'new_item_name' => 'New Class Name',
        'parent_item' => 'Parent Class',
        'search_items' => 'Search Classes',
        'popular_items' => 'Popular Classes'
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
      'public' => true,
      'exclude_from_search' => false,
      'publicly_queryable' => true
    )
  );
}

add_action('init', 'register_filmbites_class_taxonomy');

/*
  *REGISTER TAXONOMY - Email
*/

function register_filmbites_email_taxonomy() {
  register_taxonomy( 'filmbites_email', 'filmbites_student',
    array(
      'labels' => array(
        'name' => 'Associated Email Addresses',
        'singular_name' => 'Associated Email Address'
      ),
    )
  );
}

add_action('init', 'register_filmbites_email_taxonomy');

/*
  *REGISTER TAXONOMY - Invite
*/

function register_filmbites_invite_taxonomy() {
  register_taxonomy( 'filmbites_invite', 'filmbites_student',
    array(
      'labels' => array(
        'name' => 'Invites',
        'singular_name' => 'Invite'
      ),
    )
  );
}

add_action('init', 'register_filmbites_invite_taxonomy');

/*
  Do not allow non-logged in users
*/

function filmbites_redirect_non_users() {
  if ( !is_user_logged_in() ) { ?>
    <script>window.onload = function() {
      <?php $actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>
      // similar behavior as clicking on a link
      window.location.href = "<?php echo esc_url( wp_login_url( $actual_link ) ); ?>";
    }</script>
  <?php exit; }
}

add_action('wp_head', 'filmbites_redirect_non_users');

/*
  Create Filmbites Streaming User
*/

add_role('non_subscriber', __(
   'Non-Subscriber'),
   array(
       'read'            => true, // Allows a user to read
       )
);

add_role('subscriber', __(
   'Subscriber'),
   array(
       'read'            => true, // Allows a user to read
       )
);


remove_role('customer');

remove_role('contributor');

remove_role('author');

function filmbites_remove_capabilities() {

    // Get the role object.
    $editor = get_role( 'editor' );

	// A list of capabilities to remove from editors.
    $no_caps = array(
        'edit_pages',
        'edit_others_pages',
        'publish_pages',
        'delete_pages'
    );

    foreach ( $no_caps as $cap ) {
        // Remove the capability.
        $editor->remove_cap( $cap );
    }
}
add_action( 'init', 'filmbites_remove_capabilities' );

/*
  Run checks if user is non-subscriber
*/


function filmbites_run_subscriber_checks() {
  $current_user = wp_get_current_user();
  $disallowed_roles = array('non_subscriber');
  if( array_intersect($disallowed_roles, $current_user->roles ) ) {
    //Get the logged in users email
    $email = $current_user->user_email;
    $user_ID = get_current_user_id();

    //Get students associated with email address
    $args = array(
        'post_type' => 'filmbites_student',
        'tax_query' => array(
          array(
            'taxonomy' => 'filmbites_email',
            'field'    => 'name',
            'terms'    => $email,
          ),
      )
    );

    $students = new WP_Query($args);

    if($students->have_posts()) :
      wp_update_user( array(
        'ID' => $user_ID, // this is the ID of the user you want to update.
        'role' => 'subscriber'
      ) );
      $checks_out = "yes";
    else: ?>
      <div style="text-align:center;margin-top:50px;">
      <h1>Your account is not active</h1>
      <p>To activate your account, you need to be invited by another user with access to a student's work.</p>
      <p>If you don't know who to ask to invite you, please get in contact with Filmbites.</p>
      <?php
      $args = array(
          'post_type' => 'filmbites_student',
          'tax_query' => array(
            array(
              'taxonomy' => 'filmbites_invite',
              'field'    => 'name',
              'terms'    => $email,
            ),
        )
      );
      $invites = get_posts( $args );
      if ( $invites ) : ?>
      <h4>Your Student Invites</h4>
      <ul>
        <?php foreach ( $invites as $invite ): ?>
          <li><b><?php echo get_the_title( $invite->ID ); ?></b> - <a href="<?php echo get_site_url(); ?>/accept-invite-to-student?id=<?php echo $invite->ID; ?>">Accept Invite</a> | <a href="<?php echo get_site_url(); ?>/delete-student?id=<?php echo $invite->ID; ?>">Delete Invite</a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
      <p><a href="<?php echo get_site_url(); ?>/wp-admin/profile.php">Edit Your Profile</a> | <a href="<?php echo get_site_url(); ?>/wp-login.php?action=logout">Log Out</a></p>
    </div>
      <?php exit;
    endif;
  }
}

add_action('wp_head', 'filmbites_run_subscriber_checks', 20 );

//Remove admin bar

function filmbites_remove_admin_bar($content) {
	return ( current_user_can( 'edit_posts' ) ) ? $content : false;
}
add_filter( 'show_admin_bar' , 'filmbites_remove_admin_bar');

//remove dashboard
function filmbites_remove_dashboard(){
  if ( !current_user_can('edit_posts') ) {
    remove_menu_page( 'index.php' );
  }
}

add_action( 'admin_menu', 'filmbites_remove_dashboard' );

/*
  Invite User to Student
*/

function filmbites_add_email_to_student() {
  $server_request_uri = $_SERVER['REQUEST_URI'];
  if ( strpos($server_request_uri,'add-email-to-student') !== false ) {
    $new_email = $_POST['email'];
    $student_id = $_POST['student'];

    // Check if current user has permission to update student
    //Get the logged in users email
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;

    //Get students associated with email address
    $args = array(
        'post_type' => 'filmbites_student',
        'tax_query' => array(
          array(
            'taxonomy' => 'filmbites_email',
            'field'    => 'name',
            'terms'    => $user_email,
          ),
      )
    );

    $students = new WP_Query($args);

    if($students->have_posts()) :
      while($students->have_posts()) :
        $students->the_post();
        if ( $student_id == get_the_ID() ) {
          if ( !has_term( $new_email, 'filmbites_email' ) ) {
            $term = get_term_by( 'name', $new_email, 'filmbites_invite' );
            if ( $term ) {
              $term_id = $term->term_id;
            }
            if ( $term_id == false ) {
              $term = wp_insert_term( $new_email, 'filmbites_invite' );
              $term_id = $term['term_id'];
            }
            $post_ID = get_the_ID();
            wp_set_post_terms( $post_ID, array( $term_id ), 'filmbites_invite', true );

            $to = $new_email;
            $subject = 'Invite to Filmbites Streaming Service';
            $body = '<p>You\'ve been invited to access ' . get_the_title( $post_ID ) . '\'s work on Filmbites Streaming Service!</p><p><a href="' . get_site_url() . '/students/">Click here</a> to accept the invite. If you don\'t have an account yet you\'ll need to create one.</p>';
            $headers = array('Content-Type: text/html; charset=UTF-8');

            wp_mail( $to, $subject, $body, $headers );
          }
        }
    endwhile;
    endif;  ?>
    <script>window.onload = function() {
      // similar behavior as clicking on a link
      window.location.href = "<?php echo get_site_url(); ?>/students/?id=<?php echo $post_ID; ?>&email=<?php echo base64_encode( $new_email ); ?>";
    }</script>
  <?php exit;
  }
}

add_action('wp_head', 'filmbites_add_email_to_student');

/*
  Accept Invite
*/

function filmbites_accept_invite() {
  $server_request_uri = $_SERVER['REQUEST_URI'];
  if ( strpos($server_request_uri,'accept-invite-to-student') !== false ) {
    $student_id = $_GET['id'];

    // Check if current user has been invited
    //Get the logged in users email
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;

    //Get students associated with email address
    $args = array(
        'post_type' => 'filmbites_student',
        'tax_query' => array(
          array(
            'taxonomy' => 'filmbites_invite',
            'field'    => 'name',
            'terms'    => $user_email,
          ),
      )
    );

    $students = new WP_Query($args);

    if($students->have_posts()) :
      while($students->have_posts()) :
        $students->the_post();
        if ( $student_id == get_the_ID() ) {
          $term = get_term_by( 'name', $user_email, 'filmbites_email' );
          if ( $term ) {
            $term_id = $term->term_id;
          }
          if ( $term_id == false ) {
            $term = wp_insert_term( $user_email, 'filmbites_email' );
            $term_id = $term['term_id'];
          }
          $post_ID = get_the_ID();
          wp_set_post_terms( $post_ID, array( $term_id ), 'filmbites_email', true );

          $term = get_term_by( 'name', $user_email, 'filmbites_invite' );
          wp_remove_object_terms( $post_ID, $term->term_id, 'filmbites_invite' );
        }
    endwhile;
    endif;  ?>
    <script>window.onload = function() {
      // similar behavior as clicking on a link
      window.location.href = "<?php echo get_site_url(); ?>/students/";
    }</script>
  <?php exit;
  }
}

add_action('wp_head', 'filmbites_accept_invite', 19);


/*
  Delete Student
*/

function filmbites_delete_student() {
  $server_request_uri = $_SERVER['REQUEST_URI'];
  if ( strpos($server_request_uri,'delete-student') !== false ) {
    $student_id = $_GET['id'];

    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;

    $term = get_term_by( 'name', $user_email, 'filmbites_invite' );
    wp_remove_object_terms( $student_id, $term->term_id, 'filmbites_invite' );

    $term = get_term_by( 'name', $user_email, 'filmbites_email' );
    wp_remove_object_terms( $student_id, $term->term_id, 'filmbites_email' );

        ?>
    <script>window.onload = function() {
      // similar behavior as clicking on a link
      window.location.href = "<?php echo get_site_url(); ?>/students/";
    }</script>
  <?php exit;
  }
}

add_action('wp_head', 'filmbites_delete_student', 19);


/*
  Remove unnecessary admin fields
*/
function remove_personal_options(){
    echo '<script type="text/javascript">jQuery(document).ready(function($) {

$(\'form#your-profile tr.user-admin-color-wrap\').remove(); // remove the "Admin Color Scheme" field

$(\'form#your-profile tr.user-admin-bar-front-wrap\').remove(); // remove the "Toolbar" field

$(\'form#your-profile tr.user-language-wrap\').remove(); // remove the "Language" field

$(\'form#your-profile tr.user-first-name-wrap\').remove(); // remove the "First Name" field

$(\'form#your-profile tr.user-last-name-wrap\').remove(); // remove the "Last Name" field

$(\'form#your-profile tr.user-nickname-wrap\').hide(); // Hide the "nickname" field

$(\'table.form-table tr.user-display-name-wrap\').remove(); // remove the “Display name publicly as” field

$(\'table.form-table tr.user-url-wrap\').remove();// remove the "Website" field in the "Contact Info" section

$(\'h2:contains("About Yourself"), h2:contains("About the user")\').remove(); // remove the "About Yourself" and "About the user" titles

$(\'form#your-profile tr.user-description-wrap\').remove(); // remove the "Biographical Info" field

$(\'form#your-profile tr.user-profile-picture\').remove(); // remove the "Profile Picture" field

$(\'table.form-table tr.user-aim-wrap\').remove();// remove the "AIM" field in the "Contact Info" section

$(\'table.form-table tr.user-yim-wrap\').remove();// remove the "Yahoo IM" field in the "Contact Info" section

$(\'table.form-table tr.user-jabber-wrap\').remove();// remove the "Jabber / Google Talk" field in the "Contact Info" section

$(\'h2:contains("Name")\').remove(); // remove the "Name" heading

$(\'h2:contains("Contact Info")\').remove(); // remove the "Contact Info" heading

});</script>';

}

add_action('admin_head','remove_personal_options');
