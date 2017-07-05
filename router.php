<?php
/**
 * This class adds custom routes for easily adding custom templates
 * This functions pretty well if you use pretty permalinks
 * Do not forget to flush (save) your permalinks after you have added new routes.
 */
namespace WP_Router;
use WP_Error as WP_Error;

defined( 'ABSPATH' ) or die( 'Go eat veggies!' );

class Router { 
    
    /**
     * Contains the basefolder in which templates are stored
     *
     * @access private
     */
    private $folder;    
    
    /**
     * Contains our routes
     *
     * @access private
     */
    private $routes;
    
    /*
     * Our permalink structure
     *
     * @access public
     */
    public $structure;
    
    /**
     * Contains our query-variable for retrieving the right template
     *
     * @access private
     */
    private $queryVar;    
    
    /**
     * Constructs the routes and custom query vars
     *
     * @param array     $routes     The array with routes
     * @param string    $folder     The folder to search for templates
     * @param string    $queryVar   The query variable by which a template can be identified
     */
    public function __construct( Array $routes = [], $folder = 'templates', $queryVar = 'template' ) {
        
        /**
         * Initial variables
         */
        $this->folder    = $folder;
        $this->routes    = $routes;
        $this->structure = get_option('permalink_structure');
        $this->queryVar  = $queryVar;
        
        /**
         * Add our custom query vars
         */
        add_filter( 'query_vars', function( $vars ) use( $queryVar ) {
            
            array_push($vars, $queryVar);
            
            return $vars;
            
        }, 10, 1 );
        
        /**
         * Add our rewrites, but only if there are pretty permalinks
         */
        if( $this->structure )
            $this->rewrite();
        
        /**
         * Locate our templates and add additional settings
         */
        $this->locate();

        
    }
    
    /**
     * Adds the necessary rewrites for the routes
     */
    private function rewrite() {
        
        $routes     = $this->routes;
        $queryVar   = $this->queryVar;
        
        // Adds our rewrite rules based on our routes, and makes sure they are prefixed.
        add_action('init', function() use( $routes, $queryVar ) {
            
            // Watch our prefixes for pretty permalinks
            $prefix = '';
            
            if( preg_match('/(?U)(.*)(\/%.*%\/)/', $this->structure, $matches) ) {             
                
                if( ! empty($matches[1]) )                
                    $prefix = str_replace('/', '', $matches[1]) . '/';
                
            }

            
            // Register our custom routes
            foreach( $routes as $name => $properties ) {

                // Adds the rewrite rule
                if( isset($properties['route']) )
                    add_rewrite_rule( $prefix . $properties['route'] . '?$', 'index.php?' . $queryVar . '=' . $name, 'top' );
                
            }
            
        });
        
    }
    
    /**
     * Locate our custom templates and add the right settings for this template
     */
    private function locate() {

        // Load the right template  
        $folder     = $this->folder;
        $queryVar   = $this->queryVar;
        
        add_filter( 'template_include', function( $template ) use( $folder, $queryVar ) {
            
            global $wp_query;
            $name = get_query_var( $queryVar );
            
            if( $name ) {
                $template = locate_template( $folder . '/' . $name . '.php' );
                
                if( ! $template ) {
                    $error = new WP_Error( 
                        'missing_template', 
                        sprintf( __('The file for the template %s does not exist', 'wp-router'), '<b>' . $name . '</b>') 
                    );
                    echo '<b>ERROR</b>:' . $error->get_error_message();
                }
                    
                $wp_query->is_404       = false;
                $wp_query->is_custom    = true;
            }
            
            return apply_filters('wp_router_template', $template);
            
        } );
        
        // This defines the page title for our custom templates
        $routes = $this->routes;
        
        add_filter( 'document_title_parts', function( $title ) use( $routes, $queryVar ) {
            
            $name = get_query_var($queryVar);
            
            if( $name && isset($routes[$name]['title']) ) {
                $title['title'] = $routes[$name]['title'];    
            }
            
            return $title;
            
        } );
        
        // Add custom body classes to the front-end of our application so we can style accordingly.
        add_filter( 'body_class', function( $classes ) use( $queryVar ) {
            
            $name = get_query_var($queryVar);
            
            if($name)
                $classes[] = 'template-' . $name;
            
            return $classes;
            
        } );        
        
    }
    
}