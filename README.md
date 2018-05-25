<!--h-->
# Versioning
<!--/h-->

Prevents update conflicts using the optimistic lock pattern in Laravel

### Details

- uses `version` attribute by default to track versions
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