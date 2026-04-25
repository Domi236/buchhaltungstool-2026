<?php
/**
 * set autor box for blog single pages
 */

add_action('ava_before_footer', function () {
    ob_start();
    if (is_singular('post')) { ?>
        <div class="avia-section main_color avia-section-default">
            <div class="container">
                <?php
                if( have_rows('autoren_autoren', 'option') ):
                    foreach(get_field('autoren_autoren', 'option') as $autor ) {

                        if($autor['autoren_name'] === get_field('blog_choose_autor', get_the_ID())) {

                            $srcId = $autor['autoren_img'];
                            $img = wp_get_attachment_image_url($srcId, 'full');
                            $alt = get_post_meta( $srcId, '_wp_attachment_image_alt', true );
                            $img_title = get_the_title( $srcId);
                            ?>
                            <div class="pc_autorbox">
                                <div class="pc_autorbox__content">
                                    <div class="pc_autorbox__content__img_container">
                                        <img class="pc_autorbox__content__img_container__img" src="<?= $img ?>" alt="<?= $alt ?>" title="<?= $img_title ?>" />
                                    </div>
                                    <div class="pc_autorbox__content__text_container">
                                        <h4 class="pc_autorbox__content__text_container__headline">Zum Autor - <?= $autor['autoren_name']; ?></h4>
                                        <div class="pc_autorbox__content__text_container__text"><?php echo $autor['autoren_text']; ?></div>
                                    </div>
                                </div>
                                <div class="pc_autorbox__btn avia-button-wrap avia-button-left avia-builder-el-last back-button">
                                    <a href="/blog" class="avia-button avia-size-medium avia-position-left avia-color-theme-color">
                                        <span class="avia_iconbox_title">Zurück zur Übersicht</span>
                                    </a>
                                </div>
                            </div>
                        <?php }
                    }
                endif; ?>
            </div>
        </div>

        <?php
    }
    echo ob_get_clean();
});
