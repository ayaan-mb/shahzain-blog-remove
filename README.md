# Blog Post Remover (Plugin Source)

If WordPress shows **"The package could not be installed"**, you are likely uploading the repository ZIP instead of the plugin ZIP.

## Correct install steps

1. Build the plugin package from this repo:
   ```bash
   ./build-plugin-zip.sh
   ```
2. Upload the generated file:
   - `blog-post-remover.zip`
   - in **WordPress Admin > Plugins > Add New > Upload Plugin**

## Why this matters

WordPress expects a plugin ZIP that contains a plugin folder directly (`blog-post-remover/`) with `blog-post-remover.php` inside it.
The repository/source ZIP may include extra wrapper folders and metadata, which can trigger package install errors.
