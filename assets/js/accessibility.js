/* Preferências de aparência, sem dados pessoais e sem transmissão ao servidor. */
(() => {
  'use strict';
  const key = 'fluxon.aparencia.v1';
  let settings = { tema: 'claro', fonte: 'padrao' };
  try {
    const stored = JSON.parse(localStorage.getItem(key) || '{}');
    if (stored && ['claro', 'escuro'].includes(stored.tema))
      settings.tema = stored.tema;
    if (stored && ['padrao', 'grande', 'maior'].includes(stored.fonte))
      settings.fonte = stored.fonte;
  } catch {
    /* Armazenamento indisponível: a página mantém o padrão. */
  }
  const apply = () => {
    document.documentElement.dataset.tema = settings.tema;
    document.documentElement.dataset.fonte = settings.fonte;
  };
  apply(); // Executado no head, antes de desenhar a página.
  document.addEventListener('DOMContentLoaded', () => {
    const theme = document.getElementById('a11y-theme');
    const font = document.getElementById('a11y-font');
    const reset = document.getElementById('a11y-reset');
    const status = document.getElementById('a11y-status');
    if (!theme || !font || !reset || !status) return;
    theme.value = settings.tema;
    font.value = settings.fonte;
    const save = () => {
      apply();
      try {
        localStorage.setItem(key, JSON.stringify(settings));
        status.textContent = 'Aparência atualizada e salva neste navegador.';
      } catch {
        status.textContent =
          'Aparência aplicada nesta página. O navegador não permitiu salvar a preferência.';
      }
    };
    theme.addEventListener('change', () => {
      if (!['claro', 'escuro'].includes(theme.value)) return;
      settings.tema = theme.value;
      save();
    });
    font.addEventListener('change', () => {
      if (!['padrao', 'grande', 'maior'].includes(font.value)) return;
      settings.fonte = font.value;
      save();
    });
    reset.addEventListener('click', () => {
      settings = { tema: 'claro', fonte: 'padrao' };
      theme.value = settings.tema;
      font.value = settings.fonte;
      save();
    });
  });
})();
