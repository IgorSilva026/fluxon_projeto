/* Visualização local da senha. */
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

  document
    .querySelectorAll('input[type="password"]')
    .forEach((input, index) => {
      if (input.closest('.password-wrap')) return;

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
      button.innerHTML = eye;

      if (!input.id) {
        input.id = 'fluxon-password-' + index;
      }

      button.setAttribute('aria-controls', input.id);
      wrap.appendChild(button);

      let visible = false;
      let revealed = -1;
      let timer;

      function paint() {
        const css = getComputedStyle(input);

        mirror.style.left =
          parseFloat(css.borderLeftWidth) + parseFloat(css.paddingLeft) + 'px';

        mirror.style.right = css.paddingRight;
        mirror.style.top = input.offsetTop + 'px';
        mirror.style.height = input.offsetHeight + 'px';
        mirror.style.font = css.font;
        mirror.style.letterSpacing = css.letterSpacing;

        text.textContent = visible
          ? ''
          : Array.from(input.value)
              .map((char, i) => (i === revealed ? char : '•'))
              .join('');

        text.style.transform = 'translateX(' + -input.scrollLeft + 'px)';
      }

      function mask() {
        clearTimeout(timer);
        revealed = -1;
        paint();
      }

      function renderButton() {
        button.setAttribute(
          'aria-label',
          visible ? 'Ocultar senha' : 'Mostrar senha',
        );

        button.setAttribute('aria-pressed', String(visible));
        wrap.classList.toggle('password-shown', visible);
      }

      function hide() {
        visible = false;
        input.type = 'password';
        renderButton();
        mask();
      }

      input.addEventListener('input', (event) => {
        mask();

        if (
          !visible &&
          !event.isComposing &&
          event.inputType === 'insertText' &&
          event.data &&
          document.activeElement === input &&
          input.selectionStart === input.selectionEnd
        ) {
          revealed =
            Array.from(input.value.slice(0, input.selectionStart)).length - 1;

          timer = setTimeout(mask, 700);
        }

        paint();
        requestAnimationFrame(paint);
      });

      input.addEventListener('compositionend', mask);
      input.addEventListener('scroll', paint);
      input.addEventListener('select', mask);
      input.addEventListener('focus', paint);
      input.addEventListener('blur', hide);

      button.addEventListener('mousedown', (event) => {
        event.preventDefault();
      });

      button.addEventListener('click', () => {
        mask();
        visible = !visible;
        input.type = visible ? 'text' : 'password';
        renderButton();
        input.focus();
        paint();
      });

      input.form?.addEventListener('reset', () => {
        hide();
        setTimeout(paint, 0);
      });

      window.addEventListener('pageshow', hide);
      window.addEventListener('resize', paint);

      document.addEventListener('visibilitychange', () => {
        if (document.hidden) hide();
      });

      renderButton();
      wrap.classList.add('password-enhanced');
      paint();

      setInterval(() => {
        if (revealed < 0) paint();
      }, 250);
    });
})();
