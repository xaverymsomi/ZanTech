# Controllers And Responses

`Http\Controller` is the base class for module controllers.

## Rendering Views

Render a module view:

```php
$this->view()->title = 'Users';
$this->render('index');
```

Render without the shared layout:

```php
$this->render('index', true);
```

Render a full path:

```php
$this->renderFull('resources/views/plain.php');
```

## JSON Responses

Return raw JSON:

```php
return $this->responseJson([
    'ok' => true,
    'data' => $rows,
]);
```

Return a standardized success response:

```php
return $this->responseSuccess(200, 'Saved successfully', [
    'id' => $id,
]);
```

Return a standardized error response:

```php
return $this->responseError('Unable to save record', 422);
```

Standard response helpers return `Http\Response` instances.

## Redirects

```php
return $this->responseRedirect('/Dashboard/index');
```

Redirect targets are normalized by the base controller.

## Request Access

Access the current request:

```php
$request = $this->request();
```

Validate request data:

```php
$data = $this->validate([
    'txt_name' => 'required',
    'txt_email' => 'required|email',
]);
```

## Permissions

Use `requirePermission()` inside controller methods:

```php
public function create(): void
{
    $this->requirePermission('add_user');
    $this->render('create');
}
```

If the permission fails, the controller throws `Exceptions\ForbiddenException`.

## Controller Event Logging

Controllers can write structured debug events:

```php
$this->logControllerEvent('user-created', [
    'id' => $id,
]);
```

For exceptions:

```php
$this->logControllerException($exception, 'USER_CREATE_FAILED');
```

