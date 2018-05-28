<!--h-->
# Versioning
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/ff415bb65927479a80d173622d3c11ed)](https://www.codacy.com/app/laravel-enso/Versioning?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=laravel-enso/Versioning&amp;utm_campaign=Badge_Grade)
[![StyleCI](https://github.styleci.io/repos/134861936/shield?branch=master)](https://github.styleci.io/repos/134861936)
[![License](https://poser.pugx.org/laravel-enso/versioning/license)](https://packagist.org/packages/laravel-enso/versioning)
[![Total Downloads](https://poser.pugx.org/laravel-enso/versioning/downloads)](https://packagist.org/packages/laravel-enso/versioning)
[![Latest Stable Version](https://poser.pugx.org/laravel-enso/versioning/version)](https://packagist.org/packages/laravel-enso/versioning)
<!--/h-->

Prevents update conflicts using the optimistic lock pattern in Laravel

### Details

- by default, uses `version` attribute to track versions
- the default versioning attribute can be customized by using `protected $versioningAttribute = 'customVersionAttribte'` on the model
- requires the presence of the proper table column
- once the structure is set up, by using the proper trait, the versioning is handled automatically
- throws a `ConflictHttpException` if the version is incorrect

### Use

1. In the Model where you want to handle versions just add

    ```
    use Versioning;
    ```

2. Make sure that the model's table has the default `version` field(s), or if you need customizing use on the model `protected $versioningAttribute = 'customVersionAttr'`

<!--h-->
### Contributions

are welcome. Pull requests are great, but issues are good too.

### License

This package is released under the MIT license.
<!--/h-->