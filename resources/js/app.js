import Alpine from 'alpinejs';
import { contractForm } from './contract-form';
import { pushToggle } from './push';

window.Alpine = Alpine;

Alpine.data('contractForm', contractForm);
Alpine.data('pushToggle', pushToggle);

Alpine.start();

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}
