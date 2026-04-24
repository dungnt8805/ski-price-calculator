<?php
/**
 * Template Name: Ski Engine Inquiry
 * Description: Dedicated inquiry form page for requesting ski package quotes
 */

get_header(); ?>

<?php
$inq_overline    = get_option('spcu_inquiry_page_overline',    'Contact Us');
$inq_heading     = get_option('spcu_inquiry_page_heading',     'Get Your Custom Quote');
$inq_subheading  = get_option('spcu_inquiry_page_subheading', "Tell us about your trip and we'll send you a detailed, final quote within 24 hours.");
?>
<main role="main" class="spcu-inquiry-page" style="max-width:900px; margin:0 auto; padding:2rem 1rem;">
    <section style="margin-bottom:4rem; text-align:center;">
        <?php if ($inq_overline): ?>
        <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:#3b82f6; margin-bottom:0.5rem;"><?php echo esc_html($inq_overline); ?></div>
        <?php endif; ?>
        <h1 style="font-family:'Playfair Display',serif; font-size:2.5rem; color:#0f1b2d; margin-bottom:0.8rem;"><?php echo esc_html($inq_heading); ?></h1>
        <?php if ($inq_subheading): ?>
        <p style="font-size:1.05rem; color:#64748b; max-width:600px; margin:0 auto;"><?php echo esc_html($inq_subheading); ?></p>
        <?php endif; ?>
    </section>

    <?= do_shortcode('[spcu_inquiry_form]') ?>
</main>

<?php get_footer();
