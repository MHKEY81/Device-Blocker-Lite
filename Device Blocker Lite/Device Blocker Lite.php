<?php
/**
 * Plugin Name: Device Blocker Lite
 * Description: A lightweight WordPress plugin that blocks or excludes visitors based on client‑side detection, preventing page content from loading for excluded visitors.
 * Version: 3.0.0
 * Author: MHKEY
 * Author URI: https://github.com/MHKEY81
 * License: MIT
 */

if (!defined('ABSPATH')) exit;

/* ---------------- Activation: defaults ---------------- */

register_activation_hook(__FILE__, function () {
    if (get_option('dbp_blocked_models') === false) {
        add_option('dbp_blocked_models', []);
    }
    if (get_option('dbp_blocked_ua') === false) {
        add_option('dbp_blocked_ua', []); // NEW: بلاک لیست UA
    }
    if (get_option('dbp_redirect_url') === false) {
        add_option('dbp_redirect_url', home_url('/'));
    }
});

/* ---------------- REST API: check blocked ---------------- */

add_action('rest_api_init', function () {
    register_rest_route('dbp/v1', '/is-blocked', [
        'methods'  => 'POST',
        'callback' => 'dbp_is_blocked',
        'permission_callback' => '__return_true',
    ]);
});

function dbp_is_blocked(WP_REST_Request $req) {
    $model = sanitize_text_field($req->get_param('model')) ?: '';
    $ua    = sanitize_textarea_field($req->get_param('ua')) ?: '';

    $blocked_models = get_option('dbp_blocked_models', []);
    $blocked_ua     = get_option('dbp_blocked_ua', []);
    $redirect_url   = get_option('dbp_redirect_url', home_url('/'));

    $blocked = false;

    // 1) چک بر اساس model
    if ($model !== '') {
        $m = mb_strtolower(trim($model));
        foreach ($blocked_models as $bm) {
            $bm = mb_strtolower(trim($bm));
            if ($bm !== '' && mb_stripos($m, $bm) !== false) {
                $blocked = true;
                break;
            }
        }
    }

    // 2) اگر هنوز بلاک نیست، چک بر اساس User-Agent
    if (!$blocked && $ua !== '') {
        $ua_l = mb_strtolower($ua);
        foreach ($blocked_ua as $pat) {
            $pat_l = mb_strtolower(trim($pat));
            if ($pat_l !== '' && mb_stripos($ua_l, $pat_l) !== false) {
                $blocked = true;
                break;
            }
        }
    }

    return new WP_REST_Response([
        'blocked'  => $blocked,
        'redirect' => esc_url_raw($redirect_url),
    ], 200);
}

/* ---------------- Frontend: ultra-early JS in head ---------------- */

add_action('wp_head', function () {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

    $endpoint = esc_url_raw(rest_url('dbp/v1/is-blocked'));

    ?>
    <script>
    (() => {
      // قبل از رندر، صفحه نامرئی
      const style = document.createElement("style");
      style.id = "dbp-hide";
      style.textContent = "html{visibility:hidden!important}";
      document.head.appendChild(style);

      const unblock = () => {
        const s = document.getElementById("dbp-hide");
        if (s) s.remove();
        document.documentElement.style.visibility = "visible";
      };

      const hardBack = () => {
        // کوکی برای دفعات بعد
        document.cookie = "dbp_blocked=1; path=/; max-age=" + (60*60*24*30);

        // اگر صفحه قبلی هست، بک
        if (window.history.length > 1) {
          window.history.back();
          return;
        }

        // fallback: اگر referrer داریم
        if (document.referrer) {
          location.replace(document.referrer);
          return;
        }

        // fallback آخر
        location.replace("https://www.google.com/");
      };

      (async () => {
        try {
          let model = "";
          let ua = navigator.userAgent || "";

          if (navigator.userAgentData?.getHighEntropyValues) {
            const data = await navigator.userAgentData.getHighEntropyValues(["model"]);
            model = (data.model || "").trim();
          }

          const res = await fetch("<?php echo $endpoint; ?>", {
            method: "POST",
            headers: {"Content-Type":"application/json"},
            body: JSON.stringify({ model, ua }),
            cache: "no-store",
            keepalive: true
          });

          const out = await res.json();

          if (out.blocked) {
            hardBack();
            return;
          }

          unblock();
        } catch (e) {
          unblock(); // سالم‌ها گیر نکنن
        }
      })();
    })();
    </script>
    <?php
}, 0);

/* ---------------- Server-side redirect for repeat visits ---------------- */

add_action('muplugins_loaded', function () {
    if (is_admin()) return;

    if (!empty($_COOKIE['dbp_blocked']) && $_COOKIE['dbp_blocked'] === '1') {
        $redirect_url = get_option('dbp_redirect_url', home_url('/'));
        wp_redirect($redirect_url, 302);
        exit;
    }
}, 0);

/* ---------------- Admin UI: blacklist + redirect URL + UA patterns ---------------- */

add_action('admin_menu', function () {
    add_menu_page(
        'Device Blocker',
        'Device Blocker',
        'manage_options',
        'device-blocker-pro',
        'dbp_admin_page',
        'dashicons-shield',
        58
    );
});

add_action('admin_post_dbp_save_settings', function () {
    if (!current_user_can('manage_options')) wp_die('No access');
    check_admin_referer('dbp_save_settings');

    // مدل‌ها
    $raw_models = $_POST['blocked_models'] ?? '';
    $lines_models = array_filter(array_map('trim', explode("\n", $raw_models)));
    update_option('dbp_blocked_models', $lines_models);

    // UA patternها
    $raw_ua = $_POST['blocked_ua'] ?? '';
    $lines_ua = array_filter(array_map('trim', explode("\n", $raw_ua)));
    update_option('dbp_blocked_ua', $lines_ua);

    // Redirect URL
    $redirect_url = esc_url_raw($_POST['redirect_url'] ?? '');
    if ($redirect_url) {
        update_option('dbp_redirect_url', $redirect_url);
    }

    wp_redirect(admin_url('admin.php?page=device-blocker-pro&saved=1'));
    exit;
});

function dbp_admin_page() {
    if (!current_user_can('manage_options')) return;

    $blocked_models = get_option('dbp_blocked_models', []);
    $blocked_ua     = get_option('dbp_blocked_ua', []);
    $redirect_url   = get_option('dbp_redirect_url', home_url('/'));

    $text_models = implode("\n", $blocked_models);
    $text_ua     = implode("\n", $blocked_ua);
    ?>
    <div class="wrap">
        <h1>Device Blocker (Model + UA Blacklist)</h1>

        <?php if (!empty($_GET['saved'])): ?>
            <div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background:#fff;border:1px solid #ddd;padding:16px;max-width:900px;">
            <?php wp_nonce_field('dbp_save_settings'); ?>
            <input type="hidden" name="action" value="dbp_save_settings" />

            <h2 style="margin-top:0;">Redirect URL</h2>
            <input type="url" name="redirect_url" style="width:100%;max-width:700px"
                   value="<?php echo esc_attr($redirect_url); ?>" />
            <p style="color:#666;margin-top:6px;">
                هر کسی که بلاک شود (بر اساس مدل یا User-Agent)، مستقیم به این آدرس هدایت می‌شود یا با بک/رفرر از سایت خارج می‌شود.
            </p>

            <hr style="margin:16px 0;">

            <h2>Blocked Models (by UA-CH model)</h2>
            <p>هر خط یک عبارت برای فیلد <code>model</code>. مثال:</p>
            <ul style="list-style:disc;margin-left:20px;color:#444;">
                <li>Redmi Note 8</li>
                <li>SM-A505F</li>
                <li>Galaxy A12</li>
            </ul>

            <textarea name="blocked_models" rows="8" style="width:100%;max-width:700px"><?php echo esc_textarea($text_models); ?></textarea>

            <hr style="margin:16px 0;">

            <h2>Blocked User-Agent Patterns</h2>
            <p>هر خط یک عبارت که اگر داخل <code>navigator.userAgent</code> بود، بلاک شود. مثال‌ها:</p>
            <ul style="list-style:disc;margin-left:20px;color:#444;">
                <li><code>Firefox/133</code> (برای بلاک یک ورژن خاص فایرفاکس)</li>
                <li><code>Android 9</code> (برای بلاک اندروید ۹)</li>
                <li><code>Windows NT 6.1</code> (برای بلاک ویندوز ۷)</li>
            </ul>

            <textarea name="blocked_ua" rows="8" style="width:100%;max-width:700px"><?php echo esc_textarea($text_ua); ?></textarea>

            <p style="margin-top:12px;">
                <button class="button button-primary">Save Settings</button>
            </p>
        </form>

        <p style="margin-top:14px;color:#555;">
            - اگر مرورگر <strong>model</strong> نده، فقط User-Agent چک می‌شود.<br>
            - اگر هیچ‌کدام نخورد، کاربر عادی وارد سایت می‌شود.
        </p>
    </div>
    <?php
}
