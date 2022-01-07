<?php
/**
 * This class adds custom routes for easily adding custom templates
 * This functions pretty well if you use pretty permalinks
 * Do not forget to flush (save) your permalinks after you have added new routes.
 */
namespace MakeitWorkPress\WP_Router;
use WP_Error as WP_Error;

defined( 'ABSPATH' ) or die( 'Go eat veggies!' );

class Router { 
    
    /**
     * Contains the basefolder in which templates are stored
     *
     * @var string
     * @access private
     */
    private $folder;    
    
    /**
     * Contains our routes
     *
     * @var array
     * @access private
     */
    private $routes;
    
    /*
     * Our permalink structure
     *
     * @var string
     * @access public
     */
    public $structure;
    
    /**
     * Contains our query-variable for retrieving the right template
     *
     * @var string|array
     * @access private
     */
    private $query_var;    
    
    /**
     * Constructs the routes and custom query vars
     *
     * @param array     $routes     The array with routes
     * @param string    $folder     The folder to search for templates. If this is a full path, this will be used instead
     * @param string    $query_var  The query variable by which a template can be identified
     */
    public function __construct( array $routes = [], $folder = 'templates', $query_var = 'template', $debug = false ) {
        
        /**
         * Initial variables
         */
        $this->folder    = apply_filters('wp_router_template_folder', $folder);
        $this->routes    = $routes;
        $this->structure = get_option('permalink_structure');
        $this->query_var = apply_filters('wp_router_query_vars', $query_var);
        
        /**
         * Add our custom query vars
         */
        $query_var        = $this->query_var;

        add_filter( 'query_vars', function( $vars ) use( $query_var ) {

            if( is_array($query_var) ) {
                $vars = array_merge($vars, $query_var);
            } else {
                array_push($vars, $query_var);
            }
            
            return $vars;
            
        }, 10, 1 );
        
        /**
         * Add our rewrites, but only if there are pretty permalinks
         */
        if( $this->structure ) {
            $this->rewrite();
        }
        
        /**
         * Locate our templates and add additional settings
         */
        $this->locate();

        if( $debug ) {
            add_action('wp', function() {
                global $wp_rewrite;
                var_dump($wp_rewrite->rules);
            });
        }
        
    }
    
    /**
     * Adds the necessary rewrites for the routes
     */
    private function rewrite(): void {
        
        $routes     = $this->routes;
        $structure  = $this->structure;
        $query_var  = $this->query_var;
        
        // Adds our rewrite rules based on our routes, and makes sure they are prefixed.
        add_action('init', function() use( $routes, $query_var, $structure ) {
            
            // Watch our prefixes for pretty permalinks, which may include the /blog prefix
            $prefix = '';
            if( preg_match('/(?U)(.*)(\/%.*%\/)/', $structure, $matches) ) {             
                if( ! empty($matches[1]) ) {              
                    $prefix = str_replace('/', '', $matches[1]) . '/';
                } 
            }

            // Register our custom routes
            foreach( $routes as $name => $properties ) {

                // Adds the rewrite rule, both prefixed and not prefixed
                if( isset($properties['route']) ) {
                    if( $prefix ) {
                        add_rewrite_rule( $prefix . $properties['route'] . '?$', 'index.php?' . $query_var . '=' . $name, 'top' );
                    }
                    add_rewrite_rule( $properties['route'] . '?$', 'index.php?' . $query_var . '=' . $name, 'top' );
                }
                
            }
            
        });
        
    }
    
    /**
     * Locate our custom templates and add the right settings for this template
     */
    private function locate(): void {

        // Load the right template  
        $folder     = $this->folder;
        $query_var  = $this->query_var;
        
        add_filter( 'template_include', function( $template ) use( $folder, $query_var ) {
            
            $name = get_query_var( $query_var );

            
            if( ! $name ) {
                return $template;
            }

            // We can also use an absolute path for the folder
            if( strpos( $folder, ABSPATH ) !== false ) {
                $template = file_exists($folder . '/' . $name . '.php') ? $folder . '/' . $name . '.php' : false;
            } else {
                $template = locate_template( $folder . '/' . $name . '.php' );
            }

            // Set our query vars for the custom page       
            global $wp_query;
            $wp_query->is_404       = false;
            $wp_query->is_custom    = true;            
             
            // Returns an error message if we don't have a template
            if( ! $template ) {
                $error = new WP_Error( 
                    'missing_template', 
                    sprintf( __('The file for the template %s does not exist', 'wp-router'), '<b>' . $name . '</b>') 
                );
                echo $error->get_error_message();
            }            
            
            return apply_filters('wp_router_template', $template);
            
        } );
        
        // This defines the page title for our custom templates
        $routes = $this->routes;
        
        add_filter( 'document_title_parts', function( $title ) use( $routes, $query_var ) {
            
            $name = get_query_var($query_var);
            
            if( $name && isset($routes[$name]['title']) ) {
                $title['title'] = $routes[$name]['title'];    
            }
            
            return $title;
            
        } );
        
        // Add custom body classes to the front-end of our application so we can style accordingly.
        add_filter( 'body_class', function( $classes ) use( $query_var ) {
            
            $name = get_query_var($query_var);
            
            if($name)
                $classes[] = 'template-' . $name;
            
            return $classes;
            
        } );        
        
    }
    
}