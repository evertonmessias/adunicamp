<?php get_header(); ?>
<main id="main" class="post" data-aos="fade-up">
  <!-- ======= Breadcrumbs ======= -->
  <section class="breadcrumbs">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <h2>ESPREDES</h2>
        <ol>
          <li><a href="/">home</a></li>
          &emsp;/&emsp;
          <li>
            <?php
            if (url_active()[2] == "") echo url_active()[1];
            else echo "<a href='/" . url_active()[1] . "'>" . url_active()[1] . "</a>";
            ?>
          </li>          
        </ol>
      </div>
    </div>
  </section><!-- Breadcrumbs Section -->

		<!-- ======= Portfolio Details Section ======= -->
    <section id="page" class="portfolio-details">

    <div class="container" data-aos="fade-up">

<div class="row portfolio-container" data-aos="fade-up" data-aos-delay="200">

    <?php
    $args = array(
        'post_type' => url_active()[1],
        'posts_per_page' => 100
    );
    $loop = new WP_Query($args);
    while ($loop->have_posts()) {
        $loop->the_post();
        if (has_post_thumbnail()) {
            $imagem = get_the_post_thumbnail_url(get_the_ID(),'full');
        } else {
            $imagem = SITEPATH . "assets/img/semimagem.png";
        }
    ?>
        <div class="col-lg-4 portfolio-item">
        <?php if (url_active()[1] != 'disciplina') { ?>
            <img src="<?php echo $imagem; ?>" class="img-fluid" title="<?php echo get_the_title() ?>">
            <?php } ?>
            <div class="portfolio-info">
                    <h4><?php echo get_the_title() ?></h4>
                    <a class="get-started-btn" target='_blank' href="<?php echo get_the_permalink(); ?>" title="Abrir">Leia Mais +</a>
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