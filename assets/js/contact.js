(() => {
  'use strict';
  const button = document.getElementById('copiar-contato');
  const field = document.getElementById('contato-email');
  const status = document.getElementById('contato-status');
  if (!button || !field || !status) return;
  button.addEventListener('click', async () => {
    try {
      if (!navigator.clipboard?.writeText)
        throw new Error('Clipboard unavailable');
      await navigator.clipboard.writeText(field.value);
      status.textContent =
        'E-mail copiado. Cole no destinatário da sua mensagem.';
    } catch {
      field.focus();
      field.select();
      field.setSelectionRange(0, field.value.length);
      status.textContent =
        'Endereço selecionado. Pressione Ctrl+C ou use a opção Copiar do dispositivo.';
    }
  });
})();
