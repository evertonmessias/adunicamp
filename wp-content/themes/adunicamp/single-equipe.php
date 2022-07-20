<?php get_header(); ?>
<?php
$imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
$titulo = get_the_title();
$conteudo = get_the_content();
$agenda_inicio = get_post_meta($post->ID, 'agenda_data_inicio', true);
$agenda_fim = get_post_meta($post->ID, 'agenda_data_fim', true);
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
  <section id="news" class="new-posts agenda">
    <div class="container" data-aos="fade-up">

      <div class="row">

        <div class="col-lg-12 entries">

          <article class="entry entry-single agenda">

            <?php
            if ($imagem != "") { ?>
              <div class="entry-img">
                <img src="<?php echo $imagem; ?>" title="<?php echo $titulo ?>" alt="" class="img-fluid">
              </div>
            <?php }
            ?>
            <div class="entry-content">
            <?php
            if ($agenda_inicio != "" && $agenda_fim != "") {

              $campo_agenda_inicio = explode('T',$agenda_inicio);
              $hora_inicio = $campo_agenda_inicio[1];
              $array_agenda_inicio = explode('-', $campo_agenda_inicio[0]);

              $campo_agenda_fim = explode('T',$agenda_fim);
              $hora_fim = $campo_agenda_fim[1];
              $array_agenda_fim = explode('-', $campo_agenda_fim[0]);
            ?>
              <div class="entry-info">
                <h5>Início:&nbsp;<?php echo $array_agenda_inicio[2] . "/" . $array_agenda_inicio[1] . "/" . $array_agenda_inicio[0]  . " - " . $hora_inicio; ?></h5>
                <h5>Fim:&emsp;<?php echo $array_agenda_fim[2] . "/" . $array_agenda_fim[1] . "/" . $array_agenda_fim[0]  . " - " . $hora_fim; ?></h5>
              </div>
            <?php }
            ?>
            <?php echo $conteudo ?>
            </div>
          </article><!-- End blog entry -->

        </div>

      </div>
  </section><!-- End Blog Single Section -->

</main><!-- End #main -->
<?php get_footer(); ?>