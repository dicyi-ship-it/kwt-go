'use strict';

const modal = document.getElementById('share_modal');
const trigger = document.querySelector('[data-target="#share_modal"] button');
let backdrop;
function closeShare() {
  modal.classList.remove('show');
  modal.style.display = 'none';
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
  backdrop?.remove();
  trigger.focus();
}
trigger.addEventListener('click', () => {
  modal.style.display = 'block';
  modal.classList.add('show');
  modal.setAttribute('aria-hidden', 'false');
  modal.setAttribute('aria-modal', 'true');
  document.body.classList.add('modal-open');
  backdrop = document.createElement('div');
  backdrop.className = 'modal-backdrop fade show';
  document.body.append(backdrop);
  modal.querySelector('.close').focus();
});
modal.querySelector('[data-dismiss="modal"]').addEventListener('click', closeShare);
modal.addEventListener('click', event => { if (event.target === modal) closeShare(); });
document.addEventListener('keydown', event => {
  if (!modal.classList.contains('show')) return;
  if (event.key === 'Escape') closeShare();
  if (event.key === 'Tab') {
    const controls = [...modal.querySelectorAll('button:not(.d-none), a[href], input')];
    const first = controls[0], last = controls.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  }
});
document.querySelector('[data-clipboard-target]').addEventListener('click', async event => {
  const button = event.currentTarget;
  const input = document.getElementById('share');
  input.select();
  try {
    await navigator.clipboard.writeText(input.value);
    button.setAttribute('aria-label', 'Disalin!');
  } catch {
    document.execCommand('copy');
  }
});
const nativeShare = document.querySelector('[data-native-share]');
if (navigator.share) {
  nativeShare.classList.remove('d-none');
  nativeShare.addEventListener('click', () => navigator.share({ title: document.title, url: nativeShare.dataset.shareUrl }).catch(() => {}));
}
