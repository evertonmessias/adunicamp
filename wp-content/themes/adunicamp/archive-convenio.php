<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <h2><?php echo ucfirst(url_active()[1]); if($_GET['cat'] != "")echo ": ".ucfirst($_GET['cat']); ?></h2>
      <ol>
        <li><a href="/">inicio</a></li>
        <li><?php echo url_active()[1] ?></li>
      </ol>
    </div>
  </section><!-- End Breadcrumbs -->

  <!-- ======= Portfolio Details Section ======= -->
  <section class="portfolio-details">

    <div class="container" data-aos="fade-up">

      <div class="row portfolio-container" data-aos="fade-up" data-aos-delay="200">

        <?php
        $args = array(
          'post_type' => url_active()[1],
          'posts_per_page' => 100,
          'convenio_categories' => $_GET['cat']
        );
        $loop = new WP_Query($args);
        while ($loop->have_posts()) {
          $loop->the_post();
          if (has_post_thumbnail()) {
            $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
          } else {
            $imagem = SITEPATH . "assets/img/semimagem.png";
          }
        ?>
          <div class="col-lg-3 portfolio-item agenda convenio">
            <a href="<?php echo get_the_permalink(); ?>" title="Abrir"><img src="<?php echo $imagem; ?>" class="img-fluid" title="<?php echo get_the_title() ?>"></a>
            <div class="portfolio-info">
              <h4><a href="<?php echo get_the_permalink(); ?>" title="Abrir"><?php echo get_the_title() ?></a></h4>
            </div>
          </div>
        <?php }
        wp_reset_postdata();
        ?>
      </div>
    </div>

  </section><!-- End Portfolio Details Section -->

</main><!-- End #main -->
<?php get_footer(); ?>