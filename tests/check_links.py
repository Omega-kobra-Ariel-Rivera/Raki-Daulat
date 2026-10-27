#!/usr/bin/env python3
"""Verifica que todos los enlaces e imágenes locales de las páginas HTML existan.

Uso: python3 tests/check_links.py
"""
import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse, unquote

ROOT = Path(__file__).resolve().parent.parent
ATTRS = {"href", "src", "data-lightbox", "action", "data-endpoint"}
CSS_URL = re.compile(r"url\(\s*['\"]?([^'\")]+)['\"]?\s*\)")


class RefParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.refs, self.ids = [], set()

    def handle_starttag(self, tag, attrs):
        for name, value in attrs:
            if name == "id" and value:
                self.ids.add(value)
            if name in ATTRS and value:
                self.refs.append(value)


def is_local(ref):
    parsed = urlparse(ref)
    return not parsed.scheme and not ref.startswith("//")


def main():
    errors = []
    pages = sorted(ROOT.glob("*.html"))
    for page in pages:
        parser = RefParser()
        parser.feed(page.read_text(encoding="utf-8"))
        for ref in parser.refs:
            if not is_local(ref):
                continue
            path, _, fragment = ref.partition("#")
            if not path:
                if fragment and fragment not in parser.ids:
                    errors.append(f"{page.name}: ancla inexistente #{fragment}")
                continue
            target = (page.parent / unquote(path)).resolve()
            if not target.exists():
                errors.append(f"{page.name}: no existe '{ref}'")

    for css in sorted(ROOT.glob("assets/css/*.css")):
        for ref in CSS_URL.findall(css.read_text(encoding="utf-8")):
            if is_local(ref) and not (css.parent / ref).resolve().exists():
                errors.append(f"{css.relative_to(ROOT)}: no existe '{ref}'")

    if errors:
        print("Referencias rotas encontradas:")
        print("\n".join(f"  - {e}" for e in errors))
        return 1
    print(f"OK: {len(pages)} páginas revisadas, sin enlaces rotos.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
