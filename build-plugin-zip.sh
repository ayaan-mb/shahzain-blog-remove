#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR="blog-post-remover"
ZIP_NAME="blog-post-remover.zip"

if [[ ! -f "${PLUGIN_DIR}/blog-post-remover.php" ]]; then
  echo "Error: ${PLUGIN_DIR}/blog-post-remover.php was not found."
  exit 1
fi

rm -f "${ZIP_NAME}"
zip -r "${ZIP_NAME}" "${PLUGIN_DIR}" -x "*/.DS_Store" -x "*/__MACOSX/*"

echo "Created ${ZIP_NAME}"
echo "Upload this ZIP in WordPress: Plugins > Add New > Upload Plugin"
