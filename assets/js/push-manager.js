/**
 * SVMS - Web Push Subscription Manager
 */

const PushManager = {
    // VAPID Public Key (A real-world app would generate this)
    // For demo purposes, this is a placeholder. 
    // In production, you'd use your own VAPID public key.
    vapidPublicKey: 'BBCiSrLfOgW6yINtSFAxLRgYo2QJ73guhYbfmyMgRQHBZBcno91z78tSQdBYViffdIwsLqMXQbx8G8elKXakZQE',

    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            alert('Push messaging is NOT supported in this browser.');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                console.log('User is already subscribed to push');
                this.sendSubscriptionToServer(subscription);
            }
        } catch (err) {
            alert('Service Worker Error: ' + err.message);
        }
    },

    async subscribeUser() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const sub = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            console.log('Subscription successful! Saving to server...');
            await this.sendSubscriptionToServer(sub);
            return true;
        } catch (err) {
            alert('Subscription Failed: ' + err.message);
            console.error('Failed to subscribe user: ', err);
            return false;
        }
    },

    async sendSubscriptionToServer(subscription) {
        try {
            const key = subscription.getKey('p256dh');
            const token = subscription.getKey('auth');

            // Convert ArrayBuffer to base64
            const p256dhBase64 = btoa(String.fromCharCode(...new Uint8Array(key)));
            const authBase64 = btoa(String.fromCharCode(...new Uint8Array(token)));

            const basePath = document.querySelector('script[src*="push-manager"]')?.src.split('/assets')[0] || '';
            
            const res = await fetch(basePath + '/api/save_subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: p256dhBase64,
                        auth: authBase64
                    }
                })
            });

            const data = await res.json();
            if (data.success) {
                console.log('Push Token saved successfully!');
            } else {
                console.error('Server Error:', data.message || 'Unknown error');
            }
        } catch (err) {
            console.error('Fetch Error:', err.message);
        }
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
document.addEventListener('DOMContentLoaded', async () => {
    PushManager.init();

    const btn = document.getElementById('enablePushBtn');
    if (!btn) return;

    // Push requires Secure Context (HTTPS or localhost)
    if (!window.isSecureContext) {
        alert('DEBUG: Not in a Secure Context (HTTPS). Push will fail.');
        console.warn('Push alerts require an HTTPS connection.');
        btn.innerHTML = '<i class="bi bi-shield-lock me-1"></i>Need HTTPS';
        btn.disabled = true;
        btn.style.display = 'inline-block';
        return;
    }

    if (!('Notification' in window)) {
        btn.style.display = 'none';
        return;
    }

    // Check if we are already subscribed
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        // No subscription found, show the button
        btn.style.display = 'inline-block';
        if (Notification.permission === 'denied') {
            btn.textContent = 'Alerts Blocked';
            btn.disabled = true;
        }
    } else {
        // Already subscribed, hide it
        btn.style.display = 'none';
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
