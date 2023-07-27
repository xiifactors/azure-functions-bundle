Prereqs
=============
- [Composer](https://getcomposer.org/doc/00-intro.md)
- [Azure Functions Core Tools](https://learn.microsoft.com/en-us/azure/azure-functions/functions-run-local?tabs=linux%2Cportal%2Cv2%2Cbash&pivots=programming-language-csharp#install-the-azure-functions-core-tools)
     
  If using Homebrew you can run: `brew install azure-functions-core-tools@4`
----------------------------------------

Installation
-------------

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

### Step 5: Copy over required files

This repo includes example `host.json` and `local.settings.json` files (suffixed with `.example`), as well as a bash script (`run.sh`) that has a one-liner to execute the PHP webserver with the correct env vars.

Run the following in a terminal from the root of your project:

```bash
cp vendor/xiifactors/azure-functions-bundle/host.json.example host.json
cp vendor/xiifactors/azure-functions-bundle/local.settings.json.example local.settings.json
cp vendor/xiifactors/azure-functions-bundle/run.sh .
```

### Step 6: Create the Azure Function HTTP Entrypoint

In the root of the project create a directory named `HttpEntrypoint`.

Inside this directory create a `function.json` file with the following contents:

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
            "methods": ["GET", "POST", "PUT", "PATCH", "DELETE"]
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

You must use the `ResponseDto` to help with formatting the response:

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

## enableForwardingHttpRequest

This is a flag set in the `host.json` file for the custom handler. Our `host.json.example` has it enabled by default.

If this flag is false it mean that the Azure Function host will send a POST request to the URI `/{NameOfFunction}` including the details about the request (route, method, body, etc) in the request body of that POST request. In this instance it is the job of `HttpEntrypointController` to process that request and then send an internal request to the desired route.

But if the flag is set to true, it means that the function host will simply forward the original request onto our application. This bundle includes a `ConvertResponseListener` (which will be enabled when you import the services - described in Step 4.) that will seamlessly handle both scenarios.

**NOTE: When the flag is true it will improve performance, but be aware that the forwarding will only happen if the function is defined with an HTTP trigger "in" binding, and an HTTP "out" binding. If there are any additional bindings the request will not be forwarded and the behaviour reverts to that as if the flag is false** - See the [official documentation](https://learn.microsoft.com/en-us/azure/azure-functions/functions-custom-handlers#http-only-function).