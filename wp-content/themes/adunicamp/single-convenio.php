<?php get_header(); ?>
<?php
$imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
$titulo = get_the_title();
$conteudo = get_the_content();
?>

<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <h2><?php echo ucfirst(url_active()[1]) . ": " . $titulo ?></h2>
      <ol>
        <li><a href="/">inicio</a></li>
        <li>
          <a href="/<?php echo url_active()[1] ?>"><?php echo url_active()[1] ?></a>
        </li>
      </ol>
    </div>
  </section><!-- End Breadcrumbs -->

  <!-- ======= Blog Single Section ======= -->
  <section id="news" class="new-posts convenio">
    <div class="container" data-aos="fade-up">

      <div class="row">

        <div class="col-lg-12 entries">
          <article class="entry entry-single convenio">

            <div class="row">

              <div class="col-lg-6">

                <div class="row">
                  <div class="col-lg-12">
                    <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>" alt="" class="img-fluid img-convenio">
                  </div>
                </div>

                <div class="row">
                  <div class="col-lg-12 contatos">
                    <h3>Contatos</h3>
                    <?php
                    echo "<p>" . get_post_meta($post->ID, 'convenio_telefones', true) . "</p>";
                    echo "<p>" . get_post_meta($post->ID, 'convenio_email', true) . "</p>";
                    ?>
                  </div>
                </div>

              </div>
              <div class="col-lg-6 right">
                <?php
                echo get_post_meta($post->ID, 'convenio_informacoes1', true);
                ?>
              </div>

            </div>

            <?php if (get_post_meta($post->ID, 'convenio_informacoes2', true) != "") { ?>

              <hr class="line">
              <div class="row">

                <div class="col-lg-6">
                  <?php
                  echo get_post_meta($post->ID, 'convenio_informacoes2', true);
                  ?>
                </div>
                <div class="col-lg-6 right">
                  <?php
                  echo get_post_meta($post->ID, 'convenio_informacoes3', true);
                  ?>
                </div>

              </div>

            <?php } ?>

            <?php if (get_post_meta($post->ID, 'convenio_informacoes4', true) != "") { ?>

              <br>
              <hr class="line">

              <div class="row">

                <div class="col-lg-6">
                  <?php
                  echo get_post_meta($post->ID, 'convenio_informacoes4', true);
                  ?>
                </div>
                <div class="col-lg-6 right">
                  <?php
                  echo get_post_meta($post->ID, 'convenio_informacoes5', true);
                  ?>
                </div>

              </div>

            <?php } ?>

          </article><!-- End blog entry -->

        </div>

      </div>
  </section><!-- End Blog Single Section -->

</main><!-- End #main -->
<?php get_footer(); ?>