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

If this flag is false it mean that the Azure Function host will send a POST request to the URI `/{NameOfFunction}` (e.g. `/HttpEntrypoint`) including all details of the original request (like URI, method, params, etc) in the body. In this instance it is the job of `HttpEntrypointController` to process that request and then send an internal request to the desired route.

But if the flag is set to true, it means that the function host will simply forward the original request onto our application. This bundle includes a `ConvertResponseListener` (which will be enabled when you import the services - described in Step 4.) that will seamlessly handle both scenarios.

**NOTE: When the flag is true it will improve performance, but be aware that the forwarding will only happen if the function is defined with an HTTP trigger "in" binding, and an HTTP "out" binding. If there are any additional bindings the request will not be forwarded and the behaviour reverts to that as if the flag is false** - See the [official documentation](https://learn.microsoft.com/en-us/azure/azure-functions/functions-custom-handlers#http-only-function).


## Output Bindings

The following example shows a function that receives an HTTP request and then uses an output binding to write a message to a queue. In this example you need to set the `Outputs` property of the `ResponseDto` object.

**Note: This will mean that `enableForwardingHttpRequest` will be nullified even if it is set to `true`, as we have defined an extra binding.**

The `function.json`:

```json
// ./Example/function.json

{
  "disabled": false,
  "bindings": [
    {
      "authLevel": "anonymous",
      "type": "httpTrigger",
      "direction": "in",
      "name": "req",
      "route": "example"
    },
    {
      "type": "http",
      "direction": "out",
      "name": "$return"
    },
    {
      "type": "queue",
      "direction": "out",
      "name": "exampleItem",
      "queueName": "example-queue",
      "connection": "AzureWebJobsStorage"
    }
  ]
}
```

The controller:

```php
// src/Controller/ExampleController.php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use XIIFactors\AzureFunctions\Dto\ResponseDto;

#[
    Route(
        path: '/api/example',
        name: 'myapp.output_example',
        defaults: ['_format' => 'json'],
        methods: ['POST'],
    )
]
class ExampleController extends AbstractController
{
    public function __invoke(): Response
    {
        return new JsonResponse(new ResponseDto(
            // Sends the message to the "example" queue via the "exampleItem" output binding
            Outputs: ['exampleItem' => json_encode(['subject' => 'example'])],
            ReturnValue: json_encode([
                'success' => true,
            ])
        ));
    }
}
```

## Input Bindings

If you are only dealing with HTTP then you will just create controllers like the `HealthController` above.

If you need to deal with other types of input, then it will be similar but note that the function host will POST to the name of the function, and the details of the input will be included in the request body. You can use the `RequestDto` to map the request data if you want.

The `function.json`:

```json
// ./QueueFunction/function.json

{
    "disabled": false,
    "bindings": [
        {
            "type": "queueTrigger",
            "direction": "in",
            "name": "exampleItem",
            "queueName": "example"
        },
        {
            "type": "blob",
            "direction": "out",
            "name": "outputBlob",
            "path": "example/{rand-guid}"
        }
    ]
}
```

The controller:

```php
// src/Controller/QueueFunctionController.php

namespace App\Controller;

use RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use XIIFactors\AzureFunctions\Dto\RequestDto;
use XIIFactors\AzureFunctions\Dto\ResponseDto;

#[
    Route(
        path: '/QueueFunction',
        name: 'myapp.input_example',
        defaults: ['_format' => 'json'],
        methods: ['POST'],
    )
]
class QueueFunctionController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] RequestDto $rd): Response
    {
        // Grab the queue item
        $queueItem = $rd->Data['exampleItem'] ?? throw new RuntimeException('Queue item is missing');

        // Do something with queue item...
        $decoded = json_decode($queueItem, true);

        // Write queue item to blob storage
        return new JsonResponse(new ResponseDto(
            Outputs: ['outputBlob' => $queueItem]
        ));
    }
}
```

## Deploying the function to Azure

There is more than one way to deploy the PHP function, the method offered here is to create a Docker image and then update the function app to use that image instead of its default one.

### 1. Create the Dockerfile:

```dockerfile
# ./Dockerfile

FROM mcr.microsoft.com/azure-functions/dotnet:4-appservice 
ENV AzureWebJobsScriptRoot=/home/site/wwwroot \
    AzureFunctionsJobHost__Logging__Console__IsEnabled=true

# Install PHP 8.1
RUN apt -y install lsb-release apt-transport-https ca-certificates 
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
RUN apt update && apt install php8.1 php8.1-xml -y

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy codebase into web root
COPY . /home/site/wwwroot

# Install composer deps and the bundle public/bundles/azurefunctions/index.php (see run.sh)
RUN cd /home/site/wwwroot && \
    composer install -o --no-scripts && \
    bin/console assets:install
```

### 2. Build and push the image to your ACR (Azure Container Registry)

```bash
az login --identity
az acr login --name {YOUR_ACR_NAME}

az acr build \
  --registry {YOUR_REGISTRY_ID} \
  --image {YOUR_REGISTRY_ID}.azurecr.io/examplefunction:{YOUR_IMAGE_TAG}
```  

### 3. Add any necessary environment variables to the function app

```bash
az login --identity

az functionapp config appsettings set \
  --resource-group {YOUR_RESOURCE_GROUP} \
  --name {YOUR_FUNCTION_NAME} \
  --settings APP_ENV=${APP_ENV} APP_DEBUG=${APP_DEBUG}
```

### 4. Deploy the new image

```bash
az login --identity

az functionapp config container set \
  --resource-group {YOUR_RESOURCE_GROUP} \
  --name {YOUR_FUNCTION_NAME} \
  --image {YOUR_REGISTRY_ID}.azurecr.io/examplefunction:{YOUR_IMAGE_TAG}
```

Within a couple of minutes the image should have been updated and new function deployed.