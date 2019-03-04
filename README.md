# Fondant for CakePHP

Scaffolding and templates for CakePHP.
Fondant attempts improve upon the default templates in CakePHP to allow for an improved user experience with less customization by focusing on integrating more Javascript into the bake templates. 

## Requirements

* CakePHP 3.6.0 or greater.
* PHP 5.6 or greater

## Install

```
composer require gregnuj/fondant:*
```

or by adding this package to your project's `composer.json`:

```
"require": {
	"gregnuj/fondant": "*"
}
```

Now, enable the plugin in your `bootstrap.php` (exclude bootstrap and routes):

```
Plugin::load('gregnuj/Fondant', ['bootstrap' => false, 'routes' => false]);
```

You will also need to symlink the assets:

|From                                                    |To                             |
|--------------------------------------------------------|-------------------------------|
TBD

That's it! You can now begin using Fondant!

# Documentation
Fondant supplies custom components and templates meant to be used together.  The the primary difference in the templates is the use of Jquery [datatables](https://datatables.net/) with the FondantComponent supplying [server-side methods for datatables](https://datatables.net/manual/server-side).

## Baking with fondant
To bake using the fondant templates use bake as you would normally with the -t|--template option when baking controller and templates.
```
cake bake controller MyStuffs -t Fondant
cake bake template MyStuffs -t Fondant
```

## Using FondantComponent in your controllers
The recommended approach is to bake your controllers as described above, and make modifications as needed.

Example Controller class:
```
<?php
namespace App\Controller;

use App\Controller\AppController;

class AliasesController extends AppController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Fondant.Fondant');
        $this->loadComponent('Fondant.JqueryUi');
    }

    public function select2(){
        $this->JqueryUi->select2();
    }

    public function getAssociations(){
        $this->Fondant->getAssociations();
    }

    public function getAssociatedDetails(){
        $this->Fondant->getAssociatedDetails();
    }

    public function index()
    {
        $this->Fondant->index();
    }

    public function view($id = null)
    {
        $this->Fondant->view($id);
    }

    public function add()
    {
        $this->Fondant->add();
    }

    public function edit($id = null)
    {
        $this->Fondant->edit($id);
    }

    public function delete($id = null)
    {
        $this->Fondant->delete($id);
    }
}
```

## Using JqueryUiComponent in your controllers
The JqueryUiComponent provides server-side function to support jquery used in the templates.

[Select2]() methods:
select2 - provides lists for [select2](https://select2.org/) dropdowns in add/edit templates
autocomplete - filters the lists for [select2(https://select2.org/) inputs
