package com.profetional.global

import android.Manifest
import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.webkit.CookieManager
import android.webkit.DownloadListener
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import android.webkit.JavascriptInterface
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.ComponentActivity
import androidx.core.content.ContextCompat
import com.onesignal.OneSignal

class MainActivity : ComponentActivity() {
    private lateinit var webView: WebView
    private var fileCallback: ValueCallback<Array<Uri>>? = null

    private val filePicker = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
        val data = result.data
        val uris = if (result.resultCode == RESULT_OK && data != null) {
            data.clipData?.let { clip -> Array(clip.itemCount) { i -> clip.getItemAt(i).uri } }
                ?: data.data?.let { arrayOf(it) }
        } else null
        fileCallback?.onReceiveValue(uris)
        fileCallback = null
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        OneSignal.initWithContext(this, BuildConfig.ONESIGNAL_APP_ID)
        if (BuildConfig.ONESIGNAL_APP_ID.startsWith("REPLACE_").not()) {
            OneSignal.User.addTag("platform", "android")
        }
        requestNotificationPermissionIfNeeded()

        webView = WebView(this)
        setContentView(webView)
        webView.settings.javaScriptEnabled = true
        webView.settings.domStorageEnabled = true
        webView.settings.allowFileAccess = true
        webView.settings.javaScriptCanOpenWindowsAutomatically = true
        webView.addJavascriptInterface(NativeBridge(), "PPGNative")
        CookieManager.getInstance().setAcceptCookie(true)
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true)
        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, url: String): Boolean {
                val uri = Uri.parse(url)
                if (uri.host != null && (uri.scheme == "http" || uri.scheme == "https") && uri.host!!.endsWith("prophetprecious.com")) return false
                return try { startActivity(Intent(Intent.ACTION_VIEW, uri)); true } catch (_: Exception) { false }
            }
        }
        webView.webChromeClient = object : WebChromeClient() {
            override fun onShowFileChooser(view: WebView, callback: ValueCallback<Array<Uri>>, params: FileChooserParams): Boolean {
                fileCallback?.onReceiveValue(null)
                fileCallback = callback
                return try { filePicker.launch(params.createIntent()); true } catch (_: Exception) { fileCallback = null; false }
            }
        }
        webView.setDownloadListener(DownloadListener { url, _, contentDisposition, mimeType, _ ->
            try {
                val request = DownloadManager.Request(Uri.parse(url))
                    .setMimeType(mimeType)
                    .addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url) ?: "")
                    .setTitle("Download")
                    .setDescription("Downloading file")
                    .setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                    .setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, "download")
                (getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager).enqueue(request)
                Toast.makeText(this, "Download started", Toast.LENGTH_SHORT).show()
            } catch (_: Exception) { Toast.makeText(this, "Unable to download this file", Toast.LENGTH_LONG).show() }
        })
        if (savedInstanceState == null) webView.loadUrl("https://prophetprecious.com/app-home/") else webView.restoreState(savedInstanceState)
    }

    inner class NativeBridge {
        @JavascriptInterface
        fun login(userId: Int) {
            if (userId > 0 && !BuildConfig.ONESIGNAL_APP_ID.startsWith("REPLACE_")) {
                runOnUiThread { OneSignal.login("wp_" + userId) }
            }
        }
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= 33 && ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(arrayOf(Manifest.permission.POST_NOTIFICATIONS), 1001)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) { super.onSaveInstanceState(outState); webView.saveState(outState) }
    @Deprecated("Deprecated in Android")
    override fun onBackPressed() { if (::webView.isInitialized && webView.canGoBack()) webView.goBack() else super.onBackPressed() }
}
