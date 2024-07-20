# Simple Sitemap

A WordPress plugin to generate a simple sitemap - a list of links of all pages/posts on your site.  You can choose to auto-insert onto a page, or insert via shortcode.  There's even a Gutenberg block to make it user-friendly.

This plugin is useful in helping your visitors find their way around the site, and is beneficial for search engines crawling your site.

## Development

Node commands are available for:
```ssh
npm run start # Start the WP docker environment
npm run start # Start the WP docker environment in xdebug mode
npm run stop # Shut down the WP docker environment
npm run watch # Run the wp-scripts builder for the Gutenberg block in dev mode
npm run build # Build ready-to-distribute code for the Gutenberg block
```

## SVN Deployment

I'm trying to use a Github workflow to auto-deploy tagged commits from git to the SVN Plugin Repository.
