<?php

class Offsprout_Template_Site_Multisite_Menu {
    function __construct() {
        add_action('admin_menu', array( $this, 'offsprout_template_sites_create_menu' ) );
    }

    function offsprout_template_sites_create_menu() {

        //create new top-level menu
        add_menu_page('Active Template Clouds', 'Active Template Clouds', 'administrator', __FILE__, array( $this, 'offsprout_template_sites_settings' ), 'dashicons-cloud' );

        //call register settings function
        add_action( 'admin_init', array( $this, 'register_offsprout_template_sites' ) );
    }

    function register_offsprout_template_sites() {
        //register our settings
        register_setting( 'offsprout-template-sites', 'ocb_active_template_sites' );
    }

    /**
     * Saves an array of active sites like where key is blog_id:
     *
     * array(2) { [2]=> string(1) "1" [4]=> string(1) "1" }
     */
    function offsprout_template_sites_settings() {
        $sites = get_sites();

        $checkboxes = '';
        $active_template_sites = get_option( 'ocb_active_template_sites' );

        foreach( $sites as $site ){
            if( $site->blog_id == 1 )
                continue;

            $checked = checked( 1 == $active_template_sites[$site->blog_id], true, false );

            $checkboxes .= '<tr valign="top">
                        <th scope="row">' . $site->domain . '</th>
                        <td><input type="checkbox" name="ocb_active_template_sites[' . $site->blog_id . ']" value="1" ' . $checked . '/></td>
                    </tr>';
        }

        ?>
        <div class="wrap">
            <h1>Active Template Clouds</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'offsprout-template-sites' ); ?>
                <?php do_settings_sections( 'offsprout-template-sites' ); ?>
                <table class="form-table">
                    <?php echo $checkboxes; ?>
                </table>

                <?php submit_button(); ?>

            </form>
        </div>
        <?php
    }

    function testing(){
        $active_template_sites = get_option( 'ocb_active_template_sites' );
        $active_sites = array();

        foreach( $active_template_sites as $key => $included ){
            $active_sites[] = $key;
        }

        $sites = array();

        foreach( $active_sites as $site_id ){
            switch_to_blog( $site_id );

            //Get the homepage template
            /*$homepages = new WP_Query( array(
                'post_type' => 'ocb_tree_template',
                'number' => 2,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'ocb_template_folder',
                        'field'    => 'slug',
                        'terms' => 'home'
                    )
                )
            ));*/

            $the_homepage = get_page_by_title( 'Home', OBJECT, 'ocb_tree_template' );

            $site = array(
                'blog_id' => $site_id,
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_bloginfo('url'),
                'skins' => get_option( 'ocb_site_skins' ),
                'home' => $the_homepage
            );

            $sites[] = $site;

            ob_start();

            echo '<pre>';

            var_dump( $site );

            echo '</pre>';

            echo ob_get_clean();

            restore_current_blog();
        }
    }

}

new Offsprout_Template_Site_Multisite_Menu();