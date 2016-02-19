<?php
/**
 *
 * This script will convert WPSC product attributes (inserted with WP e-Commerce - Custom Fields Plugin) to WOOCOMMERCE product attributes
 * Load this file in your theme folder, then create a new page from WP admin panel and select 'Script' as template, then load that page.
 * This is ONLY to import the custom WPEC attributes, not the rest.
 * Run it at your own risk, I made it for my own purposes...and it works.
 *
 * Made by Alessandro Romani maildialex@gmail.com
 *
 * Template Name: Script
 *
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        global $wpdb;

        // @param int $post_id - The id of the post that you are setting the attributes for
        // @param array[] $attributes - This needs to be an array containing ALL your attributes so it can insert them in one go
        function wc_set_attributes($post_id, $attributes) {
            $i = 0;
            // Loop through the attributes array
            foreach ($attributes as $name => $value) {
                if($value!=''){
                $product_attributes[$i] = array (
                    'name' => htmlspecialchars( stripslashes( $name ) ), // set attribute name
                    'value' => $value, // set attribute value
                    'position' => 1,
                    'is_visible' => 1,
                    'is_variation' => 1,
                    'is_taxonomy' => 0
                );
                $i++;
            }
            else{
                $product_attributes[$i] = array (
                    'name' => htmlspecialchars( stripslashes( $name ) ), // set attribute name
                    'value' => $value, // set attribute value
                    'position' => 1,
                    'is_visible' => 0,
                    'is_variation' => 1,
                    'is_taxonomy' => 0
                );

                $i++;

            }
            }
            // Now update the post with its new attributes
            update_post_meta($post_id, '_product_attributes', $product_attributes);
        }

       $post_ids = $wpdb->get_results('SELECT ID FROM wp_posts WHERE post_type = "product" OR post_type = "wpsc-product"', OBJECT);
         ?>
        <?php foreach($post_ids as $post_id_arr) {// $post_id = 11155;
            $post_id=$post_id_arr->ID;
            $meta = get_post_meta($post_id); ?>
            <?php
            //echo '<pre>';   print_r($meta);echo '</pre>';
            $chiavi = '';
            // PREPARE ATTRIBUTES IN WOOCOMMERCE
            // TAKE ATTRIBUTES FROM WPSC META
            // FOR EACH WPSC ATTRIBUTE CREATE A NEW WOOCOMMERCE ATTRIBUTE ENTRY
            foreach ($meta as $key => $value) {
                //explode, and take off the _wpsc prefix.
                $strpos_filter = strpos($key, 'wpsc');
                if ($strpos_filter == 1) {
                    $exp_key = explode('_', $key);
                    if ($exp_key[1] == 'wpsc') {
                        // change_wpsc prefix to pa prefix
                        $key2 = (str_replace("_wpsc", "pa", $key));
                        $key4 = (str_replace("_wpsc_", "", $key));
                        $key3 = ucfirst($key4);
                        $chiavi .= "'" . $key2 . "'',";
                        $attr_name = str_replace("_wpsc_", "", $key);
                        $arr_result[$key3] = $value[0];
                        //0) SEARCH FOR THE WOOCOMMERCE ATTRIBUTE, IF IT DOESN'T EXIST CREATE IT
                        $woo_attributes_array = $wpdb->get_results("SELECT * FROM wp_woocommerce_attribute_taxonomies WHERE attribute_name = '" . $attr_name . "'", OBJECT);
                        // echo '<pre>';   print_r($woo_attributes_array);echo '</pre>';
                        if (!$woo_attributes_array) {
                            echo 'vuoto';
                            //Does not exist, create it
                            $wpdb->insert('wp_woocommerce_attribute_taxonomies', array('attribute_name' => "" . $attr_name . "", 'attribute_label' => "" . $attr_name . "", 'attribute_type' => 'text', 'attribute_orderby' => 'menu_order', 'attribute_public' => 1));
                        }

                    }
                    //Unset all the product meta wich are NOT custom but already imported from WPEC data
                    unset($arr_result['Product_metadata']);
                    unset($arr_result['Is_donation']);
                    unset($arr_result['Sku']);
                    unset($arr_result['Special_price']);
                    unset($arr_result['Price']);
                    unset($arr_result['Stock']);
                    unset($arr_result['Currency']);



                }
            }

            //TAKE THE TERMS IDS FROM WP_TERM_RELATIONSHIPS - RELATIONSHIPS BETWEEN POST ID AND TAXONOMY ID
            $tax_ids = $wpdb->get_results('SELECT * FROM wp_term_relationships WHERE object_id = ' . $post_id . '', OBJECT);
            foreach ($tax_ids as $tax_id) {
                //TAKE THE TERM_TAXONOMY_ID FROM WP_TERM_TAXONOMY
                $terms = $wpdb->get_results('SELECT * FROM wp_term_taxonomy WHERE term_taxonomy_id = ' . $tax_id->term_taxonomy_id . ' AND taxonomy IN (' . $key2 . ')', OBJECT);
                //Check if attributes are already set for that product

                foreach ($terms as $term) {
                    //TAKE VALUE FROM WP_TERMS
                    //print_r($term->taxonomy . ': ');
                    $names = $wpdb->get_results('SELECT * FROM wp_terms WHERE term_id = ' . $term->term_id . '', OBJECT);
                    foreach ($names as $name) {
                      //  print_r('nome: ' . $name->name . '<br>');
                    }
                }
                if (!$terms) {
                    //Create the term, term taxonomy, term relationship
                   $wpdb->insert( 'wp_terms', array('name'=>"".$value[0]."",'slug'=>"".$value[0]."", 'term_group'=>0) );
                    $next_term_id = $wpdb->insert_id;
                    //print_r($next_term_id);
                    foreach ($key2 as $chiave) {
                        $wpdb->insert('wp_term_taxonomy', array('term_id' => "" . $next_term_id . "", 'taxonomy' => "" . $chiave . "", 'description' => ' ', 'parent' => 0, 'count' => 0));
                        $next_term_taxonomy_id = $wpdb->insert_id;
                        //print_r($next_term_taxonomy_id);
                        $wpdb->insert('wp_term_relationships', array('object_id' => "" . $post_id . "", 'term_taxonomy_id' => "" . $next_term_taxonomy_id . "", 'term_order' => 0));
                        $next_rel_taxonomy_id = $wpdb->insert_id;
                        //print_r($next_rel_taxonomy_id);
                    }
                    wc_set_attributes($post_id, $arr_result);
                    break;


                } else {
                    foreach ($terms as $term) {
                        //Term already exist, just update the value
                        $wpdb->update('wp_terms', array('name' => "" . $value[0] . "", 'slug' => "" . $value[0] . "", 'term_group' => 0), array('term_id' => $term->term_id));

                    }
                }

            }
            echo '<br> Product ID '.$post_id .' has been processed Successfully';

        } ?>
    </main><!-- .site-main -->

    <?php  echo 'ALL DONE! go back to your products and check for attributes ';?>

</div><!-- .content-area -->

<?php get_footer(); ?>