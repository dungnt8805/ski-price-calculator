<?php
/**
 * Template Name: Ski Engine Inquiry
 * Description: Dedicated inquiry form page for requesting ski package quotes
 */

get_header(); ?>

<main role="main" class="spcu-inquiry-page" style="max-width:900px; margin:0 auto; padding:2rem 1rem;">
    <section style="margin-bottom:4rem; text-align:center;">
        <h1 style="font-family:'Playfair Display',serif; font-size:2.5rem; color:#0f1b2d; margin-bottom:0.5rem;">Request a Quote</h1>
        <p style="font-size:1.05rem; color:#64748b; max-width:600px; margin:0 auto;">
            Fill out the form below with your preferred dates and details. We'll get back to you within 24 hours with a personalized package quote.
        </p>
    </section>

    <?= do_shortcode('[spcu_inquiry_form]') ?>
</main>

<?php get_footer();
