# Laravel Basic CRUD For API

## Installation

Add to composer.json
```
    "require": {
        ...
        "hasandotprayoga/basic-crud": "*"
    }
```

On your terminal, run `composer update`

## Configuration

This package require `hasandotprayoga/format-resposne` for response. See configuration for this package [FormatResponse](https://github.com/hasandotprayoga/format-response#Configuration).

1. Create model for eloquent.
2. Create controller and see how to configure:
    ```php
    <?php 

    namespace App\Http\Controllers;

    class ExampleController extends Controller
    {
        use \BasicCrud\BasicCrud; // call trait

        public $model = \App\Models\Fakers::class; // variable for your model
        
        public $dataDelete = ['field_name'=>'value']; // if use soft delete
        
        // variable for insert validation
        public $insertValidation = [
            'field1'=>'required',
            'field2'=>'required|numeric',
            'field3'=>'required'
        ];
        
        // variable for update validation
        public $updateValidation = [
            'field1'=>'required',
            'field2'=>'required|numeric',
            'field3'=>'required'
        ];

    }

    ```

3. Add to route this:
    ```
    ...
    $router->get('/examples', ['as'=>'read','uses'=>'ExamplesController@index']);
    $router->post('/examples',  ['as'=>'create','uses'=>'ExamplesController@store']);
    $router->put('/examples',  ['as'=>'update','uses'=>'ExamplesController@update']);
    $router->delete('/examples/{id}[/{type}]',  ['as'=>'delete','uses'=>'ExamplesController@destroy']);
    ```