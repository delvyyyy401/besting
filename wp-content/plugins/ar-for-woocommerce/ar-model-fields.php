<?php
/**
 * AR Display
 * https://augmentedrealityplugins.com
**/
if (!defined('ABSPATH'))
    exit;

/* Adding a custom AR Display Product Data tab*/

function ar_woo_tab( $tabs ) {
  $tabs['ardisplay'] = array(
    'label'  => __( 'AR Models', 'ar-for-woocommerce' ),
    'target' => 'ardisplay_panel',
    'class'  => array(),
  );
  return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'ar_woo_tab' );

function ar_woo_tab_panel() {
    global $post, $wpdb, $shortcode_examples, $ar_whitelabel, $wp, $ar_wcfm, $ar_css_styles, $ar_css_names;
    if(isset($wp->query_vars['wcfm-products-manage'])){
        $post = get_post($wp->query_vars['wcfm-products-manage']);
    } 
    $plan_check = get_option('ar_licence_plan');
  echo '
  <div id="ardisplay_panel" class="panel woocommerce_options_panel" style="padding:10px !important">
    <div class="options_group">
    <div style="width:50%;float:left;vertical-align:bottom">
        <div style="float:left">';
        $ar_wl_logo = get_option('ar_wl_file'); 
          if ($ar_wl_logo){
              echo '<img src="'.$ar_wl_logo.'" style="max-height:160px;padding: 0 30px 50px 0;" align="left">';
          }elseif ($ar_whitelabel!=true){
            echo '<img src="'.esc_url( plugins_url( "assets/images/Ar_logo.png", __FILE__ ) ).'" height="120" style="padding-right:30px;padding-bottom:40px">';
          }
    
    //Licence check      
    $model_count = ar_model_count();
    if ((substr(get_option('ar_licence_valid'),0,5)!='Valid')AND($model_count>=2)){
        echo '<br clear="all"><b><a href="admin.php?page=wc-settings&tab=ar_display">'.__('Please check your subscription & license key.', 'ar-for-woocommerce').'</a>'.__('If you are using the free version of the plugin then you have exceeded the limit of allowed models.', 'ar-for-woocommerce').'</b>';
        exit;
    }else{
        $model_array=array();
        $model_array['id'] = $post->ID;
     echo '
    </div>
		<b><input id="ar_shortcode" type="text" value="[ardisplay id='.$post->ID.']" readonly style="width:150px; float:none !important;" onclick="copyToClipboard(\'ar_shortcode\');document.getElementById(\'copied\').innerHTML=\'&nbsp;Copied!\';"></b> <span id="copied"></span>
		<br>'.__('Please place this shortcode on your page where you would like the model displayed.', 'ar-for-woocommerce').' <br><br>';
		if (!isset($ar_wcfm)){
    		echo '<b>'.__('To replace your featured image with the AR model', 'ar-for-woocommerce').'</b>, '.__('copy the woocommerce folder found in the AR for Woocommerce plugin "templates" folder to your theme.', 'ar-for-woocommerce').'
    		<br clear="all"><br>'.__('Models can be uploaded as a USDZ or REALITY file for iOS, and a GLB or GLTF file for viewing on Android devices and within the broswer display. The following formats can be uploaded and will be automatically converted to GLB format - DAE, DXF, 3DS, OBJ, PDF, PLY, STL, or Zipped versions of these files. Model conversion accuracy cannot be guaranteed, please check your model carefully.', 'ar-for-woocommerce').'
            ';
    		
            if (!$ar_whitelabel){
    		    echo '<br><a href="https://augmentedrealityplugins.com/support/#3d" target="_blank">'.__('Sample 3D Models', 'ar-for-woocommerce').'</a> <a href="https://augmentedrealityplugins.com/support/#hdr" target="_blank">'.__('Sample HDR Images', 'ar-for-woocommerce').'</a> ';
    		}
		}
    	echo '
        </div>
        <div style="width:50%;float:left">'.$shortcode_examples.'
        </div>
        <div style="clear:both"></div>
            <hr><div style="float:left">';
            //GLB File Input
    		woocommerce_wp_text_input( array(
    			'id'				=> '_glb_file',
    			'label'				=> '<img src="'.esc_url( plugins_url( "assets/images/android.png", __FILE__ ) ).'" style="height:20px; padding-right:10px; vertical-align: middle; ">'.__('GLB/GLTF 3D Model', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'class'             => 'ar_input_field',
    			'wrapper_class' => 'form-row-first',
    			'description'		=> __( 'Upload a GLB or GLTF 3D model file for Android devices. You can also upload a DAE, DXF, 3DS, OBJ, PDF, PLY, STL, or Zipped version of these files and they will be converted automatically.', 'ar-for-woocommerce' )
    		) );
    		echo '</div><div style="float:left;padding-top:10px"><input id="upload_usdz_button" class="button" type="button" value="'.__('Upload AR Files', 'ar-for-woocommerce').'" /></div><br clear="all">';
    		//USDZ File input
    		echo '<div style="float:left">';
    		woocommerce_wp_text_input( array(
    			'id'				=> '_usdz_file',
    			'label'				=> '<img src="'.esc_url( plugins_url( "assets/images/ios.png", __FILE__ ) ).'" style="height:20px; padding-right:10px; vertical-align: middle; ">'.__('USDZ 3D Model', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'class'             => 'ar_input_field',
    			'description'		=> __( 'Upload a USDZ 3D model file for iOS devices', 'ar-for-woocommerce' )
    		) );
    		echo '</div><div style="float:left;padding-top:10px"><input id="upload_usdz_button" class="button" type="button" value="'.__('Upload AR Files', 'ar-for-woocommerce').'" /></div><br clear="all">';
    		if($plan_check!='Premium') { 
    		    echo '<b>'.__('Premium Plans Only', 'ar-for-woocommerce').'</b><hr>'; 
    		    $disabled = ' disabled';
    		    $readonly = ['readonly' => 'readonly'];
    		    $custom_attributes = $readonly;
    		    echo '<div style="pointer-events: none;">'; //disable mouse clicking 
    		}else{
    		    $disabled = '';
    		    $readonly = '';
    		    //Used for Scale inputs
    		    $custom_attributes = array(
                    'step' => '0.1',
                    'min' => '0.1');
    		}
    		//Skybox File Input
    		echo '<div style="float:left">';
    		woocommerce_wp_text_input( array(
    			'id'				=> '_skybox_file',
    			'label'				=> __( 'Skybox/Background', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'class'             => 'ar_input_field',
    			'description'		=> __( 'Upload a HDR, JPG or PNG file to use as the Skybox or background image - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
    		) );
    		echo '</div><div style="float:left;padding-top:10px"><input id="upload_skybox_button" class="button" type="button" value="'.__('Upload Skybox File','ar-for-woocommerce').'" '.$disabled.' /></div><br clear="all">';
    		//Environment Image
    		echo '<div style="float:left">';
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_environment',
    			'label'				=> __( 'Environment Image', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'class'             => 'ar_input_field',
    			'description'		=> __( 'Upload a HDR, JPG or PNG file to use as the environment image - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
    		) );
    		echo '</div><div style="float:left;padding-top:10px"><input id="upload_environment_button" class="button" type="button" value="'.__('Upload Environment File','ar-for-woocommerce').'" '.$disabled.' /></div><br clear="all">';
    		//Placement
    		woocommerce_wp_select( array(
    			'id' 		=> '_ar_placement',
    			'label' 	=> __( 'Model placement', 'ar-for-woocommerce' ),
                    'options' => array(
                        'floor' => __('Floor - Horizontal', 'ar-for-woocommerce'),
                        'wall' => __('Wall - Vertical', 'ar-for-woocommerce')
                    ),
                'desc_tip'			=> 'true',
    			'class'             => 'ar_input_field',
    			'description'		=> __( 'Place your model on a horizontal or vertical surface', 'ar-for-woocommerce' ),
    			'custom_attributes' => $disabled
    		) );
            
			//Scale Inputs
			$ar_x = get_post_meta($post->ID, '_ar_x', true );
            if ( ! $ar_x ) {
                $ar_x = 1;
            }
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_x',
    			'label'				=> __( 'Scale X', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'description'		=> __( '1 = 100%, only affects desktop view, not available in AR', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_x,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		$ar_y = get_post_meta($post->ID, '_ar_y', true );
            if ( ! $ar_y ) {
                $ar_y = 1;
            }
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_y',
    			'label'				=> __( 'Scale Y', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'description'		=> __( '1 = 100%, only affects desktop view, not available in AR', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_y,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		$ar_z = get_post_meta($post->ID, '_ar_z', true );
            if ( ! $ar_z ) {
                $ar_z = 1;
            }
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_z',
    			'label'				=> __( 'Scale Z', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'description'		=> __( '1 = 100%, only affects desktop view, not available in AR', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_z,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		echo '
          <br clear="all">';
            //Zoom and Field of View Inputs
            $fov_in_array=array();
            $fov_in_array['default']=__('Default', 'ar-for-woocommerce' );
            for ($x = 10; $x <= 180; $x+=10) {
                $fov_in_array [$x] = $x.' '.__('Degrees', 'ar-for-woocommerce' );
            }
    		woocommerce_wp_select( array(
    			'id'				=> '_ar_field_of_view',
    			'label'				=> __( 'Field of View', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'options' =>  $fov_in_array,
                )
    		);
    		$zoom_in_array=array();
            $zoom_in_array['default']=__('Default', 'ar-for-woocommerce' );
            for ($x = 100; $x >= 0; $x-=10) {
                $zoom_in_array [$x] = $x.'%';
            }
    		woocommerce_wp_select( array(
    			'id'				=> '_ar_zoom_in',
    			'label'				=> __( 'Zoom In', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'options' =>  $zoom_in_array,
    			
                )
    		);
            $zoom_out_array=array();
            $zoom_out_array['default']=__('Default', 'ar-for-woocommerce' );
            for ($x = 0; $x <= 100; $x+=10) {
                $zoom_out_array [$x] = $x.'%';
            }
    		woocommerce_wp_select( array(
    			'id'				=> '_ar_zoom_out',
    			'label'				=> __( 'Zoom Out', 'ar-for-woocommerce' ),
    			'wrapper_class' => 'scale_input',
    			'options' =>  $zoom_out_array
                )
    		);
    		echo '
          <br clear="all">';
          //Exposure and Shadow Inputs
			$ar_exposure = get_post_meta($post->ID, '_ar_exposure', true );
            if ((!$ar_exposure)AND($ar_exposure!='0')){ $ar_exposure = 1; }
            $custom_attributes = array(
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '2');
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_exposure',
    			'label'				=> __( 'Exposure', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_exposure,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		$ar_shadow_intensity = get_post_meta($post->ID, '_ar_shadow_intensity', true );
    		if ((!$ar_shadow_intensity)AND($ar_shadow_intensity!='0')){ $ar_shadow_intensity = 1; }
            $custom_attributes = array(
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '2');
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_shadow_intensity',
    			'label'				=> __( 'Shadow Intensity', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_shadow_intensity,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		$ar_shadow_softness = get_post_meta($post->ID, '_ar_shadow_softness', true );
    		if ((!$ar_shadow_softness)AND($ar_shadow_softness!='0')){ $ar_shadow_softness = 1; }
            $custom_attributes = array(
                    'step' => '0.1',
                    'min' => '0',
                    'max' => '1');
    		woocommerce_wp_text_input( array(
    			'id'				=> '_ar_shadow_softness',
    			'label'				=> __( 'Shadow Softness', 'ar-for-woocommerce' ),
    			'desc_tip'			=> 'true',
    			'wrapper_class' => 'scale_input',
    			'type' => 'number',
    			'value' => $ar_shadow_softness,
                'custom_attributes' => $custom_attributes
    		    ) 
    		);
    		echo '
          <br clear="all">
          <div class="ar_admin_viewer">';
            woocommerce_wp_checkbox( array( 
				'id'            => '_ar_rotate', 
				'label'         => __('Disable Interaction Prompt', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Turn off the rotation and cursor prompt on your model? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly,
				)
			);
          // Variants
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_variants', 
				'label'         => __('Model includes variants', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Does your model include texture variants? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly,
				)
			);
    		woocommerce_wp_checkbox( array( 
				'id'            => '_ar_environment_image', 
				'label'         => __('Legacy lighting', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'The default lighting is designed as a neutral lighting environment that is evenly lit on all sides, but there is also a baked-in legacy lighting primarily for frontward viewing available', 'ar-for-woocommerce' ),
				'custom_attributes' => $readonly
				)
			);
    		woocommerce_wp_checkbox( array( 
				'id'            => '_ar_resizing', 
				'label'         => __('Resizing - Disable in AR', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Disable the ability for the user to rezise the model in the AR view on Android devices only? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_view_hide', 
				'label'         => __('AR View - Hide', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Disable the ability for the user to view the model in the AR view? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_qr_hide', 
				'label'         => __('QR Code - Hide', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Hide the QR code on the desktop view? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_hide_dimensions', 
				'label'         => __('AR Dimensions - Hide', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Disable the ability for the user to view the dimensions of a model? - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_animation', 
				'label'         => __('Animation - Play/Pause Button', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Show a play/pause button if your GLB/GLTF contains animation. Only displays on desktop view - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			woocommerce_wp_checkbox( array( 
				'id'            => '_ar_autoplay', 
				'label'         => __('Animation - Auto Play', 'ar-for-woocommerce' ), 
				'desc_tip'			=> 'true',
				'description'   => __( 'Auto Play your animation if your GLB/GLTF contains animation. Only animates on desktop view - Optional', 'ar-for-woocommerce' ),
    			'custom_attributes' => $readonly
				)
			);
			
			?>
			<p class="form-field _ar_cta_field ">
            <label for="_ar_cta"><?php _e( 'Call To Action Button', 'ar-for-woocommerce' ); ?></label><span class="woocommerce-help-tip" data-tip="<?php _e( 'Button Displays in 3D Model view and in AR view on Android only', 'ar-for-woocommerce' );?>"></span>
            <input type="text" name="_ar_cta" id="_ar_cta" class="regular-text" value="<?php echo get_post_meta( $post->ID, '_ar_cta', true );?>" <?php echo $disabled;?> style="width:140px;" > </p>
            <p class="form-field _ar_autoplay_field ">
            <label for="_ar_cta_url"><?php _e( 'Call To Action URL', 'ar-for-woocommerce' ); ?></label>
            <input type="url" pattern="https?://.+" name="_ar_cta_url" id="_ar_cta_url" class="regular-text" value="<?php echo get_post_meta( $post->ID, '_ar_cta_url', true );?>" <?php echo $disabled;?> > </p>
            
			<p class="form-field _ar_hotspot_field ">
		    <label for="_ar_hotspot_text"><?php _e( 'Hotspots', 'ar-for-woocommerce' );?></label><span class="woocommerce-help-tip" data-tip="<?php _e( 'Add your text, click the Add Hotspot button, then click on your model where you would like it placed', 'ar-for-woocommerce' );?>"></span>
		    <input type="text" name="_ar_hotspot_text" id="_ar_hotspot_text" class="regular-text hotspot_annotation" placeholder="<?php _e( 'Hotspot Text', 'ar-for-woocommerce' );?>" <?php echo $disabled;?>>
            <input type="checkbox" name="_ar_hotspot_check" id="_ar_hotspot_check" class="regular-text" value="y" style="display:none;">
            <input type="button" class="button" onclick="enableHotspot()" value="<?php _e( 'Add Hotspot', 'ar-for-woocommerce' );?>" <?php echo $disabled;?>> </p>
		    
        	
        	
        	<?php 
        	if (get_post_meta( $post->ID, '_ar_hotspots', true )){
        	    $_ar_hotspots = get_post_meta( $post->ID, '_ar_hotspots', true );
        	    $hotspot_count = count($_ar_hotspots['annotation']);
        	    $hide_remove_btn = '';
        	    foreach ($_ar_hotspots['annotation'] as $k => $v){
        	        echo '<div id="_ar_hotspot_container_'.$k.'"><p class="form-field _ar_autoplay_field "><label for="_ar_animation">Hotspot '.$k.'</label><span id="_ar_hotspot_field_'.$k.'">
        	        <input hidden="true" id="_ar_hotspots[data-normal]['.$k.']" name="_ar_hotspots[data-normal]['.$k.']" value="'.$_ar_hotspots['data-normal'][$k].'">
        	        <input hidden="true" id="_ar_hotspots[data-position]['.$k.']" name="_ar_hotspots[data-position]['.$k.']" value="'.$_ar_hotspots['data-position'][$k].'">
        	        <input type="text" class="regular-text hotspot_annotation" id="_ar_hotspots[annotation]['.$k.']" name="_ar_hotspots[annotation]['.$k.']" hotspot_name="hotspot-'.$k.'" value="'.$v.'">
        	        </span></div></p>';
        	    
        	    }
        	}else{
        	    $hotspot_count = 0;
        	    $hide_remove_btn = 'style="display:none;"';
        	    echo '<div id="_ar_hotspot_container_0"></div>';
        	}
        	?>
        	<p class="form-field _ar_hotspot_field "><label for="_ar_remove_hotspot"></label> <input id="_ar_remove_hotspot" type="button" class="button" <?php echo $hide_remove_btn;?> onclick="removeHotspot()" value="Remove last hotspot" <?php echo $disabled;?>></p>
        	
        	<div style="clear:both"></div>
                <h3> <?php
                	    _e('Element Positions and CSS Styles', 'ar-for-woocommerce' );
                        if ($disabled!=''){echo ' - '.__('Premium Plans Only', 'ar-for-woocommerce');}
                        ?></h3>
                <p class="form-field _ar_css_field"><label for="_ar_animation"><?php _e( 'Override Global Settings', 'ar-for-woocommerce' );?></label><input type="checkbox" name="_ar_css_override" id="_ar_css_override" class="regular-text" value="1" <?php if (get_post_meta( $post->ID, '_ar_css_override', true )=='1'){echo 'checked';$hide_custom_css='';}else{$hide_custom_css='style="display:none;"';} echo $disabled;?>> </p>
                <div style="clear:both"></div>
                <div id="ar_custom_css_div" <?php echo $hide_custom_css;?>>
                    <input type="button" class="button" onclick="importCSS()" value="<?php _e( 'Import Global Settings', 'ar-for-woocommerce' );?>" <?php echo $disabled;?>><br  clear="all"><br>
                    
                    <?php //CSS Positions
                    $ar_css_positions = get_post_meta( $post->ID, '_ar_css_positions', true );
                    foreach ($ar_css_names as $k => $v){
                        ?>
                        <div>
                          <div style="width:160px;float:left;"><strong>
                              <?php _e($k, 'ar-for-woocommerce' );?> </strong></div>
                          <div style="float:left;"><select id="_ar_css_positions[<?=$k;?>]" name="_ar_css_positions[<?=$k;?>]" <?= $disabled;?>>
                              <option value="">Default</option>
                              <?php 
                              foreach ($ar_css_styles as $pos => $css){
                                echo '<option value = "'.$pos.'"';
                                if (is_array($ar_css_positions)){
                                    if ($ar_css_positions[$k]==$pos){echo ' selected';}
                                }
                                echo '>'.$pos.'</option>';
                              }?>
                              
                              </select></div>
                        </div>
                        <br  clear="all">
                        <br>
                    <?php
                    }
                    ?>
                    <div>
                      <div style="width:160px;float:left;"><strong>
                          <?php
                            $ar_css = get_post_meta( $post->ID, '_ar_css', true );
                            $ar_css_import_global='';
                            if (get_option('ar_css')!=''){
                                $ar_css_import_global = get_option('ar_css');
                            }
                            $ar_css_import=ar_curl(esc_url( plugins_url( "assets/css/ar-display-custom.css", __FILE__ ) ));
                    	    _e('CSS Styling', 'ar-for-woocommerce' );
                            ?>
                            </strong>
                            </div>
                      <div style="float:left;"><textarea id="_ar_css" name="_ar_css" style="width: 450px; height: 200px;" <?= $disabled;?>><?php echo $ar_css; ?></textarea></div>
                    </div>
                </div>
            </div>
			<?php
			
          /* Display the 3D model if it exists */
          if (get_post_meta($model_array['id'], '_glb_file', true )!=''){
            echo '<div class="ar_admin_viewer">';
            echo '<div style="width: 100%; border: 1px solid #f8f8f8;">'.ar_display_shortcode($model_array).'</div>'; 
            $ar_camera_orbit = get_post_meta( $post->ID, '_ar_camera_orbit', true );?>
            
            <button id="downloadPosterToBlob" onclick="downloadPosterToDataURL()" class="button" type="button" style="margin-top:10px">Set Featured Image</button>
            <input type="hidden" id="_ar_poster_image_field" name="_ar_poster_image_field">
          
            <input id="camera_view_button" class="button" type="button" style="float:right;margin-top: 10px" value="<?php _e( 'Set Current Camera View as Initial', 'ar-for-woocommerce' );?>" <?php echo $disabled;?> />
            <div id="_ar_camera_orbit_set" style="float:right;margin: 10px;display:none"><span style="color:green;margin-left: 7px; font-size: 19px;">&#10004;</span></div><input id="_ar_camera_orbit" name="_ar_camera_orbit" type="text" value="<?php echo $ar_camera_orbit;?>" style="display:none;"><br clear="all" style="float:right;">
            
            <?php 
            echo '</div>';
          }
        
           /* Asset Builder */
           echo '</div><br clear="all">
          <hr clear="all">
        <span href="#" id="asset_builder_button" class="asset_btn">'.__('3D Asset Builder', 'ar-for-woocommerce').'</span>
        <div id="asset_builder" style="display:none">
            <p>'.__('Choose a model below and then upload your texture files.', 'ar-for-woocommerce').'<br>'.__('You may need to refresh your browser once your AR Asset is built to ensure latest texture files are shown.', 'ar-for-woocommerce').'</p>
    	   <input type="hidden" name="_asset_file" id="_asset_file" class="regular-text">';
             for($i = 0; $i<10; $i++) {
            	 echo '
               <span id="texture_'.$i.'" class="nodisplay"><p>
    	        <input type="text" name="_asset_texture_file_'.$i.'" id="_asset_texture_file_'.$i.'" class="regular-text"> <input id="upload_asset_texture_button_'.$i.'" class="button" type="button" value="'.__('Texture File', 'ar-for-woocommerce').'" /> <a href="#" onclick="document.getElementById(\'_asset_texture_file_'.$i.'\').value = \'\'"><img src="'.esc_url( plugins_url( "assets/images/delete.png", __FILE__ ) ).'" style="width: 15px;vertical-align: middle;"></a>
    	        <input type="text" name="_asset_texture_id_'.$i.'" id="_asset_texture_id_'.$i.'" class="nodisplay"></p></span>';
        	 }
            echo '<input type="text" name="_asset_texture_flip" id="_asset_texture_flip" class="nodisplay">
            <div id="asset_builder_iframe"></div>
        </div>';
          
           } 
           if($plan_check!='Premium') { 
        	    echo '</div>'; 
        	//close the div that disables mouse clicking 
        	} 
          
          echo '
    </div>
  </div>';
    ?>
    <script>
     
        document.getElementById('_skybox_file').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            element.setAttribute("skybox-image", this.value);
        });
        document.getElementById('_ar_environment').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            element.setAttribute("environment-image", this.value);
        });
        document.getElementById('_ar_placement').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            if (this.value == 'floor'){
                element.setAttribute("ar-placement", '');
            }else{
                element.setAttribute("ar-placement", this.value);
            }
        });
        document.getElementById('_ar_zoom_in').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            if (this.value == 'default'){
                element.setAttribute("min-camera-orbit", 'auto auto 20%');
            }else{
                element.setAttribute("min-camera-orbit", 'auto auto '+(100 - this.value) +'%');
            }
        });
        document.getElementById('_ar_zoom_out').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            if (this.value == 'default'){
                element.setAttribute("max-camera-orbit", 'Infinity auto 300%');
            }else{
                element.setAttribute("max-camera-orbit", 'Infinity auto '+(((this.value/100)*400)+100) +'%');
            }
        });
        document.getElementById('_ar_field_of_view').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            if (this.value == 'default'){
                element.setAttribute("field-of-view", '');
            }else{
                element.setAttribute("field-of-view", this.value +'deg');
            }
        });
        document.getElementById('_ar_environment_image').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            if (document.getElementById("_ar_environment_image").checked == true){
                element.setAttribute("environment-image", 'legacy');
            }else{
                element.setAttribute("environment-image", '');
            }
            console.log(document.getElementById("_ar_environment_image").checked);
        });
        document.getElementById('_ar_exposure').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            element.setAttribute("exposure", this.value);
        });
        document.getElementById('_ar_shadow_intensity').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            element.setAttribute("shadow-intensity", this.value);
        });
        document.getElementById('_ar_shadow_softness').addEventListener('change', function() {
            var element = document.getElementById("model_<?php echo $model_array['id']; ?>");
            element.setAttribute("shadow-softness", this.value);
        });
        
        const modelViewer = document.querySelector('#model_<?php echo $model_array['id']; ?>');
        modelViewer.addEventListener('camera-change', () => {
            const orbit = modelViewer.getCameraOrbit();
            const orbitString = `${orbit.theta}rad ${orbit.phi}rad ${orbit.radius}m`;
            jQuery(document).ready(function($){
                $( "#camera_view_button" ).click(function() {
                    document.getElementById("_ar_camera_orbit_set").style.display='block';
                    document.getElementById("_ar_camera_orbit").value=orbitString;
                });
            });
        });
        
        document.getElementById('_ar_view_hide').addEventListener('change', function() {
            var element = document.getElementById("ar-button_<?php echo $model_array['id']; ?>");
            if (document.getElementById("_ar_view_hide").checked == true){
                element.style.display = "none";
            }else{
                element.style.display = "block";
            }
        });
        
        document.getElementById('_ar_qr_hide').addEventListener('change', function() {
            var element = document.getElementById("ar-qrcode_<?php echo $model_array['id']; ?>");
            if (document.getElementById("_ar_qr_hide").checked == true){
                element.style.display = "none";
            }else{
                element.style.display = "block";
            }
        });
        
        document.getElementById('_ar_hide_dimensions').addEventListener('change', function() {
                var element = document.getElementById("controls");
                var element_checkbox = document.getElementById("show-dimensions_<?php echo $model_array['id']; ?>");
                if (document.getElementById("_ar_hide_dimensions").checked == true){
                    element.style.display = "none";
                    element_checkbox.checked = false;
                    const modelViewer = document.querySelector('#model_<?php echo $model_array['id']; ?>');
                    modelViewer.querySelectorAll('button').forEach((hotspot) => {
                      if ((hotspot.classList.contains('dimension'))||(hotspot.classList.contains('dot'))){
                            hotspot.classList.add('nodisplay');
                      }
                    });
                }else{
                    element.style.display = "block";
                }
            });
        
        [ _ar_x, _ar_y, _ar_z ].forEach(function(element) {
            element.addEventListener('change', function() {
                var x = document.getElementById('_ar_x').value;
                var y = document.getElementById('_ar_y').value;
                var z = document.getElementById('_ar_z').value;
                const updateScale = () => {
                  modelViewerTransform.scale = x +' '+ y +' '+ z;
                };
                updateScale();
            });
        });
        document.getElementById('_ar_animation').addEventListener('change', function() {
            var element = document.getElementById("ar-button-animation");
            if (document.getElementById("_ar_animation").checked == true){
                element.style.display = "block";
            }else{
                element.style.display = "none";
            }
        });
        
        document.body.addEventListener( 'keyup', function ( event ) {
            //Hotspots update on change
            if( event.target.id.startsWith('_ar_hotspots' )) {
                var hotspot_name = event.target.getAttribute("hotspot_name");
                var hotspot_content = document.getElementById(event.target.getAttribute("hotspot_name")).innerHTML;
                document.getElementById(hotspot_name).innerHTML='<div class="annotation">'+event.target.value+'</div>';
            };
            //CTA update on change 
            if( event.target.id=='_ar_cta') {
                document.getElementById("ar-cta-button-container").style="display:block";
                document.getElementById("ar-cta-button").innerHTML=event.target.value;
            };
        });
        
        //Custom CSS Importing
        function importCSS(){
            var css_content = '<?php if ($ar_css_import_global!=''){ echo ar_encodeURIComponent($ar_css_import_global);}else{echo ar_encodeURIComponent($ar_css_import);}?>';
            document.getElementById('_ar_css').value = decodeURI(css_content);
            <?php 
            $ar_css_positions = get_option('ar_css_positions');
            if (is_array($ar_css_positions)){
                foreach ($ar_css_positions as $k => $v){
                      echo "document.getElementById('_ar_css_positions[".$k."]').value = '".$v."';
                      ";
                }
            }
            ?>
        }
        
        document.getElementById('_ar_css_override').addEventListener('change', function() {
            var element = document.getElementById("ar_custom_css_div");
            if (document.getElementById("_ar_css_override").checked == true){
                element.style.display = "block";
            }else{
                element.style.display = "none";
            }
        });
            
        //Save screenshot of model
        function downloadPosterToDataURL() {
            var btn = document.getElementById("downloadPosterToBlob");
            btn.innerHTML = 'Creating Image';
            btn.disabled = true;

            const url = modelViewer.toDataURL();
            const a = document.createElement("a");
            document.getElementById("_ar_poster_image_field").value=url;
            var xhr = new XMLHttpRequest();
            var data = new FormData();
            data.append('post_ID',document.getElementById("post_ID").value);
            data.append('post_title',document.getElementById("original_post_title").value);
            data.append('_ar_poster_image_field',document.getElementById("_ar_poster_image_field").value);
            data.append('action',"set_arwc_featured_image");
            data.append('nonce',"<?php echo wp_create_nonce('set_arwc_featured_image'); ?>");
            xhr.open("POST", "<?php echo admin_url('admin-ajax.php');?>", true);
            
            xhr.onload = function () { 
                var attachmentID = xhr.responseText; 
                wp.media.featuredImage.set( attachmentID );
                btn.innerHTML = 'Set Featured Image';
                btn.disabled = false;
            }

            xhr.send(data);
            return false;
        }
    </script>
    <!-- HOTSPOTS -->
    <!-- The following libraries and polyfills are recommended to maximize browser support -->
    <!-- Web Components polyfill to support Edge and Firefox < 63 -->
    <script src="https://unpkg.com/@webcomponents/webcomponentsjs@2.1.3/webcomponents-loader.js"></script>
    <!-- Intersection Observer polyfill for better performance in Safari and IE11 -->
    <script src="https://unpkg.com/intersection-observer@0.5.1/intersection-observer.js"></script>
    <!-- Resize Observer polyfill improves resize behavior in non-Chrome browsers -->
    <script src="https://unpkg.com/resize-observer-polyfill@1.5.1/dist/ResizeObserver.js"></script>
    <script>
        var hotspotCounter = <?php echo $hotspot_count; ?>;
        function addHotspot(MouseEvent) {
            //var _ar_hotspot_check = document.getElementById('_ar_hotspot_check').value;
            if (document.getElementById("_ar_hotspot_check").checked != true){
            return;
                
            }
            var inputtext = document.getElementById('_ar_hotspot_text').value;
        
            // if input = nothing then alert error if it isnt then add the hotspot
            if (inputtext == ""){
                alert("<?php _e( 'Enter hotspot text first, then click the Add Hotspot button.', 'ar-for-woocommerce' );?>");
                return;
            }else{
           
                const viewer = document.querySelector('#model_<?php echo $model_array['id']; ?>');
            
                const x = event.clientX;
                const y = event.clientY;
                const positionAndNormal = viewer.positionAndNormalFromPoint(x, y);
                
                // if the model is not clicked return the position in the console
                if (positionAndNormal == null) {
                    console.log('no hit result: mouse = ', x, ', ', y);
                    return;
                }
                const {position, normal} = positionAndNormal;
                
                // create the hotspot
                const hotspot = document.createElement('button');
                hotspot.slot = `hotspot-${hotspotCounter ++}`;
                hotspot.classList.add('hotspot');
                hotspot.id = `hotspot-${hotspotCounter}`;
                hotspot.dataset.position = position.toString();
                if (normal != null) {
                    hotspot.dataset.normal = normal.toString();
                }
                viewer.appendChild(hotspot);
                // adds the text to last hotspot
                var element = document.createElement("div");
                element.classList.add('annotation');
                element.appendChild(document.createTextNode(inputtext));
                document.getElementById(`hotspot-${hotspotCounter}`).appendChild(element);
                
                //Add Hotspot Input fields
                var hotspot_container = document.getElementById(`_ar_hotspot_container_${hotspotCounter -1}`);
                
		    
                hotspot_container.insertAdjacentHTML('afterend', `<div id="_ar_hotspot_container_${hotspotCounter}"><p class="form-field _ar_autoplay_field "><label for="_ar_animation">Hotspot ${hotspotCounter}</label><span class="ar_admin_field" id="_ar_hotspot_field_${hotspotCounter}">`);
                
                var hotspot_fields = document.getElementById(`_ar_hotspot_field_${hotspotCounter}`);
                var inputList = document.createElement("input");
                inputList.setAttribute('type','text');
                inputList.setAttribute('class','regular-text hotspot_annotation');
                inputList.setAttribute('id',`_ar_hotspots[annotation][${hotspotCounter}]`);
                inputList.setAttribute('name',`_ar_hotspots[annotation][${hotspotCounter}]`);
                inputList.setAttribute('hotspot_name',`hotspot-${hotspotCounter}`);
                inputList.setAttribute('value',document.getElementById('_ar_hotspot_text').value);
                hotspot_fields.insertAdjacentElement('afterend', inputList);
                
                var inputList = document.createElement("input");
                inputList.setAttribute('hidden','true');
                inputList.setAttribute('id',`_ar_hotspots[data-position][${hotspotCounter}]`);
                inputList.setAttribute('name',`_ar_hotspots[data-position][${hotspotCounter}]`);
                inputList.setAttribute('value',hotspot.dataset.position);
                hotspot_fields.insertAdjacentElement('afterend', inputList);
                
                var inputList = document.createElement("input");
                inputList.setAttribute('hidden','true');
                inputList.setAttribute('id',`_ar_hotspots[data-normal][${hotspotCounter}]`);
                inputList.setAttribute('name',`_ar_hotspots[data-normal][${hotspotCounter}]`);
                inputList.setAttribute('value',hotspot.dataset.normal);
                hotspot_fields.insertAdjacentElement('afterend', inputList);
                
                hotspot_fields.insertAdjacentHTML('afterend', '</span></p></div>');
                
                //Reset hotspot text box and checkbox
                document.getElementById('_ar_hotspot_text').value = "";
                document.getElementById("_ar_hotspot_check").checked = false;
                
                //Show Remove Hotspot button
                document.getElementById('_ar_remove_hotspot').style = "display:block;";
            }
        }
        function enableHotspot(){
            var inputtext = document.getElementById('_ar_hotspot_text').value;
            if (inputtext == ""){
                alert("<?php _e( 'Enter hotspot text first, then click Add Hotspot button.', 'ar-for-woocommerce' );?>");
                return;
            }else{
                document.getElementById("_ar_hotspot_check").checked = true;
            }
        }
        function removeHotspot(){
            var el = document.getElementById(`_ar_hotspot_container_${hotspotCounter}`);
            var el2 = document.getElementById(`hotspot-${hotspotCounter}`);
            if (el == null){
                alert("No hotspots to delete");
            }else{
                hotspotCounter --;
                el.remove(); // Removes the last added hotspot fields
                el2.remove(); // Removes the last added hotspot from model
            }
        }
    </script>
    <?php
    
    //Output Upload Choose AR Model Files Javascript
    echo ar_upload_button_js();
}
add_action( 'woocommerce_product_data_panels', 'ar_woo_tab_panel' );
/**
 * Add a bit of style.
 */
function ar_woo_custom_style() {
	$output='
	<style>
		#woocommerce-product-data .ardisplay_options.active:hover > a:before,
		#woocommerce-product-data .ardisplay_options > a:before {
			background: url( \''.esc_url( plugins_url( "assets/images/chair.png", __FILE__ ) ).'\' ) center center no-repeat;
			content: " " !important;
			background-size: 100%;
			width: 13px;
			height: 13px;
			display: inline-block;
			line-height: 1;
		}
		@media only screen and (max-width: 900px) {
			#woocommerce-product-data .ardisplay_options.active:hover > a:before,
			#woocommerce-product-data .ardisplay_options > a:before,
			#woocommerce-product-data .ardisplay_options:hover a:before {
				background-size: 35%;
			}
		}
		.ardisplay_options:hover a:before {
			background: url( \''.esc_url( plugins_url( "assets/images/chair.png", __FILE__ ) ).'\' ) center center no-repeat;
		}

	</style>';
	echo $output;

}
add_action( 'admin_head', 'ar_woo_custom_style' );

//Save Woocommerce product custom fields
add_action( 'woocommerce_process_product_meta_simple', 'save_ar_option_fields'  );
add_action( 'woocommerce_process_product_meta_variable', 'save_ar_option_fields'  );

add_action('plugins_loaded', function(){
  if($GLOBALS['pagenow']=='post.php'){
    add_action('admin_print_scripts', 'ar_woo_admin_scripts');
  }
});

function ar_woo_admin_scripts(){
  wp_enqueue_script('jquery');
  wp_enqueue_script('media-upload');
}
?>