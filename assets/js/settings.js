const frequency = document.getElementById('frequencia');
const time = document.getElementById('horario');

function syncTime() {
  time.required = frequency.value !== 'evento';
  time.disabled = frequency.value === 'evento';
}

frequency.addEventListener('change', syncTime);
syncTime();

document.getElementById('manage-plan').addEventListener('click', () => {
  document.getElementById('plan-info').hidden = false;
});
