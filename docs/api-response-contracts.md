# API And Response Contracts

ZanTech supports both full HTML views and JSON-style controller responses. This guide documents the response shapes used by the base HTTP layer.

## Response Class

`Http\Response` is the low-level response object.

It stores:

- Content body.
- HTTP status code.
- Headers.

Factory methods:

```php
Response::html(string $content, int $status = 200, array $headers = [])
Response::json(array $data, int $status = 200, array $headers = [])
Response::redirect(string $location, int $status = 302)
```

`Response::send()` writes the status code, headers, and body.

## HTML Responses

Use full layout rendering for normal page requests:

```php
$this->view()->title = 'Users';
$this->render('index');
```

Use `responseView()` when a controller method should return a `Response` object:

```php
return $this->responseView('index');
```

Response headers:

```text
Content-Type: text/html; charset=UTF-8
```

## JSON Responses

Use `responseJson()` for custom payloads:

```php
return $this->responseJson([
    'ok' => true,
    'data' => $rows,
]);
```

Response headers:

```text
Content-Type: application/json; charset=UTF-8
```

## Success Payload

Use `responseSuccess()` for standard success messages:

```php
return $this->responseSuccess(201, 'User created', [
    'id' => $id,
]);
```

Payload:

```json
{
  "status": 201,
  "ok": true,
  "title": "User created",
  "code": 201,
  "message": "User created",
  "id": 123
}
```

## Error Payload

Use `responseError()` for standard error messages:

```php
return $this->responseError('Unable to save user', 422);
```

Payload:

```json
{
  "status": 422,
  "ok": false,
  "title": "Unable to save user",
  "code": 422,
  "message": "Unable to save user"
}
```

Extra keys can be merged into the payload:

```php
return $this->responseError('Validation failed', 422, [
    'errors' => $errors,
]);
```

## Redirects

Use:

```php
return $this->responseRedirect('/Dashboard/index');
```

Default status:

```text
302
```

Header:

```text
Location: /Dashboard/index
```

## Validation Errors

Request validation is handled by:

```php
$data = $this->validate([
    'txt_email' => 'required|email',
]);
```

The validator throws `Exceptions\ValidationException` with a field-keyed error array.

Example error shape inside the exception:

```json
{
  "txt_email": [
    "The txt_email must be a valid email address."
  ]
}
```

Supported validation rules:

| Rule | Meaning |
|------|---------|
| `required` | Value must not be null or an empty string. |
| `email` | Value must be a valid email address when present. |
| `numeric` | Value must be numeric when present. |
| `min:n` | String length or numeric value must be at least `n`. |
| `max:n` | String length or numeric value must not exceed `n`. |

## Request Input Order

`Http\Request::input()` checks data in this order:

1. JSON body.
2. POST body.
3. Query string.
4. Default value.

`Request::all()` merges query, POST, and JSON data.

## AJAX Detection

`Request::isAjax()` returns true when:

- `X-Requested-With: XMLHttpRequest` is present.
- `Content-Type` contains `application/json`.
- `Accept` contains `application/json`.

The view renderer also uses `X-Requested-With: XMLHttpRequest` to decide whether to skip the shared layout.

## Recommended API Pattern

For JSON endpoints:

```php
public function save(): \Http\Response
{
    $data = $this->validate([
        'txt_name' => 'required|max:120',
        'txt_email' => 'required|email',
    ]);

    $id = $this->model->save($data);

    return $this->responseSuccess(201, 'Saved successfully', [
        'id' => $id,
    ]);
}
```

Use status codes intentionally:

| Code | Typical Use |
|------|-------------|
| `200` | Successful read/update. |
| `201` | Created a record. |
| `202` | Accepted for later approval or processing. |
| `302` | Browser redirect. |
| `400` | Bad request. |
| `401` | Not authenticated. |
| `403` | Authenticated but not permitted. |
| `404` | Route, file, or record not found. |
| `422` | Validation failed. |
| `500` | Unexpected server error. |

