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

### Step 5: Add host.json and local.settings.json

This repo includes example `host.json` and `local.settings.json` files (suffixed with `.example`). Run the following from a terminal to copy into your project:

```bash
cp host.json.example host.json
cp local.settings.json.example local.settings.json
```

### Step 6: Create the Azure Function HTTP Entrypoint

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

### Step 7: Create your first controller

You can use the `ResponseDto` to help with formatting the response:

```php
// src/Controller/HealthController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use XIIFactors\AzureFunctions\Dto\ResponseDto;

#[
    Route(
        path: '/api/health',
        name: 'myapp.health',
        defaults: ['_format' => 'json'],
        methods: ['GET'],
    )
]
class HealthController extends AbstractController
{
    public function __invoke(): Response
    {
        return new JsonResponse(new ResponseDto(
            ReturnValue: json_encode([
                'success' => true,
            ])
        ));
    }
}
```

### Step 8: Start the function

Run the following in your terminal:

```bash
func start
```

You should now be able to run the following curl request locally:

```bash
curl -vvv http://localhost:7071/api/health
```

and receive:

```json
{
    "success": true
}
```