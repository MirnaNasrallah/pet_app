// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { onBackgroundMessage } from "firebase/messaging/sw";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
    apiKey: "AIzaSyD7cIJL2Nh4FAvFXiNpscSrCa4dqrKjkYQ",
    authDomain: "pet-app-5fbb3.firebaseapp.com",
    projectId: "pet-app-5fbb3",
    storageBucket: "pet-app-5fbb3.appspot.com",
    messagingSenderId: "173515185885",
    appId: "1:173515185885:web:32f9ace60a7e5bc9174534",
    measurementId: "G-YTSFLSTBTG"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
const messaging = getMessaging(app);
getToken(messaging, { vapidKey: 'BCnndvlySu3DQG04Sc4Q4DzRQn-Y1-sTT21_SVAMYfHk1YsHVzRIPNlr04b0TkosiP3gbvdoCebCwvcwZzU9Zvw' }).then((currentToken) => {
    if (currentToken) {
        // Send the token to your server and update the UI if necessary
        // ...
        console.log(currentToken);
    } else {
        // Show permission request UI
        console.log('No registration token available. Request permission to generate one.');
        // ...
    }
}).catch((err) => {
    console.log('An error occurred while retrieving token. ', err);
    // ...
});

onMessage(messaging, (payload) => {
    console.log('Message received. ', payload);
    console.log(messaging);
    // ...
});
// onBackgroundMessage(messaging, (payload) => {
//     console.log('[firebase-messaging-sw.js] Received background message ', payload);
//     // Customize notification here
//     const notificationTitle = 'Background Message Title';
//     const notificationOptions = {
//         body: 'Background Message body.',
//         icon: '/firebase-logo.png'
//     };

//     self.registration.showNotification(notificationTitle,
//         notificationOptions);
// });