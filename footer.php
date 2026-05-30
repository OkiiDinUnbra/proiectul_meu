<footer id="contact">
    <div class="container">
        <!-- Contact section removed as per user request -->
    </div>
</footer>

<div id="loginPopup" class="popup-overlay">
    <div class="popup-box modern-popup">
        <span class="close-btn" onclick="closePopup('loginPopup')">&times;</span>
        <h2>Bine ai revenit! 👋</h2>
        <p class="popup-subtitle">Autentifică-te pentru a continua</p>
        
        <form method="POST" action="login.php" class="modern-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group-modern">
                <input type="email" name="email" id="loginEmail" required placeholder=" ">
                <label for="loginEmail">Email-ul tău</label>
            </div>
            
            <div class="form-group-modern password-group">
                <input type="password" name="parola" id="loginParola" required placeholder=" ">
                <label for="loginParola">Parola</label>
                <span class="toggle-password" onclick="togglePassword('loginParola', this)">👁️</span>
            </div>
            
            <div class="form-options">
                <a href="#" class="forgot-link" onclick="showToast('Resetarea parolei nu este disponibilă momentan.', 'info'); return false;">
                    Ai uitat parola?
                </a>
            </div>
            
            <button type="submit" class="btn-submit-modern">Autentificare</button>
        </form>
        
        <p class="popup-footer-text">Nu ai cont? <a href="#" onclick="closePopup('loginPopup'); setTimeout(() => openPopup('registerPopup'), 300); return false;">Înregistrează-te aici</a></p>
    </div>
</div>

<div id="registerPopup" class="popup-overlay">
     <div class="popup-box modern-popup" style="width: 380px;">
        <span class="close-btn" onclick="closePopup('registerPopup')">&times;</span>
        <h2>Creare Cont Nou 🚀</h2>
        <p class="popup-subtitle">Alătură-te comunității noastre</p>
        
        <form method="POST" action="register.php" class="modern-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group-modern">
                <input type="text" name="nume" id="regNume" required placeholder=" ">
                <label for="regNume">Nume complet</label>
            </div>
            
            <div class="form-group-modern">
                <input type="email" name="email" id="regEmail" required placeholder=" ">
                <label for="regEmail">Adresă de email</label>
            </div>
            
            <div class="form-group-modern password-group">
                <input type="password" name="parola" id="regParola" required placeholder=" ">
                <label for="regParola">Parola (min. 8 caract., o majusculă, o cifră)</label>
                <span class="toggle-password" onclick="togglePassword('regParola', this)">👁️</span>
            </div>
            
            <div class="form-group-modern password-group">
                <input type="password" name="confirmare" id="regConfirmare" required placeholder=" ">
                <label for="regConfirmare">Confirmare parolă</label>
                <span class="toggle-password" onclick="togglePassword('regConfirmare', this)">👁️</span>
            </div>

            <div class="form-group-modern">
                <input type="text" name="telefon" id="regTelefon" required placeholder=" ">
                <label for="regTelefon">Număr de telefon (ex: 0722123456)</label>
            </div>

            <div class="checkbox-modern">
                <input type="checkbox" id="newsletter" name="newsletter" value="1">
                <label for="newsletter">Doresc să primesc noutăți pe email</label>
            </div>

            <button type="submit" class="btn-submit-modern">Creează Contul</button>
        </form>
        
        <p class="popup-footer-text">Ai deja cont? <a href="#" onclick="closePopup('registerPopup'); setTimeout(() => openPopup('loginPopup'), 300); return false;">Autentifică-te</a></p>
    </div>
</div>

<div id="contactPopup" class="popup-overlay">
    <div class="popup-box" style="width: 400px; padding: 30px;">
        <span class="close-btn" onclick="closePopup('contactPopup')">×</span>
        <h2 style="text-align: center; color: #333; margin-bottom: 10px;">🔗 Rămâi Conectat</h2>
        <p style="text-align: center; color: #666; font-size: 15px; margin-bottom: 25px;">Urmărește-ne pe rețelele sociale pentru noutăți și evenimente!</p>
        
        <ul class="contact-social-list">
            <li>
                <a href="https://www.facebook.com/andrei.filote.50/" target="_blank" class="fb-link">
                    <i class="fab fa-facebook-f"></i>
                    <span>Facebook Oficial</span>
                </a>
            </li>
            <li>
                <a href="https://www.instagram.com/fmandrei/" target="_blank" class="insta-link">
                    <i class="fab fa-instagram"></i>
                    <span>Instagram @fmandrei</span>
                </a>
            </li>
            <li>
                <a href="https://x.com/MAFilot" target="_blank" class="x-link">
                    <i class="fab fa-x-twitter"></i>
                    <span>Urmărește-ne pe X</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<div id="eventDetailsPopup" class="popup-overlay">
    <div class="popup-box" style="width: 450px; text-align: left;">
        <span class="close-btn" onclick="closePopup('eventDetailsPopup')">×</span>
        <h2 id="modalEventTitle" style="margin-bottom: 15px; text-align: center; color: #333; font-weight: 700;">Titlu Eveniment</h2>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
            <p style="margin-bottom: 8px; font-size: 16px;"><strong>📅 Data:</strong> <span id="modalEventDate" style="color: #0056b3;"></span></p>
            <p style="margin-bottom: 0; font-size: 16px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>📍 Locație:</strong> <span id="modalEventLocation"></span></span>
                <a id="btnMapEvent" href="#" style="background: #eef5ff; color: #0056b3; padding: 6px 12px; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: bold; border: 1px solid #cce5ff; transition: 0.2s;">🗺️ Deschide Harta</a>
            </p>
        </div>
        
       <p style="margin-bottom: 5px; font-size: 16px; font-weight: bold;">📝 Descriere:</p>
        <p id="modalEventDescription" style="font-size: 15px; color: #555; line-height: 1.6;"></p>

        <div style="text-align: center; margin-top: 25px;">
            <a id="btnPaginaEveniment" href="#" style="display: inline-block; background: #0056b3; color: white; padding: 12px 20px; border-radius: 12px; font-weight: bold; text-decoration: none; width: 100%; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,86,179,0.3); box-sizing: border-box;">
                Vezi mai mult
            </a>
        </div>

        <div id="adminEventControls" style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ccc; text-align: right; display: none;">
            <a id="btnEditEvent" href="#" style="background-color: #ffc107; color: #333; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-right: 10px; transition: 0.3s;">✏️ Editează</a>
            <a id="btnDeleteEvent" href="#" style="background-color: #dc3545; color: white; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s;" onclick="return confirm('Ești sigur că vrei să ștergi definitiv acest eveniment?');">🗑️ Șterge</a>
        </div>
    </div>
</div>

<div id="mapPopup" class="popup-overlay">
    <div class="popup-box" style="width: 700px; max-width: 95%; padding: 20px;">
        <span class="close-btn" onclick="closePopup('mapPopup')">×</span>
        <h2 style="margin-bottom: 15px; text-align: center; color: #333; font-weight: 700;">📍 Locație Eveniment</h2>
        
        <div style="width: 100%; height: 450px; border-radius: 12px; overflow: hidden; background: #eee; box-shadow: inset 0 0 10px rgba(0,0,0,0.1);">
            <iframe id="googleMapIframe" width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src=""></iframe>
        </div>
    </div>
</div>

<style>
    #toast-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast-msg {
        min-width: 250px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.4s ease-in-out;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
    }
    .toast-msg.show {
        opacity: 1;
        transform: translateX(0);
    }
    .toast-success { background: #28a745; }
    .toast-error { background: #dc3545; }
    .toast-info { background: #0056b3; }
</style>

<div id="toast-container"></div>

<script>
    // 1. Funcții Pop-up Moderne
    function openPopup(id) {
        const popup = document.getElementById(id);
        popup.style.display = 'flex';
        setTimeout(() => {
            popup.classList.add('active');
        }, 10);
    }
    
    function closePopup(id) {
        const popup = document.getElementById(id);
        popup.classList.remove('active');
        setTimeout(() => {
            popup.style.display = 'none';
        }, 300);
    }

    // 2. Parola Arata/Ascunde
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.innerText = "🙈";
        } else {
            input.type = "password";
            icon.innerText = "👁️";
        }
    }

    // 3. Afisare Pop-up Evenimente + Logica de Harta In-App
    // 3. Afisare Pop-up Evenimente + Logica de Harta In-App
function openEventPopup(id, title, date, location, description) {
    document.getElementById('modalEventTitle').innerText = title;
    document.getElementById('modalEventDate').innerText = date;
    document.getElementById('btnPaginaEveniment').href = 'evenimentextins.php?id=' + id;
    document.getElementById('modalEventLocation').innerText = location || 'Nespecificat';
    document.getElementById('modalEventDescription').innerText = description || 'Nu există o descriere pentru acest eveniment.';
    fetch('track_view.php?id=' + id);

    let mapBtn = document.getElementById('btnMapEvent');
    if (location && location !== 'Nespecificat') {
        mapBtn.style.display = 'inline-block';
        mapBtn.onclick = function(e) {
            e.preventDefault();
            let embedUrl = 'https://maps.google.com/maps?q=' + encodeURIComponent(location + ', Braila, Romania') + '&t=&z=16&ie=UTF8&iwloc=&output=embed';
            document.getElementById('googleMapIframe').src = embedUrl;
            closePopup('eventDetailsPopup');
            setTimeout(() => { openPopup('mapPopup'); }, 300);
        };
    } else {
        mapBtn.style.display = 'none';
    }

    let adminControls = document.getElementById('adminEventControls');
    let btnEdit = document.getElementById('btnEditEvent');
    let btnDelete = document.getElementById('btnDeleteEvent');
    
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        adminControls.style.display = 'block';
        if (btnEdit && btnDelete) {
            btnEdit.href = 'editeaza_eveniment.php?id=' + id;
            btnDelete.href = 'sterge_eveniment.php?id=' + id;
        }
    <?php else: ?>
        adminControls.style.display = 'none';
    <?php endif; ?>
    
    openPopup('eventDetailsPopup');
}

    // 4. Logica Toast Messages
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        
        let icon = type === 'success' ? '✅' : '❌';
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400); 
        }, 4000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('login') === 'eroare_parola') {
            showToast('Parola introdusă este incorectă!', 'error');
            openPopup('loginPopup');
        }
        if (urlParams.get('login') === 'eroare_email') {
            showToast('Nu am găsit niciun cont cu acest email.', 'error');
            openPopup('loginPopup');
        }
        if (urlParams.get('login') === 'succes') {
            showToast('Te-ai autentificat cu succes!', 'success');
        }

        if (urlParams.get('register') === 'succes') {
            showToast('Cont creat cu succes! Acum te poți autentifica.', 'success');
            openPopup('loginPopup');
        }
       if (urlParams.get('register') === 'eroare_parola_scurta') {
    showToast('Parola trebuie să aibă minim 8 caractere.', 'error');
    openPopup('registerPopup');
}
if (urlParams.get('register') === 'eroare_parola_slaba') {
    showToast('Parola trebuie să conțină cel puțin o majusculă și o cifră.', 'error');
    openPopup('registerPopup');
}
        if (urlParams.get('register') === 'eroare_duplicat') {
            showToast('Acest email este deja folosit.', 'error');
            openPopup('registerPopup');
        }
        if (urlParams.get('register') === 'eroare_server') {
          showToast('A apărut o eroare. Încearcă din nou.', 'error');
           openPopup('registerPopup');
            }

            if (urlParams.get('register') === 'eroare_telefon') {
    showToast('Numărul de telefon nu este valid. Exemplu: 0722123456', 'error');
    openPopup('registerPopup');
}

        if(window.history.replaceState && (urlParams.has('login') || urlParams.has('register'))) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    });
</script>
</body>
</html>