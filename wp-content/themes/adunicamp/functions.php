<?php

//Functions adunicamp

define('SITEPATH', '/wp-content/themes/adunicamp/');

//************* Admin Login Logo
function tf_wp_admin_login_logo()
{ ?>
  <style type="text/css">
    body.login {
      background-image: url('<?php echo get_option('portal_input_2'); ?>');
      background-size: cover;
      background-position: center center;
    }

    #login {
      background: #fff;
      margin-top: 50px !important;
      padding: 0% 0 0 !important;
      padding: 20px !important;
      box-shadow: 0 0 15px rgb(0, 0, 0, 0.8) !important;
      border-radius: 5px;
    }

    #login h1 a {
      background-image: url('<?php echo get_option('portal_input_1'); ?>');
      background-size: 250px;
      width: 100%;
      height: 120px;
    }

    .language-switcher,
    #login .galogin-powered {
      display: none;
    }
  </style>
<?php }
add_action('login_enqueue_scripts', 'tf_wp_admin_login_logo');


//************* Admin Login Logo Link URL
function tf_wp_admin_login_logo_url()
{
  return home_url();
}
add_filter('login_headerurl', 'tf_wp_admin_login_logo_url');


//************* Admin Login Logo's Title
function tf_wp_admin_login_logo_title($headertext)
{
  $headertext = esc_html__(get_bloginfo('name'), 'plugin-textdomain');
  return $headertext;
}
add_filter('login_headertext', 'tf_wp_admin_login_logo_title');

//************* URL from breadcrumbs
function url_active()
{
  return explode("/", $_SERVER['REQUEST_URI']);
}
add_action('url_active', 'url_active');

//************* Limit Exceerpt
function get_excerpt($limit, $source = null)
{

  $excerpt = $source == "content" ? get_the_content() : get_the_excerpt();
  $excerpt = preg_replace(" (\[.*?\])", '', $excerpt);
  $excerpt = strip_shortcodes($excerpt);
  $excerpt = strip_tags($excerpt);
  $excerpt = substr($excerpt, 0, $limit);
  $excerpt = substr($excerpt, 0, strripos($excerpt, " "));
  $excerpt = trim(preg_replace('/\s+/', ' ', $excerpt));
  $excerpt = $excerpt . ' ... <a href="' . get_permalink(get_the_ID()) . '">mais</a>';
  return $excerpt;
}

//************* List Calendar
function listCalendar()
{
  $string_saida = "";

  $loop = new WP_Query(array('post_type' => 'agenda'));

  while ($loop->have_posts()) {
    $loop->the_post();
    $string_calendar = "";
    $data_inicio = get_post_meta(get_the_ID(), 'agenda_data_inicio', true);
    $data_fim = get_post_meta(get_the_ID(), 'agenda_data_fim', true);

    if ($data_inicio != "" && $data_fim != "") {

      $adatai = explode('T', $data_inicio);
      $datai = explode('-', $adatai[0]);
      $horai = explode(':', $adatai[1]);

      $adataf = explode('T', $data_fim);
      $dataf = explode('-', $adataf[0]);
      $horaf = explode(':', $adataf[1]);

      $string_calendar =
        "{title:'" . get_the_title() . "'," .
        "start: new Date(" . $datai[0] . "," . ($datai[1] - 1) . "," . $datai[2] . "," . $horai[0] . "," . $horai[1] . ")," .
        "end: new Date(" . $dataf[0] . "," . ($dataf[1] - 1) . "," . $dataf[2] . "," . $horaf[0] . "," . $horaf[1] . ")," .
        "allDay: false,url:'" . get_the_permalink() . "',className: 'success'},";

      $string_saida .= $string_calendar;
    }
  }
  wp_reset_postdata();

  return $string_saida;
}
add_action('listCalendar', 'listCalendar');


//************* Post Pagination
function post_pagination()
{
  global $wp_query;  
  $pager = 999999999; // need an unlikely integer
  echo paginate_links(array(
    'base' => str_replace($pager, '%#%', esc_url(get_pagenum_link($pager))),
    'current' => max(1, get_query_var('paged')),
    'total' => $wp_query->max_num_pages
  ));
}
add_action('post_pagination', 'post_pagination');

//************* Move Comment Field to Bottom
function wpb_move_comment_field_to_bottom( $fields ) {
  $comment_field = $fields['comment'];
  unset( $fields['comment'] );
  $fields['comment'] = $comment_field;
  return $fields;
  }   
  add_filter( 'comment_form_fields', 'wpb_move_comment_field_to_bottom' );
