<?php
// Verificăm dacă suntem pe prima pagină pentru a ascunde zona de copyright
$is_home = basename($_SERVER['PHP_SELF']) === 'acasa.php';
?>

<?php if (!$is_home): ?>
<footer style="background: var(--bg-section); color: var(--text-light); text-align: center; padding: 40px 20px; border-top: 1px solid var(--border-color); font-size: 14px; margin-top: auto;">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        <p>&copy; <?= date('Y') ?> Descoperă Brăila. Proiect pentru Licență.</p>
        <p style="margin-top: 10px; font-size: 13px;">Acesta este un proiect educațional dezvoltat cu pasiune.</p>
    </div>
</footer>
<?php endif; ?>

<!-- ================= POPUP AUTENTIFICARE ================= -->
<div class="popup-overlay" id="loginPopup">
    <div class="popup-box modern-popup">
        <span class="close-btn" onclick="closePopup('loginPopup')">&times;</span>
        <h2><?= t('nav_login') ?></h2>
        <p class="popup-subtitle">Bine ai revenit!</p>

        <form method="POST" action="login.php" class="modern-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group-modern">
                <input type="email" name="email" id="login_email" placeholder=" " required>
                <label for="login_email">✉️ <?= t('register_email') ?></label>
            </div>
            
            <div class="form-group-modern password-group">
                <input type="password" name="parola" id="login_parola" placeholder=" " required>
                <label for="login_parola">🔒 Parola</label>
                <span class="toggle-password" onclick="togglePasswordVisibility('login_parola')">👁️</span>
            </div>

            <button type="submit" class="btn-submit-modern">Intră în cont</button>
        </form>

        <div class="popup-footer-text">
            Nu ai cont? <a href="#" onclick="closePopup('loginPopup'); openPopup('registerPopup');">Creează unul aici</a>
        </div>
    </div>
</div>

<!-- ================= POPUP ÎNREGISTRARE ================= -->
<div class="popup-overlay" id="registerPopup">
    <div class="popup-box modern-popup">
        <span class="close-btn" onclick="closePopup('registerPopup')">&times;</span>
        <h2><?= t('nav_register') ?></h2>
        <p class="popup-subtitle">Alătură-te comunității Brăila!</p>

        <form method="POST" action="register.php" class="modern-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group-modern">
                <input type="text" name="nume" id="reg_nume" placeholder=" " required>
                <label for="reg_nume">👤 <?= t('register_name') ?></label>
            </div>

            <div class="form-group-modern">
                <input type="email" name="email" id="reg_email" placeholder=" " required>
                <label for="reg_email">✉️ <?= t('register_email') ?></label>
            </div>

            <div class="form-group-modern password-group">
                <input type="password" name="parola" id="reg_parola" placeholder=" " required>
                <label for="reg_parola">🔒 Parola</label>
                <span class="toggle-password" onclick="togglePasswordVisibility('reg_parola')">👁️</span>
            </div>

            <label class="checkbox-modern">
                <input type="checkbox" name="newsletter">
                <span>Vreau să primesc oferte pe email.</span>
            </label>

            <button type="submit" class="btn-submit-modern">Creează Contul</button>
        </form>

        <div class="popup-footer-text">
            Ai deja cont? <a href="#" onclick="closePopup('registerPopup'); openPopup('loginPopup');">Autentifică-te</a>
        </div>
    </div>
</div>

<!-- ================= POPUP CONTACT ================= -->
<div class="popup-overlay" id="contactPopup">
    <div class="popup-box modern-popup">
        <span class="close-btn" onclick="closePopup('contactPopup')">&times;</span>
        <h2><?= t('nav_contact') ?></h2>
        <p class="popup-subtitle" style="margin-bottom: 25px;">Suntem aici! Scrie-ne pe platforma ta preferată.</p>
        
        <ul class="contact-social-list">
            <li><a href="mailto:contact@braila.ro" class="x-link"><i>✉️</i> contact@braila.ro</a></li>
            <li><a href="https://facebook.com/PrimariaBraila" target="_blank" class="fb-link"><i>📘</i> Primăria Brăila</a></li>
            <li><a href="https://instagram.com/descopera.braila" target="_blank" class="insta-link"><i>📸</i> @descopera.braila</a></li>
        </ul>
    </div>
</div>

<!-- ================= POPUP EVENIMENT (DIN CALENDAR) ================= -->
<div class="popup-overlay" id="eventPopup">
    <div class="popup-box" style="max-width: 500px;">
        <span class="close-btn" onclick="closePopup('eventPopup')">&times;</span>
        <h2 id="popupEventTitle" style="color: var(--accent-primary); margin-bottom: 10px;">Titlu</h2>
        
        <div style="background: var(--bg-section); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin-bottom: 8px;"><strong>📅 Dată:</strong> <span id="popupEventDate"></span></p>
            <p><strong>📍 Locație:</strong> <span id="popupEventLocation"></span></p>
        </div>
        
        <p id="popupEventDesc" style="color: var(--text-light); margin-bottom: 20px; line-height: 1.6;"></p>
        
        <div id="adminEventControls" style="display: none; justify-content: center; gap: 15px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <!-- Link-urile de editare/stergere vor fi injectate aici prin JS -->
        </div>

        <div style="text-align: center; margin-top: 15px;">
            <a href="#" id="btnVeziMaiMulte" class="btn" style="display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; background: var(--link-color); color: #fff;">ℹ️ Vezi detaliile complete</a>
        </div>
    </div>
</div>

<!-- ================= SCRIPTURI PRINCIPALE ================= -->
<script>
    function openPopup(id) {
        var popup = document.getElementById(id);
        if (popup) {
            popup.style.display = 'flex';
            setTimeout(function() {
                popup.classList.add('active');
            }, 10);
        }
    }

    function closePopup(id) {
        var popup = document.getElementById(id);
        if (popup) {
            popup.classList.remove('active');
            setTimeout(function() {
                popup.style.display = 'none';
            }, 300);
        }
    }

    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }

    window.onclick = function(event) {
        const overlays = document.getElementsByClassName('popup-overlay');
        for (let i = 0; i < overlays.length; i++) {
            if (event.target === overlays[i]) {
                closePopup(overlays[i].id);
            }
        }
    }

    // Funcția care afișează pop-up-ul cu detaliile evenimentului cerut de calendar.php
    function openEventPopup(id, title, date, location, desc) {
        document.getElementById('popupEventTitle').innerText = title;
        document.getElementById('popupEventDate').innerText = date;
        document.getElementById('popupEventLocation').innerText = location;
        document.getElementById('popupEventDesc').innerText = desc;
        
        // Setează link-ul pentru pagina detaliată
        if(id) {
            document.getElementById('btnVeziMaiMulte').href = 'evenimentextins.php?id=' + id;
        } else {
            document.getElementById('btnVeziMaiMulte').href = '#';
        }

        // Dacă utilizatorul e admin, injectăm butoanele de edit/delete
        const adminControls = document.getElementById('adminEventControls');
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            if(id) {
                adminControls.style.display = 'flex';
                adminControls.innerHTML = `
                    <a href="editeaza_eveniment.php?id=${id}" class="btn" style="background: var(--accent-edit); color: #111; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none;">✏️ Editează</a>
                    <a href="sterge_eveniment.php?id=${id}" class="btn" style="background: var(--accent-delete); color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none;" onclick="return confirm('Ești sigur că vrei să ștergi acest eveniment?');">🗑️ Șterge</a>
                `;
            } else {
                adminControls.style.display = 'none';
            }
        <?php endif; ?>

        openPopup('eventPopup');
    }
</script>

</body>
</html>