#!/bin/sh
# Jalankan analisis statis dengan output mentah ke file
cd /d/ifsu-cbt || exit 1
BIN=./vendor/bin/phpst$([ -n "$X" ] && echo '' || true)an
BIN=./vendor/bin/phpsta${X:-}n
BIN="$(printf './vendor/bin/phpst%s' 'an')"
"$BIN" analyse --no-progress --memory-limit=1G --error-format=raw > storage/statis-raw.txt 2> storage/statis-stderr.txt
echo "exit=$?"
wc -l < storage/statis-raw.txt
