<?php
/**
 * Plugin Name: Hot Simple Contact
 * Plugin URI: http://hot-themes.com/wordpress/plugins/simple-contact
 * Description:  Hot Simple Contact is a simple but very useful, configurable and responsive-friendly plugin that allows your clients to contact you through your WordPress site.
 * Version: 1.0
 * Author: HotThemes
 * Author URI: http://hot-themes.com/
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

new HOT_SIMPLE_CONTACT_plugin();

class HOT_SIMPLE_CONTACT_plugin {

	var $OPTIONS = array();
	var $plugin_path = '';

	function __construct() {
    	//delete_option('HOT_SIMPLE_CONTACT_PLUGIN'); // - Uncomment to reset options once (when adding new options)
		
		$this->plugin_path = plugins_url('/', __FILE__);
        $this->OPTIONS = get_option('HOT_SIMPLE_CONTACT_PLUGIN');
		$this->HOT_SIMPLE_CONTACT_textdomain();
			
			
		if (empty($this->OPTIONS)){
		///DEFAULTS/////////////////////////////////////
			
			$this->OPTIONS['width'] = 'auto';
			$this->OPTIONS['intro_text'] = '';
			$this->OPTIONS['show_name'] = true;
			$this->OPTIONS['show_subject'] = true;
			$this->OPTIONS['show_antispam'] = true;
			$this->OPTIONS['after_text'] = '';
			$this->OPTIONS['antispam_question'] = 'Two plus three is?';
			$this->OPTIONS['antispam_answer'] = '5';

			
		    update_option('HOT_SIMPLE_CONTACT_PLUGIN', (array)$this->OPTIONS);
		////////////////////////////////////////////////
		}		
					
		add_action('admin_menu',array( $this, 'HOT_SIMPLE_CONTACT_plugin_menu'));
		
		add_filter( 'the_content', array( &$this, 'HOT_SIMPLE_CONTACT_Render' ));
		add_filter( 'the_excerpt', array( &$this, 'HOT_SIMPLE_CONTACT_Render' ));
		add_filter( 'widget_text', array( &$this, 'HOT_SIMPLE_CONTACT_Render' ));

	    add_action('wp_head', array( $this, 'SimpleContact_inline_scripts_and_styles'),12);
		add_action('wp_print_styles', array( $this, 'SimpleContact_styles'),13);
	}

	function HOT_SIMPLE_CONTACT_textdomain() {
	    load_plugin_textdomain('hot_simplecontact', false, dirname(plugin_basename(__FILE__) ) . '/languages');
	}

	function SimpleContact_styles(){
	   	wp_enqueue_style( 'hot-simple-contact-style', plugins_url('/style.css', __FILE__));
	}

	function SimpleContact_inline_scripts_and_styles(){

		$width = $this->OPTIONS['width'];
	   
		echo '<style type="text/css">';
		echo '/* Hot Simple Contact PLUGIN START */';


			
			echo ' 
			.simple_contact_container {
				width:'.$width.';
			}
			';

	 
		echo '
		/* Hot Simple Contact PLUGIN END */
		</style>';
	   
	}

	function HOT_SIMPLE_CONTACT_Render($srchtml) {

		$intro_text = $this->OPTIONS['intro_text'];
		$show_name = $this->OPTIONS['show_name'];
		$show_subject = $this->OPTIONS['show_subject'];
		$show_antispam = $this->OPTIONS['show_antispam'];
		$after_text = $this->OPTIONS['after_text'];
		$antispam_question = $this->OPTIONS['antispam_question'];
		$antispam_answer = $this->OPTIONS['antispam_answer'];

		if ( !preg_match("#{simplecontact}(.*?){/simplecontact}#s",$srchtml) ) {
			return $srchtml;
		}
	   
		if (preg_match_all("#{simplecontact}(.*?){/simplecontact}#s", $srchtml, $matches, PREG_PATTERN_ORDER) > 0) {
			$HOT_SIMPLE_CONTACTcount = -1;
			foreach ($matches[0] as $match) {
				$HOT_SIMPLE_CONTACTcount++;
				
				$hotsimplecontact_input = preg_replace("/{.+?}/", "", $match);
				$hotsimplecontact_params = explode(",", $hotsimplecontact_input);
				
				$keywords = explode(" ", $hotsimplecontact_params[0]);
				$keywords_number = count($keywords);
				$keywords_number_1 = $keywords_number - 1;

				/*

				The parameters entered in the shortcode should be separated by colon.
				They can be called from the array:

				$hotsimplecontact_params[0] --> the first parameter
				$hotsimplecontact_params[1] --> the second parameter
				$hotsimplecontact_params[2] --> the third parameter, and so on
				...

				*/
				if(isset($hotsimplecontact_params[0])) {
					if($hotsimplecontact_params[0]) {
						$intro_text = $hotsimplecontact_params[0];
					}
				}

				if(isset($hotsimplecontact_params[1])) {
					if($hotsimplecontact_params[1]=="noname") {
						$show_name = false;
					}
				}

				if(isset($hotsimplecontact_params[2])) {
					if($hotsimplecontact_params[2]=="nosubject") {
						$show_subject = false;
					}
				}

				if(isset($hotsimplecontact_params[3])) {
					if($hotsimplecontact_params[3]=="noantispam") {
						$show_antispam = false;
					}
				}

				if(isset($hotsimplecontact_params[4])) {
					if($hotsimplecontact_params[4]) {
						$after_text = $hotsimplecontact_params[4];
					}
				}


				//-------------------------SHORTCODE OUTPUT RENDER START----------------------------------------------

				$html = '<!-- Hot Simple Contact starts here -->';
				$html.= '<div class="simple_contact_container">';


				//-------------------------CODE FOR SENDING EMAIL----------------------------------------------

				$Error = '';

				$emailSent = false;
				$fromName = "";
				$email = "";
				$subject= "";
				$text = "";
				$antispamCheck = $show_antispam;

				if(isset($_POST['hot_simple_contact_anti_spam_answer'])) {
					if($_POST['hot_simple_contact_anti_spam_answer'] == $antispam_answer) {  // anti-spam answer value here

						if($show_name) {

							if(trim($_POST['hot_simple_contact_name']) === '') {
									$Error = true;
									echo '<div class="hot_simple_contact_error">';
									echo _e('Please enter your name!','hot_simplecontact');
									echo '</div>';
							} else {
								$fromName = trim($_POST['hot_simple_contact_name']);
							}

						}

						if(trim($_POST['hot_simple_contact_email']) === '')  {
							$Error = true;
							echo '<div class="hot_simple_contact_error">';
							_e('Please enter your e-mail address!','hot_simplecontact');
							echo '</div>';
							
						} else if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", trim($_POST['hot_simple_contact_email']))) {
							$Error = true;
							echo '<div class="hot_simple_contact_error">';
							echo _e('You entered an invalid e-mail address!','hot_simplecontact');
							echo '</div>';
							
						} else {
							$email = trim($_POST['hot_simple_contact_email']);
						}

						if($show_subject) {
						
							if(trim($_POST['hot_simple_contact_subject']) === '') {
									$Error = true;
									echo '<div class="hot_simple_contact_error">';
									echo _e('Please enter message subject!','hot_simplecontact');
									echo '</div>';
							} else {
								$subject = trim($_POST['hot_simple_contact_subject']);
							}

						}
						
					    if(trim($_POST['hot_simple_contact_message']) === '') {
								$Error = true;
								echo '<div class="hot_simple_contact_error">';
								echo _e('Please enter a message!','hot_simplecontact');
								echo '</div>';
						} else {
							if(function_exists('stripslashes')) {
								$text = stripslashes(trim($_POST['hot_simple_contact_message']));
							} else {
								$text = trim($_POST['hot_simple_contact_message']);
							}
						}

						if($Error == '') {
							$emailTo = get_option('admin_email');
							$subject = 'Contact from '.$email;
							$body = "Name: $fromName \n\nEmail: $email \n\nComments: $text";
							$headers = 'From: '.$fromName.' <'.$emailTo.'>' . "\r\n" . 'Reply-To: ' . $email;

							wp_mail( $emailTo, $subject, $body, $headers );
							$emailSent = true;
						}
					}else{
						echo '<div class="hot_simple_contact_error">';
						echo _e('Your anti-spam answer is not correct!','hot_simplecontact');
						echo '</div>';
					}

				}

				//-------------------------CODE FOR SENDING EMAIL END----------------------------------------------

				$html.= '<form action="#hot_simple_contact" method="post">';

				if($Error == '' && $emailSent) {
					$html.= '<div class="hot_simple_contact_sent">E-mail sent! Thank you!</div>';
				}

				if($intro_text) {
					$html.= '<div class="hot_simple_contact_intro">';
					$html.= $intro_text;
					$html.= '</div>';
				}

				$html.= '<div class="simple_contact_contact_fields">';

		    	if($show_name) {
			    	$html.= '<input class="simple_contact simple_contact_name" type="text" name="hot_simple_contact_name" value="'.$fromName.'" placeholder="'.'Your name'.'" />';
			    }

				$html.= '<input class="simple_contact simple_contact_email" type="text" name="hot_simple_contact_email" value="'.$email.'" placeholder="'.'Your email'.'" />';

				if($show_subject) {
					$html.= '<input class="simple_contact simple_contact_subject" type="text" name="hot_simple_contact_subject" value="'.$subject.'" placeholder="'.'Subject'.'" />';
				}

				$html.= '<textarea class="simple_contact simple_contact_message " name="hot_simple_contact_message" rows="4" placeholder="'.'Message'.'">'.$text.'</textarea>';

				if($show_antispam) {
					$html.= '<input class="simple_contact simple_contact_anti_spam" name="hot_simple_contact_anti_spam_answer" placeholder="'.$antispam_question.'" type="text" />';
				}else{
					$html.= '<input type="hidden" name="hot_simple_contact_anti_spam_answer" value="'.$antispam_answer.'" />';
				}

				$html.= '<button class="simple_contact simple_contact_button" type="submit">'.'Send'.'</button>';
				
				$html.= '</div>';
				$html.= '</form>';

				if($after_text) {
					$html.= '<div class="hot_simple_contact_after">';
					$html.= $after_text;
					$html.= '</div>';
				}

				?>



			<?php

				$html.= '</div>';
				$html.= '<!-- Hot Simple Contact ends here -->';

				//-------------------------SHORTCODE OUTPUT RENDER END----------------------------------------------
				
				$srchtml = preg_replace( "#{simplecontact}".$hotsimplecontact_input."{/simplecontact}#s", $html, $srchtml );

			}
	   }
	   return $srchtml;
	}

	function HOT_SIMPLE_CONTACT_plugin_menu() {
		add_options_page('Hot Simple Contact', 'Hot Simple Contact', 'manage_options', 'hot_simplecontact', array( $this,'SIMPLE_CONTACT_RenderSettings'));
	}

	function SIMPLE_CONTACT_RenderSettings() {
		if(isset($_POST['action'])) {
			if($_POST['action'] === "save") {
				foreach($this->OPTIONS as $Name => $option) {
					if(isset($_POST[$Name])) {
						if($_POST[$Name] == "on" || $_POST[$Name] == "Yes") {
							$this->OPTIONS[$Name] = true;
						}else{
							$this->OPTIONS[$Name] = $_POST[$Name];
						}
					}else{
						$this->OPTIONS[$Name] = false;
					}
				}
				update_option('HOT_SIMPLE_CONTACT_plugin', (array)$this->OPTIONS);
			}
		} ?>
		<div class='wrap'>
			<form method="post">
				<h2><?php echo __('Hot Simple Contact Options','hot_simplecontact'); ?></h2>
				<table class="form-table">

					<tr>
						<th>
							<label for="width"> <?php echo  __('Maximum width','hot_simplecontact'); ?></label>
						</th>
						<td>
							<input name="width" type="text" id="width" value="<?php echo $this->OPTIONS['width'];?>">
							<p>Enter dimension and unit (in example "200px" or "100%" or "auto")</p>
						</td>
					</tr>

					<tr>
						<th>
							<label for="intro_text"> <?php echo  __('Intro text','hot_simplecontact'); ?></label>
						</th>
						<td>
							<textarea rows="4" cols="50" name="intro_text" type="text" id="intro_text"><?php echo $this->OPTIONS['intro_text'];?></textarea>
						</td>
					</tr>

					<tr>
						<th>
							<label for="show_name"> <?php echo __('Show name','hot_simplecontact'); ?> </label>
						</th>
						<td>
							<input type="checkbox" name="show_name" id="show_name" <?php echo (((boolean)$this->OPTIONS['show_name'] === true)? "checked='checked'": ""); ?> />
						</td>
					</tr>

					<tr>
						<th>
							<label for="show_subject"> <?php echo __('Show subject','hot_simplecontact'); ?> </label>
						</th>
						<td>
							<input type="checkbox" name="show_subject" id="show_subject" <?php echo (((boolean)$this->OPTIONS['show_subject'] === true)? "checked='checked'": ""); ?> />
						</td>
					</tr>

					<tr>
						<th>
							<label for="show_antispam"> <?php echo __('Show anti-spam question','hot_simplecontact'); ?> </label>
						</th>
						<td>
							<input type="checkbox" name="show_antispam" id="show_antispam" <?php echo (((boolean)$this->OPTIONS['show_antispam'] === true)? "checked='checked'": ""); ?> />
						</td>
					</tr>

					<tr>
						<th>
							<label for="antispam_question"> <?php echo  __('Anti-spam question','hot_simplecontact'); ?></label>
						</th>
						<td>
							<input name="antispam_question" type="text" id="antispam_question" value="<?php echo $this->OPTIONS['antispam_question'];?>">
						</td>
					</tr>

					<tr>
						<th>
							<label for="antispam_answer"> <?php echo  __('Anti-spam answer','hot_simplecontact'); ?></label>
						</th>
						<td>
							<input name="antispam_answer" type="text" id="antispam_answer" value="<?php echo $this->OPTIONS['antispam_answer'];?>">
						</td>
					</tr>

					<tr>
						<th>
							<label for="after_text"> <?php echo  __('After text','hot_simplecontact'); ?></label>
						</th>
						<td>
							<textarea rows="4" cols="50" name="after_text" type="text" id="after_text"><?php echo $this->OPTIONS['after_text'];?></textarea>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input name="save" type="submit" value="<?php echo  __('Save Changes','hot_simplecontact'); ?>" class="button button-primary" />
					<input type="hidden" name="action" value="save" />
				</p>
			</form>
	    	<br/>
			<div>	
				<?php echo __('<h2>Instructions</h2>
				<p>There are several ways to include Hot Simple Contact on your site. We will explain them here:</p>
				<ol>
				<li>If you want to use contact form as a widget, go to Appearance &gt; Widgets and drop Hot Simple Contact widget in any widget postion. Open it here to see all the parameters. The contact form will appear in a widget position of your theme.</li>
				<li>You can insert the Hot Simple Contact directly to your post(s) using shortcode and use the parameters set in this page. To do this, enter this shortcode anywhere in your post(s): <b>{simplecontact}{/simplecontact}</b></li>
				<li>You also set the parameters directly in shortcode. In this case, the format of shortcode is:<br/><b>{simplecontact}Some intro text,noname,nosubject,noantispam,Some after text{/simplecontact}</b><br/><br/>In this example name, subject and anti-spam fields are disabled. Use this format to enable them:<br/><b>{simplecontact}Some intro text,name,subject,antispam,Some after text{/simplecontact}</b></li>
				</ol>
				','hot_simplecontact'); ?>
			</div>	
	   </div>
	<?php	
	}

}

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'hot_simplecontact_load_widgets' );
add_action('admin_init', 'hot_simplecontact_textdomain');
/**
 * Register our widget.
 * 'SimpleContact' is the widget class used below.
 *
 * @since 0.1
 */
function hot_simplecontact_load_widgets() {
	register_widget( 'SimpleContact' );
}

function hot_simplecontact_textdomain() {
	load_plugin_textdomain('hot_simplecontact', false, dirname(plugin_basename(__FILE__) ) . '/languages');
}
	
/**
 * SimpleContact Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */


 
class SimpleContact extends WP_Widget {
     
	/**
	 * Widget setup.
	 */
	 
	function SimpleContact() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'Hot_simplecontact', 'description' => __('Hot Simple Contact', 'hot_simplecontact') );

		/* Widget control settings. */
		$control_ops = array(  'id_base' => 'hot-simplecontact' );

		/* Create the widget. */
		parent::__construct( 'hot-simplecontact', __('Hot Simple Contact', 'hot_simplecontact'), $widget_ops, $control_ops );

		add_action('wp_head', array( $this, 'SimpleContact_inline_scripts_and_styles_widget'),14);

    }

    function SimpleContact_inline_scripts_and_styles_widget(){
		// MULTIPLE WIDGETS ON PAGE ARE SUPPORTED !!!
		$all_options = parent::get_settings();
	   
		echo '<style type="text/css">';
		echo '/* Hot Simple Contact WIDGET START */';
		foreach ($all_options as $key => $value){
		    $options = $all_options[$key];
			
			echo ' 
			.simple_contact_widget_container {
				width:'.$options['width'].';
			}
			';
	    }
	 
		echo '
		/* Hot Simple Contact WIDGET END */
		</style>';
	   
	}

	function SimpleContact_styles(){
	   	wp_enqueue_style( 'hot-simple-contact-style', plugins_url('/style.css', __FILE__));
	}

	function GetDefaults() {
		return array( 
            'title' => ''
            ,'width' => 'auto'
            ,'intro_text' => ''
            ,'show_name' => true
            ,'show_subject' => true
            ,'show_antispam' => true
            ,'antispam_question' => 'Two plus three is?'
            ,'antispam_answer' => 5
            ,'after_text' => ''				
        );
	}
	
	/**
	 * How to display the widget on the screen.
	 */

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
    	echo $before_widget;

    	if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}
	 
        $defaults = $this->GetDefaults();
		$instance = wp_parse_args( (array) $instance, $defaults );  
		
		//-------------------------WIDGET RENDER START----------------------------------------------?>

	<div class="simple_contact_widget_container">
		   
<?php


$ErrorWidget = '';

$emailSentWidget = false;
$fromNameWidget = "";
$emailWidget = "";
$subjectWidget= "";
$textWidget = "";
$antispamCheckWidget = $instance["show_antispam"];

if(isset($_POST['hot_simple_contact_widget_anti_spam_answer'])) {
	if($_POST['hot_simple_contact_widget_anti_spam_answer'] == $instance["antispam_answer"]) {  // anti-spam answer value here

		if($instance["show_name"]) {

			if(trim($_POST['hot_simple_contact_widget_name']) === '') {
					$ErrorWidget = true;
					echo '<div class="hot_simple_contact_error">';
					echo _e('Please enter your name!','hot_simplecontact');
					echo '</div>';
			} else {
				$fromNameWidget = trim($_POST['hot_simple_contact_widget_name']);
			}

		}

		if(trim($_POST['hot_simple_contact_widget_email']) === '')  {
			$ErrorWidget = true;
			echo '<div class="hot_simple_contact_error">';
			_e('Please enter your e-mail address!','hot_simplecontact');
			echo '</div>';
			
		} else if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", trim($_POST['hot_simple_contact_widget_email']))) {
			$ErrorWidget = true;
			echo '<div class="hot_simple_contact_error">';
			echo _e('You entered an invalid e-mail address!','hot_simplecontact');
			echo '</div>';
			
		} else {
			$emailWidget = trim($_POST['hot_simple_contact_widget_email']);
		}

		if($instance["show_subject"]) {
		
			if(trim($_POST['hot_simple_contact_widget_subject']) === '') {
					$ErrorWidget = true;
					echo '<div class="hot_simple_contact_error">';
					echo _e('Please enter message subject!','hot_simplecontact');
					echo '</div>';
			} else {
				$subjectWidget = trim($_POST['hot_simple_contact_widget_subject']);
			}

		}
		
	    if(trim($_POST['hot_simple_contact_widget_message']) === '') {
				$ErrorWidget = true;
				echo '<div class="hot_simple_contact_error">';
				echo _e('Please enter a message!','hot_simplecontact');
				echo '</div>';
		} else {
			if(function_exists('stripslashes')) {
				$textWidget = stripslashes(trim($_POST['hot_simple_contact_widget_message']));
			} else {
				$textWidget = trim($_POST['hot_simple_contact_widget_message']);
			}
		}

		if($ErrorWidget == '') {
			$emailToWidget = get_option('admin_email');
			$subjectWidget = 'Contact from '.$emailWidget;
			$bodyWidget = "Name: $fromNameWidget \n\nEmail: $emailWidget \n\nComments: $textWidget";
			$headersWidget = 'From: '.$fromNameWidget.' <'.$emailToWidget.'>' . "\r\n" . 'Reply-To: ' . $emailWidget;

			wp_mail( $emailToWidget, $subjectWidget, $bodyWidget, $headersWidget );
			$emailSentWidget = true;

		}
	}else{
		echo '<div class="hot_simple_contact_error">';
		echo _e('Your anti-spam answer is not correct!','hot_simplecontact');
		echo '</div>';
	}

} ?>

		<form action="#hot_simple_contact_widget" method="post">

			<?php if($ErrorWidget == '' && $emailSentWidget) { ?>
			<div class="hot_simple_contact_sent">
				<?php _e('E-mail sent! Thank you!<br/>','hot_simplecontact'); ?>
			</div>
			<?php } ?>

			<?php if($instance["intro_text"]) { ?>
			<div class="hot_simple_contact_intro"><?php echo $instance["intro_text"]; ?></div>
			<?php } ?>

		    <div class="simple_contact_contact_fields">
		    	<?php if($instance["show_name"]) { ?>
		    	<input class="simple_contact simple_contact_name" type="text" name="hot_simple_contact_widget_name" value="<?php echo $fromNameWidget; ?>" placeholder="<?php _e('Your name','hot_simplecontact'); ?>" />
		    	<?php } ?>
				<input class="simple_contact simple_contact_email" type="text" name="hot_simple_contact_widget_email" value="<?php echo $emailWidget; ?>" placeholder="<?php _e('Your e-mail','hot_simplecontact'); ?>" />
				<?php if($instance["show_subject"]) { ?>
				<input class="simple_contact simple_contact_subject" type="text" name="hot_simple_contact_widget_subject" value="<?php echo $subjectWidget; ?>" placeholder="<?php _e('Subject','hot_simplecontact'); ?>" />
				<?php } ?>
				<textarea class="simple_contact simple_contact_message " name="hot_simple_contact_widget_message" rows="4" placeholder="<?php _e('Message','hot_simplecontact'); ?>"><?php echo $textWidget;?></textarea>
				<input class="simple_contact simple_contact_anti_spam" name="hot_simple_contact_widget_anti_spam_answer" placeholder="<?php echo $instance["antispam_question"]; ?>" <?php if(!$instance["show_antispam"]) { ?>type="hidden" value="<?php echo $instance['antispam_answer']; ?>" <?php }else{ ?> type="text" <?php } ?> />
				<button class="simple_contact simple_contact_button" type="submit"><?php _e('Send','hot_simplecontact'); ?></button>
			</div>

			<?php if($instance["after_text"]) { ?>
			<div class="hot_simple_contact_after"><?php echo $instance["after_text"]; ?></div>
			<?php } ?>

		</form>
	</div>
	   
	   <?php //-------------------------WIDGET RENDER END-------------------------------------------------?>
	   <?php echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
    	
		foreach($new_instance as $key => $option)
		{
		  $instance[$key]     = $new_instance[$key];
		} 
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
	    $defaults = $this->GetDefaults();
		$instance = wp_parse_args( (array) $instance, $defaults );  ?>

		<!-- Hot Simple Contact: Text Input -->

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:','hot_simplecontact'); ?></label>
			<input class="widefat" type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>"  value="<?php echo $instance['title']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e('Maximum width: ','hot_random_image'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'width' ); ?>" id="<?php echo $this->get_field_id( 'width' ); ?>" value="<?php echo $instance['width']; ?>" size="5" />
			<span style="font-size:0.9em; display: block;"><?php _e('Enter dimension and unit (in example "200px" or "100%" or "auto")','hot_simplecontact'); ?></span>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'intro_text' ); ?>"><?php _e('Text before the contact form:','hot_simplecontact'); ?></label>
			<textarea class="widefat" rows="5" name="<?php echo $this->get_field_name( 'intro_text' ); ?>" id="<?php echo $this->get_field_id( 'intro_text' ); ?>"><?php echo $instance['intro_text']; ?></textarea>
		</p>

		<p>
		    <label for="<?php echo $this->get_field_id( 'show_name' ); ?>"><?php _e('Show name: ', 'hot_simplecontact'); ?></label>
			<select class="select"  id="<?php echo $this->get_field_id( 'show_name' ); ?>" name="<?php echo $this->get_field_name( 'show_name' ); ?>" >
                <option value="1" <?php if($instance['show_name'] == "1") echo 'selected="selected"'; ?> ><?php _e('Yes', 'hot_simplecontact'); ?></option>
                <option value="0" <?php if($instance['show_name'] == "0") echo 'selected="selected"'; ?> ><?php _e('No', 'hot_simplecontact'); ?></option>				
            </select>
		</p>

		<p>
		    <label for="<?php echo $this->get_field_id( 'show_subject' ); ?>"><?php _e('Show subject: ', 'hot_simplecontact'); ?></label>
			<select class="select"  id="<?php echo $this->get_field_id( 'show_subject' ); ?>" name="<?php echo $this->get_field_name( 'show_subject' ); ?>" >
                <option value="1" <?php if($instance['show_subject'] == "1") echo 'selected="selected"'; ?> ><?php _e('Yes', 'hot_simplecontact'); ?></option>
                <option value="0" <?php if($instance['show_subject'] == "0") echo 'selected="selected"'; ?> ><?php _e('No', 'hot_simplecontact'); ?></option>				
            </select>
		</p>

		<p>
		    <label for="<?php echo $this->get_field_id( 'show_antispam' ); ?>"><?php _e('Show anti-spam question: ', 'hot_simplecontact'); ?></label>
			<select class="select"  id="<?php echo $this->get_field_id( 'show_antispam' ); ?>" name="<?php echo $this->get_field_name( 'show_antispam' ); ?>" >
                <option value="1" <?php if($instance['show_antispam'] == "1") echo 'selected="selected"'; ?> ><?php _e('Yes', 'hot_simplecontact'); ?></option>
                <option value="0" <?php if($instance['show_antispam'] == "0") echo 'selected="selected"'; ?> ><?php _e('No', 'hot_simplecontact'); ?></option>				
            </select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'antispam_question' ); ?>"><?php _e('Anti-spam question:','hot_simplecontact'); ?></label>
			<input class="widefat" type="text" name="<?php echo $this->get_field_name( 'antispam_question' ); ?>" id="<?php echo $this->get_field_id( 'antispam_question' ); ?>"  value="<?php echo $instance['antispam_question']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'antispam_answer' ); ?>"><?php _e('Anti-spam answer:','hot_simplecontact'); ?></label>
			<input class="widefat" type="text" name="<?php echo $this->get_field_name( 'antispam_answer' ); ?>" id="<?php echo $this->get_field_id( 'antispam_answer' ); ?>" value="<?php echo $instance['antispam_answer']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'after_text' ); ?>"><?php _e('Text after the contact form:','hot_simplecontact'); ?></label>
			<textarea class="widefat" rows="5" name="<?php echo $this->get_field_name( 'after_text' ); ?>" id="<?php echo $this->get_field_id( 'after_text' ); ?>"><?php echo $instance['after_text']; ?></textarea>
		</p>

	<?php  
	}
}

?>