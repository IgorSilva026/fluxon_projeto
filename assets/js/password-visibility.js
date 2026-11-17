
/*
 * FLUXON — PASSWORD VISIBILITY
 *
 * Visualização local da senha.
 * Não envia nem persiste o conteúdo da senha.
 */

(() => {
  'use strict';

  const eye = `
    <svg viewBox="0 0 24 24" width="22" height="22"
      aria-hidden="true" fill="none"
      stroke="currentColor" stroke-width="2">
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
      <circle cx="12" cy="12" r="3"/>
    </svg>
  `;

  const eyeOff = `
    <svg viewBox="0 0 24 24" width="22" height="22"
      aria-hidden="true" fill="none"
      stroke="currentColor" stroke-width="2">
      <path d="m3 3 18 18M10 5.2c.7-.1 1.3-.2 2-.2 6.5 0 10 7 10 7a19 19 0 0 1-3 3.8M6.3 6.3A20 20 0 0 0 2 12s3.5 7 10 7c1.9 0 3.6-.6 5-1.4M10 10a3 3 0 0 0 4 4"/>
    </svg>
  `;

  function initializePassword(input, index) {
    if (input.dataset.passwordInitialized === 'true') {
      return;
    }

    if (!input.parentNode) {
      return;
    }

    input.dataset.passwordInitialized = 'true';

    // Reaproveita um wrapper existente, se houver.
    let wrap = input.closest('.password-wrap');

    if (!wrap) {
      wrap = document.createElement('span');
      wrap.className = 'password-wrap';

      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
    }

    // Cria ou reaproveita o espelho.
    let mirror = wrap.querySelector('.password-mirror');

    if (!mirror) {
      mirror = document.createElement('span');
      mirror.className = 'password-mirror';
      mirror.setAttribute('aria-hidden', 'true');
      wrap.appendChild(mirror);
    }

    let text = mirror.querySelector('span');

    if (!text) {
      text = document.createElement('span');
      mirror.appendChild(text);
    }

    // Cria ou reaproveita o botão.
    let button = wrap.querySelector('.password-eye');

    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'password-eye';
      wrap.appendChild(button);
    }

    if (!input.id) {
      let id = `fluxon-password-${index}`;

      while (document.getElementById(id)) {
        id += '-field';
      }

      input.id = id;
    }

    button.setAttribute('aria-controls', input.id);

    let visible = false;
    let timer = null;
    let revealed = -1;
    let animationFrame = null;

    // Sincroniza o espelho com as dimensões e a fonte do campo.
    function syncMirror() {
      const style = window.getComputedStyle(input);

      mirror.style.left = style.paddingLeft;
      mirror.style.right = style.paddingRight;

      mirror.style.top =
        `${input.offsetTop + input.offsetHeight / 2}px`;

      mirror.style.transform = 'translateY(-50%)';

      text.style.fontFamily = style.fontFamily;
      text.style.fontSize = style.fontSize;
      text.style.fontWeight = style.fontWeight;
      text.style.fontStyle = style.fontStyle;
      text.style.letterSpacing = style.letterSpacing;
      text.style.lineHeight = style.lineHeight;
      text.style.textIndent = style.textIndent;
    }

    function paint() {
      if (visible) {
        text.textContent = '';
        return;
      }

      const characters = Array.from(input.value);

      text.textContent = characters
        .map((character, position) => {
          return position === revealed ? character : '•';
        })
        .join('');

      text.style.transform =
        `translateX(${-input.scrollLeft}px)`;
    }

    // Evita várias atualizações no mesmo quadro.
    function schedulePaint() {
      if (animationFrame !== null) {
        cancelAnimationFrame(animationFrame);
      }

      animationFrame = requestAnimationFrame(() => {
        animationFrame = null;
        syncMirror();
        paint();
      });
    }

    function mask() {
      clearTimeout(timer);
      timer = null;
      revealed = -1;
      schedulePaint();
    }

    function renderButton() {
      button.innerHTML = visible ? eyeOff : eye;

      button.setAttribute(
        'aria-label',
        visible ? 'Ocultar senha' : 'Mostrar senha'
      );

      button.setAttribute(
        'aria-pressed',
        String(visible)
      );

      wrap.classList.toggle('password-shown', visible);
    }

    function hidePassword() {
      clearTimeout(timer);
      timer = null;

      visible = false;
      revealed = -1;

      if (input.type !== 'password') {
        input.type = 'password';
      }

      renderButton();
      schedulePaint();
    }

    input.addEventListener('input', event => {
      clearTimeout(timer);
      timer = null;
      revealed = -1;

      const canReveal =
        !visible &&
        !event.isComposing &&
        event.inputType === 'insertText' &&
        event.data &&
        document.activeElement === input &&
        input.selectionStart === input.selectionEnd;

      if (canReveal) {
        revealed = Array.from(
          input.value.slice(0, input.selectionStart)
        ).length - 1;

        timer = setTimeout(mask, 700);
      }

      schedulePaint();
    });

    input.addEventListener('compositionend', mask);
    input.addEventListener('scroll', paint);
    input.addEventListener('select', mask);
    input.addEventListener('focus', schedulePaint);
    input.addEventListener('blur', hidePassword);

    button.addEventListener('mousedown', event => {
      event.preventDefault();
    });

    button.addEventListener('click', () => {
      mask();

      visible = !visible;
      input.type = visible ? 'text' : 'password';

      renderButton();

      input.focus();
      schedulePaint();
    });

    input.form?.addEventListener('reset', () => {
      hidePassword();
      setTimeout(schedulePaint, 0);
    });

    window.addEventListener('pageshow', hidePassword);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        hidePassword();
      }
    });

    // Atualiza o espelho quando o campo ou a janela mudarem.
    window.addEventListener('resize', schedulePaint);

    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(schedulePaint);
      observer.observe(input);
    }

    renderButton();

    wrap.classList.add('password-enhanced');

    schedulePaint();
  }

  function initializeAllPasswords() {
    document
      .querySelectorAll('input[type="password"]')
      .forEach(initializePassword);
  }

  if (document.readyState === 'loading') {
    document.addEventListener(
      'DOMContentLoaded',
      initializeAllPasswords,
      { once: true }
    );
  } else {
    initializeAllPasswords();
  }
})();
