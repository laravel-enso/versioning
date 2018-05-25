<!--h-->
# Versioning
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/c2848e5734e44faab61fb3391a91a11e)](https://www.codacy.com/app/laravel-enso/TrackWho?utm_source=github.com&utm_medium=referral&utm_content=laravel-enso/TrackWho&utm_campaign=badger)
[![StyleCI](https://styleci.io/repos/85499255/shield?branch=master)](https://styleci.io/repos/85499255)
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