<?php 
$page_title = "Blog | Descoperă Brăila";
include 'header.php'; 
?>

<section class="blog" style="margin-top: 120px;">
    <div class="container">
        <h2>Articole & Știri Locale</h2>
        <div class="blog-posts">
            <div class="post">
                <h3>5 lucruri pe care nu le știai despre Brăila</h3>
                <p>Istorie, gastronomie, tradiții – lucruri care te vor surprinde.</p>
                <a href="#" onclick="showToast('Articolul va fi disponibil în curând!', 'info'); return false;">Citește mai mult</a>
            </div>
            <div class="post">
                <h3>Trasee de o zi prin județ</h3>
                <p>Recomandări de excursii și locuri de descoperit în natură.</p>
                <a href="#" onclick="showToast('Articolul va fi disponibil în curând!', 'info'); return false;">Citește mai mult</a>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>