/**
 * Notification System for Mobile Devices
 * This script handles push notification registration and management
 */

// Import Firebase (if using a module system)
// import * as firebase from 'firebase/app';
// import 'firebase/messaging';

// Firebase configuration for web push notifications
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "your-app.firebaseapp.com",
  projectId: "your-app",
  storageBucket: "your-app.appspot.com",
  messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
  appId: "YOUR_APP_ID",
}

// Initialize Firebase if it's available (web browsers)
let messaging = null
if (typeof firebase !== "undefined") {
  firebase.initializeApp(firebaseConfig)

  if (firebase.messaging.isSupported()) {
    messaging = firebase.messaging()
  }
}

/**
 * Register the device for push notifications
 */
async function registerForPushNotifications() {
  // Check if the browser supports notifications
  if (!("Notification" in window)) {
    console.log("This browser does not support notifications")
    return
  }

  // Check if permission is already granted
  if (Notification.permission === "granted") {
    await getAndRegisterToken()
  } else if (Notification.permission !== "denied") {
    // Request permission
    const permission = await Notification.requestPermission()

    if (permission === "granted") {
      await getAndRegisterToken()
    }
  }
}

/**
 * Get device token and register it with the server
 */
async function getAndRegisterToken() {
  try {
    // Get token from Firebase
    if (!messaging) {
      console.log("Firebase messaging is not supported in this browser")
      return
    }

    // Get registration token
    const currentToken = await messaging.getToken({
      vapidKey: "YOUR_VAPID_KEY",
    })

    if (currentToken) {
      // Send the token to your server
      await registerTokenWithServer(currentToken, "web")
      console.log("Token registered successfully")

      // Listen for token refresh
      messaging.onTokenRefresh(async () => {
        const refreshedToken = await messaging.getToken()
        await registerTokenWithServer(refreshedToken, "web")
        console.log("Token refreshed and registered")
      })

      // Set up notification handler
      setupNotificationHandler()
    } else {
      console.log("No registration token available")
    }
  } catch (error) {
    console.error("Error getting token:", error)
  }
}

/**
 * Register the device token with the server
 */
async function registerTokenWithServer(token, deviceType) {
  try {
    const response = await fetch("/api/register-device.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        device_token: token,
        device_type: deviceType,
      }),
    })

    const data = await response.json()

    if (!data.success) {
      console.error("Failed to register token with server:", data.message)
    }

    return data.success
  } catch (error) {
    console.error("Error registering token with server:", error)
    return false
  }
}

/**
 * Set up notification handler for incoming messages
 */
function setupNotificationHandler() {
  if (!messaging) return

  // Handle foreground messages
  messaging.onMessage((payload) => {
    console.log("Message received:", payload)

    // Display notification using the Notification API
    const notificationTitle = payload.notification.title
    const notificationOptions = {
      body: payload.notification.body,
      icon: "/images/notification-icon.png",
      badge: "/images/notification-badge.png",
      data: payload.data,
    }

    // Create and show notification
    const notification = new Notification(notificationTitle, notificationOptions)

    // Handle notification click
    notification.onclick = () => {
      // Close the notification
      notification.close()

      // Handle different notification types
      if (payload.data && payload.data.type === "course_assignment") {
        // Redirect to admission letter page
        window.location.href = payload.data.action_url || "admission-letter.php"
      } else if (payload.data && payload.data.type === "application_status") {
        // Redirect to application status page
        window.location.href = "dashboard.php"
      } else {
        // Default action - open dashboard
        window.location.href = "dashboard.php"
      }
    }
  })
}

// Initialize push notifications when the page loads
document.addEventListener("DOMContentLoaded", () => {
  // Only register for notifications if the user is logged in
  const isLoggedIn = document.body.classList.contains("logged-in")

  if (isLoggedIn) {
    registerForPushNotifications()
  }
})

/**
 * Update notification settings
 */
async function updateNotificationSettings(settings) {
  try {
    const response = await fetch("/api/update-notification-settings.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(settings),
    })

    const data = await response.json()
    return data.success
  } catch (error) {
    console.error("Error updating notification settings:", error)
    return false
  }
}

// Add event listeners to notification settings form if it exists
const notificationForm = document.getElementById("notification-settings-form")
if (notificationForm) {
  notificationForm.addEventListener("submit", (event) => {
    event.preventDefault()

    const settings = {
      application_updates: document.getElementById("application_updates").checked,
      course_assignments: document.getElementById("course_assignments").checked,
      document_requests: document.getElementById("document_requests").checked,
      general_announcements: document.getElementById("general_announcements").checked,
      email_notifications: document.getElementById("email_notifications").checked,
      push_notifications: document.getElementById("push_notifications").checked,
    }

    updateNotificationSettings(settings).then((success) => {
      if (success) {
        alert("Notification settings updated successfully")
      } else {
        alert("Failed to update notification settings")
      }
    })
  })
}

