<?php get_header(); ?>
<main id="main">

  <!-- ======= Breadcrumbs ======= -->
  <section id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <h2><?php echo get_the_title() ?></h2>
      <ol>
        <li><a href="/">inicio</a></li>
        <li><a href="/arquivos">arquivos</a></li>
        <li><a href="/arquivos/<?php echo get_the_category()[0]->slug ?>"><?php echo get_the_category()[0]->slug ?></a></li>
      </ol>      
    </div>
  </section><!-- End Breadcrumbs -->

  <!-- ======= Blog Single Section ======= -->
  <section id="news" class="new-posts">
    <div class="container" data-aos="fade-up">

      <div class="row">

        <div class="col-lg-9 entries">

          <article class="entry entry-single">

            <div class="entry-img">
              <?php
              $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
              if ($imagem == "") $imagem = SITEPATH . "assets/img/semimagem.png";
              ?>
              <img src="<?php echo $imagem; ?>" alt="" class="img-fluid">
            </div>

            <h2 class="entry-title">
              <?php echo get_the_title(); ?>
            </h2>

            <div class="entry-meta">
              <ul>
                <li class="d-flex align-items-center"><i class="bi bi-person"></i> <?php echo get_the_author_meta('display_name',$post->post_author) ?></li>
                <li class="d-flex align-items-center"><i class="bi bi-clock"></i> <time><?php echo get_the_date('d M Y', $post->ID) ?></time></li>
                <li class="d-flex align-items-center"><i class="bi bi-chat-dots"></i> <?php echo get_comments_number(get_the_ID()) ?> Comentários</li>
              </ul>
            </div>

            <div class="entry-content">
              <?php the_content() ?>
            </div>
          </article><!-- End blog entry -->


          <div class="blog-comments">
            <h4 class="comments-count"><?php echo get_comments_number(get_the_ID()) ?> Comentários</h4>
            <?php
            $x = 1;
            $argsComments = array(
              'post_id' => get_the_ID(),
              'status' => 'approve'
            );
            $comments = get_comments($argsComments);
            foreach ($comments as $comment) {
              echo '<div id="comment-' . $x . '" class="comment"><div class="d-flex"><div><h5>' . get_comment_author() . '</h5><time>' . get_comment_date() . '</time><p>' . get_comment_text() . '</p></div></div></div>';
              $x++;
            }
            ?>
            <div class="reply-form">
              <?php
              $comments_args = array(
                'fields' => array(
                  'author' => '<p><input class="form-control" id="author" name="author" required placeholder="Nome *"></input></p>',
                  'email' => '<p><input class="form-control" id="email" name="email" required placeholder="E-Mail *"></input></p>',
                  'cookies' => '',
                ),
                'label_submit' => __('Enviar'),
                'title_reply' => __('Deixe um comentário'),
                'comment_field' => '<p"><textarea class="form-control" id="comment" name="comment" required placeholder="Comentário *"></textarea></p>',
                'comment_notes_after' => '',
                'id_submit' => __('comment-submit'),
              );
              comment_form($comments_args);
              ?>
            </div>

          </div><!-- End blog comments -->
        </div><!-- End blog entries list -->

        <div class="col-lg-3">
          <div class="sidebar">
            <h3 class="sidebar-title">Busca</h3>

            <div class="sidebar-item search-form search">
              <form action="/" method="get">
                <input type="text" placeholder="Pesquisar" required name="s" id="search" value="<?php the_search_query(); ?>" />
                <button type="submit"><i class="bi bi-search"></i></button>
              </form>
            </div><!-- End sidebar search formn-->

            <div class="sidebar-item categories">
              <ul>
                <li><a class="getstarted empty" href="/arquivos">Todos os Arquivos</a></li>
                <li><a class="getstarted" href="/arquivos/<?php echo get_the_category()[0]->slug ?>"><?php echo get_the_category()[0]->name ?> <span>(<?php echo get_the_category()[0]->count ?>)</span></a></li>
              </ul>
            </div><!-- End sidebar categories-->

            <h3 class="sidebar-title">Recentes</h3>
            <div class="sidebar-item recent-posts">

              <?php
              $x = 1;
              $args = array(
                'post_type' => 'post',
                'category_name' => get_the_category()[0]->slug,
                'order' => 'DESC',
                'posts_per_page' => 10
              );

              $loop = new WP_Query($args);

              foreach ($loop->posts as $post) {

                $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
                if ($imagem == "") $imagem = SITEPATH . "assets/img/semimagem.png";

                echo '<div class="post-item clearfix">
              <a href="' . get_the_permalink() . '"><img src="' . $imagem . '" alt=""></a>
              <h4><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h4>
              <time>' . get_the_date('d M Y', $post->ID) . '</time>
              </div>';
                $x++;
              } ?>

            </div><!-- End sidebar recent posts-->

          </div><!-- End sidebar -->

        </div><!-- End blog sidebar -->

      </div>

    </div>
  </section><!-- End Blog Single Section -->

</main><!-- End #main -->
<?php get_footer(); ?>