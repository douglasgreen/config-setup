# config-setup

Preconfigured setup files for linting, fixing, and testing PHP and JavaScript
projects

## Process

First, add the repsitory to `composer.json`:

```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/douglasgreen/config-setup"
        }
    ],
    "require": {
        "douglasgreen/config-setup": "dev-main"
    }
}
```

Then add this script to `composer.json` to copy the config files to your project
root:

```
{
    "scripts": {
        "post-install-cmd": [
            "bin/copy_files.php"
        ]
    }
}
```

The file copier will check your list of Git project files and overwrite any file
not listed there.
