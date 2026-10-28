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

const serviceDialog = document.getElementById('service-form');
if (serviceDialog) {
  const addButton = document.getElementById('service-add');
  addButton.addEventListener('click', event => {
    if (serviceDialog.querySelector('[name="id"]').value !== '') return;
    event.preventDefault();
    serviceDialog.showModal();
  });
  for (const button of serviceDialog.querySelectorAll('[data-close-service]')) {
    button.addEventListener('click', () => serviceDialog.close());
  }
  serviceDialog.addEventListener('close', () => addButton.focus());
  if (serviceDialog.dataset.autoOpen === '1') serviceDialog.showModal();
}
