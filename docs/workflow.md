# Common Workflows

## Installing a New Module

When you first add a module to your project:

```console
php spark module:scan
php spark module:install Modules\\Blog
php spark module:enable Modules\\Blog
```

## Temporarily Disabling a Module

To turn off functionality without losing data:

```console
php spark module:disable Modules\\Blog
```

Later, to reactivate:

```console
php spark module:enable Modules\\Blog
```

## Completely Removing a Module

To remove a module and all its data:

```console
php spark module:disable Modules\\Blog
php spark module:uninstall Modules\\Blog
```

## Updating Modules

After updating module files:

```console
php spark module:update Modules\\Blog
```

Or to update everything:

```console
php spark module:update --all
```

## Checking Module Status

To see what modules you have and their states:

```console
php spark module:list
php spark module:info Modules\\Blog
```
