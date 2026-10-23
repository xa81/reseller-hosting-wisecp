# Değişiklik Günlüğü / Changelog

## WiseCP 3 — 1.1.0

**Türkçe**

- Sunucunun Hostname alanı tam bir sunucu adıysa bağlantı ona yapılıyor ve TLS sertifikası doğrulanıyor
- Hesap şifreleri ve token'lar URL-kodlu ya da XML-kaçışlı halleriyle de loglardan maskeleniyor
- cPanel'de her işlemden önce hesabın bu hizmetin alan adını sunduğu doğrulanıyor
- Durum bilgisi olmayan bir WHM yanıtı artık başarı sayılmıyor
- Trafik sorgusu yalnızca ilgili hesabı istiyor; bayinin diğer hesapları loglara düşmüyor
- Plesk tek oturum açmada istemci adresi Plesk'in beklediği biçimde gönderiliyor; sahiplik kontrolü burada da çalışıyor
- Plesk disk kullanımı doğru alandan okunuyor; aboneliği kalmayan müşteri sonlandırmada siliniyor
- Bütün mesajlar dil dosyalarına taşındı (İngilizce ve Türkçe)

**English**

- When the server's Hostname is a full host name the module connects to it and verifies the TLS certificate
- Account passwords and tokens are masked in the logs in their URL-encoded and XML-escaped forms too
- On cPanel every operation first checks that the account serves this service's domain
- A WHM reply without a status block is no longer counted as success
- The bandwidth query asks for this account only; the reseller's other accounts no longer reach the logs
- Plesk single sign-on sends the client address the way Plesk expects it, and runs the ownership check too
- Plesk disk usage is read from the right field; a customer left without subscriptions is removed on termination
- Every message moved to the language files (English and Turkish)

## WiseCP 5 — 2.0.0

**Türkçe — WiseCP 5 için yeniden yazıldı.**

- Depo iki kuşağa ayrıldı: `v5/coremio/` WiseCP 5, `v3/coremio/` WiseCP 3; her sürüm iki ayrı paketle yayınlanır
- WiseCP 5 paketi `manifest.json` taşır; pazar yerinden güncelleme alır
- Modül WiseCP 5 sunucu modülü sözleşmesine taşındı: `create`, `suspend`, `unsuspend`, `cancel`, `change_password`, `upgrade`, `sso_panel_login`, `dashboard_data`, `metrics_usage`
- Hatalar artık istisna olarak bildiriliyor; panel yanıtı bozuk geldiğinde bile işlem başarılı sayılmıyor
- TLS doğrulaması sunucu formundaki **Verify SSL Certificate** anahtarına bağlandı
- Kimlik bilgisi yalnızca şifreli saklanan **Password** alanından okunuyor; düz metin saklanan **Access Hash** alanı kullanılmıyor
- Hesap açma yeniden denemeye dayanıklı: kullanıcı adı ve şifre panel çağrısından önce kaydediliyor, yanıtı kaybolan hesap devralınıyor
- Plesk'te sahiplik doğrulaması her işlemde, tek oturum açma dahil, çalışıyor
- Hizmet genel bakışında disk ve trafik göstergeleri, hesap bilgileri ve tek tıkla panel girişi
- Bütün mesajlar dil dosyalarına taşındı (İngilizce ve Türkçe)
- Plesk disk kullanımı doğru alandan (`gen_info/real_size`) okunuyor
- Aboneliği kalmayan Plesk müşterisi sonlandırmada artık siliniyor

**English — Rewritten for WiseCP 5.**

- The repository is split by generation: `v5/coremio/` for WiseCP 5, `v3/coremio/` for WiseCP 3; every release ships two packages
- The WiseCP 5 package carries `manifest.json`, so it receives updates from the marketplace
- The module moved to the WiseCP 5 server module contract: `create`, `suspend`, `unsuspend`, `cancel`, `change_password`, `upgrade`, `sso_panel_login`, `dashboard_data`, `metrics_usage`
- Failures are reported as exceptions; a malformed panel response is never counted as success
- TLS verification follows the **Verify SSL Certificate** switch on the server form
- The credential is read from the **Password** field only, which WiseCP stores encrypted; the plain-text **Access Hash** field is not used
- Account creation is safe to retry: the username and password are saved before the panel call, and an account whose response was lost is adopted
- On Plesk the ownership check runs before every operation, single sign-on included
- Disk and bandwidth gauges, account details and one-click panel login on the service overview
- Every message moved to the language files (English and Turkish)
- Plesk disk usage is read from the right field (`gen_info/real_size`)
- A Plesk customer left without subscriptions is now removed on termination

## WiseCP 3 — 1.0.0

**Türkçe — İlk sürüm.**

- cPanel/WHM ve Plesk için tek modül, panel otomatik tespit ediliyor
- Hesap açma, askıya alma, askıyı kaldırma, sonlandırma
- Şifre değiştirme, paket değiştirme
- Disk ve trafik kullanımı
- Müşteri ve yönetici tek tıkla panel girişi
- Sonlandırmada mükerrer alan adı koruması
- Plesk'te external-id ile sahiplik doğrulaması

**English — Initial release.**

- A single module for cPanel/WHM and Plesk, with automatic panel detection
- Account creation, suspend, unsuspend, termination
- Password change, package change
- Disk and bandwidth usage
- One-click panel login for both client and admin
- Duplicate domain protection on termination
- Ownership verification on Plesk through an external id
