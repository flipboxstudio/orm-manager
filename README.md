# Laravel ORM Manager

[![Total Downloads](https://poser.pugx.org/flipbox/orm-manager/d/total.svg)](https://packagist.org/packages/flipbox/orm-manager)
[![Latest Stable Version](https://poser.pugx.org/flipbox/orm-manager/v/stable.svg)](https://packagist.org/packages/flipbox/orm-manager)
[![Latest Unstable Version](https://poser.pugx.org/flipbox/orm-manager/v/unstable.svg)](https://packagist.org/packages/flipbox/orm-manager)
[![License](https://poser.pugx.org/flipbox/orm-manager/license.svg)](https://packagist.org/packages/flipbox/orm-manager)

This package is manager for laravel or lumen ORM (object relational mapping) Model. You can generate relation method and control Model development in your project. **Relation method** is method in Model class that reference to another Model for get data in related Model. For example you have a model **User** and **Phone** with relation one to one. Both model will be called connected if there is relation method in both class.
In class User.php there should be
```php
class User extends Authenticatable
{
    /**
     * Get the phone record associated with the user.
     */
    public function phone()
    {
        return $this->hasOne(Phone::class);
    }
}
```
In class Phone.php
```php
class Phone extends Model
{
    /**
     * Get the user that owns the phone
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

```
When you are working with very large projects, you might feel more difficult to connect all models in your project. This package can be auto generate all relation method of your project's model, even relation method that you don't need right now. And with this package we hope development relation method of Models faster and easier.
## Install
require with composer:
```
composer require flipbox/orm-manager
```
Add service provider for Laravel in the file `config/app.php`
```
Flipbox\OrmManager\OrmManagerServiceProvider::class,
```
Add service provider for Lumen in the file `bootstrap/app.php`
```
$app->register(Flipbox\OrmManager\OrmManagerServiceProvider::class);
```
## Features
See `php artisan` in console, if you install this package correctly you will see list of features with prefix `orm:`
### Control ORM Model
#### List of Model
Type `php artisan orm:list` in console, that will show you list of your project model and its properties. for example:
![ScreenShot](https://raw.githubusercontent.com/flipboxstudio/orm-manager/develop/screenshoots/list.png)
#### Detail of Model
Type `php artisan orm:detail User` in console, that will show you detail of selected Model. for example:
![ScreenShot](https://raw.githubusercontent.com/flipboxstudio/orm-manager/develop/screenshoots/detail.png)
### Generate Relation Method
**How it works?** Generator it first check the database connection of your project, if you has been created the database and its connected, it will check required option of relation in the database schema such as `primary key`, `foreign key`, `pivot table`, etc. And if you hasn't yet create database or its not connected, it will offer some required options of relation. And after required option is fulfilled it will check is method exists in the method, than will create method in the model if hasn't.
#### Generate relation method in single Model
```bash
php artisan orm:connect User hasMany Phone
```
It will generate method in class Model User
- first argument is Model where method will be created
- second is relation name such as `hsaOne`, `hasMany`, `belongsTo`, `belongsToMany`, etc.
- third is reference Model.

#### Generate relation method in both Model
```bash
php artisan orm:both-connect User oneToMany Phone
```
It will generate method in class Model User and Phone
- first argument is first Model
- second is both-relation name such as `oneToOne`, `oneToMany`, `manyToMany`, `morphOneToOne`, etc.
- third is second Model.

#### Auto Generate All Models
important *) it only work if you has been created database and project is connected.
```bash
php artisan orm:auto-connect
```
it will search possible connection between two models or more, and generate method to each model, sometime need your approval to decide relation type.
