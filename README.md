![Screenshot](/screenshot.png?raw=true)

Joomlatools Console
=====================

[Joomlatools Console](https://www.joomlatools.com/developer/tools/console/) simplifies the management of Joomla sites. It is designed to work on Linux and MacOS. Windows users can use it in [Joomlatools Server](https://github.com/joomlatools/joomlatools-server).

## Requirements

* PHP7.3 or newer
* Linux, MacOS, or [Joomlatools Server](https://github.com/joomlatools/joomlatools-server)
* Composer
* Joomla versions 3.5 and 4.x

## Installation

1. Install using Composer:

 `$ composer global require joomlatools/console`

1. Tell your system where to find the executable by adding the composer directory to your PATH. Add the following line to your shell configuration file called either .profile, .bash_profile, .bash_aliases, or .bashrc. This file is located in your home folder.

 `$ export PATH="$PATH:~/.composer/vendor/bin"`
 
 For Ubuntu 19+ you may find you should use:

`export PATH="$PATH:$HOME/.config/composer/vendor/bin"`

1. Verify the installation

 `$ joomla --version`

1. To create a new site with the latest Joomla version, run:

  ```shell
     joomla site:create testsite
  ```

   The newly installed site will be available at /var/www/testsite and testsite.test after that. The default Super User's name and password is set to: `admin` / `admin`.

   By default, the web server root is set to _/var/www_. You can pass _--www=/my/server/path_ to commands for custom values. You can choose the Joomla version or the sample data to be installed:

   ```shell
     joomla site:create testsite --release=4.0 --sample-data=blog
   ```

1. For other available options, run:

  `$ joomla --list`

1. Read our [documentation pages](https://www.joomlatools.com/developer/tools/console/) to learn more about using the tool.

## Development

To setup the tool for development:

1. Clone the repository:

```
git clone git@github.com:joomlatools/joomlatools-console.git
```
    
1. Fetch the dependencies:

```
composer install
```
   
1. Now you can execute the tool with:

```
bin/joomla list
```

1. Happy coding!

## Contributing

Joomlatools Console is an open source, community-driven project. Contributions are welcome from everyone.
We have [contributing guidelines](CONTRIBUTING.md) to help you get started.

## Contributors

See the list of [contributors](https://github.com/joomlatools/joomlatools-console/contributors).

## License

Joomlatools Console is free and open-source software licensed under the [MPLv2 license](LICENSE).

## Community

Keep track of development and community news.

* Follow [@joomlatoolsdev on Twitter](https://twitter.com/joomlatoolsdev)
* Join [joomlatools/dev on Gitter](http://gitter.im/joomlatools/dev)
* Read the [Joomlatools Developer Blog](https://www.joomlatools.com/developer/blog/)
* Subscribe to the [Joomlatools Developer Newsletter](https://www.joomlatools.com/developer/newsletter/)

[Joomlatools Console]: https://www.joomlatools.com/developer/tools/console/
