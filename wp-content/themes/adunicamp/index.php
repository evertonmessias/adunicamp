<?php get_header(); ?>
<main id="main" class="post" data-aos="fade-up">
  <!-- ======= Breadcrumbs ======= -->
  <section class="breadcrumbs">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <h2><?php the_title() ?></h2>
        <ol>
          <li><a href="/">home</a></li>
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

    <div class="container">

    <div class="portfolio-details-container">
        <?php if (has_post_thumbnail()) { ?>
          <div class="owl-carousel portfolio-details-carousel">
            <a href="<?php the_post_thumbnail_url('full'); ?>" target="_blank">
              <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" title="<?php the_title() ?>">
            </a>
          </div>
          <br><br>
        <?php } ?>
      </div>

      <div class="portfolio-description text-justify">
          <h5><strong>Data Início:</strong>&ensp;<?php echo get_post_meta($post->ID, 'agenda_data_inicio', true); ?></h5>
          <h5><strong>Data Fim:</strong>&ensp;<?php echo get_post_meta($post->ID, 'agenda_data_fim', true); ?></h5>
          <?php
          $adata = explode('T', get_post_meta($post->ID, 'agenda_data_inicio', true));
          
          $data = explode('-', $adata[0]);
          $hora = explode(':', $adata[1]);          

          echo "<p>".$data[2] . "/" . $data[1] . "/" . $data[0]."</p>";
          echo "<p>".$hora[0]." :: ".$hora[1]."</p>";
          ?>
        <?php the_content() ?>        
      </div>
      
    </div>

  </section><!-- End Portfolio Details Section -->

</main><!-- End #main -->
<?php get_footer(); ?>