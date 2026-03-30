#!/usr/bin/env bash
set -euo pipefail

REPO="${1:-sanderdewit/uniekglas_hyva}"
TARGET_DIR="${2:-$HOME/sites/uniekglas_hyva}"

mkdir -p "$(dirname "$TARGET_DIR")"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required command: $1" >&2
    exit 1
  }
}

need_cmd gh
need_cmd git

if ! gh auth status >/dev/null 2>&1; then
  echo "GitHub CLI is not authenticated."
  echo "Running: gh auth login"
  gh auth login
fi

gh auth setup-git
gh auth status

if [ -d "$TARGET_DIR/.git" ]; then
  echo "Repo already exists: $TARGET_DIR"
else
  gh repo clone "$REPO" "$TARGET_DIR"
fi

echo "Clone step completed."
