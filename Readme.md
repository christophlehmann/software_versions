# Software versions

Detect software version from multiple sources

* uscan
* ppa
* pecl
* dpa
* git
* debian

## Configuration

File: configuration.yaml

```yaml
sources:
  dpa:
   releases:
     - jessie
     - stretch

packages:

  php7.0:
    # uses uscan
    upstream:
      path: /path/to/sources

    git:
      url: https://github.com/php/php-src.git
      tagRegex: 'php-7.0.[0-9]*'

    debian:
      name: php7.0

    ppa:
      url: https://launchpad.net/~ondrej/+archive/ubuntu/php
      name: php7.0

    dpa:
      url: https://packages.sury.org/php
      name: php7.0

  php-xdebug:

    pecl:
     name: xdebug

    dpa:
      url: https://packages.sury.org/php
      name: php-xdebug
```

## Usage

```bash
/usr/bin/php run.php

php7.0:
    upstream: error
    git: php-7.0.27
    debian: { buster: [7.0.27-1], sid: [7.0.27-1, 7.0.14-2], stretch: [7.0.19-1] }
    ppa: [7.0.27-1+ubuntu17.10.1+deb.sury.org+1, 7.0.27-1+ubuntu17.04.1+deb.sury.org+1, 7.0.27-1+ubuntu16.04.1+deb.sury.org+1, 7.0.27-1+ubuntu14.04.1+deb.sury.org+1]
    dpa: [7.0.27-1+0~20180114070440.15+jessie~1.gbp3bc0e2, 7.0.27-1+0~20180114070557.15+stretch~1.gbp3bc0e2]
php-xdebug:
    pecl: error
    dpa: [2.5.5-1+0~20170628201550.1+jessie~1.gbp5a1f48, 2.5.5-1+0~20170628201539.1+stretch~1.gbp5a1f48]
```