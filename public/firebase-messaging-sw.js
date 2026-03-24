importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

// Security note: keep Firebase config out of git. These placeholders prevent
// GitHub push-protection secret scanning from blocking pushes.
// Configure real values in your deployment, e.g. via server-injected config.
let config = {
  apiKey: "DEMO_FIREBASE_API_KEY",
  authDomain: "demo.firebaseapp.com",
  projectId: "demo-project",
  storageBucket: "demo.appspot.com",
  messagingSenderId: "000000000000",
  appId: "1:000000000000:web:DEMO",
  measurementId: "G-DEMO",
};

firebase.initializeApp(config);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/images/required/firebase-logo.png'
  };
  self.registration.showNotification(notificationTitle, notificationOptions);
});

