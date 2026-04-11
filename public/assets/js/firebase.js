import {initializeApp} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import {getMessaging, getToken, onMessage} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";

function toSafeHttpUrl(url) {
    if (!url || typeof url !== 'string') return null;
    try {
        const resolved = new URL(url, window.location.origin);
        return ['http:', 'https:'].includes(resolved.protocol) ? resolved.toString() : null;
    } catch (_e) {
        return null;
    }
}

async function initFirebase() {
    try {
        // 🔹 Check if config is cached
        let firebaseConfig = JSON.parse(localStorage.getItem('firebase_config'));

        // 🔹 If not found, call API once
        if (!firebaseConfig) {
            const { data } = await axios.get('/api/settings/firebase-config');
            firebaseConfig = data.data;
            localStorage.setItem('firebase_config', JSON.stringify(firebaseConfig));
        }

        // 🔹 Bail out if Firebase is not configured
        if (!firebaseConfig || !firebaseConfig.projectId || !firebaseConfig.apiKey) {
            return;
        }

        // 🔹 Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // 🔹 Ask for notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.warn('Notification permission not granted');
            return;
        }

        // 🔹 Fetch FCM token
        const vapidKey = firebaseConfig.vapidKey;
        const token = await getToken(messaging, {vapidKey});
        localStorage.setItem('fcm_token', token);

        // 🔹 Listen for messages when tab is active
        onMessage(messaging, (payload) => {
            console.log('Message received in foreground:', payload);

            const { title, body, image } = payload.notification || {};

            // Create toast container if not already present
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-bg-blue border-0 show mb-2 shadow';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            const header = document.createElement('div');
            header.className = 'toast-header';

            const safeImage = toSafeHttpUrl(image);
            if (safeImage) {
                const imageEl = document.createElement('img');
                imageEl.src = safeImage;
                imageEl.className = 'rounded me-2';
                imageEl.alt = 'Notification Image';
                imageEl.style.width = '30px';
                imageEl.style.height = '30px';
                imageEl.style.objectFit = 'cover';
                header.appendChild(imageEl);
            }

            const titleEl = document.createElement('strong');
            titleEl.className = 'me-auto';
            titleEl.textContent = title || 'Notification';
            header.appendChild(titleEl);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'toast');
            closeBtn.setAttribute('aria-label', 'Close');
            header.appendChild(closeBtn);

            const bodyEl = document.createElement('div');
            bodyEl.className = 'toast-body';
            bodyEl.textContent = body || '';

            toastEl.appendChild(header);
            toastEl.appendChild(bodyEl);

            toastContainer.appendChild(toastEl);

            // Show using Bootstrap's JS
            // const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            // toast.show();
        });


    } catch (err) {
        console.error('Error initializing Firebase:', err);
    }
}

initFirebase();
