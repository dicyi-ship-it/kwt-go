"""HTTP integration checks. Use only a disposable, seeded test database/server.

Usage: python3 tests/http_smoke.py BASE_URL ADMIN_USERNAME < password-file
This creates visitor records and creates/updates/deletes a temporary service.
"""
import http.cookiejar
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class Page(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.csrf = None
        self.proof = None
        self.stats = []
        self.in_strong = False
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name') == 'csrf':
            self.csrf = attrs['value']
        if tag == 'meta' and attrs.get('name') == 'visit-proof':
            self.proof = attrs['content']
        if tag == 'strong':
            self.in_strong = True

    def handle_endtag(self, tag):
        if tag == 'strong':
            self.in_strong = False

    def handle_data(self, text):
        if self.in_strong and text.replace('.', '').isdigit():
            self.stats.append(int(text.replace('.', '')))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


class Client:
    def __init__(self, base):
        self.base = base.rstrip('/')
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies), NoRedirect())

    def request(self, path='', data=None, headers=None):
        headers = {'User-Agent': 'MinaGoIntegration/1.0', **(headers or {})}
        req = urllib.request.Request(self.base + '/' + path, data=None if data is None else urllib.parse.urlencode(data).encode(), headers=headers)
        try:
            response = self.opener.open(req, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.headers, response.read().decode()


def main():
    if os.environ.get('MINA_TEST_ALLOW_MUTATION') != '1' or len(sys.argv) != 3:
        raise SystemExit('Use a disposable database. Set MINA_TEST_ALLOW_MUTATION=1 and pass BASE_URL ADMIN_USERNAME; password through stdin.')
    base, username = sys.argv[1:]
    password = sys.stdin.readline().rstrip('\r\n')
    admin = Client(base)
    assert admin.request('admin/')[0] == 303
    status, _, html = admin.request('login.php')
    assert status == 200
    csrf = Page(html).csrf
    assert admin.request('login.php', {'username': username, 'password': password})[0] == 403
    assert admin.request('login.php', {'csrf': csrf, 'username': username, 'password': password})[0] == 303
    status, _, html = admin.request('admin/')
    assert status == 200
    assert len(Page(html).stats) == 3
    baseline = Page(html).stats
    csrf = Page(html).csrf
    assert not Page(admin.request()[2]).proof, 'Logged-in admin must not be tracked'
    visitor = Client(base)
    status, _, html = visitor.request()
    assert status == 200 and html.count('class="service-card"') == 21
    assert visitor.request('app/bootstrap.php')[0] == 404
    proof = Page(html).proof
    assert proof
    assert visitor.request('track.php')[0] == 405
    assert Client(base).request('track.php', {'proof': proof})[0] == 403
    assert visitor.request('track.php', {'proof': '0.invalid'})[0] == 403
    assert visitor.request('track.php', {'proof': proof})[0] == 204
    assert visitor.request('track.php', {'proof': proof})[0] == 204
    current = Page(admin.request('admin/')[2]).stats
    assert current == [n + 1 for n in baseline], (baseline, current)
    visitor2 = Client(base)
    proof2 = Page(visitor2.request()[2]).proof
    assert visitor2.request('track.php', {'proof': proof2}, {'User-Agent': 'Googlebot'})[0] == 204
    assert not Page(Client(base).request(headers={'User-Agent': 'Googlebot'})[2]).proof
    assert Page(admin.request('admin/')[2]).stats == current
    assert visitor2.request('track.php', {'proof': proof2})[0] == 204
    assert Page(admin.request('admin/')[2]).stats == [n + 2 for n in baseline]
    print('PASS: admin login, CSRF, private-path isolation, signed-cookie tracking, refresh deduplication, bot/admin exclusion, real database counts')

    label = '<script>alert("mina-test")</script>'
    payload = {'action': 'save', 'id': '', 'csrf': csrf, 'label': label, 'icon': 'chat', 'target_type': 'link', 'url': 'https://example.test/service?x=1&y=2', 'sort_order': 0, 'is_active': '1'}
    assert admin.request('admin/', {**payload, 'csrf': 'bad'})[0] == 403
    assert admin.request('admin/', {**payload, 'url': 'javascript:alert(1)'})[0] == 422
    assert admin.request('admin/', payload)[0] == 303
    html = visitor.request()[2]
    assert html.count('class="service-card"') == 22
    assert '&lt;script&gt;alert(&quot;mina-test&quot;)&lt;/script&gt;' in html and label not in html
    # Discover the created record through its real edit form, not SQL assumptions.
    class Links(HTMLParser):
        def __init__(self, source):
            super().__init__(); self.ids = []; self.feed(source)
        def handle_starttag(self, tag, attrs):
            href = dict(attrs).get('href', '')
            if tag == 'a' and href.startswith('?edit='):
                self.ids.append(urllib.parse.parse_qs(urllib.parse.urlsplit(href).query)['edit'][0])
    ids = Links(admin.request('admin/')[2]).ids
    created = max(ids, key=int)
    payload['id'] = created
    payload['label'] = 'Layanan sementara'
    message = 'Assalamualaikum, Ibu & RT+12?\nNama: 🌿 #Mina'
    wa = {**payload, 'target_type': 'whatsapp', 'wa_phone': '+62 (812) 3456-7890', 'wa_message': message}
    assert admin.request('admin/', {**wa, 'wa_phone': '0812abc345'})[0] == 422
    assert admin.request('admin/', {**wa, 'icon': '<svg>'})[0] == 422
    assert admin.request('admin/', {**wa, 'wa_message': 'x' * 2001})[0] == 422
    assert admin.request('admin/', wa)[0] == 303
    class Targets(HTMLParser):
        def __init__(self, source):
            super().__init__(); self.urls = []; self.feed(source)
        def handle_starttag(self, tag, attrs):
            attrs = dict(attrs)
            if tag == 'a' and attrs.get('class') == 'service-card': self.urls.append(attrs['href'])
    parsed = urllib.parse.urlsplit(Targets(visitor.request()[2]).urls[0])
    assert parsed.netloc == 'wa.me' and parsed.path == '/6281234567890'
    assert urllib.parse.parse_qs(parsed.query)['text'] == [message]
    assert 'name="wa_phone"' in admin.request('admin/?edit=' + created)[2]
    # Switching back to a website must use that destination, never stale WA fields.
    assert admin.request('admin/', payload)[0] == 303
    assert Targets(visitor.request()[2]).urls[0] == payload['url']
    assert admin.request('admin/', {**payload, 'is_active': '0'})[0] == 303
    assert visitor.request()[2].count('class="service-card"') == 21
    assert admin.request('admin/', payload)[0] == 303
    assert 'Layanan sementara' in visitor.request()[2]
    assert admin.request('admin/?delete=' + created)[0] == 200
    assert 'Layanan sementara' in visitor.request()[2], 'GET confirmation must not delete'
    assert admin.request('admin/', {'action': 'delete', 'id': created})[0] == 403
    assert admin.request('admin/', {'action': 'delete', 'id': created, 'csrf': csrf})[0] == 303
    assert visitor.request()[2].count('class="service-card"') == 21
    assert admin.request('admin/?edit=' + created)[0] == 404
    assert admin.request('admin/', {'action': 'logout', 'csrf': csrf})[0] == 303
    assert admin.request('admin/')[0] == 303
    print('PASS: create, escaped content, URL validation, update, active/inactive, delete confirmation, deletion, missing-record handling, logout')

    # Fixed-window login limit; intentionally run last on this isolated server.
    client = Client(base)
    csrf = Page(client.request('login.php')[2]).csrf
    statuses = [client.request('login.php', {'csrf': csrf, 'username': username, 'password': 'wrong-password'})[0] for _ in range(6)]
    assert statuses == [200] * 5 + [429], statuses
    print('PASS: database-backed login attempt limit')
    print(json.dumps({'status': 'passed', 'baseline_visitors': baseline, 'unique_visitors_added': 2, 'temporary_service_removed': True}))


if __name__ == '__main__':
    main()
