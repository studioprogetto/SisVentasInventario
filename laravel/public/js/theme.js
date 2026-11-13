(() => {
  'use strict';
  let preference;
  try { preference = localStorage.getItem('studio-theme'); } catch (_) {}
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  const apply = theme => {
    document.documentElement.dataset.theme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      const dark = theme === 'dark';
      button.setAttribute('aria-label', dark ? 'Activar modo claro' : 'Activar modo oscuro');
      button.setAttribute('aria-pressed', String(dark));
      button.title = dark ? 'Activar modo claro' : 'Activar modo oscuro';
    });
  };
  apply(preference === 'dark' || preference === 'light' ? preference : (media.matches ? 'dark' : 'light'));
  document.addEventListener('DOMContentLoaded', () => {
    apply(document.documentElement.dataset.theme);
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
      preference = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      apply(preference);
      try { localStorage.setItem('studio-theme', preference); } catch (_) {}
    }));
    const heading = document.querySelector('main h1');
    const label = document.querySelector('[data-page-title]');
    if (heading && label) label.textContent = heading.textContent;
    document.querySelector('[data-close-nav]')?.addEventListener('click', () => {
      const toggle = document.getElementById('nav-toggle');
      if (toggle?.getAttribute('aria-expanded') === 'true') toggle.click();
    });
  });
  media.addEventListener('change', event => { if (!preference) apply(event.matches ? 'dark' : 'light'); });
})();
