#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$PWD}"
THEME_TAILWIND_DIR="${2:-$PROJECT_DIR/app/design/frontend/Uniekglas/hyva_child/web/tailwind}"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required command: $1" >&2
    exit 1
  }
}

need_cmd node
need_cmd npm

if [ ! -d "$THEME_TAILWIND_DIR" ]; then
  echo "Hyva Tailwind directory not found: $THEME_TAILWIND_DIR" >&2
  exit 1
fi

cd "$THEME_TAILWIND_DIR"

echo "Node: $(node -v)"
echo "NPM:  $(npm -v)"

if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

npm run build

echo "Hyva build completed."
