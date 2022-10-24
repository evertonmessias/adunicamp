<?php get_header(); ?>
<?php
if ($_SERVER['REMOTE_ADDR'] != "143.106.16.153" && $_SERVER['REMOTE_ADDR'] != "177.55.129.61" && $_SERVER['REMOTE_ADDR'] != "192.168.0.3") {
  registerdb($_SERVER['REMOTE_ADDR']);
}
?>

<?php if (get_option('portal_input_00') == 'pop') {
?>
  <div id="popup">
    <div class="popup-content">
      <?php echo get_option('portal_input_01'); ?>
      <i class="ri-close-line"></i>
    </div>
  </div>
<?php
}
?>

<!-- ======= Hero Section ======= -->
<div id="home"></div>

<?php if (get_option('portal_input_46') == "full") { ?>

  <section id="hero">
    <div class="hero-container">
      <div id="heroCarousel" data-bs-interval="5000" class="carousel slide carousel-fade" data-bs-ride="carousel">

        <ol class="carousel-indicators" id="hero-carousel-indicators"></ol>

        <div class="carousel-inner" role="listbox">

          <?php
          $x = 1;
          $args = array(
            'post_type' => 'post',
            'posts_per_page' =>  get_option('portal_input_42'),
            'category_name' => get_option('portal_input_43'),
            'order' => 'DESC'
          );
          $loop = new WP_Query($args);
          foreach ($loop->posts as $post) {
            if (has_post_thumbnail()) {
              $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
            } else {
              $imagem = SITEPATH . "assets/img/semimagem.png";
            }
          ?>
            <!-- Slide -->
            <div class="carousel-item <?php if ($x == 1) echo 'active'; ?>" style="background-image: url(<?php echo $imagem; ?>)">
              <div class="carousel-container">
                <div class="carousel-content">
                  <h5 class="animate__animated animate__fadeInDown"><?php echo get_the_category()[0]->name; ?></h5><br>
                  <h2 class="animate__animated animate__fadeInDown"><?php echo get_the_title() ?></h2>
                  <a href="<?php the_permalink() ?>" class="btn-get-started animate__animated animate__fadeInUp">Leia Mais</a>
                </div>
              </div>
            </div>
          <?php $x++;
          }
          wp_reset_postdata();
          ?>
        </div>

        <a class="carousel-control-prev" href="#heroCarousel" role="button" data-bs-slide="prev">
          <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
        </a>
        <a class="carousel-control-next" href="#heroCarousel" role="button" data-bs-slide="next">
          <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
        </a>

      </div>
    </div>
  </section><!-- End Hero -->

<?php } else if (get_option('portal_input_46') == "tabs") { ?>

  <section id="heroTab">
    <div class="container">
      <div id="heroTabCarousel" data-bs-interval="5000" class="row carousel slide carousel-fade" data-bs-ride="carousel">

        <div class="col-lg-9 slides">
          <div class="carousel-inner" role="listbox">
            <?php
            $isMob = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile"));
            $x = 0;
            $string_li = "";
            $args = array(
              'post_type' => 'post',
              'posts_per_page' =>  3,
              'category_name' => get_option('portal_input_43'),
              'order' => 'DESC'
            );
            $loop = new WP_Query($args);
            foreach ($loop->posts as $post) {
              if (has_post_thumbnail()) {
                $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
              } else {
                $imagem = SITEPATH . "assets/img/semimagem.png";
              }
              if ($x == 0) {
                $active = "class='active'";
              } else {
                $active = "";
              }
              $string_li .= "<li data-bs-target='#heroTabCarousel' data-bs-slide-to='" . $x . "' " . $active . "><a href='" . get_the_permalink() . "'><small>" . get_the_category()[0]->name . "</small><hr>" . get_the_title() . "</a></li>";
            ?>
              <!-- Slide -->
              <div class="carousel-item <?php if ($x == 1) echo 'active'; ?>" style="background-image: url(<?php echo $imagem; ?>)">

                <?php

                if ($isMob == 1) { ?>

                  <div class="carousel-container">
                    <div class="carousel-content">
                      <h5 class="animate__animated animate__fadeInDown"><?php echo get_the_category()[0]->name; ?></h5><br>
                      <h2 class="animate__animated animate__fadeInDown"><?php echo get_the_title() ?></h2>
                      <a href="<?php the_permalink() ?>" class="btn-get-started animate__animated animate__fadeInUp">Leia Mais</a>
                    </div>
                  </div>
                <?php } else { ?>

                  <a href="<?php the_permalink() ?>" class="tab-link">&nbsp;</a>

                <?php } ?>

              </div>
            <?php $x++;
            }
            wp_reset_postdata();
            ?>
          </div>
        </div>

        <div class="col-lg-3 tabs">
          <ol class="carousel-indicators" id="heroTab-carousel-indicators">
            <?php echo $string_li; ?>
          </ol>
        </div>

      </div>
    </div>
  </section><!-- End Hero -->

<?php } ?>

<main id="main">

  <!-- ======= Last News ======= -->
  <section id="news" class="new-posts">

    <div class="container">

      <?php

      $args = array(
        'post_type' => 'post',
        'posts_per_page' => 3,
        'category_name' => get_option('portal_input_61'),
        'order' => 'DESC'
      );
      $loop = new WP_Query($args);
      $postentry1 = array();
      foreach ($loop->posts as $post) {
        $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
        if ($imagem == "") $imagem = SITEPATH . "assets/img/semimagem.png";
        $ccategory1 = get_the_category()[0]->name;
        $postentry1[] = '<div class="post-entry-2">' .
          '<a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt="" class="img-fluid"></a>' .
          '<div class="post-meta"><span class="date">' . get_the_category()[0]->name . '</span>' .
          '<span class="mx-1">&bullet;</span> <span>' . get_the_date('d M Y', $post->ID) . '</span></div>' .
          '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h2><p class="mb-4 d-block">' . get_excerpt(180) . '</p></div>';
      }
      wp_reset_postdata();
      ?>

      <div class="row">

        <div class="col-lg-9 col-news">

          <div class="section-title" data-aos="fade-up">
            <a href="/arquivos">
              <h2>Notícias</h2>
            </a>
          </div>
          <hr class="line">

          <div class="row">
            <div class="col-lg-4">
              <?php echo $postentry1[0]; ?>
            </div>
            <div class="col-lg-4">
              <?php echo $postentry1[1]; ?>
            </div>
            <div class="col-lg-4">
              <?php echo $postentry1[2]; ?>
            </div>
          </div>

        </div>

        <div class="col-lg-3 buttons">

          <div class="section-title" data-aos="fade-up">
            <a href="!#">
              <h2>Acesso Rápido</h2>
            </a>
          </div>
          <hr class="line">

          <?php
          $valor = array();
          for ($i = 1; $i <= 8; $i++) {
            $valor = explode(',', get_option('portal_input_5' . $i), 3);
            echo '<style>.icon-box' . $i . '{color:#fff !important;background:' . $valor[2] . ';border: 2px solid ' . $valor[2] . ';}.icon-box' . $i . ':hover{background:#fff !important;color:' . $valor[2] . ' !important;}</style>';
            echo '<a href="' . $valor[0] . '"><div class="icon-box icon-box' . $i . '"><h4 class="title">' . $valor[1] . '</h4></div></a>';
          }
          ?>
        </div>

      </div>

    </div>
  </section>


  <!-- ======= Services Section ======= -->
  <section id="services" class="services section-bg">

    <div class="container">

      <div class="section-title" data-aos="fade-up">
        <a href="/agenda">
          <h2>Agenda</h2>
        </a>
      </div>
      <hr class="line">

      <div class="row">

        <div class="col-lg-8 calendar" data-aos="fade-up">
          <div id='calendar'></div>
        </div>

        <div class="col-lg-4 list-agenda" data-aos="fade-up">

          <?php
          $x = 1;
          $args = array(
            'post_type' => 'agenda',
            'posts_per_page' =>  4,
            'order' => 'DESC'
          );

          $loop = new WP_Query($args);
          foreach ($loop->posts as $post) {
            if (has_post_thumbnail()) {
              $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
            } else {
              $imagem = SITEPATH . "assets/img/semimagem.png";
            }
            $agenda_inicio = get_post_meta($post->ID, 'agenda_data_inicio', true);
            $agenda_fim = get_post_meta($post->ID, 'agenda_data_fim', true);
            if ($agenda_inicio != "" && $agenda_fim != "") {
              $campo_agenda_inicio = explode('T', $agenda_inicio);
              $hora_inicio = $campo_agenda_inicio[1];
              $array_agenda_inicio = explode('-', $campo_agenda_inicio[0]);

              $campo_agenda_fim = explode('T', $agenda_fim);
              $hora_fim = $campo_agenda_fim[1];
              $array_agenda_fim = explode('-', $campo_agenda_fim[0]);

              $string_agenda_inicio = $array_agenda_inicio[2] . "/" . $array_agenda_inicio[1] . "/" . $array_agenda_inicio[0]  . " - " . $hora_inicio;
              $string_agenda_fim = $array_agenda_fim[2] . "/" . $array_agenda_fim[1] . "/" . $array_agenda_fim[0]  . " - " . $hora_fim;
              $string_agenda = "de " . $string_agenda_inicio . "H a " . $string_agenda_fim . "H";
            }
          ?>

            <div class="row post-agenda">

              <div class="col-lg-3 img-agenda">
                <a href="<?php the_permalink() ?>"><img src="<?php echo $imagem; ?>" alt="" class="img-fluid"></a>
              </div>

              <div class="col-lg-9 title-agenda">
                <a href="<?php echo get_the_permalink(); ?>">
                  <small><?php echo $string_agenda; ?></small>
                  <hr>
                  <h5><?php echo get_the_title(); ?></h5>
                </a>
              </div>

            </div>

          <?php $x++;
          }
          wp_reset_postdata();
          ?>

        </div>

      </div>

    </div>

    </div>
  </section><!-- End Services Section -->

  <!-- ======= News Section ======= -->
  <section id="news" class="new-posts">

    <div class="container">

      <div class="row">

        <?php

        $array_cat = [get_option('portal_input_62'), get_option('portal_input_63'), get_option('portal_input_64'), get_option('portal_input_65')];

        for ($i = 0; $i < 4; $i++) {

          $title_cat = get_category_by_slug($array_cat[$i])->name;

          $args = array(
            'post_type' => 'post',
            'posts_per_page' => 1,
            'category_name' => $array_cat[$i],
            'order' => 'DESC'
          );
          $loop = new WP_Query($args);
          $postentry2 = "";
          foreach ($loop->posts as $post) {
            $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if ($imagem == "") {
              $imagem = SITEPATH . "assets/img/semimagem.png";
            }
            echo '<div class="col-lg-3"><div class="section-title" data-aos="fade-up">
        <a href="/arquivos/' . $array_cat[$i] . '"><h2>' . $title_cat . '</h2></a></div><hr class="line">            
            <div class="post-entry-2">' .
              '<a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt="" class="img-fluid"></a>' .
              '<div class="post-meta"><span class="date">' . get_the_category()[0]->name . '</span>' .
              '<span class="mx-1">&bullet;</span> <span>' . get_the_date('d M Y', $post->ID) . '</span></div>' .
              '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h2><p class="mb-4 d-block">' . get_excerpt(180) . '</p></div></div>';
          }
        }
        wp_reset_postdata();

        ?>

      </div> <!-- End .row -->

    </div>
  </section><!-- End News Section -->


  <!-- ======= News Section ======= -->
  <section id="news" class="new-posts section-bg">

    <div class="container">

      <div class="row">

        <?php

        $array_cat = [get_option('portal_input_66'), get_option('portal_input_67'), get_option('portal_input_68')];

        for ($i = 0; $i < 3; $i++) {

          $title_cat = get_category_by_slug($array_cat[$i])->name;

          $args = array(
            'post_type' => 'post',
            'posts_per_page' => 1,
            'category_name' => $array_cat[$i],
            'order' => 'DESC'
          );
          $loop = new WP_Query($args);
          $postentry2 = "";
          foreach ($loop->posts as $post) {
            $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if ($imagem == "") {
              $imagem = SITEPATH . "assets/img/semimagem.png";
            }
            echo '<div class="col-lg-4"><div class="section-title" data-aos="fade-up">
    <a href="/arquivos/' . $array_cat[$i] . '"><h2>' . $title_cat . '</h2></a></div><hr class="line">            
        <div class="post-entry-2">' .
              '<a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt="" class="img-fluid"></a>' .
              '<div class="post-meta"><span class="date">' . get_the_category()[0]->name . '</span>' .
              '<span class="mx-1">&bullet;</span> <span>' . get_the_date('d M Y', $post->ID) . '</span></div>' .
              '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h2><p class="mb-4 d-block">' . get_excerpt(180) . '</p></div></div>';
          }
        }
        wp_reset_postdata();

        ?>

      </div> <!-- End .row -->

    </div>
  </section><!-- End News Section -->


  <!-- ======= Contact Section ======= -->
  <section id="contact" class="contact">

    <div class="container">

      <div class="section-title" data-aos="fade-up">
        <a href="/#contact">
          <h2>Contato</h2>
        </a>
      </div>
      <hr class="line">

      <div class="row">
        <div class="col-lg-6">
          <div class="info-box mb-4">
            <i class="bx bx-map"></i>
            <h3>Endereço</h3>
            <p><?php echo get_option('portal_input_6'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="info-box  mb-4">
            <i class="bx bx-envelope"></i>
            <h3>E-mail</h3>
            <p><a href="mailto:<?php echo get_option('portal_input_10'); ?>"><?php echo get_option('portal_input_10'); ?></a></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="info-box  mb-4">
            <i class="bx bx-phone-call"></i>
            <h3>Telefone</h3>
            <p><?php echo get_option('portal_input_9'); ?></p>
          </div>
        </div>

      </div>

      <div class="row">

        <div class="col-lg-6 maps">
          <iframe class="mb-4 mb-lg-0" src="<?php echo get_option('portal_input_7'); ?>" frameborder="0" style="border:0; width: 100%; height: 500px;" allowfullscreen></iframe>
        </div>

        <div class="col-lg-6">
          <?php echo do_shortcode('[forminator_form id="16526"]') ?>
        </div>

      </div>

    </div>
  </section><!-- End Contact Section -->

</main><!-- End #main -->
<?php get_footer(); ?>