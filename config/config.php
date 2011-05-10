<?php
/**
 * ini_set values
 */
$config['ini_set'] = array(
    'error_reporting'   => (E_ALL | E_STRICT),
    'display_errors'    => true,
    'html_errors'       => true,
    'date.timezone'     => 'America/New_York',
);

/**
 * locale
 */
$config['locale'] = 'en_US';

/**
 * database
 */
$config['database'] = array(
    'type' => 'mysql', // mysql, postgres
    'host' => 'localhost',
    'user' => '',
    'pass' => '',
    'name' => '',
    'port' => null,
    // 'port' => ''
    'cache' => array(
        'type' => 'memcache', // memcache
        'host' => 'localhost',
        'port' => 11211,
    )
);

/**
 * default controller
 */
$config['default_controller'] = 'Index';

/**
 * routing
 */
$config['routing'] = array(
    'replace' => array(
/*
        // example replacements
        '{:action}'     => '([a-z-]+)',
        '{:alpha}'      => '([a-zA-Z]+)',
        '{:alnum}'      => '([a-zA-Z0-9]+)',
        '{:controller}' => '([a-z-]+)',
        '{:digit}'      => '([0-9]+)',
        '{:param}'      => '([^/]+)',
        '{:params}'     => '(.*)',
        '{:slug}'       => '([a-zA-Z0-9-]+)',
        '{:word}'       => '([a-zA-Z0-9_]+)',
*/
    ),
    'rewrite' => array(
        // example routes
        //'index/blah' => 'index/main',
        //'index/{:alpha}' => 'index/main/$1',
    )
);
