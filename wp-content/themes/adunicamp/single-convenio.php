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
            <?php /*
            if ($imagem != "") { ?>
              <div class="entry-img">
                <img src="<?php echo $imagem; ?>" title="<?php echo $titulo ?>" alt="" class="img-fluid">
              </div>
            <?php }
            */ ?>
            <div class="entry-content">
              <?php echo $conteudo ?>
            </div>
          </article><!-- End blog entry -->

        </div>

      </div>
  </section><!-- End Blog Single Section -->

</main><!-- End #main -->
<?php get_footer(); ?>