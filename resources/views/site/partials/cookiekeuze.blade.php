{{-- Cookiekeuze en Google Analytics: Analytics laadt pas na toestemming (zie de privacyverklaring). Client-side, dus de paginacache blijft werken. --}}
@php $gaId = config('site.ga_id'); @endphp
@if (filled($gaId) && app()->isProduction())
<style>
#cookiekeuze{position:fixed;left:16px;bottom:16px;z-index:2147483000;box-sizing:border-box;width:420px;max-width:calc(100vw - 32px);padding:20px;border-radius:18px;background:#1C0F03;color:#FAF5EA;font-family:inherit;font-size:14px;line-height:1.55;box-shadow:0 12px 40px rgba(28,15,3,.35)}
#cookiekeuze p{margin:0 0 14px;color:inherit}
#cookiekeuze a{color:inherit;text-decoration:underline}
#cookiekeuze div{display:flex;flex-wrap:wrap;gap:8px}
#cookiekeuze button{flex:1 1 auto;padding:10px 16px;border-radius:9999px;border:1px solid rgba(250,245,234,.4);background:transparent;color:#FAF5EA;font:inherit;font-size:13px;font-weight:600;cursor:pointer}
#cookiekeuze button[data-keuze="ja"]{background:#C8860A;border-color:#C8860A;color:#1C0F03}
#cookiekeuze button:focus-visible{outline:2px solid #FAF5EA;outline-offset:2px}
.cookie-knop{padding:0;border:0;background:none;color:inherit;font:inherit;cursor:pointer;text-align:left}
.cookie-knop:hover{text-decoration:underline}
.footer-links .cookie-knop{color:rgba(250,245,234,.5);font-size:.85rem}
.footer-links .cookie-knop:hover{color:#FAF5EA}
</style>
<script>
(function () {
  var ID = @js($gaId), SLEUTEL = 'dgb-keuze-v1';

  // Alleen op de echte website: niet op het testadres en niet lokaal.
  if (location.hostname.replace(/^www\./, '') !== 'degoudenoliebol.nl') return;

  function lees() { try { return localStorage.getItem(SLEUTEL); } catch (e) { return null; } }
  function bewaar(keuze) { try { localStorage.setItem(SLEUTEL, keuze); } catch (e) {} }

  function laadAnalytics() {
    window['ga-disable-' + ID] = false;
    if (window.gtag) return;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ID);
  }

  // Toestemming ingetrokken: niet meer meten en de cookies weg.
  function stopAnalytics() {
    window['ga-disable-' + ID] = true;
    var domein = location.hostname.replace(/^www\./, '');
    document.cookie.split(';').forEach(function (c) {
      var naam = c.split('=')[0].trim();
      if (naam.indexOf('_ga') !== 0) return;
      document.cookie = naam + '=; Max-Age=0; path=/';
      document.cookie = naam + '=; Max-Age=0; path=/; domain=.' + domein;
    });
  }

  function toonBalk() {
    if (document.getElementById('cookiekeuze')) return;
    var b = document.createElement('div');
    b.id = 'cookiekeuze';
    b.setAttribute('role', 'dialog');
    b.setAttribute('aria-label', 'Cookie-instellingen');
    b.innerHTML = '<p>Mogen wij meten hoe deze website wordt gebruikt? Daarvoor plaatst Google Analytics cookies, alleen als je dat goedvindt. Meer in onze <a href="/privacy#s5">privacyverklaring</a>.</p>'
      + '<div><button type="button" data-keuze="nee">Alleen noodzakelijk</button><button type="button" data-keuze="ja">Statistieken toestaan</button></div>';
    b.addEventListener('click', function (e) {
      var keuze = e.target.getAttribute('data-keuze');
      if (!keuze) return;
      bewaar(keuze);
      b.remove();
      if (keuze === 'ja') laadAnalytics(); else stopAnalytics();
    });
    document.body.appendChild(b);
  }

  if (lees() === 'ja') laadAnalytics();

  function start() {
    document.querySelectorAll('[data-cookie-instellingen]').forEach(function (k) {
      k.hidden = false;
      k.addEventListener('click', toonBalk);
    });
    if (!lees()) toonBalk();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
</script>
@endif
