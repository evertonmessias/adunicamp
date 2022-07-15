<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <ol>
        <li><a href="/">inicio</a></li>
        <li>arquivos</li>
      </ol>
      <h2>Arquivos</h2>
    </div>
  </section><!-- End Breadcrumbs -->

  <!-- ======= Blog Section ======= -->
  <section id="news" class="new-posts">
    <div class="container" data-aos="fade-up">

      <?php
      $x = 1;
      $args = array(
        'post_type' => 'post',
        'order' => 'DESC',
        'posts_per_page' => 12
      );
      $loop = new WP_Query($args);
      $postentry = array();
      foreach ($loop->posts as $post) {
        if (has_post_thumbnail()) {
          $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
        } else {
          $imagem = SITEPATH . "assets/img/semimagem.png";
        }
        $postentry[] = '<div class="post-entry-2">' .
          '<a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt="" class="img-fluid"></a>' .
          '<div class="post-meta"><span class="date">' . get_the_category()[0]->name . '</span>' .
          '<span class="mx-1">&bullet;</span> <span>' . get_the_date('d M Y', $post->ID) . '</span></div>' .
          '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h2></div>';
        $x++;
      }
      wp_reset_postdata();
      ?>

      <div class="row">

        <div class="col-lg-9 entries">

          <div class="row">
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[0] . $postentry[1] . $postentry[2] . $postentry[3]; ?>
            </div>
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[4] . $postentry[5] . $postentry[6] . $postentry[7]; ?>
            </div>
            <div class="col-lg-4 border-start custom-border">
              <?php echo $postentry[8] . $postentry[9] . $postentry[10] . $postentry[11]; ?>
            </div>
          </div>  

        </div><!-- End blog entries list -->

        <div class="col-lg-3">
          <div class="sidebar">
            <h3 class="sidebar-title">Search</h3>
            <div class="sidebar-item search-form">
              <form action="">
                <input type="text">
                <button type="submit"><i class="bi bi-search"></i></button>
              </form>
            </div><!-- End sidebar search formn-->

            <h3 class="sidebar-title">Categories</h3>
            <div class="sidebar-item categories">
              <ul>
                <li><a href="#">General <span>(25)</span></a></li>
                <li><a href="#">Lifestyle <span>(12)</span></a></li>
                <li><a href="#">Travel <span>(5)</span></a></li>
                <li><a href="#">Design <span>(22)</span></a></li>
                <li><a href="#">Creative <span>(8)</span></a></li>
                <li><a href="#">Educaion <span>(14)</span></a></li>
              </ul>
            </div><!-- End sidebar categories-->

            <h3 class="sidebar-title">Recent Posts</h3>
            <div class="sidebar-item recent-posts">
              <div class="post-item clearfix">
                <img src="<?php echo SITEPATH; ?>assets/img/blog/blog-recent-1.jpg" alt="">
                <h4><a href="blog-single.html">Nihil blanditiis at in nihil autem</a></h4>
                <time datetime="2020-01-01">Jan 1, 2020</time>
              </div>

              <div class="post-item clearfix">
                <img src="<?php echo SITEPATH; ?>assets/img/blog/blog-recent-2.jpg" alt="">
                <h4><a href="blog-single.html">Quidem autem et impedit</a></h4>
                <time datetime="2020-01-01">Jan 1, 2020</time>
              </div>

              <div class="post-item clearfix">
                <img src="<?php echo SITEPATH; ?>assets/img/blog/blog-recent-3.jpg" alt="">
                <h4><a href="blog-single.html">Id quia et et ut maxime similique occaecati ut</a></h4>
                <time datetime="2020-01-01">Jan 1, 2020</time>
              </div>

              <div class="post-item clearfix">
                <img src="<?php echo SITEPATH; ?>assets/img/blog/blog-recent-4.jpg" alt="">
                <h4><a href="blog-single.html">Laborum corporis quo dara net para</a></h4>
                <time datetime="2020-01-01">Jan 1, 2020</time>
              </div>

              <div class="post-item clearfix">
                <img src="<?php echo SITEPATH; ?>assets/img/blog/blog-recent-5.jpg" alt="">
                <h4><a href="blog-single.html">Et dolores corrupti quae illo quod dolor</a></h4>
                <time datetime="2020-01-01">Jan 1, 2020</time>
              </div>
            </div><!-- End sidebar -->

          </div><!-- End blog sidebar -->

        </div>

        <h1>&nbsp;</h1>

        <div class="row">
          <div class="col-lg-12">
          <div class="new-posts-pagination">
            <ul class="justify-content-center">
              <li><a href="#">1</a></li>
              <li class="active"><a href="#">2</a></li>
              <li><a href="#">3</a></li>
            </ul>
          </div>
          </div>
        </div>


  </section><!-- End Blog Section -->

</main><!-- End #main -->
<?php get_footer(); ?>