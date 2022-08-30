<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<?php
// ************* Cor do portal
$post_color = get_option('portal_input_3');
?>

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?php bloginfo() ?></title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="<?php echo get_option('portal_input_1'); ?>" rel="icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Roboto:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?php echo SITEPATH; ?>assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/venobox/venobox.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="<?php echo SITEPATH; ?>assets/css/style.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/css/calendar.css" rel="stylesheet">

  <style>
    #header {
      background: <?php echo $post_color ?>;
    }
  </style>

  <?php wp_head(); ?>

</head>

<body>

  <!-- ======= Top Bar ======= -->
  <section id="topbar" class="d-flex align-items-center <?php if (is_user_logged_in()) echo "user-logged"; ?>">
    <div class="container d-flex justify-content-center justify-content-md-between">
      <div class="contact-info d-flex align-items-center">
        <i class="bi bi-envelope d-flex align-items-center"><a href="mailto:<?php echo get_option('portal_input_9'); ?>"><?php echo get_option('portal_input_10'); ?></a></i>
        <i class="bi bi-phone d-flex align-items-center ms-4"><span><?php echo get_option('portal_input_9'); ?></span></i>
      </div>
      <div class="top-search">
        <form action="/" method="get">
          <input type="text" placeholder="Pesquisar" required name="s" id="search" value="<?php the_search_query(); ?>" />
          <button type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>
      <div class="cta d-none d-md-flex align-items-center">
        <div class="portal-desc"><?php echo get_option('portal_input_4'); ?></div>
        <a href="<?php echo explode(',', get_option('portal_input_41'))[0]; ?>" class="scrollto"><?php echo explode(',', get_option('portal_input_41'))[1]; ?></a>
      </div>
    </div>
  </section>

  <!-- ======= Header ======= -->
  <header id="header" class="d-flex align-items-center <?php if (is_user_logged_in()) echo "user-logged"; ?>">
    <div class="container d-flex align-items-center justify-content-between">

      <div class="logo">
        <a href="/"><img src="<?php echo get_option('portal_input_1'); ?>" title="<?php echo get_option('portal_input_0'); ?>"></a>
      </div>

      <nav id="navbar" class="navbar">
        <?php wp_nav_menu(array('menu' => 'NAV-ADUnicamp')); ?>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
    <a class="logo-andes" target="_blank" href="https://www.andes.org.br/"><img src="/wp-content/uploads/2022/07/logo_andes-83x56-1.png"></a>
  </header><!-- End Header -->