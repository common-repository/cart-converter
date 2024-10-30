<?php
require_once( CART_CONVERTER_DIR . '/cart-converter-logger.php' );

//load base model
require_once( CART_CONVERTER_DIR . '/models/model.php' );
// load all models
$models = glob( CART_CONVERTER_DIR . '/models/*.php' );
foreach( $models as $model ) {
    require_once( $model );
}

// load base controller
require_once( CART_CONVERTER_DIR . '/controllers/controller.php' );
// load all controllers
$controllers = glob( CART_CONVERTER_DIR . '/controllers/*.php' );

foreach( $controllers as $controller ) {
    require_once( $controller );
}
