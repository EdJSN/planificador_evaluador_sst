import { assignFormListener } from '../shared/formHandlers';

export function setupSettingsUsers() {
  const form = document.getElementById('createUserForm');
  assignFormListener(form);
}
