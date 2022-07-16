<?php get_header(); ?>
<?php
if ($_SERVER['REMOTE_ADDR'] != "143.106.16.179" && $_SERVER['REMOTE_ADDR'] != "177.55.129.61" && $_SERVER['REMOTE_ADDR'] != "192.168.0.3") {
  registerdb($_SERVER['REMOTE_ADDR']);
}
?>

<!-- ======= Hero Section ======= -->
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

<main id="main">

  <!-- ======= Last News ======= -->
  <section id="top-news" class="gallery section-bg">
    <div class="container" data-aos="fade-up">

      <div class="section-title" data-aos="fade-up">
        <h2>Destaques</h2>
      </div>

      <div class="gallery-slider swiper">
        <div class="swiper-wrapper icon-boxes">

          <?php
          $x = 1;
          $args = array(
            'post_type' => 'post',
            'posts_per_page' =>  get_option('portal_input_44'),
            'category_name' => get_option('portal_input_45'),
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

            <div class="swiper-slide">

              <div class="post-entry-1">
                <a href="<?php the_permalink() ?>"><img src="<?php echo $imagem; ?>" alt="" class="img-fluid"></a>
                <div class="post-meta"><span class="date"><?php echo get_the_category()[0]->name; ?></span>
                  <span class="mx-1">&bullet;</span> <span><?php echo get_the_date('d M Y', $post->ID); ?></span>
                </div>
                <h2><a href="<?php the_permalink() ?>"><?php echo get_the_title() ?></a></h2>
              </div>

            </div>

          <?php $x++;
          }
          wp_reset_postdata();
          ?>

        </div>
        <div class="swiper-pagination"></div>
      </div>

      <div class="row">
        <div class="col-lg-12 more">
          <a class="getstarted" href="/arquivos">Veja todos</a>
        </div>
      </div>

    </div>
  </section><!-- End Gallery -->

  <!-- ======= Services Section ======= -->
  <section id="services" class="services" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo get_option('portal_input_2') ?>') center center;background-size: cover;padding: 50px 0;background-attachment: fixed;">

    <div class="container">

      <div class="section-title" data-aos="fade-up">
        <h2 class="white">Serviços</h2>
      </div>

      <div class="row">

        <div class="col-lg-8 calendar" data-aos="fade-up">
          <div id='calendar'></div>
        </div>

        <div class="col-lg-4 buttons" data-aos="fade-up">
          <?php
          $valor = array();
          for ($i = 1; $i <= 8; $i++) {
            $valor = explode(',', get_option('portal_input_5' . $i), 3);
            echo '<a href="' . $valor[0] . '"><div class="icon-box"><div class="icon"><i class="' . $valor[1] . '"></i></div><h4 class="title">' . $valor[2] . '</h4></div></a>';
          }
          ?>
        </div>

      </div>

    </div>

    </div>
  </section><!-- End Services Section -->

  <!-- ======= Convênios Section ======= -->
  <section id="agreement" class="clients section-bg">
    <div class="container" data-aos="fade-up">

      <div class="section-title" data-aos="fade-up">
        <h2>Convênios</h2>
      </div>

      <div class="clients-slider swiper">
        <div class="swiper-wrapper align-items-center">

          <?php
          $x = 1;
          $args = array(
            'post_type' => 'convenio',
            'order' => 'DESC',
            'posts_per_page' => 20
          );
          $loop = new WP_Query($args);
          foreach ($loop->posts as $post) {
            if (has_post_thumbnail()) {
              $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
            } else {
              $imagem = SITEPATH . "assets/img/semimagem.png";
            }
          ?>

            <div class="swiper-slide">
              <a href="<?php echo get_the_permalink() ?>"><img src="<?php echo $imagem; ?>" class="img-fluid" alt="" title="<?php echo $post->post_title; ?>">
                <p><?php echo $post->post_title; ?></p>
              </a>
            </div>

          <?php $x++;
          }
          wp_reset_postdata();
          ?>

        </div><br>
        <div class="swiper-pagination"></div>
      </div>

    </div>
  </section><!-- End Convênios Section -->



  <!-- ======= News Section ======= -->
  <section id="news" class="new-posts">
    <div class="container">

      <div class="section-title" data-aos="fade-up">
        <h2>Arquivos</h2>
      </div>

      <?php
      $x = 1;
      $args = array(
        'post_type' => 'post',
        'order' => 'DESC',
        'posts_per_page' => 11
      );
      $loop = new WP_Query($args);
      $postentry = array();
      foreach ($loop->posts as $post) {
        if (has_post_thumbnail()) {
          $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
        } else {
          $imagem = SITEPATH . "assets/img/semimagem.png";
        }
        if ($x <= 2) {
          $excerpt = '<p class="mb-4 d-block">' . get_excerpt(300) . '</p>';
        } else {
          $excerpt = "";
        }
        $postentry[] = '<div class="post-entry-2">' .
          '<a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt="" class="img-fluid"></a>' .
          '<div class="post-meta"><span class="date">' . get_the_category()[0]->name . '</span>' .
          '<span class="mx-1">&bullet;</span> <span>' . get_the_date('d M Y', $post->ID) . '</span></div>' .
          '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h2>' . $excerpt . '</div>';
        $x++;
      }
      wp_reset_postdata();
      ?>


      <div class="row">
        <div class="col-lg-4">
          <?php echo $postentry[0] . $postentry[1]; ?>
        </div>

        <div class="col-lg-8">
          <div class="row">
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[2] . $postentry[3] . $postentry[4]; ?>
            </div>
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[5] . $postentry[6] . $postentry[7]; ?>
            </div>
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[8] . $postentry[9] . $postentry[10]; ?>
            </div>
          </div>
        </div>

      </div> <!-- End .row -->
      <div class="row">
        <div class="col-lg-12 more">
          <a class="getstarted" href="/arquivos">Veja todos</a>
        </div>
      </div>
    </div>
  </section><!-- End News Section -->


  <div id="media">
    <!-- ======= Video Section ======= -->
    <section id="podcast" class="podcast" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo get_option('portal_input_2') ?>') center center;background-size: cover;background-attachment: fixed;">>
      <div class="container" data-aos="fade-up">

        <div class="section-title" data-aos="fade-up">
          <h2 class="white">Podcast</h2>
        </div>

        <div class="podcast-slider swiper">
          <div class="swiper-wrapper align-items-center">

            <?php
            $x = 1;
            $args = array(
              'post_type' => 'post',
              'category_name' => 'podcast',
              'order' => 'DESC',
              'posts_per_page' => 10
            );
            $loop = new WP_Query($args);
            foreach ($loop->posts as $post) {
              if (has_post_thumbnail()) {
                $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
              } else {
                $imagem = SITEPATH . "assets/img/semimagem.png";
              }
            ?>
              <div class="swiper-slide"><a href="<?php echo get_permalink() ?>"><img src="<?php echo $imagem ?>" class="img-fluid" alt="" title="<?php echo get_the_title() ?>"></a></div>
            <?php $x++;
            }
            wp_reset_postdata();
            ?>


          </div>
          <div class="swiper-pagination"></div>
        </div>

      </div>
    </section><!-- End Gallery Section -->

    <!-- ======= Portfolio Section ======= -->
    <section id="gallery" class="portfolio section-bg">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Galeria</h2>
        </div>

        <div class="row" data-aos="fade-up" data-aos-delay="100">
          <div class="col-lg-12 d-flex justify-content-center">
            <ul id="portfolio-flters">
              <!--<li data-filter="*" class="filter-active">Todos</li>-->
              <?php
              $x = 1;
              $args = array(
                'post_type' => 'album',
                'order' => 'ASC',
                'posts_per_page' => 5
              );
              $loop = new WP_Query($args);
              foreach ($loop->posts as $post) {
              ?>
                <li data-filter=".filter-<?php echo $post->post_name ?>" class="btn-album <?php if ($x == 1) echo "filter-active"; ?>"><?php echo $post->post_title ?></li>
              <?php
                $x++;
              }
              wp_reset_postdata();
              ?>
            </ul>
          </div>
        </div>

        <div class="row portfolio-container" data-aos="fade-up" data-aos-delay="200">

          <?php
          $x = 1;
          $args = array(
            'post_type' => 'album',
            'order' => 'ASC',
            'posts_per_page' => 5
          );
          $loop = new WP_Query($args);
          foreach ($loop->posts as $post) {
            $array_imgs = explode(',', rtrim(get_post_meta($post->ID, 'post_upload_0', true), ','));
            foreach ($array_imgs as $img) {
          ?>
              <div class="<?php if ($x != 1) echo "display"; ?> col-lg-4 col-md-6 portfolio-item filter-<?php echo $post->post_name ?>">
                <div class="portfolio-wrap">
                  <img src="<?php echo $img ?>" class="img-fluid" alt="">
                  <div class="portfolio-info">
                    <div class="portfolio-links">
                      <a href="<?php echo $img ?>" data-gallery="portfolioGallery" class="portfolio-lightbox"><i class="bx bx-plus"></i></a>
                    </div>
                  </div>
                </div>
              </div>
          <?php
            }
            $x++;
          }
          wp_reset_postdata();
          ?>
        </div>
      </div>
    </section><!-- End Portfolio Section -->
  </div>

  <!-- ======= Contact Section ======= -->
  <section id="contact" class="contact">
    <div class="container">

      <div class="section-title">
        <h2>Contato</h2>
      </div>

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

        <div class="col-lg-6 ">
          <iframe class="mb-4 mb-lg-0" src="<?php echo get_option('portal_input_7'); ?>" frameborder="0" style="border:0; width: 100%; height: 384px;" allowfullscreen></iframe>
        </div>

        <div class="col-lg-6">
          <?php echo do_shortcode('[contact-form-7 id="130" title="Contato"]') ?>
        </div>

      </div>

    </div>
  </section><!-- End Contact Section -->

</main><!-- End #main -->
<?php get_footer(); ?>