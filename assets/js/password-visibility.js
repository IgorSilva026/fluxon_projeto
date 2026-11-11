/* Visualização local: nunca envia nem persiste o conteúdo da senha. */
(() => {
  'use strict';
  const eye = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeOff = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10 5.2c.7-.1 1.3-.2 2-.2 6.5 0 10 7 10 7a19 19 0 0 1-3 3.8M6.3 6.3A20 20 0 0 0 2 12s3.5 7 10 7c1.9 0 3.6-.6 5-1.4M10 10a3 3 0 0 0 4 4"/></svg>';
  document.querySelectorAll('input[type="password"]').forEach((input, index) => {
    const wrap = document.createElement('span');
    wrap.className = 'password-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    const mirror = document.createElement('span');
    mirror.className = 'password-mirror';
    mirror.setAttribute('aria-hidden', 'true');
    const text = document.createElement('span');
    mirror.appendChild(text);
    wrap.appendChild(mirror);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-eye';
    if (!input.id) input.id = 'fluxon-password-' + index;
    button.setAttribute('aria-controls', input.id);
    button.setAttribute('aria-pressed', 'false');
    wrap.appendChild(button);
    let visible = false;
    let timer;
    let revealed = -1;
    const paint = () => {
      if (visible) {
        text.textContent = '';
      } else {
        text.textContent = Array.from(input.value).map((c, i) => i === revealed ? c : '•').join('');
        text.style.transform = 'translateX(' + (-input.scrollLeft) + 'px)';
      }
    };
    const mask = () => {
      clearTimeout(timer);
      revealed = -1;
      paint();
    };
    const renderButton = () => {
      button.innerHTML = visible ? eyeOff : eye;
      button.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
      button.setAttribute('aria-pressed', String(visible));
      wrap.classList.toggle('password-shown', visible);
    };
    input.addEventListener('input', event => {
      mask();
      // Só a digitação direta revela brevemente um caractere; colagem/autofill permanecem mascarados.
      if (!visible && !event.isComposing && event.inputType === 'insertText' && event.data && document.activeElement === input && input.selectionStart === input.selectionEnd) {
        revealed = Array.from(input.value.slice(0, input.selectionStart)).length - 1;
        timer = setTimeout(mask, 700);
      }
      paint();
      requestAnimationFrame(paint);
    });
    input.addEventListener('compositionend', mask);
    input.addEventListener('scroll', paint);
    input.addEventListener('select', mask);
    input.addEventListener('focus', paint);
    input.addEventListener('blur', () => {
      mask();
      // A senha volta a ficar oculta ao sair do campo.
      visible = false; input.type = 'password'; renderButton(); paint();
    });
    // Mantém foco ao clicar com mouse, sem prejudicar ativação por teclado.
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => {
      mask();
      visible = !visible;
      input.type = visible ? 'text' : 'password';
      renderButton();
      input.focus(); paint();
    });
    input.form?.addEventListener('reset', () => { mask(); setTimeout(paint, 0); });
    window.addEventListener('pageshow', mask);
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {visible = false;input.type = 'password';renderButton();mask();}
    });
    renderButton();
    // Só substitui o desenho nativo se a decoração foi instalada com sucesso.
    wrap.classList.add('password-enhanced');
    paint();
    // Sincroniza preenchimento automático que não dispara input.
    setInterval(() => { if (revealed < 0) paint(); }, 250);
  });
})();
