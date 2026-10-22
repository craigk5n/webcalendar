/**
 * Regression guard for the wizard's live field validation (#728).
 *
 * wizard.js revalidates a field on every input event, and each validation is
 * an async round-trip to the server. The replies are not guaranteed to arrive
 * in the order they were sent. Because a password with minlength=8 is invalid
 * for its first seven characters, a late reply for an early keystroke used to
 * overwrite the good result for the complete value, leaving fieldValidation
 * false and the submit button disabled on a form whose fields all read
 * correctly. A click on a disabled button is silently dropped, so the wizard
 * sat on the Admin User step until an unrelated wait timed out.
 *
 * Run: node tests/wizard-validation-race.js
 *
 * This drives the real class rather than a copy of it, with just enough of the
 * DOM stubbed to construct one, so it fails if the guard is removed.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const WIZARD_JS = path.join(__dirname, '..', 'wizard', 'wizard.js');

class FakeFormData {
  constructor() { this.entries = []; }
  append(key, value) { this.entries.push([key, value]); }
  forEach(fn) { this.entries.forEach(([key, value]) => fn(value, key)); }
}

/** Load wizard.js and hand back the class, without running page-load code. */
function loadWizard(fetchStub) {
  const source = fs.readFileSync(WIZARD_JS, 'utf8');
  const noop = () => {};
  const document = {
    addEventListener: noop,
    querySelectorAll: () => [],
    querySelector: () => null,
    getElementById: () => null,
    createElement: () => ({ classList: { add: noop, remove: noop }, appendChild: noop }),
  };
  const Wizard = new Function(
    'window', 'document', 'fetch', 'FormData', 'console',
    source + '\n; return WebCalendarWizard;'
  )({ addEventListener: noop }, document, fetchStub, FakeFormData, console);
  Wizard.prototype.init = noop;
  return Wizard;
}

function makeField(name, value, form, group) {
  return {
    name,
    value,
    classList: { add: () => {}, remove: () => {} },
    closest: (selector) => (selector === 'form' ? form : group),
  };
}

const tick = () => new Promise((resolve) => setImmediate(resolve));

async function main() {
  const pendingReplies = [];
  const Wizard = loadWizard(() => new Promise((res) => pendingReplies.push(res)));

  const submitButton = { disabled: false };
  const form = { querySelector: () => submitButton };
  const group = { querySelector: () => null, appendChild: () => {} };
  const wizard = new Wizard({});

  // Two keystrokes: "a" is too short to be valid, "admin123" is the real value.
  wizard.validateField(makeField('admin_password', 'a', form, group));
  wizard.validateField(makeField('admin_password', 'admin123', form, group));

  if (pendingReplies.length !== 2) {
    throw new Error(`expected 2 validation requests, saw ${pendingReplies.length}`);
  }

  // Answer the newest request first, then let the stale one land last.
  const [staleReply, freshReply] = pendingReplies;
  freshReply({ json: async () => ({ valid: true, fieldErrors: {} }) });
  await tick();
  staleReply({
    json: async () => ({
      valid: false,
      fieldErrors: { admin_password: 'Password must be at least 8 characters' },
    }),
  });
  await tick();
  await tick();

  const failures = [];
  if (wizard.fieldValidation.admin_password !== true) {
    failures.push(
      `fieldValidation.admin_password is ${wizard.fieldValidation.admin_password}, ` +
      'expected true: the stale reply overwrote the newer one');
  }
  if (submitButton.disabled !== false) {
    failures.push('submit button is disabled, expected enabled');
  }

  if (failures.length) {
    failures.forEach((line) => console.error('FAIL: ' + line));
    process.exit(1);
  }
  console.log('ok - a stale validation reply does not overwrite a newer one (#728)');
}

main().catch((err) => {
  console.error('FAIL: ' + err.message);
  process.exit(1);
});
