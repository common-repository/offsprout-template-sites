<?php

class Offsprout_Template_Site_API extends WP_REST_Controller{

    function register_routes(){
        $namespace = 'offsproutTemplateSite/';
        $version = 'v1';

        register_rest_route( $namespace . $version, '/templates', array(
            array(
                'methods'         => 'GET,POST,PUT',
                'callback'        => array( $this, 'get_templates' ),
                'permission_callback' => array( $this, 'get_template_permissions_check' )
            ),
        ) );

        register_rest_route( $namespace . $version, '/page_templates', array(
            array(
                'methods'         => 'GET,POST,PUT',
                'callback'        => array( $this, 'get_page_templates' ),
                'permission_callback' => array( $this, 'get_template_permissions_check' )
            ),
        ) );

        register_rest_route( $namespace . $version, '/skins', array(
            array(
                'methods'         => 'GET,POST,PUT',
                'callback'        => array( $this, 'get_skins' ),
                'permission_callback' => array( $this, 'get_template_permissions_check' )
            ),
        ) );

        register_rest_route( $namespace . $version, '/packs', array(
            array(
                'methods'         => 'GET,POST,PUT',
                'callback'        => array( $this, 'get_template_packs' ),
                'permission_callback' => array( $this, 'get_template_permissions_check' )
            ),
        ) );

    }

    /**
     * Return true for now
     *
     * May eventually want to implement the ability to have keys that are sent in the request and verified
     *
     * @param $request
     * @return bool
     */
    function get_template_permissions_check( $request ){
        $json_payload = $request->get_json_params();

        return true;
    }

    /**
     * Get ocb_template posts with their meta values
     *
     * May want to use get_custom instead
     *
     * @param $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_templates( $request ){
        //This ensures that connector items that don't have values return a default value
        global $ocb_connector_default;
        $ocb_connector_default = true;

        $json_payload = $request->get_json_params();

        $number = isset( $json_payload['number'] ) ? (int) $json_payload['number'] : 1000;
        $offset = isset( $json_payload['offset'] ) ? (int) $json_payload['offset'] : 0;

        $return = get_posts( array(
            'post_type' => 'ocb_template',
            'numberposts' => $number,
            'offset' => $offset
        ) );

        foreach( $return as $index => $content ){
            $return[$index]->{ "taxonomies" } = new stdClass();
            foreach( array( 'ocb_template_global', 'ocb_template_type', 'ocb_template_folder', 'ocb_template_module_type', 'ocb_template_industry', 'ocb_template_theme' ) as $key ){
                $terms = wp_get_post_terms( $content->ID, $key );
                if( is_wp_error( $terms ) ){
                    $return[$index]->{ "taxonomies" }->{ $key } = '';
                } else {
                    $first_term = isset( $terms[0] ) ? $terms[0] : false;
                    if( $first_term )
                        $return[$index]->{ "taxonomies" }->{ $key } = $first_term->name;
                    else
                        $return[$index]->{ "taxonomies" }->{ $key } = '';
                }
            }

            $return[$index]->{ "meta" } = new stdClass();
            foreach ( array( 'ocb_object_css', 'ocb_tree_content', 'ocb_id', 'ocb_template_requirements' ) as $key ) {
                $return[$index]->{ "meta" }->{$key} = get_post_meta( $content->ID, $key, true );
            }

            $the_content = do_shortcode( $content->post_content );
            $the_content = Offsprout_Model::replace_strings( $the_content );

            $return[$index]->{"post_content_shortcode"} = $the_content;
            $return[$index]->{"remote_template"} = 1;
        }

        if( $return ){

            //Should cache here

            return new WP_REST_Response( $return, 200, array(
                'Access-Control-Allow-Origin: *',
            ) );

        }

        return new WP_Error( 'noTemplates', __( 'Could not find any templates', 'offsprout' ), array( 'status' => 405 ) );
    }

    /**
     * Get ocb_template posts with their meta values
     *
     * May want to use get_custom instead
     *
     * @param $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_page_templates( $request ){
        //This ensures that connector items that don't have values return a default value
        global $ocb_connector_default;
        $ocb_connector_default = true;

        $json_payload = $request->get_json_params();

        $number = isset( $json_payload['number'] ) ? (int) $json_payload['number'] : 1000;
        $offset = isset( $json_payload['offset'] ) ? (int) $json_payload['offset'] : 0;

        $return = get_posts( array(
            'post_type' => 'ocb_tree_template',
            'numberposts' => $number,
            'offset' => $offset
        ) );

        global $post;

        foreach( $return as $index => $post ){

            setup_postdata($post);

            $return[$index]->{ "taxonomies" } = new stdClass();
            foreach( array( 'ocb_template_folder', 'ocb_template_industry', 'ocb_template_theme' ) as $key ){
                $terms = wp_get_post_terms( $post->ID, $key );
                if( is_wp_error( $terms ) ){
                    $return[$index]->{ "taxonomies" }->{ $key } = '';
                } else {
                    $first_term = isset( $terms[0] ) ? $terms[0] : false;
                    if( $first_term )
                        $return[$index]->{ "taxonomies" }->{ $key } = $first_term->name;
                    else
                        $return[$index]->{ "taxonomies" }->{ $key } = '';
                }
            }

            $return[$index]->{ "meta" } = new stdClass();
            foreach ( array( 'ocb_page_css', 'ocb_tree_content', 'ocb_structure', 'ocb_id', 'ocb_template_post_type', '_wp_page_template', 'ocb_template_requirements' ) as $key ) {
                $return[$index]->{ "meta" }->{$key} = get_post_meta( $post->ID, $key, true );
            }

            $structure = get_post_meta( $post->ID, 'ocb_structure', true );

            if( Offsprout_Model::has_offsprout_theme() && $structure ) {
                $structure_id = Offsprout_Model::get_post_id_from_custom( $structure, 'ocb_structure' );
                $structure_post = get_post( $structure_id );
                $structure_content = $structure_post->post_content;
                if ( !isset( $return[$index]->ocb_page_css ) )
                    $return[$index]->ocb_page_css = '';

                $return[$index]->ocb_page_css .= get_post_meta( $structure_id, 'ocb_page_css', true );

                $return[$index]->{"post_content"} = do_shortcode( str_replace( '[ocb_content_module]', $post->post_content, $structure_content ) );
            } else {
                $return[$index]->ocb_page_css = get_post_meta( $post->ID, 'ocb_page_css', true );
            }

            $the_content = do_shortcode( $post->post_content );
            $the_content = Offsprout_Model::replace_strings( $the_content );

            $return[$index]->{"post_content_shortcode"} = $the_content;
            $return[$index]->{"remote_template"} = 1;

            wp_reset_postdata();

        }

        if( $return ){

            //Should cache here

            return new WP_REST_Response( $return, 200, array(
                'Access-Control-Allow-Origin: *',
            ) );

        }

        return new WP_Error( 'noTemplates', __( 'Could not find any templates', 'offsprout' ), array( 'status' => 405 ) );
    }

    public function get_skins( $request ){
        $json_payload = $request->get_json_params();

        $skins = get_option( 'ocb_site_skins' );

        if( $skins ){

            //Should cache here

            return new WP_REST_Response( $skins, 200, array(
                'Access-Control-Allow-Origin: *',
            ) );

        }

        //Can't return an error or it will affect the overall template call
        return new WP_REST_Response( array(), 200, array(
            'Access-Control-Allow-Origin: *',
        ) );

        //return new WP_Error( 'noSkins', __( 'Could not find any skins', 'offsprout' ), array( 'status' => 405 ) );
    }

    public function get_template_packs( $request ){

        if( ! is_multisite() )
            return new WP_Error( 'noPacks', __( 'This template cloud is not configured for template packs', 'offsprout' ), array( 'status' => 405 ) );


        //if( ! $sites = get_transient( 'ocb_template_packs' ) ){

            switch_to_blog( 1 );
            $active_template_sites = get_option( 'ocb_active_template_sites' );
            restore_current_blog();

            $active_sites = array();

            foreach( $active_template_sites as $key => $included ){
                $active_sites[] = $key;
            }

            $sites = array();

            foreach( $active_sites as $site_id ){
                switch_to_blog( $site_id );

                //Get the homepage template
                $the_homepage = get_page_by_title( 'Home', OBJECT, 'ocb_tree_template' );

                $the_homepage->{ "meta" } = new stdClass();
                foreach ( array( 'ocb_page_css', 'ocb_tree_content', 'ocb_structure', 'ocb_id', 'ocb_template_post_type', '_wp_page_template', 'ocb_template_requirements' ) as $key ) {
                    $the_homepage->{ "meta" }->{$key} = get_post_meta( $the_homepage->ID, $key, true );
                }

                $the_content = do_shortcode( $the_homepage->post_content );
                $the_content = Offsprout_Model::replace_strings( $the_content );
                $the_homepage->{"post_content_shortcode"} = $the_content;
                $the_homepage->{"remote_template"} = 1;

                $site = array(
                    'blog_id' => $site_id,
                    'name' => get_bloginfo('name'),
                    'description' => get_bloginfo('description'),
                    'url' => get_bloginfo('url'),
                    'skins' => get_option( 'ocb_site_skins' ),
                    'template' => $the_homepage
                );

                $sites[] = $site;

                restore_current_blog();
            }

            set_transient( 'ocb_template_packs', $sites, ( 60 * 60 * 24 * 1 ) );

        //}


        if( count( $sites ) > 0 ){
            return new WP_REST_Response( $sites, 200, array(
                'Access-Control-Allow-Origin: *',
            ) );
        }

        return new WP_Error( 'noPacks', __( 'This template cloud does not have template packs', 'offsprout' ), array( 'status' => 405 ) );

    }

}

function offsprout_register_template_site_api_extensions() {
    $controller = new Offsprout_Template_Site_API();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'offsprout_register_template_site_api_extensions' );