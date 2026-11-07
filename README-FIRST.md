# Prophet Precious Global Android App

This repository contains an Android WebView app and a WordPress companion plugin.

## Android build
1. Open the repository on GitHub.
2. Open the Actions tab.
3. Select **Build Android APK** and choose **Run workflow**, or push a change to the `main` branch.
4. When the workflow completes, open the run and download the `prophet-precious-global-debug-apk` artifact.

The artifact is a debug APK for testing, not a signed Play Store release.

## OneSignal setup
The Android app uses OneSignal SDK 5.1.28. Before testing notifications, replace `REPLACE_WITH_ANDROID_ONESIGNAL_APP_ID` in `android-app/app/build.gradle.kts` with the OneSignal App ID configured for the Android platform and its Firebase Cloud Messaging credentials. Do not put the OneSignal REST API key in the Android app.

## WordPress setup
1. Upload `wordpress-plugin/prophet-precious-app-bridge.php` as a plugin (or zip the `wordpress-plugin` folder and upload it under Plugins > Add New > Upload Plugin).
2. Activate **Prophet Precious Global App Bridge**.
3. Create or edit the `/app-home/` page and add `[ppg_app_home]`.
4. In WordPress admin, open **PPG Android App** and configure the OneSignal App ID and REST API key.

The app loads `https://prophetprecious.com/app-home/`. The homepage displays posts from the `intervention` category.

## Important testing notes
This is a starter implementation. Test login, uploads, downloads, payment flows, push delivery, and navigation on a physical Android device before distribution. Configure a signed release build and review current Google Play requirements before publishing.
