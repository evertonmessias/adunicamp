<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <h2><?php echo $post->post_title ?></h2>
      <ol>
        <li><a href="/">inicio</a></li>
        <li><?php echo $post->post_name ?></li>
      </ol>      
    </div>
  </section><!-- End Breadcrumbs -->

  <section class="inner-page pt-3 page">
    <div class="container">
      <?php the_content() ?>
    </div>
  </section>

</main><!-- End #main -->
<?php get_footer(); ?>