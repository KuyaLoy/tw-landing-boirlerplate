<?php
require_once(__DIR__ . '/config/config.php');
require_once(__DIR__ . '/config/functions.php');

// Tells partials/footer.php to skip the reCAPTCHA loader + 60s token-refresh
// interval. The form is already submitted; loading grecaptcha here just wastes
// bandwidth and runs a setInterval that never gets used.
$isThankYou = true;

include(__DIR__ . '/partials/header.php');
?>
<style>
    .thankyou-message * {
        line-height: normal;
    }

    .thankyou-message {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }

    .thankyou-message.show {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .thankyou-message .content {
        background: #fff;
        border-top: 6px solid var(--brand-accent);
        padding: 40px 30px;
        border-radius: 20px;
        text-align: center;
        max-width: 420px;
        width: 100%;
        position: relative;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    }

    .thankyou-message .popup-check {
        font-size: 3rem;
        color: var(--brand-accent);
        margin-bottom: 10px;
    }

    .thankyou-message h1 {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 10px;
        color: #1a1a1a;
    }

    .thankyou-message p {
        font-size: 1rem;
        color: #555;
        line-height: 1.5;
    }

    .thankyou-message .close-btn {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 1.4rem;
        border: none;
        background: none;
        color: #888;
        cursor: pointer;
        transition: color 0.2s;
    }

    .thankyou-message .close-btn:hover {
        color: #000;
    }

    body.modal-open {
        overflow: hidden;
    }
</style>




<div class="thankyou-message" id="thankyouPopup">
    <div class="content">
        <button class="close-btn" aria-label="Close popup">&times;</button>
        <div class="popup-check">
            <span class="icon">✅</span>
        </div>
        <h1>Message Sent!</h1>
        <p>Thanks for reaching out. Our team will get in touch shortly.</p>
    </div>
</div>


<script>
    const body = document.body;
    const popup = document.getElementById('thankyouPopup');
    const closeBtn = popup.querySelector('.close-btn');

    window.addEventListener('load', function() {
        setTimeout(() => {
            popup.classList.add('show');
            body.style.overflow = 'hidden'; // prevent scrolling
        }, 1500);
    });

    closeBtn.addEventListener('click', () => {
        popup.classList.remove('show');
        body.style.overflow = '';
    });
</script>






<?php
include(__DIR__ . '/partials/main.php');
include(__DIR__ . '/partials/footer.php');
?>