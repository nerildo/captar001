<?php
        
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, exiting." . PHP_EOL; // WPCS: XSS ok.
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Define AUTOBP_PLUGIN_FILE se não estiver definido (geralmente definido no arquivo principal do plugin)
    if ( ! defined( 'AUTOBP_PLUGIN_FILE' ) ) {
        define( 'AUTOBP_PLUGIN_FILE', dirname( dirname( dirname( __FILE__ ) ) ) . '/autoblogpro.php' );
    }
    // Define AUTOBP_PLUGIN_DIR se não estiver definido
    if ( ! defined( 'AUTOBP_PLUGIN_DIR' ) ) {
        define( 'AUTOBP_PLUGIN_DIR', dirname( dirname( dirname( __FILE__ ) ) ) . '/' );
    }
    require AUTOBP_PLUGIN_FILE;
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Ativar o plugin (opcional, mas pode ser necessário para hooks de ativação)
// activate_plugin( 'autoblogpro/autoblogpro.php' );
?>
