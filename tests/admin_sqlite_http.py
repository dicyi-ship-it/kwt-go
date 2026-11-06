"""Run against a disposable SQLite copy: MINA_TEST_ALLOW_MUTATION=1 python tests/admin_sqlite_http.py URL USER < password-file."""
import os
import re
import sys
from http_smoke import Client, Page

if os.environ.get('MINA_TEST_ALLOW_MUTATION') != '1' or len(sys.argv) != 3:
    raise SystemExit('Use a disposable database and MINA_TEST_ALLOW_MUTATION=1; password through stdin.')

base, username = sys.argv[1:]
password = sys.stdin.readline().rstrip('\r\n')
admin = Client(base)
for path in ['admin/', 'admin/structure.php', 'admin/umkm.php']:
    assert admin.request(path)[0] == 303
csrf = Page(admin.request('login.php')[2]).csrf
assert admin.request('login.php', {'username': username, 'password': password})[0] == 403
assert admin.request('login.php', {'csrf': csrf, 'username': username, 'password': password})[0] == 303
assert admin.request('admin/')[0] == 200
public_services = Client(base).request('?layanan=permasyarakatan')[2]
admin_services = admin.request('admin/?layanan=permasyarakatan')[2]
for logo, count in [('kota-tangerang.png',1),('polri.png',1),('pmi.svg',2),('damkar.png',1),('puskesmas-panunggangan-barat.jpg',1)]:
    assert public_services.count('/assets/' + logo) == count
    assert admin_services.count('/assets/' + logo) == count + 1, 'Admin includes the reusable logo picker'
assert 'Telepon &middot; 112' in admin_services and 'Telepon &middot; 110' in admin_services
assert 'Tidak tampil di halaman publik' in admin_services
dashboard = admin.request('admin/')[2]
assert dashboard.count('>Kelola layanan</a>') == 3
assert 'id="service-form"' not in dashboard and '?edit=' not in dashboard
assert '+ Tambah tombol' not in dashboard
assert 'id="service-form"' not in admin.request('admin/?edit=1')[2]
assert admin.request('admin/', {'csrf': Page(dashboard).csrf, 'action': 'save'})[0] == 400
assert admin.request('admin/', {'csrf': Page(dashboard).csrf, 'action': 'delete', 'id': '1'})[0] == 400

assert not Page(admin.request()[2]).proof

path = 'admin/?layanan=administrasi'
csrf = Page(admin.request(path)[2]).csrf
contact = {'action': 'save_structure', 'csrf': csrf, 'rt1_name': '<Pak RT Uji>', 'rt1': '081234567890'}
assert admin.request(path, {**contact, 'csrf': 'bad'})[0] == 403
assert admin.request(path, {**contact, 'rt1': 'invalid'})[0] == 422
assert admin.request(path, {**contact, 'rt1_name': 'x' * 121})[0] == 422
assert admin.request(path, contact)[0] == 303
public = Client(base)
html = public.request('?layanan=administrasi')[2]
assert '&lt;Pak RT Uji&gt;' in html and '<Pak RT Uji>' not in html
assert 'https://wa.me/6281234567890' in html
rt_cards = re.findall(r'<article class="rt-card".*?</article>', html, re.S)
assert len(rt_cards) == 6
assert '&lt;Pak RT Uji&gt;' in rt_cards[0] and '081234567890' in rt_cards[0]
assert all('Sekretaris RT' not in card and 'Bendahara RT' not in card and '<details' not in card for card in rt_cards)
letters = '?layanan=administrasi&persuratan=1'
administration = public.request('?layanan=administrasi')[2]
access_services = administration.split('id="services-title"', 1)[1].split('</section>', 1)[0]
assert '>Persuratan</span>' in access_services
assert 'persuratan=1' in access_services
assert 'letters-entry-title' not in administration
assert 'Pengantar Umum' in public.request(letters)[2]
assert 'Kontak belum tersedia' in public.request(letters + '&surat=domisili')[2]
assert 'Pilih RT 1' in public.request(letters + '&surat=domisili')[2]
status, _, letter_html = public.request(letters + '&surat=domisili&rt=rt1')
assert status == 200 and '&lt;Pak RT Uji&gt;' in letter_html
from html import unescape
from urllib.parse import urlsplit, parse_qs
wa = unescape(re.search(r'href="(https://wa.me/[^"]+)"', letter_html).group(1))
assert urlsplit(wa).path == '/6281234567890'
message = parse_qs(urlsplit(wa).query)['text'][0]
assert 'warga RT 1' in message and 'Keterangan Domisili' in message
assert 'Buka WhatsApp Pak RT' not in public.request(letters + '&surat=domisili&rt=rt6')[2]
for query in ['&surat[]=umum', '&surat=bad', '&surat=umum&rt[]=rt1', '&surat=umum&rt=rt7']:
    status, _, invalid = public.request(letters + query)
    assert status == 200 and 'Buka WhatsApp Pak RT' not in invalid
print('PASS: public letter flow, RT contact routing, message encoding, unavailable contacts, invalid parameters')

assert admin.request(path, {**contact, 'rt1_name': 'Pak RT Baru'})[0] == 303
assert 'Pak RT Baru' in public.request('?layanan=administrasi')[2]
assert admin.request(path, {'csrf': csrf, 'action': 'save_structure'})[0] == 303
assert 'Pak RT Baru' not in public.request('?layanan=administrasi')[2]
print('PASS: protected admin, login, CSRF, RT create/read/update/delete, validation, HTML escaping')

path = 'admin/umkm.php'
csrf = Page(admin.request(path)[2]).csrf
business = {'csrf': csrf, 'action': 'save', 'id': '', 'name': 'UMKM Uji SQLite', 'category': 'Kuliner', 'description': 'Usaha pengujian', 'address': 'Alamat uji', 'wa_phone': '081234567890'}
assert admin.request(path, {**business, 'csrf': 'bad'})[0] == 403
assert admin.request(path, {**business, 'wa_phone': 'invalid'})[0] == 422
assert admin.request(path, business)[0] == 303
html = admin.request(path)[2]
created = str(max(map(int, re.findall(r'\?edit=(\d+)', html))))
assert business['name'] in public.request('?layanan=ekonomi&direktori=umkm')[2]
assert admin.request(path, {**business, 'id': created, 'name': 'UMKM Diperbarui'})[0] == 303
assert 'UMKM Diperbarui' in public.request('?layanan=ekonomi&direktori=umkm')[2]
assert admin.request(path + '?delete=' + created)[0] == 200
assert 'UMKM Diperbarui' in public.request('?layanan=ekonomi&direktori=umkm')[2]
assert admin.request(path, {'csrf': csrf, 'action': 'delete', 'id': created})[0] == 303
assert admin.request(path + '?edit=' + created)[0] == 404
print('PASS: UMKM create/read/update/delete, confirmation, validation, CSRF')

before = Page(admin.request('admin/')[2]).stats
visitor = Client(base)
proof = Page(visitor.request()[2]).proof
assert proof
assert visitor.request('track.php', {'proof': proof})[0] == 204
assert visitor.request('track.php', {'proof': proof})[0] == 204
assert Page(admin.request('admin/')[2]).stats == [n + 1 for n in before]
print('PASS: SQLite visitor upsert and daily deduplication')

# Admin channels use the same classification as public pages and retain CRUD context.
path = 'admin/?layanan=administrasi'
html = admin.request(path)[2]
assert '<dialog class="service-dialog"' in html and 'data-auto-open="0"' in html
assert 'data-auto-open="1"' in admin.request(path + '&tambah=1')[2]
assert 'Pengurus RW dan RT</summary>' in html and 'Kontak Layanan Administrasi</summary>' in html and 'Kelola Daftar UMKM' not in html
assert 'Kelola Daftar UMKM' in admin.request('admin/?layanan=ekonomi')[2]
assert admin.request('admin/?layanan[]=administrasi')[0] == 200
csrf = Page(html).csrf
service = {'csrf': csrf, 'action': 'save', 'id': '', 'label': 'Kontak Administrasi Uji', 'icon': 'chat', 'target_type': 'whatsapp', 'wa_phone': '081234567890', 'wa_message': 'Halo', 'sort_order': '1', 'is_active': '1'}
status, headers, _ = admin.request(path, service)
assert status == 303 and 'layanan=administrasi' in headers['Location']
html = admin.request(path)[2]
created = str(max(map(int, re.findall(r'edit=(\d+)', html))))
assert 'Kontak Administrasi Uji' in html
assert 'Kontak Administrasi Uji' in public.request('?layanan=administrasi')[2]
for channel in ['ekonomi', 'permasyarakatan']:
    assert 'Kontak Administrasi Uji' not in admin.request('admin/?layanan=' + channel)[2]
assert admin.request(path + '&edit=' + created)[0] == 200
assert 'data-auto-open="1"' in admin.request(path + '&edit=' + created)[2]
status, _, invalid = admin.request(path, {**service, 'wa_phone': 'invalid'})
assert status == 422 and 'data-auto-open="1"' in invalid and service['label'] in invalid
assert admin.request(path, {**service, 'id': created, 'label': 'Kontak Diubah'})[0] == 303
assert 'Kontak Diubah' in public.request('?layanan=administrasi')[2]
assert admin.request(path + '&delete=' + created)[0] == 200
status, headers, _ = admin.request(path, {'csrf': csrf, 'action': 'delete', 'id': created})
assert status == 303 and 'layanan=administrasi' in headers['Location']
assert 'Kontak Diubah' not in admin.request(path)[2]
print('PASS: admin/public channel grouping, channel-specific service CRUD and redirects')

path = 'admin/?layanan=permasyarakatan'
csrf = Page(admin.request(path)[2]).csrf
logo_service = {'csrf': csrf, 'action': 'save', 'id': '', 'label': 'Template Logo Uji', 'icon': 'logo_pmi', 'target_type': 'whatsapp', 'wa_phone': '081234567890', 'wa_message': 'Uji', 'sort_order': '1', 'is_active': '1'}
assert admin.request(path, logo_service)[0] == 303
logo_id = str(max(map(int, re.findall(r'edit=(\d+)', admin.request(path)[2]))))
for icon, asset in [('logo_pmi','pmi.svg'),('logo_damkar','damkar.png')]:
    assert admin.request(path, {**logo_service, 'id': logo_id, 'icon': icon})[0] == 303
    html = public.request('?layanan=permasyarakatan')[2]
    card = next(card for card in re.findall(r'<a class="service-card".*?</a>', html, re.S) if 'Template Logo Uji' in card)
    assert asset in card and 'wa.me/6281234567890' in card
    edit = admin.request(path + '&edit=' + logo_id)[2]
    assert f'value="{icon}" checked' in edit
assert admin.request(path, {'csrf':csrf,'action':'delete','id':logo_id})[0] == 303
print('PASS: reusable logos persist through create/edit with arbitrary contact numbers')


client = Client(base)
csrf = Page(client.request('login.php')[2]).csrf
statuses = [client.request('login.php', {'csrf': csrf, 'username': username, 'password': 'wrong'})[0] for _ in range(6)]
assert statuses == [200] * 5 + [429], statuses
print('PASS: SQLite login attempt limit')
