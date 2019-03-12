# wp-router
Enables developers to add custom routes and templates to their WordPress theme. In other Words, you can easily add custom permalinks to your theme such as yourwebsite.dev/custom/ which in order point to a custom template.

WP Router is maintained by [Make it WorkPress](https://www.makeitworkpress.com/wordpress-solutions/scripts/wp-router/).

## Usage
Include the WP-Router in your plugin, theme or child theme files. Require it in your functions.php file or use a PHP autoloader. You can read more about autoloading in [the readme of wp-autoload](https://github.com/makeitworkpress/wp-autoload). 

### Create a new instance of WP_Router\Router
Create a new instance of the Router class with the array of routes as an argument in the format displayed below. 

    $router = new MakeitWorkPress\WP_Router\Router( 
        [
            'custom'    => ['route' => custom/, 'title' => __('Custom Template Title')],
            'another'   => ['route' => friedpizza/, 'title' => __('Fried Pizza!')]
        ], 
        'templates', // The folder in your theme or child theme which the custom templates are stored. If you use any complete path, you can also use this to locate templates in plugins.
        'template'   // The query var by which the template is identified, in this case through get_query_var('template'). Defaults to template.
    );
    
* The keys of the routes array self indicate the names of the specific template and will also refer to the name of the specific file in your templates folder. 
* The route key in the values of this array indicate the Regular Expression for the permalink, while the title key indicates an optional title that is displayed in the head section of your website. 
* Optionally, you can define a custom folder for your templates as a second argument and the custom variable by which a template is queried in the third argument. By default, the templates folder in your theme or child theme is expected.

### Include template
Include the specific templates in your theme. In the case above, you need to have have a custom.php and an another.php template in the folder /templates/ in your parent or child theme. Obviously, this will be another folder if you changed the second argument.

### Flush Permalinks
After adding new routes, do not forget to flush your permalinks. The easiest way to do is to head over to your permalink settings and save your settings. Please note that WP-Router only supports pretty permalinks.

### Result
With the above example, you will have yourwebsite.dev/custom/ using templates/custom.php and yourwebsite.dev/friedpizza/ using templates/another.php