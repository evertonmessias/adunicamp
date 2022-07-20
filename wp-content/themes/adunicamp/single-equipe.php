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

          <article class="entry entry-single agenda equipe">

            <?php
            if ($imagem != "") { ?>
              <div class="entry-img">
                <img src="<?php echo $imagem; ?>" title="<?php echo $titulo ?>" alt="" class="img-fluid">
              </div>
            <?php }
            ?>
            <div class="entry-content">
              <?php
              $equipe_funcao = get_post_meta($post->ID, 'equipe_funcao', true);
              $equipe_local = get_post_meta($post->ID, 'equipe_local', true);
              $equipe_contato = get_post_meta($post->ID, 'equipe_contato', true);
              ?>
              <div class="entry-info">
              <h4><?php echo get_the_terms($post->ID,'equipe_categories')[0]->name; ?></h4>
                <h5><b>Função:</b>&ensp;<?php echo $equipe_funcao ?></h5>
                <?php if ($equipe_local != "") { ?>
                  <h5><b>Local:</b>&nbsp;&emsp;<?php echo $equipe_local ?></h5>
                <?php } else if ($equipe_contato != "") { ?>
                  <h5><b>Contato:</b>&nbsp;<?php echo $equipe_contato ?></h5>
                <?php } ?>
              </div>
            </div>
          </article><!-- End blog entry -->

        </div>

      </div>
  </section><!-- End Blog Single Section -->

</main><!-- End #main -->
<?php get_footer(); ?>