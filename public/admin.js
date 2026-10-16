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
