# Laravel Simplified Translation Manager

Laravel offers a robust solution for managing multiple languages. However, as the project scales, incorporating numerous languages and developers, managing translations can become intricate, leading to occasional lapses in translations variables across files.

The solution to this issue lies within this package. It mandates that developers add new translation variables solely in English (en) language. Once this is done, the package takes over, processing all languages and making appropriate adjustments to all variables. This streamlines the translation process and ensures consistency across all language files.

In addition, there are also additional settings, such as adding Suffixes or Prefixes to variables without translation, to help identify them.

# Working process

The package will do this:

* Create a backup of the current lang/ folder
* Get the languages of the system (folders of lang/)
* Sort keys alphabetically of the templates files inside en/ folder
* Merge all keys of English base with anothers languages
  * If the file doesn't exist, the package will create automaticaly
  * If the destiny file contains translated keys, they will not be changed
  * The new keys will receive a prefix or/and suffix (see config)
  * All keys will be sorted alphabetically

# Version Control

The version of the package is related with the Laravel Version:

| **Package Version** | **Laravel version** |
|---------------------|---------------------|
| v10                 | v10                 |

# How to Install 

The installation is made via composer in your Laravel project:

```
php composer require vagkaefer/laravel-simplified-translation-manager --dev
```

# How to Configure 

You can define the options with the config file or via Environment variables:

The ENV variables are:

```
SIM_TRANS_MANAGER_PREFIX=
SIM_TRANS_MANAGER_SUFFIX=" - NEED TRANSLATION"
SIM_TRANS_MANAGER_ALPHABETIZE_ENGLISH=true
SIM_TRANS_MANAGER_ALPHABETIZE_OUTPUT=true
SIM_TRANS_MANAGER_BACKUP=true
```

To publish the config file using the command:

```
php artisan vendor:publish --provider=VagKaefer\\LaravelSimplifiedTranslationManager\\ManagerServiceProvider
```

The options are:

- **prefix**: (string) When a new translation word is detected, the prefix string is added in the beginning of the translation value

- **suffix**: (string) When a new translation word is detected, the suffix string is added to the end of the translation value

- **alphabetize_english**: (boolean - default true) This option will sort alphabetically all the keys of the English files

- **alphabetize_output_files**: (boolean - default true) This option will sort alphabetically all the keys of the merged files with the languages

- **backup_original_files**: (boolean - default true) This option will generate a .zip backup before every run, the zip can be found in the folder /lang/backups/

# How to use

Write all the variables in the English (lang/en) templates, **always include new variables in English**.

Now you just need to run the process command:

```
php artisan sim-trans-manager:process
```

You will see the Output:

```
php artisan sim-trans-manager:process

Creating backup...backup generated in ./lang/backups/1686168359-lang-backup.zip
Sorting alphabetically english files:
	File lang/en/auth.php sorted alphabetically
	File lang/en/global.php sorted alphabetically
	File lang/en/pagination.php sorted alphabetically
	File lang/en/passwords.php sorted alphabetically
	File lang/en/validation.php sorted alphabetically
Mergin with language 'pt_BR'
	File lang/en/auth.php merged with lang/pt_BR/auth.php
	File lang/en/global.php merged with lang/pt_BR/global.php
	File lang/en/pagination.php merged with lang/pt_BR/pagination.php
	File lang/en/passwords.php merged with lang/pt_BR/passwords.php
	File lang/en/validation.php merged with lang/pt_BR/validation.php
Mergin with language 'es'
	File lang/en/auth.php merged with lang/es/auth.php
	File lang/en/global.php merged with lang/es/global.php
	File lang/en/pagination.php merged with lang/es/pagination.php
	File lang/en/passwords.php merged with lang/es/passwords.php
	File lang/en/validation.php merged with lang/es/validation.php

```

# Do you like the package?

You can help me with a coffee ;D

<a href="https://www.buymeacoffee.com/vagkaefer" target="_blank">
  <img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" >
</a>


## License

As Laravel framework, this packages is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
