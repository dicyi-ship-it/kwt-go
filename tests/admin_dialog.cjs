const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/admin.js'), 'utf8');

function setup(autoOpen, id = '') {
  const handlers = {};
  const buttons = [{}, {}];
  const add = { addEventListener: (_, fn) => { handlers.add = fn; }, focus: () => { handlers.focused = true; } };
  buttons.forEach(button => { button.addEventListener = (_, fn) => { button.click = fn; }; });
  const dialog = {
    dataset: { autoOpen }, open: false,
    querySelector: () => ({ value: id }), querySelectorAll: () => buttons,
    showModal() { this.open = true; }, close() { this.open = false; handlers.close(); },
    addEventListener: (name, fn) => { handlers[name] = fn; },
  };
  vm.runInNewContext(script, {
    document: { getElementById: key => ({ 'service-form': dialog, 'service-add': add }[key] ?? null) },
    location: { hash: '' }, window: { addEventListener() {} },
  });
  return { dialog, handlers, buttons };
}
const added = setup('0');
assert.equal(added.dialog.open, false);
let prevented = false;
added.handlers.add({ preventDefault() { prevented = true; } });
assert.equal(prevented, true);
assert.equal(added.dialog.open, true);
added.buttons[0].click();
assert.equal(added.dialog.open, false);
assert.equal(added.handlers.focused, true);
assert.equal(setup('1').dialog.open, true);
const edited = setup('1', '42');
edited.handlers.add({ preventDefault() { throw Error('Adding after editing must load an empty form'); } });
console.log('PASS: popup hidden initially, add, close, focus return, automatic reopening, fresh add after edit');
