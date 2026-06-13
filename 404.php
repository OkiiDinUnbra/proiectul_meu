<?php 
$page_title = "Pagina nu a fost găsită | Descoperă Brăila";
include 'header.php'; 
?>

<div style="padding: 150px 20px; text-align: center; min-height: 80vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg-main);">
    <h1 style="font-size: 120px; margin: 0; color: var(--link-color); text-shadow: 0 10px 20px rgba(0,123,255,0.2);">404</h1>
    <h2 style="font-size: 32px; color: var(--text-main); margin-top: 10px;">Oops! Te-ai rătăcit prin Brăila?</h2>
    <p style="color: var(--text-light); max-width: 500px; margin: 20px 0; font-size: 16px; line-height: 1.6;">
        Pagina pe care o cauți a fost mutată, ștearsă sau poate nu a existat niciodată. Hai să te întoarcem la lucrurile interesante!
    </p>
    
    <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
        <a href="acasa.php" class="btn-submit-modern" style="text-decoration: none; padding: 12px 30px; width: auto; margin: 0; display: inline-block;">🏠 Înapoi Acasă</a>
        <a href="evenimente.php" class="btn-submit-modern" style="text-decoration: none; padding: 12px 30px; width: auto; margin: 0; background: transparent; border: 2px solid var(--link-color); color: var(--link-color); display: inline-block;">🎫 Vezi Evenimente</a>
    </div>
</div>

<?php include 'footer.php'; ?>