#!/bin/sh
# Jalankan phpstan dengan output mentah ke file (bypass filter rtk untuk analisis)
cd /d/ifsu-cbt || exit 1
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G --error-format=raw > storage/phpstan-raw.txt 2> storage/phpstan-stderr.txt
echo "exit=$?"
wc -l < storage/phpstan-raw.txt
