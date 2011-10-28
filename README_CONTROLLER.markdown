# Montage Controllers

## How does a request get turned into a Controller?

Montage sees urls like:

    http://app.com/foo/bar/baz/che

as a pattern that looks like this:

    http://app.com/ControllerClass/Method/$baz/$che

So, in a default Montage installation, the request string: `foo/bar/baz/che` would be converted to:

    \Controller\FooController::handleBar($baz,$che);

Notice that `foo` gets converted to `\Controller\FooController` and the method gets converted from `bar` to `handleBar`. This is by convention, class names will always be appended with `Controller` and method names will always start with `handle`.

## How are controller namespaces resolved?

Montage will first check the `\Controller\` namespace, and if it doesn't find a match, it will check the `\Montage\Controller\` namespace. If it still doesn't find a match it will check all other Controller classes looking for the shortname match. (Using the above example, that would meant that if `\Controller\FooController` and `Montage\Controller\FooController` didn't exist, Montage would then search for any controller `FooController` with any namespace).

## What about default controllers and methods?

Default controller is \Controller\IndexController and default method is: `handleIndex`. That means, the above example could resolve many different ways:

    \Controller\FooController::handleBar($baz,$che);
    \Controller\FooController::handleIndex($bar,$baz,$che);
    
    \Montage\Controller\FooController::handleBar($baz,$che);
    \Montage\Controller\FooController::handleIndex($bar,$baz,$che);
    
    \Controller\IndexController::handleFoo($bar,$baz,$che);
    \Controller\IndexController::handleIndex($foo,$bar,$baz,$che);
    
    \Montage\Controller\IndexController::handleFoo($bar,$baz,$che);
    \Montage\Controller\IndexController::handleIndex($foo,$bar,$baz,$che);

## What about catch alls or extra url path bits we don't care about?

Say we have a request like this:

    http://app.com/blog/1234/this-is-the-title

And we don't really care about `this-is-the-title`, so:

    http://app.com/blog/1234/

would be just as valid. Then you can set your controller like this:

    \Controller\BlogController::handleIndex($id,$title = '');

By setting a default value for `$title`, we're telling Montage not to worry about it if it isn't there, but since `$id` doesn't have a default, it must be there or a 404 Exception will be thrown.

What if there are a variable number of values we don't care about?

    http://app.com/blog/1234/this-is-the-title/val1/val2/val3

So, any of these would be valid:

    http://app.com/blog/1234/this-is-the-title/val1/val2/val3
    http://app.com/blog/1234/this-is-the-title/val1/
    http://app.com/blog/1234/this-is-the-title
    http://app.com/blog/1234/

This will handle it:

    \Controller\BlogController::handleIndex($id,array $params = array());

By declaring the variable after $id to be an array, we are telling Montage to just put everything after `$id` in the `$params` array.

## What about Get and Post variables?

Say you had the url:

    http://app.com/blog/1234/?foo=bar&baz=che

You can get access to `foo` and `baz` through the request object:

    \Controller\BlogController::handleIndex($id,array $params = array());

Now, in the method body:

    echo $this->request->getField('foo'); // bar

## Command line request?

Montage has a handy CLI controller `\Montage\Controller\CliController` with some handy CLI specific methods to make command line script controllers that extend it easier to use, but any Controller can be called using the CLI syntax:

    > php path/to/montage.php controller/method --var1=val1 [--var2=val2 ...][-var3 val3...]

So if I wanted to call the foo/bar task from the command line, I could do:

    > php path/to/montage.php foo/bar --foo=bar -baz che
