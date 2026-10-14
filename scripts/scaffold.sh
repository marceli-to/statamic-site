#!/usr/bin/env bash
# Lays the statamic-site scaffold over a fresh `composer create-project
# statamic/statamic` install. Usage: scaffold.sh <project-dir> <domain> "<Site name>"
set -euo pipefail

DIR=${1:?project dir}
DOMAIN=${2:?domain, e.g. example.ch}
NAME=${3:?site name}
SKILL="$(cd "$(dirname "$0")/.." && pwd)"

[ -f "$DIR/please" ] || { echo "$DIR is not a Statamic install" >&2; exit 1; }

# Replaced by the scaffold's app.css / app.js / default template.
rm -f "$DIR/resources/css/site.css" "$DIR/resources/js/site.js" \
	"$DIR/resources/views/home.antlers.html" "$DIR/content/collections/pages/home.md"

cp -R "$SKILL/scaffold/." "$DIR/"

# Placeholders, in the files that carry them.
grep -rlE '__(DOMAIN|SITE_NAME)__' "$DIR/resources" "$DIR/content" "$DIR/public/robots.txt" | while read -r f; do
	DOMAIN="$DOMAIN" NAME="$NAME" perl -pi -e 's/__DOMAIN__/$ENV{DOMAIN}/g; s/__SITE_NAME__/$ENV{NAME}/g' "$f"
done

php "$SKILL/scripts/patch.php" "$DIR" "$DOMAIN"

cd "$DIR"
composer require --dev friendsofphp/php-cs-fixer --no-interaction --quiet
npm install --ignore-scripts --silent
php artisan migrate --force --quiet
php artisan vendor:publish --tag=statamic-cp --force --quiet
npm run build --silent
npm run cp:build --silent
# Laravel's stock PHP files are space-indented; start the project on the house style.
composer lint:fix --quiet
php please stache:clear --quiet

echo "Scaffolded $DOMAIN in $DIR"
