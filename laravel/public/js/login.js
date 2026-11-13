'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('toggle-password');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');
    const form = document.getElementById('login-form');
    const submitButton = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');

    if (password && togglePassword && eyeOpen && eyeClosed) {
        togglePassword.addEventListener('click', function () {
            const showPassword = password.type === 'password';
            password.type = showPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-pressed', String(showPassword));
            togglePassword.setAttribute('aria-label', showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            eyeOpen.classList.toggle('hidden', showPassword);
            eyeClosed.classList.toggle('hidden', !showPassword);
        });
    }

    if (form && submitButton && submitText) {
        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
            submitText.textContent = 'Verificando…';
        });
    }

    const card = document.getElementById('login-card');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (card && !reduceMotion && window.matchMedia('(min-width: 1024px)').matches) {
        card.addEventListener('pointermove', function (event) {
            const bounds = card.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            card.style.transform = 'perspective(1000px) rotateX(' + (-y * 2.2) + 'deg) rotateY(' + (x * 2.2) + 'deg)';
        });
        card.addEventListener('pointerleave', function () {
            card.style.transform = '';
        });
    }

    const scene = document.querySelector('.store-scene');
    if (scene && !reduceMotion && window.matchMedia('(pointer: fine)').matches) {
        const showcase = document.querySelector('.showcase');
        showcase.addEventListener('pointermove', function (event) {
            const bounds = showcase.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;
            scene.style.transform = 'perspective(1000px) rotateY(' + (x * 3) + 'deg) rotateX(' + (-y * 2) + 'deg)';
        });
        showcase.addEventListener('pointerleave', function () {
            scene.style.transform = '';
        });
    }
});
