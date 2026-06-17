<?php
/**
 * index.php — fallback template for all non-front-page requests.
 * WordPress requires this file to exist.
 */
get_header();
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20" role="main" id="main-content">

  <?php if ( have_posts() ) : ?>

    <?php while ( have_posts() ) : the_post(); ?>

      <article <?php post_class( 'mb-12 pb-12 border-b border-gray-100 last:border-0' ); ?> id="post-<?php the_ID(); ?>">

        <header class="mb-6">
          <?php if ( ! is_singular() ) : ?>
            <h2 class="font-heading text-brand-navy uppercase text-2xl lg:text-3xl tracking-wide mb-2">
              <a href="<?php the_permalink(); ?>" class="hover:text-brand-sky transition-colors no-underline">
                <?php the_title(); ?>
              </a>
            </h2>
          <?php else : ?>
            <h1 class="font-heading text-brand-navy uppercase text-3xl lg:text-4xl tracking-wide mb-2">
              <?php the_title(); ?>
            </h1>
          <?php endif; ?>
          <p class="font-body text-sm text-gray-400">
            <?php echo esc_html( get_the_date() ); ?>
          </p>
        </header>

        <div class="font-body text-gray-700 leading-relaxed prose max-w-none">
          <?php
          if ( is_singular() ) {
              the_content();
          } else {
              the_excerpt();
          }
          ?>
        </div>

        <?php if ( ! is_singular() ) : ?>
          <footer class="mt-6">
            <a href="<?php the_permalink(); ?>" class="btn-outline-navy text-sm">
              Read more
            </a>
          </footer>
        <?php endif; ?>

      </article>

    <?php endwhile; ?>

    <?php the_posts_pagination( [
        'prev_text' => '&larr; Older',
        'next_text' => 'Newer &rarr;',
        'class'     => 'font-body text-sm text-brand-navy',
    ] ); ?>

  <?php else : ?>

    <div class="text-center py-20">
      <h1 class="section-heading text-4xl mb-4">Nothing here yet.</h1>
      <p class="font-body text-gray-600 mb-8">Looks like this page is still being set up. Head back home.</p>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">Back to Home</a>
    </div>

  <?php endif; ?>

</main>

<?php get_footer(); ?>
