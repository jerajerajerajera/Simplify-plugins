#!/usr/bin/env bash
# Runs every check that needs no panel: php -l, the translations and the Pelican Hub checks.
set -euo pipefail
cd "$(dirname "$0")/.."
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php tests/catalog.php
php tests/installer.php
php tests/lang.php
python3 tests/hub-check.py
