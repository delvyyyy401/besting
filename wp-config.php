<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'besthing3' );

/** Database username */
define( 'DB_USER', 'besthing3' );

/** Database password */
define( 'DB_PASSWORD', 'ftiukdw' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '+=7-zPE<ZNobKt! 3w$31^v@uP=ou%^l~$yVGI`Yj=i380Ks.#[)_Zu{t8GOz[xS' );
define( 'SECURE_AUTH_KEY',  ':2J0rGoBNGM|(q>&JZXi/kQav/#]Wm<7cR20r&5=w1yR1F:_^X>c<34;XDO>`gBk' );
define( 'LOGGED_IN_KEY',    '78KKzo@u1n7:0_@Q+pns3EBtuz:~j?<i.IC:Chzi~}7lDqr&a:Sau7`<7IXrF~}#' );
define( 'NONCE_KEY',        'rk,;}wk)NgS!PRg}8*N8BM(_.A=APyfb!fGVukh`)$S*<u[L8EOJ++Y+AhGX)^{T' );
define( 'AUTH_SALT',        'o6]J/N3t3Elt]n,-F5,!,n7%MOL^dIL(:19K$)W~fN-5RxHR,^*nO{E=yrWhi%x#' );
define( 'SECURE_AUTH_SALT', '!g@2Ur$1gm$I>$kjHZ.E9yZ,OIiU/eL?1Ry893T;$t5`@]}zI4JA+)9@WUOsTuKV' );
define( 'LOGGED_IN_SALT',   'p9> kbi;%rKi0:]wUAaDdQ_fAMbGx^o.GttJA*Zw(<|!J>)izJ/lRx?Fi5vSqI ,' );
define( 'NONCE_SALT',       'XGZ3}9f2s(2fX]Gu?:=Z>4O7mlL81WEvY!~m4,OA>;Tt3+Rdy^M;Eup;qSh}{: s' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
