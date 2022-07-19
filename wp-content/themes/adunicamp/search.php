<?php get_header(); ?>
<main id="main">

    <!-- ======= Breadcrumbs ======= -->
    <section id="breadcrumbs" class="breadcrumbs">
        <div class="container">
            <h2>Busca</h2>
            <ol>
                <li><a href="/">inicio</a></li>
                <li>busca</li>
            </ol>            
        </div>
    </section><!-- End Breadcrumbs -->

    <section id="search" class="search">
        <div class="container">

            <div class="row">
                <div class="col-lg-12">

                    <?php
                    $s = get_search_query();
                    $args = array(
                        's' => $s,
                        'post_type' => 'post',
                        'posts_per_page' => 100
                    );
                    $x = 1;

                    $loop = new WP_Query($args);

                    if ($loop->have_posts()) {
                        foreach ($loop->posts as $post) {

                            $imagem = get_the_post_thumbnail_url(get_the_ID(), 'full');
                            if ($imagem == "") $imagem = SITEPATH . "assets/img/semimagem.png";

                    ?>

                            <div class="row result">
                                <div class="col-lg-2">
                                    <a href="<?php echo get_the_permalink() ?>"><img src="<?php echo $imagem ?>" alt="" class="img-fluid"></a>
                                </div>
                                <div class="col-lg-10">
                                    <h4><?php echo $x ?> &bullet; <a href="<?php echo get_the_permalink() ?>"><?php echo get_the_title() ?></a></h4>
                                    <time><?php echo get_the_date('d M Y', $post->ID) ?></time>
                                    <p><?php echo get_excerpt(200) ?></p>
                                </div>
                            </div>

                        <?php
                            $x++;
                        }
                    } else { ?>

                        <section class="inner-page">
                            <div class="container">
                                <p>Nada encontrado !</p>
                            </div>
                        </section>
                    <?php } ?>

                </div>
            </div>

        </div>
    </section>

</main><!-- End #main -->
<?php get_footer(); ?>