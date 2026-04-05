<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    
</head>
<body <?php body_class(); ?>>
    <header class="gs-site-header">
        <div class="gs-nav-wrap">
            <h1 class="gs-site-title">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
            </h1>

            <button
                class="gs-menu-toggle"
                type="button"
                aria-controls="gs-primary-menu"
                aria-expanded="false"
            >
                <span aria-hidden="true">&#9776;</span>
                <span>Menu</span>
            </button>

            <nav class="gs-primary-nav" id="gs-primary-menu">
                <?php
                if (has_nav_menu('main-menu')) {
                    wp_nav_menu([
                        'theme_location' => 'main-menu',
                        'container'      => false,
                        'menu_class'     => 'gs-menu-list',
                        'fallback_cb'    => false,
                    ]);
                } else {
                    echo '<ul class="gs-menu-list">';
                    wp_list_pages([
                        'title_li' => '',
                    ]);
                    echo '</ul>';
                }
                ?>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('.gs-menu-toggle');
            var menu = document.querySelector('.gs-primary-nav');

            if (!toggle || !menu) {
                return;
            }

            toggle.addEventListener('click', function () {
                var isOpen = menu.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    </script>

    <style>       
        
    </style>