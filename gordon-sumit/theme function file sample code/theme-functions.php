<?php
/**
 * Add functions here related to the theme enhancement
 */

/**
 * Add map API
 */
add_filter( 'carbon_fields_map_field_api_key', 'carbonMapFieldAPIKey' );
function carbonMapFieldAPIKey( $current_key ) {
    return GOOGLE_MAP_API_KEY;
}

// Allow SVG images
function updateMimeTypes($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'updateMimeTypes');

//WP ajax for resource post
add_action('wp_ajax_loadPosts','loadPosts');
add_action('wp_ajax_nopriv_loadPosts','loadPosts');
function loadPosts(){
    $paged = (!empty($_POST['paged']))?$_POST['paged']:'';
    $author = (!empty($_POST['author']))?$_POST['author']:0;
    $perPage = (!empty($_POST['perPage']))?$_POST['perPage']:8;
    $haveDescription = (!empty($_POST['haveDescription'])) ? 0 : 1;
    $referenceUrl = (!empty($_POST['referenceUrl'])) ? $_POST['referenceUrl'] : '';

    //fetch query object
    $resourceQuery = resourceQuery($paged,$perPage,$author);

    $html ='';
    $count = 0;
        if($resourceQuery->have_posts()):
            while ($resourceQuery->have_posts()) : $resourceQuery->the_post();
                $class = 'col-md-6 col-xl-4';
                $featuredImgUrl = get_the_post_thumbnail_url(get_the_ID(), 'full');
                $authorPageUrl = get_author_posts_url(get_the_author_meta('ID'));
                $html .= '<div class="' . $class . ' featured-resource">
                    ' . (($featuredImgUrl) ? '<div class="img-wrapper">
                            <img src="' . esc_url($featuredImgUrl) . '" alt="resources-img">
                        </div>' : '') . '
                       
                    <div class="featured-content">
                        <h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>
                        <p>' . get_the_date('F d') . ' | <span class="author-name-info">by <strong><a href="'.$authorPageUrl.'">' . get_the_author() . '</a></strong></span></p>';
                if ($haveDescription):
                    $html .= '<p>' . get_the_excerpt() . '</p>';
                endif;
                $html .= '<div class="btn-wrapper">
                            <a href="' . esc_url(get_the_permalink(get_the_ID())) . '" class="btn-global primary-color">' . __('Read More', VAVIA_DOMAIN) . '</a>
                        </div>
                    </div>
                </div>';
                $count++;
            endwhile;
        endif;
    wp_reset_postdata();
    wp_send_json(['response'=>$html,'requestUrl'=>$referenceUrl.'page/'.($paged+1).'/']);
}
// return query object
function resourceQuery($paged = 0, $perPage = -1,$author=0)
{
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'orderby' => 'date',
        'posts_per_page' => $perPage,
        'order' => 'DESC',
    );
    if ($author) {
        $args['author'] = $author;
    }
    $args['paged'] = !empty($paged) ? $paged : 0;
    return new WP_Query($args);
}

// remove dots from the excerpt
add_filter('excerpt_more', 'removeDotsFromExcerpt');
function removeDotsFromExcerpt( $more ) {
    return '...';
}

/** Rewrite posts url and add category name in the slug */
function customRewriteRule()
{
    $currentUrl = $_SERVER['REQUEST_URI'];
    $regx = '([^/]*)';
    $postObj = getPostObject($currentUrl);

    //rewrite rule for services and product posts

        if (in_array($postObj->post_type, ['service', 'product'])) {
            if (str_contains($currentUrl, DUMPSTER_RENTAL_SLUG) !== false) {
                add_rewrite_rule($regx . '/' . $regx . '/?', 'index.php?post_type=' . $postObj->post_type . '&' . $postObj->post_type . '=$matches[2]&location=$matches[1]', 'top');
            } else {
                add_rewrite_rule($regx . '/?', 'index.php?post_type=' . $postObj->post_type . '&' . $postObj->post_type . '=$matches[1]', 'top');
            }
        }

        if ($postObj->post_name == 'terms-conditions') {
            add_rewrite_rule($regx . '/' . $regx . '/?', 'index.php?&pagename=$matches[2]&location=$matches[1]', 'top');
        }

        if ($postObj->post_type !== 'post') {
            //rewrite rule for location term
            add_rewrite_rule($regx . DUMPSTER_RENTAL_SLUG . '/?', 'index.php?location=$matches[1]' . DUMPSTER_RENTAL_SLUG, 'top');
        }

    //return location pages to 404
    $redirectPages = ['location','service','product'];
    if (!empty($redirectPages)) {
        foreach ($redirectPages as $page) {
            add_rewrite_rule($page . '/' . $regx . '/?', 'index.php?name=$matches[1]', 'top');
            add_rewrite_rule($page . '/%' . $regx . '/?', 'index.php?name=$matches[1]', 'top');
        }
    }
    flush_rewrite_rules();
}
add_action('init', 'customRewriteRule', 999, 0);

function getPostObject($url)
{
    global $wpdb;
    $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", basename($url)));
    if ($post){
        return get_post($post);
    }
}

// rewrite post slug globally
add_filter( 'post_type_link', 'rewritePostSlug', 1, 3 );
function rewritePostSlug( $post_link, $id = 0 ){
    $post = get_post($id);
    if ( is_object( $post ) && in_array($post->post_type,['service','product'])){
        $terms = wp_get_object_terms( $post->ID, 'location' );
        if( $terms ){
            $post_link =  home_url().'/'.$terms[0]->slug.'/'.$post->post_name;
        }else{
            $post_link =  home_url().'/'.$post->post_name;
        }
    }
    return $post_link;
}

// rewrite term slug globally
add_filter('term_link', 'rewriteTermSlug', 1, 3);
function rewriteTermSlug($link, $term, $taxonomy)
{
    if ($taxonomy == 'location') { //check if taxonomy is location
        return str_replace('location/'.$term->slug, $term->slug, $link);
    }
    return $link;
}



/**
 * Contact form response popup
 */
add_filter( 'ninja_forms_display_before_form', 'getContactResponsePopup', 10, 3 );
function getContactResponsePopup( $form_id, $is_preview ): string
{
    return '<div id="contactPopupModal" class="modal fade popup-modal contact-popup-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="cancel-btn-wrapper">
                    <button type="button" class="close btn-global" data-dismiss="modal" aria-label="Close">
                </div>
                <div class="popup-content-wrapper bg-white d-xl-flex">
                   <div class="contact-popup-icon flex-center">
                       <img src="'.THEME_ASSETS_URL."/branding/img/logo-active.svg".'" class="response-icon" alt="poup-icon">
                   </div>
                   <div class="popup-content">
                        <h2 class="heading-border response-heading text-left">'.__("Success",VAVIA_DOMAIN).'</h2>
                        <p class="response-msg"></p>
                        <a href="#0" class="btn-global" data-dismiss="modal">'.__("Close",VAVIA_DOMAIN).'</a>
                   </div>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Contact form response popup
 */
add_action( 'wp_footer', 'getOrderFormPopup', 10 );
function getOrderFormPopup()
{
    echo '<div id="orderNowPopupModal" class="modal fade popup-modal contact-popup-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="cancel-btn-wrapper">
                    <button type="button" class="close btn-global" data-dismiss="modal" aria-label="Close">
                </div>
                <div class="popup-content-wrapper bg-white page-template-template-contact">
                   <div id="orderNowFormWrapper" class="popup-content contact-section bg-white">
                   <h2 class="heading-border text-left hide-on-submit">'.__("Reserve A Dumpster",VAVIA_DOMAIN).'</h2>
                       '.do_shortcode('[ninja_form id=4]').'
                   </div>
                </div>
            </div>
        </div>
    </div>';
}



/*
 * SEO sitemap modification start
 */

// empty sitemap for product & service
add_filter('wpseo_sitemap_url',function ($output, $url){
    $postObj = getPostObject($url['loc']);
    if(in_array($postObj->post_type,['service','product'])) return '';
    return $output;
},10,2);

// add custom sitemap for service
add_filter("wpseo_sitemap_service_content",function (){
    return prepareXML(wp_list_pluck(get_posts(['post_type'=>'service']),'post_name'));
});
// add custom  sitemap for product
add_filter("wpseo_sitemap_product_content",function (){
    return prepareXML(wp_list_pluck(get_posts(['post_type'=>'product']),'post_name'));
});

add_filter("wpseo_sitemap_page_content",function (){
    return prepareXML(['terms-conditions'],false).'/';
});

// return xml for service and product posts
function prepareXML($slugs,$isdefault=true){
    $xml = '';
    foreach ($slugs as $slug){
        $terms = get_terms(['taxonomy'=>'location','hide_empty'=>false]);
        $postObj = getPostObject(home_url('/'.$slug));
        foreach ($terms as $term){
            $xml .='<url>
                <loc>'.get_term_link($term->term_id).$slug.'</loc>
                <lastmod>'.get_the_modified_date(DATE_W3C,$postObj->ID).'</lastmod>
            </url>';
        }
        if($isdefault){
            $xml .='<url>
                <loc>'.home_url('/'.$slug).'</loc>
                <lastmod>'.get_the_modified_date(DATE_W3C,$postObj->ID).'</lastmod>
            </url>';
        }

    }
    return $xml;

}


add_filter ( 'wpseo_sitemap_index' , 'vavia_add_resource_pagination_sitemap_to_sitemap_index' );
function vavia_add_resource_pagination_sitemap_to_sitemap_index(){
    global $wpseo_sitemaps;
    $date = date('c');

    $smp  = '<sitemap>' . "\n";
    $smp .= '<loc>' . site_url() .'/resources-pagination-sitemap.xml</loc>' . "\n";
    $smp .= '<lastmod>' . htmlspecialchars( $date ) . '</lastmod>' . "\n";
    $smp .= '</sitemap>' . "\n";

    return $smp;
}

//Add resource page pagination to sitemap
add_action( 'init', 'vavia_register_resource_pagination_sitemap', 99 );
function vavia_register_resource_pagination_sitemap() {
    global $wpseo_sitemaps;
    $wpseo_sitemaps->register_sitemap( 'resources-pagination', 'vavia_generate_pagination_sitemap' );
}

function vavia_generate_pagination_sitemap()
{
    global $wpseo_sitemaps;
    $resourceQuery = resourceQuery(1, 8)->max_num_pages;
    for ($i = 1; $i <= $resourceQuery; $i++) {
        $url = array(
            'loc' => RESOURCE_PAGE_URL . 'page/' . ($i) . '/',
            'mod' => date('c')
        );
        $output .= $wpseo_sitemaps->renderer->sitemap_url($url);
    }

    $authors = get_users(['role__in' => ['author']]);
    foreach ($authors as $author) {
        $authorResourceQuery = resourceQuery(1, 3, $author->ID)->max_num_pages;
        for ($i = 1; $i <= $authorResourceQuery; $i++) {
            $url = array(
                'loc' => get_author_posts_url($author->ID) . 'page/' . ($i) . '/',
                'mod' => date('c')
            );
            $output .= $wpseo_sitemaps->renderer->sitemap_url($url);
        }
    }

    if (empty($output)) {
        $wpseo_sitemaps->bad_sitemap = true;
        return;
    }

    $sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
    $sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
    $sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $sitemap .= $output . '</urlset>';

    $wpseo_sitemaps->set_sitemap($sitemap);
}

/**
 * Excludes terms with ID of 3 and 11 from terms sitemaps.
 *
 * @param array $terms Array of term IDs already excluded.
 *
 * @return array The terms to exclude.
 */
function sitemapExcludeAllTerms( $terms ) {
    $terms = get_terms(['taxonomy'=>'location','hide_empty'=>false]);
    return wp_list_pluck($terms,'term_id');
}

add_filter( 'wpseo_exclude_from_sitemap_by_term_ids', 'sitemapExcludeAllTerms', 10, 1 );
add_filter("wpseo_sitemap_location_content",'showAllLocationsInSeoSitemap');
function showAllLocationsInSeoSitemap(){
    $terms = get_terms(['taxonomy'=>'location','hide_empty'=>false]);
    $xml = '';
    foreach ($terms as $term){
        $xml .='<url>
                <loc>'.get_term_link($term->term_id).'</loc>
                <lastmod>'.date(DATE_W3C).'</lastmod>
            </url>';
    }
    return $xml;
}
/*
 * SEO sitemap modification end
 */
add_filter('wp_insert_term_data', 'beforeLocationTermCreated', 10, 3);
function beforeLocationTermCreated($data, $taxonomy, $args)
{
    if (str_contains($data['slug'],DUMPSTER_RENTAL_SLUG)) return $data;
    $data['slug'] = $data['slug'] . DUMPSTER_RENTAL_SLUG;
    return $data;
}
// return all posts from service and product post_type
function getServiceProductPosts(): array
{
    return get_posts(['post_type'=>['service','product'],'order'=>'ASC']);
}
//set posts as options array for service and product post_type
function setServiceProductOptions(): array
{
    $service_productPost = getServiceProductPosts();
    $options =[];
    foreach ($service_productPost as $post){
        $options[$post->post_name] = $post->post_title;
    }
    return $options;
}

// Add current page slug to hidden field to send emails
add_filter( 'ninja_forms_render_default_value', 'addPageSlugToCustomField', 10, 3 );
function addPageSlugToCustomField( $default_value, $field_type, $field_settings ) {
   if($field_settings['key']==CURRENT_PAGE_SLUG_FIELD_KEY){
       if(get_queried_object()->taxonomy=='location'){
           $default_value = get_queried_object()->slug;
       }else {
           if($queryVar = get_query_var('location')){
               $default_value = $queryVar;
           }else {
               $default_value = '';
           }
       }
    }
    return $default_value;
}

// select the option as per current page slug
add_filter('ninja_forms_render_options', 'selectRenderedOptions', 10, 2);
function selectRenderedOptions($options, $settings)
{
    $terms = wp_list_pluck(get_terms(['taxonomy'=>'location','hide_empty'=>false]),'term_id','slug');

    if ($settings['key'] == DUMPSTER_FORM_LOCATION_FIELD_KEY) {
        foreach ($options as &$option) {
            $currentSlug = !empty(get_query_var('location'))?get_query_var('location'):get_queried_object()->slug;
            $slugArray = get_term_by('term_id',$terms[$currentSlug],'location');

            if (trim($option['value']) == trim(str_replace(')','',str_replace('(','',str_replace('/','',str_replace(',','',$slugArray->name)))))) {
                $option['selected'] = 1;
            }
        }
    }
    return $options;
}

// add previous button before submit button
add_filter('ninja_forms_display_before_field_key_submit_1654530155562','addPrevButton',10,1);
function addPrevButton($before){
    $before = '<button class="btn-global nf-previous">'.__('Previous',VAVIA_DOMAIN).'</button>';
    return $before;
}

// add custom item for dropdown location
add_filter('get_terms','addCustomKeyValueForDropdown',99,3);
function addCustomKeyValueForDropdown($terms,$taxonomy,$query_vars){
    if($taxonomy[0]!=='location') return $terms;
    foreach ($terms as $key=>$term){
        $terms[$key]->locationSpecificTerm = str_replace(')','',str_replace('(','',str_replace('/','',str_replace(',','',$term->name))));
        $terms[$key]->_location_phone = carbon_get_term_meta($term->term_id,'location_contacts_phone');
    }
    return $terms;
}

/* Change the canonical link for the service and product pages */
function changeLocationSpecificCanonicalLink( $canonical ) {
    $post = get_post(get_the_ID());
    $term = get_term_by('slug', get_query_var('location'), 'location');
    if (!empty($term) && is_object($post)){
            $canonical =  home_url().'/'.$term->slug.'/'.$post->post_name.'/';
    }
    return $canonical;
}
add_filter( 'wpseo_canonical', 'changeLocationSpecificCanonicalLink', 10, 1 );

//check resource pagination
add_action('wp','checkIfPaginationExist', 10);
function checkIfPaginationExist()
{
    global $wp_query;
    $perPage = (!empty($wp_query->query['author_name'])) ? 3 : 8;
    $resourceQuery = resourceQuery($wp_query->get('paged'), $perPage, $wp_query->get('author'));
    if (!$resourceQuery->have_posts()) {
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}

/*
 * Set per page to 3 for author page resource pagination
 */
add_action( 'pre_get_posts', 'modifyPreGetPosts' );
function modifyPreGetPosts( $query ){
    if ( is_author() ) {
        $query->set( 'posts_per_page', 3 );
    }

    return $query;
}
