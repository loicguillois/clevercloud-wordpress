<?php
/**
 * The searchform.php template.
 *
 * Used any time that get_search_form() is called.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_unique_id/
 * @link https://developer.wordpress.org/reference/functions/get_search_form/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */
	$posttype=get_post_type();
	$postid=get_the_id();
	if (isset($_GET['ms'])){
		$ms = get_query_var('ms');
	}
	/* debug post type not so accurate */
	//if (($posttype=='sfwd-lessons')&&(isset($_GET['ms']))){ 
	//if (($posttype=='sfwd-lessons')){ 

	if( is_single() && is_singular() ){
		$getparam_1="ms";
		$action_1 = "";
		if((isset($_GET['ms'])  && $ms !=""  )){
			$placeholder_1= $ms;
		}
		else{
			$placeholder_1="Que recherchez vous ?";
		}
	}	
	else { 
		$action_1 = esc_url( home_url( '/' ));
		$getparam_1="s";
		$placeholder_1="Que recherchez vous ?";
	}
	//echo "<br>".$posttype ."<br>";
	//echo "<br>".$postid ."<br>";	

?>
<form role="search" method="get" class="search-form educawa-1 bonus" action="<?php echo $action_1; ?>">
	<input type="search" id="<?php ?>" placeholder="<?php echo $placeholder_1; ?>" class="search-field" value="<?php //echo get_search_query(); ?>" name="<?php echo $getparam_1; ?>" />
	<input type="submit" class="search-submit" value="<?php esc_html_e( 'Search', 'elementor-pro' ); ?>" />
</form>
