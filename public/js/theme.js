// theme.js — maneja alternancia de tema claro/oscuro y persistencia
(function(){
  function getStoredTheme(){
    try { return localStorage.getItem('theme'); } catch(e){ return null; }
  }

  function setTheme(theme){
    if(!theme || theme === 'light') { document.documentElement.removeAttribute('data-theme'); }
    else { document.documentElement.setAttribute('data-theme', theme); }
    try { if(theme && theme !== 'light') localStorage.setItem('theme', theme); else localStorage.removeItem('theme'); } catch(e){}
  }

  function toggleTheme(){
    var current = document.documentElement.getAttribute('data-theme') || null;
    var next = (current === 'dark') ? null : 'dark';
    setTheme(next);
    updateToggleButton();
  }

  function updateToggleButton(){
    var btn = document.getElementById('themeToggle');
    if(!btn) return;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    btn.textContent = isDark ? '☀️' : '🌙';
    btn.title = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Inicializa botón
    updateToggleButton();
    var btn = document.getElementById('themeToggle');
    if(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); toggleTheme(); }); }
  });

  // Expose for other scripts if needed
  window.appTheme = { getStoredTheme: getStoredTheme, setTheme: setTheme, toggleTheme: toggleTheme };
})();
