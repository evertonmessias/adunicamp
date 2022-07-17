<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <ol>
        <li><a href="/">inicio</a></li>
        <li><a href="<?php echo get_the_permalink() ?>"><?php echo $post->post_name ?></a></li>
      </ol>
      <h2><?php echo $post->post_title ?></h2>
    </div>
  </section><!-- End Breadcrumbs -->

  <section class="inner-page pt-3 page">
    <div class="container">
      <?php the_content() ?>
    </div>
  </section>

</main><!-- End #main -->
<?php get_footer(); ?>