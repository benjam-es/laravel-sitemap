
# **[Laravel Sitemap](https://github.com/r94ever/laravel-sitemap) package**

*Sitemap generator for Laravel.*

## Installation

Run the following command and provide the latest stable version (e.g v8.\*) :

```bash  
composer require r94ever/sitemap``` 
``` 

| Laravel Version            | Sitemap Version |
|----------------------------|-----------------|
| 12.x                       | 9.x             |
| 8.x \| 9.x \| 10.x \| 11.x | 8.x             |
| 7.x                        | 7.x             |
| 6.x                        | 6.x             |
| 5.8                        | 3.1.x           |
| 5.7                        | 3.0.x           |
| 5.6                        | 2.8.x           |
| 5.5                        | 2.7.x           |

*Publish needed assets (styles, views, config files) :*

```bash  
php artisan vendor:publish --provider="R94ever\Sitemap\SitemapServiceProvider"
```  

**Note:** *Composer won't update them after `composer update`, you'll need to do it manually!*

## Examples

- [How to generate dynamic sitemap (with optional caching)](https://github.com/r94ever/laravel-sitemap/wiki/How-to-Create-Dynamic-Sitemap)
- [How to generate BIG sitemaps (with more than 1M items)](https://github.com/r94ever/laravel-sitemap/wiki/How-to-Generate-Big-Sitemap)
- [How to generate sitemap to a file](https://github.com/r94ever/laravel-sitemap/wiki/How-to-Generate-Sitemap-File)
- [How to use multiple sitemaps with sitemap index](https://github.com/r94ever/laravel-sitemap/wiki/How-to-Use-Multiple-Sitemaps-with-Sitemap-Index)

and more in the [Wiki](https://github.com/r94ever/laravel-sitemap/wiki).

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
