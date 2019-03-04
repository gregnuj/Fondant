# Dashboard for CakePHP

Build beautiful dashboards for your cakes!

__This is an unstable repository and should be treated as an alpha.__

![sample](http://cdn.makeagif.com/media/9-30-2014/543VkC.gif)

## Requirements

* CakePHP 3.6.0 or greater.
* PHP 5.6 or greater

## Install

```
composer require gregnuj/fondant:*
```

or by adding this package to your project's `composer.json`:

```
"require": {
	"gregnuj/fondant": "*"
}
```

Now, enable the plugin in your `bootstrap.php` (exclude bootstrap and routes):

```
Plugin::load('gregnuj/Fondant', []);
```

You will also need to symlink the assets:

|From                                                    |To                             |
|--------------------------------------------------------|-------------------------------|
TBD

That's it! You can now begin using Fondant!
