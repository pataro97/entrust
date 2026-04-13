### Fork compatible con Laravel 13

### Installation

run:
`composer require pataro97/entrust:dev-main`

If you are going to consume it directly from GitHub before publishing it on Packagist, add the repository to your project's `composer.json`:

```json
{
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/pataro97/entrust"
		}
	]
}
```

After creating a release tag, you can require a stable version instead of `dev-main`.

Click [here](https://github.com/Zizaco/entrust/blob/master/README.md) for the full documentation.

**Este fork mantiene el paquete operativo para proyectos existentes. Para proyectos nuevos, [spatie/laravel-permission](https://github.com/spatie/laravel-permission) sigue siendo una opción más moderna.**
