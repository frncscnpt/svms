/**
 * SVMS - Web Push Subscription Manager
 */

const PushManager = {
    // VAPID Public Key (A real-world app would generate this)
    // For demo purposes, this is a placeholder. 
    // In production, you'd use your own VAPID public key.
    vapidPublicKey: 'BLhYZmVFNN683OyNvSG3xc0q_qO1GwgZxA6ChTtucbEMwH_nISy_28bCW0ENN2YfiAqqfNKI20c0Dxy6D_KM9uY',

    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push messaging is not supported');
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            console.log('User is already subscribed to push');
            this.sendSubscriptionToServer(subscription);
        }
    },

    async subscribeUser() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const sub = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            console.log('User subscribed successfully');
            await this.sendSubscriptionToServer(sub);
            return true;
        } catch (err) {
            console.error('Failed to subscribe user: ', err);
            return false;
        }
    },

    async sendSubscriptionToServer(subscription) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        const contentEncoding = (PushManager.supportedContentEncoding || 'aesgcm');

        return fetch('/api/save_subscription.php', {
            method: 'POST',
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                    auth: btoa(String.fromCharCode.apply(null, new Uint8Array(token)))
                }
            })
        });
    },

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
};

// Auto-init on page load
document.addEventListener('DOMContentLoaded', () => {
    PushManager.init();

    // Show/hide the "Enable Alerts" button based on current permission
    const btn = document.getElementById('enablePushBtn');
    if (btn && 'Notification' in window) {
        if (Notification.permission === 'default') {
            btn.style.display = 'inline-block';
        } else if (Notification.permission === 'granted') {
            btn.style.display = 'none';
        } else {
            // Denied — show a disabled state
            btn.textContent = 'Alerts Blocked';
            btn.disabled = true;
            btn.style.display = 'inline-block';
        }
    }
});

// Global function called by the "Enable Alerts" button in header.php
async function enablePushNotifications(buttonEl) {
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        const success = await PushManager.subscribeUser();
        if (success) {
            buttonEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>Enabled!';
            buttonEl.disabled = true;
            buttonEl.classList.remove('btn-outline-custom');
            buttonEl.classList.add('btn-success');
            setTimeout(() => { buttonEl.style.display = 'none'; }, 2000);
        }
    } else {
        buttonEl.textContent = 'Alerts Blocked';
        buttonEl.disabled = true;
    }
}
