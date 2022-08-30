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

  <!-- ======= Portfolio Details Section ======= -->
  <section class="portfolio-details">

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
            $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
          } else {
            $imagem = SITEPATH . "assets/img/semimagem.png";
          }
        ?>
          <div class="col-lg-3 portfolio-item agenda">
            <a href="<?php echo get_the_permalink(); ?>" title="Abrir"><img src="<?php echo $imagem; ?>" class="img-fluid" title="<?php echo get_the_title() ?>"></a>
            <div class="portfolio-info">
              <h4><a href="<?php echo get_the_permalink(); ?>" title="Abrir"><?php echo get_the_title() ?></a></h4>
            </div>
            <?php
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
            ?>
              <div class="entry-info">
                <h5><b>Início:</b>&nbsp;<?php echo $string_agenda_inicio; ?></h5>
                <h5><b>Fim:</b>&emsp;<?php echo $string_agenda_fim; ?></h5>
              </div>
            <?php }
            ?>
          </div>
        <?php }
        wp_reset_postdata();
        ?>
      </div>
    </div>

  </section><!-- End Portfolio Details Section -->

</main><!-- End #main -->
<?php get_footer(); ?>