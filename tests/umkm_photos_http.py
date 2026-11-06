"""Self-contained HTTP test: disposable SQLite database, account, and photo directory."""
import os
import json
import struct
from contextlib import closing
from pathlib import Path
import re
import secrets
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

from http_smoke import Client, Page

root = Path(__file__).resolve().parents[1]
business_id = 4000000000
photos = root / 'storage' / 'umkm' / str(business_id)
sample_photos = root / 'storage' / 'umkm' / '3999999999'
assert not photos.exists(), 'Reserved test photo directory already exists; refusing to touch it'
assert not sample_photos.exists(), 'Reserved sample test directory already exists; refusing to touch it'
png = subprocess.check_output(['php'], input=b'<?php $im = imagecreatetruecolor(2, 2); imagepng($im);')


def multipart(client, fields, images):
    boundary = secrets.token_hex(16)
    body = bytearray()
    for name, value in fields.items():
        body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
    for filename, data in images:
        body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="photos[]"; filename="{filename}"\r\nContent-Type: image/png\r\n\r\n'.encode())
        body.extend(data + b'\r\n')
    body.extend(f'--{boundary}--\r\n'.encode())
    request = urllib.request.Request(client.base + '/admin/umkm.php', data=body,
                                    headers={'Content-Type': f'multipart/form-data; boundary={boundary}'})
    try:
        response = client.opener.open(request, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    return response.code, response.read().decode()


with tempfile.TemporaryDirectory(prefix='mina-photos-') as temp:
    temp = Path(temp)
    database = temp / 'test.sqlite'
    password = secrets.token_hex(16)
    script = '<?php echo password_hash(' + repr(password) + ', PASSWORD_DEFAULT);'
    password_hash = subprocess.check_output(['php'], input=script.encode()).decode()
    with sqlite3.connect(database) as connection:
        connection.executescript((root / 'database/schema.sqlite.sql').read_text())
        connection.execute('INSERT INTO admins (username,password_hash) VALUES (?,?)', ('photo-test', password_hash))
        connection.execute('INSERT INTO umkm (id,name,category,description,address,wa_phone) VALUES (?,?,?,?,?,?)',
                           (business_id, '<Usaha Uji>', 'Kuliner', 'Deskripsi', 'RW 12', '6281294345693'))
        connection.execute('INSERT INTO umkm (id,name,category,description,address,is_sample) VALUES (?,?,?,?,?,1)',
                           (3999999999, 'Dapur Mina Rasa', 'Kuliner', 'Deskripsi', 'RW 12'))
    connection.close()
    with socket.socket() as listener:
        listener.bind(('127.0.0.1', 0))
        port = listener.getsockname()[1]
    base = f'http://127.0.0.1:{port}'
    config = temp / 'config.php'
    config.write_text("<?php return ['db'=>['driver'=>'sqlite','path'=>'" + database.as_posix()
                      + "'],'app_key'=>'" + secrets.token_hex(32) + "','base_url'=>'" + base + "'];")
    environment = {**os.environ, 'MINA_CONFIG': str(config)}
    subprocess.run(['php', str(root / 'scripts/migrate-umkm-photos.php'), '--import-existing'], env=environment, cwd=root, check=True)
    subprocess.run(['php', str(root / 'scripts/migrate-umkm-photos.php'), '--import-existing'], env=environment, cwd=root, check=True)
    with (temp / 'server.log').open('w+') as log:
        server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(root / 'public')],
                                  env=environment, stdout=log, stderr=log,
                                  creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        try:
            public = Client(base)
            for attempt in range(50):
                try:
                    if public.request('login.php')[0] == 200:
                        break
                except urllib.error.URLError:
                    time.sleep(.1)
            else:
                raise AssertionError('Test server failed to start')
            detail = f'?layanan=ekonomi&direktori=umkm&usaha={business_id}'
            assert 'usaha=' + str(business_id) in public.request('?layanan=ekonomi&direktori=umkm')[2]
            assert 'Belum ada foto usaha.' in public.request(detail)[2]
            assert public.request(detail.replace(str(business_id), '999999'))[0] == 404
            assert public.request('?layanan=ekonomi&direktori=umkm&usaha[]=1')[0] == 400
            fields = {'action': 'upload_photos', 'id': business_id}
            assert multipart(public, fields, [('photo.png', png)])[0] == 303
            admin = Client(base)
            csrf = Page(admin.request('login.php')[2]).csrf
            assert admin.request('login.php', {'csrf': csrf, 'username': 'photo-test', 'password': password})[0] == 303
            listing = admin.request('admin/umkm.php')[2]
            assert 'umkm-admin-cover' in listing and 'Kelola foto' in listing
            assert 'id="umkm-add"' in listing and '<dialog class="service-dialog umkm-dialog"' in listing
            assert re.search(r'id="umkm-form"[^>]+data-auto-open="0"', listing)
            assert re.search(r'id="umkm-form"[^>]+data-auto-open="1"', admin.request('admin/umkm.php?tambah=1')[2])
            assert re.search(r'id="umkm-form"[^>]+data-auto-open="1"', admin.request(f'admin/umkm.php?edit={business_id}&form=1')[2])
            sample_edit = admin.request('admin/umkm.php?edit=3999999999')[2]
            assert sample_edit.count('class="umkm-photo-preview"') == 5
            assert re.search(r'id="umkm-form"[^>]+data-auto-open="0"', sample_edit), 'Managing photos must not open the business form'
            assert sample_edit.index('id="umkm-photos"') < sample_edit.index('id="umkm-form"')
            assert sample_edit.count('>Hapus foto</button>') == 5
            sample_files = sorted(sample_photos.glob('*.jpg'))
            assert len(sample_files) == 5
            sample_csrf = Page(sample_edit).csrf
            sample_fields = {'id': 3999999999, 'csrf': sample_csrf}
            with closing(sqlite3.connect(database)) as conn:
                sample_order = [row[0] for row in conn.execute('SELECT filename FROM umkm_photos WHERE umkm_id=3999999999 ORDER BY sort_order,id')]
            chosen = sample_order[-1]
            assert admin.request('admin/umkm.php', {**sample_fields, 'action': 'photo_cover', 'photo': chosen})[0] == 303
            with closing(sqlite3.connect(database)) as conn:
                assert conn.execute('SELECT filename FROM umkm_photos WHERE umkm_id=3999999999 ORDER BY sort_order,id LIMIT 1').fetchone()[0] == chosen
            sample_detail = '?layanan=ekonomi&direktori=umkm&usaha=3999999999'
            assert chosen in re.findall(r'<img[^>]+src="([^"]+)"', public.request(sample_detail)[2])[0]
            assert admin.request('admin/umkm.php', {**sample_fields, 'action': 'photo_down', 'photo': chosen})[0] == 303
            with closing(sqlite3.connect(database)) as conn:
                assert list(conn.execute('SELECT filename FROM umkm_photos WHERE umkm_id=3999999999 ORDER BY sort_order,id'))[1][0] == chosen
            assert admin.request('admin/umkm.php', {**sample_fields, 'action': 'photo_up', 'photo': chosen})[0] == 303
            assert admin.request('admin/umkm.php', {**sample_fields, 'action': 'photo_cover', 'photo': 'a' * 32 + '.jpg'})[0] == 422
            assert admin.request('admin/umkm.php', {'id': business_id, 'csrf': sample_csrf, 'action': 'delete_photo', 'photo': chosen})[0] == 422
            assert len(list(sample_photos.glob('*.jpg'))) == 5
            for photo in sample_order:
                assert admin.request('admin/umkm.php', {**sample_fields, 'action': 'delete_photo', 'photo': photo})[0] == 303
                assert public.request(f'umkm-photo.php?usaha=3999999999&foto={photo}')[0] == 404
            assert not list(sample_photos.glob('*.jpg'))
            assert 'Belum ada foto usaha.' in public.request(sample_detail)[2], 'Deleted samples must never reappear'
            csrf = Page(admin.request(f'admin/umkm.php?edit={business_id}')[2]).csrf
            fields['csrf'] = csrf
            def inspect_photo(path):
                script = '<?php $im=imagecreatefromjpeg(' + json.dumps(path.as_posix()) + '); $w=imagesx($im);$h=imagesy($im);$pixels=[];foreach([[2,2],[$w-3,2],[2,$h-3],[$w-3,$h-3]] as [$x,$y]){$c=imagecolorat($im,$x,$y);$pixels[]=[($c>>16)&255,($c>>8)&255,$c&255];}echo json_encode([$w,$h,$pixels]);'
                return json.loads(subprocess.check_output(['php'], input=script.encode()))
            jpeg_fixture = subprocess.check_output(['php'], input=b'<?php $im=imagecreatetruecolor(40,20);foreach([[0,0,19,9,255,0,0],[20,0,39,9,0,255,0],[0,10,19,19,0,0,255],[20,10,39,19,255,255,0]] as [$x,$y,$r,$b,$red,$green,$blue]){imagefilledrectangle($im,$x,$y,$r,$b,imagecolorallocate($im,$red,$green,$blue));}imagejpeg($im,null,100);')
            colors = [(255,0,0),(0,255,0),(0,0,255),(255,255,0)]
            corner_orders = [(0,1,2,3),(1,0,3,2),(3,2,1,0),(2,3,0,1),(0,2,1,3),(2,0,3,1),(3,1,2,0),(1,3,0,2)]
            for orientation, corners in enumerate(corner_orders, 1):
                exif = b'Exif\0\0II' + struct.pack('<HIH',42,8,1) + struct.pack('<HHIH',274,3,1,orientation) + b'\0\0' + struct.pack('<I',0)
                oriented = jpeg_fixture[:2] + b'\xff\xe1' + struct.pack('>H',len(exif)+2) + exif + jpeg_fixture[2:]
                assert multipart(admin, fields, [('phone.jpg', oriented)])[0] == 303
                path = next(photos.glob('*.jpg'))
                width, height, pixels = inspect_photo(path)
                assert (width,height) == ((40,20) if orientation < 5 else (20,40)), orientation
                for pixel, corner in zip(pixels,corners):
                    assert max(abs(a-b) for a,b in zip(pixel,colors[corner])) < 50, (orientation,pixel,corner)
                assert admin.request('admin/umkm.php', {'csrf':csrf,'id':business_id,'action':'delete_photo','photo':path.name})[0] == 303
            transparent = subprocess.check_output(['php'],input=b'<?php $im=imagecreatetruecolor(32,16);imagealphablending($im,false);imagesavealpha($im,true);imagefill($im,0,0,imagecolorallocatealpha($im,0,0,0,127));imagefilledrectangle($im,0,0,7,15,imagecolorallocatealpha($im,0,180,0,0));imagepng($im);')
            assert multipart(admin,fields,[('product.png',transparent)])[0] == 303
            path = next(photos.glob('*.jpg'))
            assert min(inspect_photo(path)[2][1]) > 240, 'Transparent PNG must have a white background'
            assert admin.request('admin/umkm.php', {'csrf':csrf,'id':business_id,'action':'delete_photo','photo':path.name})[0] == 303
            status, _, invalid_form = admin.request('admin/umkm.php', {'csrf': csrf, 'action': 'save', 'id': business_id, 'name': 'Isian tetap terlihat'})
            assert status == 422 and 'value="Isian tetap terlihat"' in invalid_form
            assert re.search(r'id="umkm-form"[^>]+data-auto-open="1"', invalid_form)
            assert multipart(admin, {**fields, 'csrf': 'bad'}, [('photo.png', png)])[0] == 403
            status, upload_html = multipart(admin, fields, [('one.png', png), ('payload.php', png + b'<?php echo 1;')])
            assert status == 303, upload_html
            assert len(list(photos.glob('*.jpg'))) == 2
            uploaded_edit = admin.request(f'admin/umkm.php?edit={business_id}')[2]
            assert uploaded_edit.count('class="umkm-photo-preview"') == 2
            assert uploaded_edit.count('>Hapus foto</button>') == 2
            html = public.request(detail)[2]
            assert '&lt;Usaha Uji&gt;' in html and '<Usaha Uji>' not in html
            image_paths = re.findall(r'href="([^"]*umkm-photo.php[^"]+)"', html)
            assert len(image_paths) == 2
            with urllib.request.urlopen(image_paths[0].replace('&amp;', '&')) as image:
                assert image.headers['Content-Type'] == 'image/jpeg'
                image_data = image.read()
                assert image_data.startswith(b'\xff\xd8') and b'<?php' not in image_data
            assert public.request(f'umkm-photo.php?usaha={business_id}&foto=../config.local.php')[0] == 404
            assert multipart(admin, fields, [('good.png', png), ('fake.png', b'<?php echo 1;')])[0] == 422
            assert len(list(photos.glob('*.jpg'))) == 2, 'Partial uploads must be rolled back'
            with closing(sqlite3.connect(database)) as conn:
                assert conn.execute('SELECT COUNT(*) FROM umkm_photos WHERE umkm_id=?', (business_id,)).fetchone()[0] == 2
            assert multipart(admin, fields, [('big.png', png + b'0' * (5 * 1024 * 1024))])[0] == 422
            large_png = subprocess.check_output(['php'], input=b'<?php $im = imagecreatetruecolor(3000, 3000); imagepng($im);')
            assert multipart(admin, fields, [('large.png', large_png)])[0] == 422
            assert multipart(admin, fields, [('photo.png', png)] * 5)[0] == 422
            assert len(list(photos.glob('*.jpg'))) == 2
            jpeg = subprocess.check_output(['php'], input=b'<?php $im = imagecreatetruecolor(2, 2); imagejpeg($im);')
            webp = subprocess.check_output(['php'], input=b'<?php $im = imagecreatetruecolor(2, 2); imagewebp($im);')
            assert multipart(admin, fields, [('photo.png', png)] * 2 + [('photo.jpg', jpeg), ('photo.webp', webp)])[0] == 303
            assert len(list(photos.glob('*.jpg'))) == 6
            assert multipart(admin, fields, [('photo.png', png)])[0] == 422
            photo = next(photos.glob('*.jpg')).name
            delete = {'action': 'delete_photo', 'id': business_id, 'csrf': csrf, 'photo': photo}
            assert admin.request('admin/umkm.php', {**delete, 'photo': '../anything'})[0] == 422
            assert admin.request('admin/umkm.php', delete)[0] == 303
            assert len(list(photos.glob('*.jpg'))) == 5
            assert public.request(f'umkm-photo.php?usaha={business_id}&foto={photo}')[0] == 404
            assert admin.request('admin/umkm.php', {'action': 'delete', 'id': business_id, 'csrf': csrf})[0] == 303
            assert not list(photos.glob('*.jpg'))
            assert public.request(detail)[0] == 404
            print('PASS: eight EXIF orientations, transparent background, SQL photos, order/cover, ownership, sample deletion, uploads, rollback, limits and CSRF')
        finally:
            server.terminate()
            server.wait(timeout=10)
            if photos.exists():
                shutil.rmtree(photos)
            if sample_photos.exists():
                shutil.rmtree(sample_photos)
