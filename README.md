# Blog Post Remover (Plugin Source)

If WordPress shows **"The package could not be installed"**, it is usually one of these:

1. You uploaded a source/repository ZIP instead of the plugin package ZIP.
2. The plugin folder already exists on the server.

## Correct install steps

1. Build the plugin package from this repo:
   ```bash
   ./build-plugin-zip.sh
   ```
2. Upload the generated file:
   - `blog-post-remover.zip`
   - in **WordPress Admin > Plugins > Add New > Upload Plugin**

## Quick troubleshooting

- If WordPress says destination exists, remove the old folder first:
  - `wp-content/plugins/blog-post-remover/`
- If you must upload the repository ZIP directly, this repo also includes a root plugin bootstrap (`blog-post-remover.php`) so WordPress can detect the plugin during install.

## Why this matters

WordPress expects a plugin ZIP that contains a plugin folder directly (`blog-post-remover/`) with `blog-post-remover.php` inside it.
The repository/source ZIP may include extra wrapper folders and metadata, which can trigger package install errors.
