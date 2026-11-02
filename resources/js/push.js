// Web-Push auf diesem Gerät ein- oder ausschalten.
export function pushToggle(config = {}) {
  return {
    supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
    standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
    subscribed: false,
    busy: false,
    message: '',

    async init() {
      if (!this.supported) return;
      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      this.subscribed = !!sub;
    },

    async toggle() {
      this.busy = true;
      this.message = '';
      try {
        this.subscribed ? await this.unsubscribe() : await this.subscribe();
      } catch (e) {
        this.message = e.message || 'Push konnte nicht aktiviert werden.';
      } finally {
        this.busy = false;
      }
    },

    async subscribe() {
      if (!config.vapidKey) throw new Error('Push ist auf dem Server noch nicht eingerichtet (VAPID-Schlüssel fehlt).');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Mitteilungen wurden im Browser nicht erlaubt.');

      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(config.vapidKey),
      });

      const json = sub.toJSON();
      const encoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
      await send(config.storeUrl, 'POST', { ...json, contentEncoding: encoding });
      this.subscribed = true;
      this.message = 'Push ist auf diesem Gerät aktiv.';
    },

    async unsubscribe() {
      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      if (sub) {
        await send(config.destroyUrl, 'DELETE', { endpoint: sub.endpoint });
        await sub.unsubscribe();
      }
      this.subscribed = false;
      this.message = 'Push ist auf diesem Gerät aus.';
    },
  };
}

async function send(url, method, body) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error('Server hat die Anmeldung abgelehnt (' + res.status + ').');
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}
