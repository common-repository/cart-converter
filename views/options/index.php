<style type="text/css">
	#tabs { border-radius: 0px !important; background: none; border: none; }
	.ui-tabs .ui-tabs-panel { background: #fff; border: 1px solid #a6c9e2; border-radius: 0; }
	.ui-tabs .ui-tabs-nav { border-radius: 0; background: none; border: none; padding: 0; }
	.ui-tabs .ui-tabs-nav li { border-radius: 0; }
</style>
<div class="wrap">
	<h1 class="dashicons-before"><?php echo CART_CONVERTER_NAME; ?></h1>
	<p></p>
	<?php 
		if( !empty( $message ) ):
	?>
		<div id="message" class="updated notice is-dismissible"><p><?php echo $message; ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
	<?php 
		endif;
	?>
	<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php 
			$current_tab = "";
			if( isset( $tab ) && !empty( $tab ) ){
				foreach ($tab as $key => $value) {
					$class = "";
					if( $value["active"] ){
						$class = " nav-tab-active";
						$current_tab = $key;
					}
		?>
		<a href="<?php echo $value["url"]; ?>" class="nav-tab<?php echo $class; ?>"><?php echo $value["label"]; ?></a>
		<?php 
				}
			}
		?>
	</h2>

	
		
		<div id="tabs">
			<?php 
				if( "general" == $current_tab ) :
			?>
			<form method="post" action="">
				<?php echo $_wpnonce; ?>
			  	<div id="general-setting">
			  		<div class="cc-section">
			  			<h2 class="cc-heading"><?php _e("Time Settings"); ?></h2>
			  			<div class="cc-row-time">
			  				<table class="form-table">
			  					<tr>
			  						<th scope="row">Abandon Cart Time</th>
			  						<td>
			  							<input name="data[<?php echo CART_CONVERTER_PREFIX."_cart_time"; ?>]" type="text" id="<?php echo CART_CONVERTER_PREFIX."_cart_time"; ?>" value="<?php echo $data[CART_CONVERTER_PREFIX."_cart_time"] ?>" class="regular-text">
			  						</td>
			  						<tr>
			  							<th scope="row">Abandon Cart Time Measure</th>
			  							<td>
			  								<?php 
			  									$args = array( 1 => "Days", 2 => "Hours", 3 => "Minutes" );
			  									$cur_cart_time_measure = $data[CART_CONVERTER_PREFIX."_cart_time_measure"];
			  								?>
			  								<select name="data[<?php echo CART_CONVERTER_PREFIX."_cart_time_measure"; ?>]" id="<?php echo CART_CONVERTER_PREFIX."_cart_time_measure"; ?>">
			  									<?php 
			  										foreach ( $args as $key => $value ) {
			  											$selected = $key == $cur_cart_time_measure ? "selected" : "";
			  											echo sprintf( "<option value=\"%d\"%s>%s</option>", $key, $selected, $value );
			  										}
			  									?>
			  								</select>
			  								<p class="description" id="tagline-description">Please enter time measure after which the cart should be considered as abandon</p>
			  							</td>
			  						</tr>
			  					</tr>
			  				</table>
			  			</div>
					  	<div id="sidebar">
					        <div class="widget">
					            <h2 class="promo"><?php _e('Need support?'); ?></h2>
					            <p><?php _e('If you are having problems with this plugin please talk about them in the'); ?> <a href="http://wordpress.org/support/plugin/cart-converter"><?php _e('support forum'); ?></a>.</p>
					        </div>

					        <div class="widget">
					            <h2 class="promo"><?php _e('Want more features?'); ?></h2>
					            <p><?php _e('Please go to '); ?> <a href="http://cartconverterapp.com/upgrade/"><?php _e('upgrade'); ?></a> site.</p>
					        </div>
					    </div>
			  		</div>
			  	</div>
		  	<?php submit_button(); ?>
		  	</form>
		  	<?php 
		  		endif;
		  	?>
		  	<?php 
		  		if( "email" == $current_tab ) :
		  	?>
		  	<form method="post" action="">
		  		<?php echo $_wpnonce; ?>
			  	<div id="email-setting">
			    	<div class="cc-section">
			    		<h2 class="cc-heading"><?php _e("Email Template Settings"); ?></h2>

			    		<div class="cc-row-email">
			    			<table class="form-table">
			    				<tr>
			    					<th scope="row"><?php _e("Subject", CART_CONVERTER_PREFIX); ?></th>
			    					<td>
			    						<input name="data[<?php echo CART_CONVERTER_PREFIX."_subject"; ?>]" type="text" id="<?php echo CART_CONVERTER_PREFIX."_cart_link"; ?>" value="<?php echo $data[CART_CONVERTER_PREFIX."_subject"] ?>" class="regular-text" placeholder="Recover Cart Email">

			    					</td>
			    				</tr>
		    					<tr>
		    						<th scope="row">
		    							<?php _e('Email Template', CART_CONVERTER_PREFIX); ?><br/>
		    							
		    						</th>
		    						<td>
		    							<?php 
	    									$content = $data[CART_CONVERTER_PREFIX."_email_template"];
		    								wp_editor( $content, CART_CONVERTER_PREFIX."_email_template", array(
		    									"textarea_name" => "data[". CART_CONVERTER_PREFIX."_email_template" ."]",
		    									"textarea_rows" => 10
		    								) ); 
		    							?>
		    							<p class="desciprtion"> Enter the email that is sent to users to convert purchase. HTML is accepted. Available template tags:<br>
						    			{#cart_link} - A link to convert cart<br>
						    			{#customer_name} - The buyer's display name<br>
						    			</p>
		    						</td>
		    					</tr>
		    					<tr>
			    					<th scope="row"><?php _e("% Discount Cart", CART_CONVERTER_PREFIX); ?></th>
			    					<td>
			    						<input name="data[<?php echo CART_CONVERTER_PREFIX."_coupon_percent"; ?>]" type="number" id="cart_converter_discount_percent" value="<?php echo $data[CART_CONVERTER_PREFIX."_coupon_percent"] ?>" class="regular-text" placeholder="10%">
			    					</td>
			    				</tr>
			    			</table>
			    			
			    		</div>
			    	</div>
					<?php submit_button(); ?>
			  	</div>
		  	</form>
		  	<?php 
		  		endif;
		  	?>
		  	<?php 
		  		if( "cart-list" == $current_tab ) :
		  	?>
		  	<form method="post" action="">
		  		<?php echo $_wpnonce; ?>
			  	<div id="cart-list">
					<?php 
						if( isset( $table ) && !empty( $table ) ){
							$table->prepare_items();
							$table->display();
						}
						
					?>		
			  	</div>
				<?php submit_button(); ?>
		  	</form>
		  	<?php 
		  		endif;
		  	?>
		</div>
		
</div>
