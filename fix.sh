# from Magento root
find pub/media/catalog/product -type f -name '*-291-61-jpg_1.jpg' -print0 |
while IFS= read -r -d '' f; do
  dir=${f%/*}
  base=${f##*/}
  new=${base#-}                 # strip only the first leading dash
  src="$f"
  dst="$dir/$new"

  echo "Renaming:"
  echo "  SRC: $src"
  echo "  DST: $dst"

  # if cross-device or permissions bite, fall back to copy+remove
  if ! mv "$src" "$dst" 2>/dev/null; then
    # try with sudo (common on servers)
    if ! sudo mv "$src" "$dst" 2>/dev/null; then
      if cp -p "$src" "$dst" 2>/dev/null && rm -f "$src"; then
        echo "  (did copy+remove)"
      elif sudo cp -p "$src" "$dst" 2>/dev/null && sudo rm -f "$src"; then
        echo "  (did sudo copy+remove)"
      else
        echo "  ERROR: failed to move $src -> $dst" >&2
      fi
    fi
  fi
done

