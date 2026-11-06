'use strict';

const destinationType = document.getElementById('target-type');
if (destinationType) {
  const updateFields = () => {
    for (const group of document.querySelectorAll('[data-destination]')) {
      const active = group.dataset.destination === destinationType.value;
      group.hidden = !active;
      group.disabled = !active;
    }
  };
  destinationType.addEventListener('change', updateFields);
  updateFields();
}

function openLinkedAccordion() {
  const target = document.getElementById(location.hash.slice(1));
  const accordion = target?.closest('details');
  if (accordion) accordion.open = true;
}
window.addEventListener('hashchange', openLinkedAccordion);
openLinkedAccordion();

for (const [dialogId, addId, closeSelector] of [
  ['service-form', 'service-add', '[data-close-service]'],
  ['umkm-form', 'umkm-add', '[data-close-umkm]'],
]) {
  const dialog = document.getElementById(dialogId);
  if (!dialog) continue;
  const addButton = document.getElementById(addId);
  addButton.addEventListener('click', event => {
    if (dialog.querySelector('[name="id"]').value !== '') return;
    event.preventDefault();
    dialog.showModal();
  });
  for (const button of dialog.querySelectorAll(closeSelector)) {
    button.addEventListener('click', () => dialog.close());
  }
  dialog.addEventListener('close', () => addButton.focus());
  if (dialog.dataset.autoOpen === '1') dialog.showModal();
}
