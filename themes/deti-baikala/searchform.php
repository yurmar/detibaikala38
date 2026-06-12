<?php
/**
 * Форма поиска.
 */
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <div class="form-group" style="display:flex;gap:0.5rem">
    <input type="search" class="form-input" placeholder="<?php esc_attr_e( 'Поиск по сайту…', 'deti-baikala' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
    <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Найти', 'deti-baikala' ); ?></button>
  </div>
</form>
