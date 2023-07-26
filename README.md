Prereqs
=============
- Git
- Azure Functions Core Tools
-- `brew install azure-functions-core-tools@4`
----------------------------------------

Installation
-------------

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require xiifactors/azure-functions-bundle
```

Applications that don't use Symfony Flex
----------------------------------------


### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require xiifactors/azure-functions-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    XIIFactors\AzureFunctions\AzureFunctionsBundle::class => ['all' => true],
];
```

### Step 3: Import the routes

Then, import the routes by adding the following
in the `config/routes.yaml` file of your project:

```yaml
// config/routes.yaml

app_annotations:
    resource: '@AzureFunctionsBundle/src/Controller/'
    type: annotation
```

### Step 4: Import the services

Then, import the routes by adding the following
in the `config/services.yaml` file of your project:

```yaml
// config/services.yaml

imports:
    - { resource: '@AzureFunctionsBundle/config/services.yaml' }

```

### Step 5: Create the Azure Function HTTP Entrypoint

In the root of the project create a directory named `HttpEntrypoint`.

Inside this directory create a function.json file with the following contents:

```json
// HttpEntrypoint/functions.json

{
    "disabled": false,
    "bindings": [
        {
            "name": "req",
            "authLevel": "anonymous",
            "type": "httpTrigger",
            "direction": "in",
            "route": "{path}",
            "methods": ["GET", "POST"]
        },
        {
            "name": "$return",
            "type": "http",
            "direction": "out"
        }
    ]
}

```

### Step 6: Start the function

Run the following in your terminal:

```bash
func start
```