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
  <link href="<?php echo SITEPATH; ?>assets/css/adunicamp.css" rel="stylesheet">
  <link href="<?php echo SITEPATH; ?>assets/css/calendar.css" rel="stylesheet">
  <style>
    #header {
      background: <?php echo $post_color ?>;
    }
  </style>

  <?php wp_head(); ?>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">

      <div class="logo">
        <a href="/"><img src="<?php echo get_option('portal_input_1'); ?>" title="<?php echo get_option('portal_input_0'); ?>"></a>
      </div>

      <div class="portal-desc"><?php echo get_option('portal_input_4'); ?></div>

      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="/#hero">Home</a></li>
          <li><a class="nav-link scrollto" href="/#services">Serviços</a></li>
          <li><a class="nav-link scrollto" href="/#news">Notícias</a></li>
          <li class="dropdown media"><a class="nav-link scrollto" href="/#media"><span>Mídia</span> <i class="bi bi-chevron-down"></i></a>
            <ul>              
              <li><a class="nav-link scrollto" href="/#podcast">Podcast</a></li>
              <li><a class="nav-link scrollto" href="/#gallery">Galeria</a></li>
            </ul>
          </li>         
          <li><a class="nav-link scrollto" href="/#contact">Contato</a></li>
          <li class="dropdown entity"><a href="javascript:void(0)"><span>Entidade</span> <i class="bi bi-chevron-down"></i></a>
            <ul>              
              <li><a href="/#">Regimento</a></li>
              <li><a href="/#">História</a></li>
              <li><a href="/#">Diretoria</a></li>
              <li><a href="/#">Conselho</a></li>
              <li><a href="/#">Equipe</a></li>
            </ul>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->

    </div>
  </header><!-- End Header -->