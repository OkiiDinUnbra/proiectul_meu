<?php
// Ascundem textul cu copyright doar pe pagina acasa.php
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

<div class="popup-overlay" id="loginPopup" style="z-index: 99999 !important;">
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
            Nu ai cont? <a href="#" onclick="closePopup('loginPopup'); openPopup('registerPopup'); return false;">Creează unul aici</a>
        </div>
    </div>
</div>

<div class="popup-overlay" id="registerPopup" style="z-index: 99999 !important;">
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
            Ai deja cont? <a href="#" onclick="closePopup('registerPopup'); openPopup('loginPopup'); return false;">Autentifică-te</a>
        </div>
    </div>
</div>

<div class="popup-overlay" id="contactPopup" style="z-index: 99999 !important;">
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

<div class="popup-overlay" id="eventPopup" style="z-index: 99999 !important;">
    <div class="popup-box" style="max-width: 500px;">
        <span class="close-btn" onclick="closePopup('eventPopup')">&times;</span>
        <h2 id="popupEventTitle" style="color: var(--accent-primary); margin-bottom: 10px;">Titlu</h2>
        
        <div style="background: var(--bg-section); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin-bottom: 8px;"><strong>📅 Dată:</strong> <span id="popupEventDate"></span></p>
            <p><strong>📍 Locație:</strong> <span id="popupEventLocation"></span></p>
        </div>
        
        <p id="popupEventDesc" style="color: var(--text-light); margin-bottom: 20px; line-height: 1.6;"></p>
        
        <div id="adminEventControls" style="display: none; justify-content: center; gap: 15px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
        </div>

        <div style="text-align: center; margin-top: 15px;">
            <a href="#" id="btnVeziMaiMulte" class="btn" style="display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; background: var(--link-color); color: #fff;">ℹ️ Vezi detaliile complete</a>
        </div>
    </div>
</div>

<style>
    /* Stiluri Chatbot */
    #chatbot-toggle-btn {
        position: fixed; bottom: 30px; right: 30px; width: 65px; height: 65px;
        background: var(--link-color, #007bff); color: white; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 35px;
        cursor: pointer; box-shadow: 0 6px 20px rgba(0,0,0,0.3); z-index: 999999 !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease; border: 3px solid rgba(255,255,255,0.2);
    }
    #chatbot-toggle-btn:hover { transform: scale(1.1); box-shadow: 0 8px 25px rgba(0,0,0,0.4); }
    
    #chatbot-window {
        position: fixed; bottom: 110px; right: 30px; width: 380px; max-width: 90vw;
        height: 550px; max-height: 75vh; background: var(--card-bg, #fff); border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25); z-index: 999999 !important;
        display: none; flex-direction: column; overflow: hidden;
        border: 1px solid var(--border-color, #eee);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    #chatbot-header {
        background: var(--link-color, #007bff); color: white; padding: 20px;
        font-weight: bold; display: flex; justify-content: space-between; align-items: center;
        font-size: 16px; border-bottom: 2px solid rgba(0,0,0,0.1);
    }
    #chatbot-close { cursor: pointer; font-size: 24px; line-height: 1; transition: transform 0.2s; }
    #chatbot-close:hover { transform: scale(1.2); }
    
    #chatbot-messages {
        flex: 1; padding: 20px; overflow-y: auto; background: var(--bg-main, #f9f9f9);
        display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;
    }
    
    .chat-bubble { max-width: 85%; padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.5; color: #111; position: relative; animation: popInChat 0.3s ease-out; }
    .chat-bot { background: #e9ecef; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .chat-user { background: var(--link-color, #007bff); color: white; align-self: flex-end; border-bottom-right-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    .chat-options-container { display: flex; flex-direction: column; gap: 8px; margin-top: 5px; animation: popInChat 0.4s ease-out; }
    .chat-option-btn {
        background: var(--card-bg, #fff); border: 2px solid var(--link-color, #007bff); color: var(--link-color, #007bff);
        padding: 10px 15px; border-radius: 20px; cursor: pointer; font-size: 14px; text-align: left;
        font-weight: 600; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .chat-option-btn:hover { background: var(--link-color, #007bff); color: white; transform: translateY(-2px); }
    
    .typing-indicator { display: none; font-style: italic; color: var(--text-light, #888); font-size: 13px; align-self: flex-start; margin-top: -5px; padding-left: 5px; }
    
    @keyframes popInChat { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
</style>

<div id="chatbot-toggle-btn" onclick="toggleChatbot()">🤖</div>

<div id="chatbot-window">
    <div id="chatbot-header">
        <span>🤖 Ghid AI Brăila</span>
        <span id="chatbot-close" onclick="toggleChatbot()">&times;</span>
    </div>
    <div id="chatbot-messages">
        </div>
    <div class="typing-indicator" id="chatbot-typing">Asistentul tastează...</div>
</div>
<script>
    // --- FUNCTII PENTRU POP-UP-URI EXISTENTE ---
    function openPopup(id) {
        if(event) event.preventDefault();
        var popup = document.getElementById(id);
        if (popup) {
            popup.style.display = 'flex';
            setTimeout(function() { popup.classList.add('active'); }, 10);
        }
    }

    function closePopup(id) {
        if(event) event.preventDefault();
        var popup = document.getElementById(id);
        if (popup) {
            popup.classList.remove('active');
            setTimeout(function() { popup.style.display = 'none'; }, 300);
        }
    }

    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === "password") { input.type = "text"; } 
        else { input.type = "password"; }
    }

    window.onclick = function(event) {
        const overlays = document.getElementsByClassName('popup-overlay');
        for (let i = 0; i < overlays.length; i++) {
            if (event.target === overlays[i]) {
                closePopup(overlays[i].id);
            }
        }
    }

    function openEventPopup(id, title, date, location, desc) {
        if(event) event.preventDefault();
        document.getElementById('popupEventTitle').innerText = title;
        document.getElementById('popupEventDate').innerText = date;
        document.getElementById('popupEventLocation').innerText = location;
        document.getElementById('popupEventDesc').innerText = desc;
        
        if(id) { document.getElementById('btnVeziMaiMulte').href = 'evenimentextins.php?id=' + id; } 
        else { document.getElementById('btnVeziMaiMulte').href = '#'; }

        const adminControls = document.getElementById('adminEventControls');
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            if(id) {
                adminControls.style.display = 'flex';
                adminControls.innerHTML = `
                    <a href="editeaza_eveniment.php?id=${id}" class="btn" style="background: var(--accent-edit); color: #111; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none;">✏️ Editează</a>
                    <a href="sterge_eveniment.php?id=${id}" class="btn" style="background: var(--accent-delete); color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none;" onclick="return confirm('Ești sigur că vrei să ștergi acest eveniment?');">🗑️ Șterge</a>
                `;
            } else { adminControls.style.display = 'none'; }
        <?php endif; ?>
        openPopup('eventPopup');
    }

    // --- FUNCTII PENTRU CHATBOT AI ---
    let chatInitialized = false;

    function toggleChatbot() {
        const chatWindow = document.getElementById('chatbot-window');
        if (chatWindow.style.display === 'flex') {
            chatWindow.style.display = 'none';
        } else {
            chatWindow.style.display = 'flex';
            if (!chatInitialized) {
                // Dacă e prima dată când deschide chatul, pornim scenariul "start"
                trimiteMesajChat('start', null, null);
                chatInitialized = true;
            }
        }
    }

    function trimiteMesajChat(action, textUser, linkExtern) {
        // Dacă opțiunea selectată este un link extern, direcționăm utilizatorul
        if (linkExtern) {
            window.location.href = linkExtern;
            return;
        }

        const containerMesaje = document.getElementById('chatbot-messages');
        const indicatorTyping = document.getElementById('chatbot-typing');
        
        // 1. Afișăm pe ecran ce a ales utilizatorul (dacă nu e start)
        if (textUser) {
            const divUser = document.createElement('div');
            divUser.className = 'chat-bubble chat-user';
            divUser.innerText = textUser;
            containerMesaje.appendChild(divUser);
            
            // Ștergem butoanele vechi ca să facem curat pe ecran
            const butoaneVechi = containerMesaje.querySelectorAll('.chat-options-container');
            butoaneVechi.forEach(b => b.remove());
        }

        // Derulăm în jos
        containerMesaje.scrollTop = containerMesaje.scrollHeight;

        // 2. Afișăm că bot-ul gândește
        indicatorTyping.style.display = 'block';

        // 3. Trimitem comanda către PHP-ul din spate
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action })
        })
        .then(response => response.json())
        .then(data => {
            // Ascundem "Bot-ul scrie..."
            indicatorTyping.style.display = 'none';

            // 4. Afișăm răspunsul bot-ului
            const divBot = document.createElement('div');
            divBot.className = 'chat-bubble chat-bot';
            // Păstrăm bold-ul (**) din PHP înlocuindu-l cu tag-ul <strong> (opțional)
            let mesajFormatat = data.message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            divBot.innerHTML = mesajFormatat;
            containerMesaje.appendChild(divBot);

            // 5. Creăm butoanele cu următoarele decizii posibile
            if (data.options && data.options.length > 0) {
                const divOptiuni = document.createElement('div');
                divOptiuni.className = 'chat-options-container';
                
                data.options.forEach(opt => {
                    const btn = document.createElement('button');
                    btn.className = 'chat-option-btn';
                    btn.innerText = opt.text;
                    btn.onclick = () => trimiteMesajChat(opt.action, opt.text, opt.link);
                    divOptiuni.appendChild(btn);
                });
                
                containerMesaje.appendChild(divOptiuni);
            }

            // Derulăm complet în jos
            containerMesaje.scrollTop = containerMesaje.scrollHeight;
        })
        .catch(error => {
            console.error("Eroare Chatbot:", error);
            indicatorTyping.style.display = 'none';
        });
    }
</script>

</body>
</html>