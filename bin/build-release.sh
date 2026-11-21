#!/usr/bin/env bash
# Builds two upload-ready archives from the committed files (no tests, no secrets, no local data):
#   release/app.zip     -> extract into the private application folder (NOT web accessible)
#   release/public.zip  -> extract into the domain's public folder (document root)
# Usage: bash bin/build-release.sh
set -eu
cd "$(dirname "$0")/.."
command -v zip >/dev/null || { echo "zip is not installed"; exit 1; }
rm -rf release && mkdir release
FILES=$(git ls-files)
echo "$FILES" | grep -vE '^(public/|tests/|AGENTS\.md$|\.gitignore$)' | grep -vE '^storage/.*\.(log)$' > release/app.list
echo "$FILES" | grep -E '^public/' | sed 's#^public/##' > release/public.list
zip -q release/app.zip -@ < release/app.list
( cd public && zip -q ../release/public.zip -@ < ../release/public.list )
rm release/app.list release/public.list
echo "Built:"; ls -l release/*.zip | awk '{print "  " $5 " bytes  " $9}'
echo "Next: follow DEPLOYMENT.md section 'First deployment'."
