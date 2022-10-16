<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <h2><?php echo ucfirst(url_active()[1]) ?></h2>
      <ol>
        <li><a href="/">inicio</a></li>
        <li><?php echo url_active()[1] ?></li>
      </ol>
    </div>
  </section><!-- End Breadcrumbs -->

    <!-- ======= Portfolio Section ======= -->

    <section id="gallery" class="portfolio">

    <div class="container" data-aos="fade-up">

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


</main><!-- End #main -->
<?php get_footer(); ?>