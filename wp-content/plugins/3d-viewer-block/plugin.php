<?php
/**
 * Plugin Name: 3D Viewer Block
 * Description: Display interactive 3D models on the web
 * Version: 1.0.4
 * Author: bPlugins LLC
 * Author URI: http://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: 3d-viewer-block
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

// Constant
define( 'TDVB_PLUGIN_VERSION', isset($_SERVER['HTTP_HOST']) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.4' );
define( 'TDVB_ASSETS_DIR', plugin_dir_url( __FILE__ ) . 'assets/' );

// 3D Viewer Block
class TDVB3DViewerBlock{
	function __construct(){
		add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
		add_action( 'init', [$this, 'onInit'] );

		add_filter( 'upload_mimes', [$this, 'uploadMimes'] );
		if ( version_compare( $GLOBALS['wp_version'], '5.1' ) >= 0 ) {
			add_filter( 'wp_check_filetype_and_ext', [$this, 'wpCheckFiletypeAndExt'], 10, 5 );
		} else { add_filter( 'wp_check_filetype_and_ext', [$this, 'wpCheckFiletypeAndExt'], 10, 4 ); }
	}

	function enqueueBlockAssets(){
		echo wp_get_script_tag( [
			'src'	=> TDVB_ASSETS_DIR . 'js/model-viewer.min.js',
			'type'	=> 'module',
		] );
	}

	function onInit() {
		wp_register_style( 'tdvb-td-viewer-editor-style', plugins_url( 'dist/editor.css', __FILE__ ), [ 'wp-edit-blocks' ], TDVB_PLUGIN_VERSION ); // Backend Style
		wp_register_style( 'tdvb-td-viewer-style', plugins_url( 'dist/style.css', __FILE__ ), [ 'wp-editor' ], TDVB_PLUGIN_VERSION ); // Both Style

		register_block_type( __DIR__, [
			'editor_style'		=> 'tdvb-td-viewer-editor-style',
			'style'				=> 'tdvb-td-viewer-style',
			'render_callback'	=> [$this, 'render'],
		] ); // Register Block

		wp_set_script_translations( 'tdvb_editor_script', '3d-viewer-block', plugin_dir_path( __FILE__ ) . 'languages' ); // Translate
	}

	function render( $attributes ){
		extract( $attributes );

		$className = $className ?? '';
		$tdvbBlockClassName = 'wp-block-tdvb-td-viewer ' . $className . ' align' . $align;

		ob_start(); ?>
		<div class='<?php echo esc_attr( $tdvbBlockClassName ); ?>' id='tdvb3DViewerBlock-<?php echo esc_attr( $cId ) ?>' data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'></div>

		<?php return ob_get_clean();
	} // Render

	//Allow some additional file types for upload
	function uploadMimes( $mimes ) {
		// New allowed mime types.
		$mimes['glb'] = 'model/gltf-binary';
		$mimes['gltf'] = 'model/gltf-binary';
		return $mimes;
	}
	function wpCheckFiletypeAndExt( $data, $file, $filename, $mimes, $real_mime=null ){
		// If file extension is 2 or more 
		$f_sp = explode( '.', $filename );
		$f_exp_count = count( $f_sp );

		if( $f_exp_count <= 1 ){
			return $data;
		}else{
			$f_name = $f_sp[0];
			$ext = $f_sp[$f_exp_count - 1];
		}

		if( $ext == 'glb' || $ext == 'gltf' ){
			$type = 'model/gltf-binary';
			$proper_filename = '';
			return compact('ext', 'type', 'proper_filename');
		}else {
			return $data;
		}
	}
}
new TDVB3DViewerBlock;